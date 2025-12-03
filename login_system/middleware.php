<?php
// login_system/middleware.php — Helpers de sessão, autenticação, CSRF e rate limiting.

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';
require_once __DIR__ . '/session_manager.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

const REMEMBER_COOKIE_NAME = 'sitecarros_remember';
const REMEMBER_COOKIE_DAYS = 30;

function remember_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function remember_forget_cookie(): void
{
    if (empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return;
    }

    $cookieValue = (string)$_COOKIE[REMEMBER_COOKIE_NAME];
    unset($_COOKIE[REMEMBER_COOKIE_NAME]);
    setcookie(REMEMBER_COOKIE_NAME, '', remember_cookie_options(time() - 3600));

    $parts = explode(':', $cookieValue, 2);
    if (count($parts) !== 2) {
        return;
    }

    [$selector] = $parts;
    if ($selector === '') {
        return;
    }

    $stmt = db()->prepare('DELETE FROM remember_tokens WHERE selector = :selector');
    $stmt->execute([':selector' => $selector]);
}

function remember_persist_login(int $userId, ?string $existingSelector = null): void
{
    $selector = $existingSelector ?: bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expiresTimestamp = time() + (REMEMBER_COOKIE_DAYS * 86400);
    $expiresAt = date('Y-m-d H:i:s', $expiresTimestamp);

    $pdo = db();
    $pdo->prepare('DELETE FROM remember_tokens WHERE expires_at < NOW()')->execute();
    $now = now();

    if ($existingSelector) {
        $update = $pdo->prepare('UPDATE remember_tokens SET validator_hash = :validator_hash, expires_at = :expires_at, last_used_at = :last_used_at WHERE selector = :selector AND user_id = :user_id');
        $update->execute([
            ':validator_hash' => $validatorHash,
            ':expires_at' => $expiresAt,
            ':last_used_at' => $now,
            ':selector' => $selector,
            ':user_id' => $userId,
        ]);

        if ($update->rowCount() === 0) {
            $insert = $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at, last_used_at)
                VALUES (:user_id, :selector, :validator_hash, :expires_at, :created_at, :last_used_at)');
            $insert->execute([
                ':user_id' => $userId,
                ':selector' => $selector,
                ':validator_hash' => $validatorHash,
                ':expires_at' => $expiresAt,
                ':created_at' => $now,
                ':last_used_at' => $now,
            ]);
        }
    } else {
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id')->execute([':user_id' => $userId]);
        $insert = $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at, last_used_at)
            VALUES (:user_id, :selector, :validator_hash, :expires_at, :created_at, NULL)');
        $insert->execute([
            ':user_id' => $userId,
            ':selector' => $selector,
            ':validator_hash' => $validatorHash,
            ':expires_at' => $expiresAt,
            ':created_at' => $now,
        ]);
    }

    $cookiePayload = $selector . ':' . $validator;
    $_COOKIE[REMEMBER_COOKIE_NAME] = $cookiePayload;
    $options = remember_cookie_options($expiresTimestamp);
    
    if (!headers_sent()) {
        setcookie(REMEMBER_COOKIE_NAME, $cookiePayload, $options);
        error_log("[REMEMBER_ME] Cookie set for user $userId. Domain: '{$options['domain']}', Path: '{$options['path']}', Expires: {$expiresTimestamp}");
    } else {
        error_log("[REMEMBER_ME] WARNING: Headers already sent, cannot set cookie for user $userId!");
    }
}

