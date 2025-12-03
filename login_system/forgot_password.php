<?php
// login_system/forgot_password.php - Fluxo para gerar token de redefinição de senha.

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';

$pageTitle = 'Recuperar acesso - Site Carros';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!enforce_rate_limit('forgot_password', 10, 60)) {
        $errors[] = 'Muitas tentativas. Aguarde um minuto e tente novamente.';
    }

    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido.';
    }

    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, name FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        if (!$user) {
            $errors[] = 'Caso exista uma conta com este e-mail, enviaremos instruções (verifique o link abaixo).';
        } else {
            $userId = (int)$user['id'];
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $insert = db()->prepare('INSERT INTO password_resets (user_id, token, expires_at, used_at, created_at) VALUES (:user_id, :token, :expires_at, NULL, :created_at)');
            $insert->execute([
                ':user_id' => $userId,
                ':token' => $token,
                ':expires_at' => $expires,
                ':created_at' => now(),
            ]);
            record_audit($userId, 'password_reset_requested', 'users', $userId);
            
            // === [EMAIL][password_reset] enviar e-mail com link de reset instantaneamente ===
            if (!function_exists('mail_send_immediate')) {
                $try = [
                    __DIR__.'/../api/helpers_mail.php',
                    __DIR__.'/../../api/helpers_mail.php',
                    __DIR__.'/helpers_mail.php',
                ];
                foreach ($try as $p) { if (file_exists($p)) { require_once $p; break; } }
            }
            try {
                if (function_exists('mail_send_immediate')) {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host   = $_SERVER['HTTP_HOST'] ?? 'classilivreautos.com';
                    $resetUrl = $scheme.'://'.$host.'/login_system/reset_password.php?token='.$token;
                    $sent = mail_send_immediate(
                        $email,
                        'Redefinir sua senha - Classi Livre Autos',
                        'password_reset',
                        ['nome' => $user['name'] ?? '', 'resetUrl' => $resetUrl],
                        $user['name'] ?? ''
                    );
                    if (!$sent) {
                        error_log('[MAIL][forgot_password] Falha ao enviar e-mail de reset de senha: ' . mail_last_error());
                    }
                }
            } catch (Throwable $e) { error_log('[MAIL][forgot_password] '.$e->getMessage()); }
            // === [/EMAIL][password_reset] ===
            
            $success = true;
            reset_rate_limit('forgot_password');
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="form-card auth-card" style="max-width:520px;margin:3rem auto 4rem;">
    <header class="auth-card__header">
        <h2>Recuperar acesso</h2>
        <p>Informe seu e-mail para receber o link temporário de redefinição.</p>
    </header>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
    Instruções de redefinição enviadas para seu e-mail. Verifique sua caixa de entrada (e spam/junk).<br>
    <small>O e-mail pode demorar até 5 minutos para chegar. Se não receber, aguarde pelo menos 10 minutos antes de tentar novamente.</small>
    </div>
    <?php else: ?>
        <form method="post" class="auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <div class="input-group">
                <label for="email">E-mail cadastrado</label>
                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? ''); ?>" required>
            </div>
            <button type="submit" class="button button-primary auth-submit">Enviar instruções</button>
            <p class="auth-switch"><a href="/login_system/login.php">Voltar para o login</a></p>
        </form>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
