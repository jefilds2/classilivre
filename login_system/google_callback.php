<?php
// login_system/google_callback.php - Conclusão do fluxo OAuth com o Google.

declare(strict_types=1);

require_once __DIR__ . '/google_oauth.php';

if (!google_oauth_configured()) {
    flash_add('error', 'A autenticação com o Google ainda não está disponível.');
    redirect('/login_system/login.php');
}

$state = input_get('state');
$expectedState = $_SESSION['google_oauth_state'] ?? null;
unset($_SESSION['google_oauth_state']);

if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
    flash_add('error', 'Sessão inválida. Tente iniciar o login novamente.');
    redirect('/login_system/login.php');
}

$error = input_get('error');
if ($error) {
    flash_add('error', 'O Google retornou um erro: ' . e($error));
    redirect('/login_system/login.php');
}

$code = input_get('code');
if (!$code) {
    flash_add('error', 'Código de autorização ausente. Tente novamente.');
    redirect('/login_system/login.php');
}

try {
    $tokenData = google_oauth_exchange_code($code);
    $googleUser = google_oauth_fetch_userinfo($tokenData['access_token']);

    $googleId = $googleUser['sub'];
    $email = strtolower($googleUser['email'] ?? '');
    if (!$email) {
        throw new RuntimeException('Não foi possível obter o e-mail da conta Google.');
    }

    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = :google_id LIMIT 1');
    $stmt->execute([':google_id' => $googleId]);
    $user = $stmt->fetch();

    $now = now();
    $avatar = $googleUser['picture'] ?? null;
    $name = trim((string)($googleUser['name'] ?? $googleUser['given_name'] ?? 'Usuário Google'));

    if (!$user) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $pdo->prepare('UPDATE users SET google_id = :google_id, google_avatar = :google_avatar, email_verified_at = COALESCE(email_verified_at, :email_verified_at), updated_at = :updated_at WHERE id = :id');
            $update->execute([
                ':google_id' => $googleId,
                ':google_avatar' => $avatar,
                ':email_verified_at' => $now,
                ':updated_at' => $now,
                ':id' => (int)$user['id'],
            ]);
        } else {
            $randomPassword = bin2hex(random_bytes(16));
            $insert = $pdo->prepare('INSERT INTO users (
                name, email, password_hash, google_id, google_avatar, email_verified_at, terms_accepted_at, status, created_at, updated_at
            ) VALUES (
                :name, :email, :password_hash, :google_id, :google_avatar, :email_verified_at, :terms_accepted_at, "active", :created_at, :updated_at
            )');
            $insert->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => password_hash($randomPassword, PASSWORD_DEFAULT),
                ':google_id' => $googleId,
                ':google_avatar' => $avatar,
                ':email_verified_at' => $now,
                ':terms_accepted_at' => $now,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            $userId = (int)$pdo->lastInsertId();
            
            // ✅ Dar moedas iniciais para conta nova (se configurado)
            $newAccountCoins = (int)get_setting('new_account_coins', '0');
            if ($newAccountCoins > 0) {
                $updateCoins = $pdo->prepare('UPDATE users SET coin_balance = :coins WHERE id = :id');
                $updateCoins->execute([':coins' => $newAccountCoins, ':id' => $userId]);
                
                // Registrar na auditoria
                record_audit($userId, 'welcome_bonus_coins', 'users', $userId, [
                    'amount' => $newAccountCoins,
                    'reason' => 'Bônus inicial para novo usuário (Google)'
                ]);
            }
            
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            record_audit($userId, 'user_registered_google', 'users', $userId, ['email' => $email]);
        }
    }

    if (!$user) {
        throw new RuntimeException('Não foi possível localizar ou criar o usuário.');
    }

    if (!empty($avatar) && $user['google_avatar'] !== $avatar) {
        $updateAvatar = $pdo->prepare('UPDATE users SET google_avatar = :google_avatar, updated_at = :updated_at WHERE id = :id');
        $updateAvatar->execute([
            ':google_avatar' => $avatar,
            ':updated_at' => $now,
            ':id' => (int)$user['id'],
        ]);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$user['id']]);
        $user = $stmt->fetch();
    }

    if (!login_user($user, true)) {
        flash_add('error', 'Nao foi possivel iniciar sua sessao. Tente novamente.');
        redirect('/login_system/login.php');
    }
    reset_rate_limit('login');

    $redirect = $_SESSION['google_post_login_redirect'] ?? null;
    unset($_SESSION['google_post_login_redirect']);
    if (!$redirect) {
        $redirect = input_get('redirect');
    }

    if ($redirect && str_starts_with((string)$redirect, '/')) {
        redirect($redirect);
    }

    if (!empty($user['is_admin'])) {
        redirect('/admin/index.php');
    }
    redirect('/');
} catch (Throwable $exception) {
    record_audit(null, 'google_callback_error', null, null, [
        'message' => $exception->getMessage(),
    ]);
    flash_add('error', 'Não foi possível completar o login com o Google: ' . $exception->getMessage());
    redirect('/login_system/login.php');
}