function remember_attempt_auto_login(): ?array
{
    if (!empty($_SESSION['user_id'])) {
        return null;
    }

    $cookieValue = $_COOKIE[REMEMBER_COOKIE_NAME] ?? null;
    if (!$cookieValue) {
        error_log("[REMEMBER_ME] No remember cookie found. Cookie names: " . json_encode(array_keys($_COOKIE)));
        return null;
    }

    error_log("[REMEMBER_ME] Cookie found: " . substr($cookieValue, 0, 10) . "...");

    $parts = explode(':', $cookieValue, 2);
    if (count($parts) !== 2) {
        error_log("[REMEMBER_ME] Cookie format invalid: " . json_encode($parts));
        remember_forget_cookie();
        return null;
    }

    [$selector, $validator] = $parts;
    if ($selector === '' || $validator === '') {
        error_log("[REMEMBER_ME] Invalid selector/validator");
        remember_forget_cookie();
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM remember_tokens WHERE selector = :selector LIMIT 1');
    $stmt->execute([':selector' => $selector]);
    $token = $stmt->fetch();
    if (!$token) {
        error_log("[REMEMBER_ME] Token not found in database for selector: $selector");
        remember_forget_cookie();
        return null;
    }

    error_log("[REMEMBER_ME] Token found, user_id: {$token['user_id']}, expires_at: {$token['expires_at']}");

    if (strtotime($token['expires_at']) < time()) {
        error_log("[REMEMBER_ME] Token expired");
        $pdo = db();
        $pdo->prepare('DELETE FROM remember_tokens WHERE id = :id')->execute([':id' => (int)$token['id']]);
        remember_forget_cookie();
        return null;
    }

    $expectedHash = $token['validator_hash'];
    $calculatedHash = hash('sha256', $validator);

    if (!hash_equals($expectedHash, $calculatedHash)) {
        error_log("[REMEMBER_ME] Validator hash mismatch");
        $pdo = db();
        $pdo->prepare('DELETE FROM remember_tokens WHERE id = :id')->execute([':id' => (int)$token['id']]);
        remember_forget_cookie();
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$token['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        error_log("[REMEMBER_ME] User not found: {$token['user_id']}");
        remember_forget_cookie();
        return null;
    }

    $sessionToken = create_single_session(db(), (int)$user['id'], [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);

    if (!$sessionToken) {
        error_log("[REMEMBER_ME] Failed to create session for user {$user['id']}. Auto-login aborted.");
        remember_forget_cookie();
        return null;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    session_regenerate_id(true);

    remember_persist_login((int)$user['id'], $selector);
    record_audit((int)$user['id'], 'user_login_remember', 'users', (int)$user['id']);
    error_log("[REMEMBER_ME] Auto-login successful for user: {$user['id']}");

    return $user;
}

function login_user(array $user, bool $remember = false): bool
{
    $userId = (int)$user['id'];

    $sessionToken = create_single_session(db(), $userId, [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);

    if (!$sessionToken) {
        error_log("[SESSION] Falha ao criar sess�o �nica para o usu�rio {$userId}.");
        return false;
    }

    $_SESSION['user_id'] = $userId;
    session_regenerate_id(true);

    if ($remember) {
        remember_persist_login($userId);
    } else {
        remember_forget_cookie();
    }

    record_audit($userId, 'user_login', 'users', $userId);

    return true;
}

function current_user(bool $forceRefresh = false): ?array
{
    static $cached = null;

    if ($forceRefresh) {
        $cached = null;
    }

    if ($cached !== null) {
        return $cached;
    }

    if (empty($_SESSION['user_id'])) {
        $autoLoginUser = remember_attempt_auto_login();
        if ($autoLoginUser) {
            $cached = $autoLoginUser;
            return $cached;
        }
        $cached = null;
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);
        $cached = null;
        return null;
    }

    $sessionToken = $_SESSION['session_token'] ?? null;

    if (!$sessionToken) {
        $sessionToken = create_single_session(db(), (int)$user['id'], [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        if (!$sessionToken) {
            error_log("[SESSION] Nao foi possivel recuperar sessao para o usuario {$user['id']}. Forcando logout.");
            destroy_auth_session();
            $cached = null;
            return null;
        }
    } elseif (!is_session_valid(db(), (int)$user['id'], $sessionToken)) {
        destroy_auth_session();
        $cached = null;
        return null;
    }

    $cached = $user;
    return $user;
}

function ensure_verified_email(): void
{
    $user = current_user();
    if (!$user) {
        require_login();
    }
    if (empty($user['email_verified_at'])) {
        flash_add('error', 'Confirme seu e-mail para continuar.');
        redirect('/login_system/verify_email.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf(?string $token): bool
{
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function enforce_rate_limit(string $route, int $maxAttempts = 10, int $windowSeconds = 60): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, attempts, last_attempt FROM rate_limits WHERE ip_address = :ip AND route = :route LIMIT 1');
    $stmt->execute([':ip' => $ip, ':route' => $route]);
    $record = $stmt->fetch();
    $now = now();

    if ($record) {
        $diff = strtotime($now) - strtotime((string)$record['last_attempt']);
        if ($diff > $windowSeconds) {
            $update = $pdo->prepare('UPDATE rate_limits SET attempts = 1, last_attempt = :last_attempt WHERE id = :id');
            $update->execute([':last_attempt' => $now, ':id' => $record['id']]);
            return true;
        }

        if ((int)$record['attempts'] >= $maxAttempts) {
            return false;
        }

        $update = $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1, last_attempt = :last_attempt WHERE id = :id');
        $update->execute([':last_attempt' => $now, ':id' => $record['id']]);
        return true;
    }

    $insert = $pdo->prepare('INSERT INTO rate_limits (ip_address, route, attempts, last_attempt) VALUES (:ip, :route, 1, :last_attempt)');
    $insert->execute([':ip' => $ip, ':route' => $route, ':last_attempt' => $now]);
    return true;
}

function reset_rate_limit(string $route): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $stmt = db()->prepare('DELETE FROM rate_limits WHERE ip_address = :ip AND route = :route');
    $stmt->execute([':ip' => $ip, ':route' => $route]);
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método não permitido.');
    }
}

function input_post(string $key, ?string $default = null): ?string
{
    if (!isset($_POST[$key])) {
        return $default;
    }
    if (is_array($_POST[$key])) {
        return $default;
    }
    return trim((string)$_POST[$key]);
}

function input_get(string $key, ?string $default = null): ?string
{
    if (!isset($_GET[$key])) {
        return $default;
    }
    if (is_array($_GET[$key])) {
        return $default;
    }
    return trim((string)$_GET[$key]);
}

function ad_public_url(array $ad): string
{
    $slug = $ad['slug'] ?: slugify($ad['title'] ?? 'anuncio');
    return '/anuncio/' . $ad['id'] . '/' . $slug;
}

function e(string|int|float|null $value): string
{
    $stringValue = $value === null ? '' : (string)$value;
    return htmlspecialchars($stringValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function free_ads_limit(): int
{
    return (int)get_setting('free_ads_per_month', 3);
}

function max_images_allowed(): int
{
    return (int)get_setting('max_images', 12);
}

function video_allowed_for_premium(): bool
{
    return (int)get_setting('allow_video_premium', 0) === 1;
}

function format_date(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '-';
    }
    return date('d/m/Y H:i', $timestamp);
}

function require_valid_csrf(): void
{
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Token CSRF inválido. Atualize a página e tente novamente.');
    }
}

function ensure_user_not_blocked(): void
{
    $user = current_user();
    if ($user && $user['status'] === 'blocked') {
        flash_add('error', 'Sua conta está bloqueada. Entre em contato com o suporte.');
        redirect('/login_system/logout.php');
    }
}

function destroy_auth_session(): void
{
    unset($_SESSION['user_id']);
    unset($_SESSION['session_token']);
    remember_forget_cookie();
    session_regenerate_id(true);
}

function flash_add(string $type, string $message): void
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    if (!isset($_SESSION['flash_messages'][$type])) {
        $_SESSION['flash_messages'][$type] = [];
    }
    $_SESSION['flash_messages'][$type][] = $message;
}

function flash_get(bool $clear = true): array
{
    $messagesRaw = $_SESSION['flash_messages'] ?? [];
    $messages = [];
    
    // Converter formato ['type' => [msg1, msg2]] para [['type' => 'type', 'message' => 'msg1'], ...]
    foreach ($messagesRaw as $type => $typeMessages) {
        if (is_array($typeMessages)) {
            foreach ($typeMessages as $msg) {
                $messages[] = ['type' => $type, 'message' => $msg];
            }
        }
    }
    
    if ($clear) {
        unset($_SESSION['flash_messages']);
    }
    return $messages;
}

function redirect(string $path): void
{
    if (!headers_sent()) {
        header('Location: ' . $path);
    } else {
        // Fallback se headers já foram enviados
        echo '<script>window.location.href = ' . json_encode($path) . ';</script>';
    }
    exit;
}

function require_login(?string $redirectTo = null): void
{
    $user = current_user();
    if (!$user) {
        if ($redirectTo) {
            redirect($redirectTo);
        }
        redirect('/login_system/login.php');
    }
}

function require_admin(?string $redirectTo = null): void
{
    $user = current_user();
    if (!$user) {
        redirect('/login_system/login.php');
    }
    if (empty($user['is_admin'])) {
        flash_add('error', 'Você não tem permissão para acessar essa área.');
        redirect($redirectTo ?? '/');
    }
}

