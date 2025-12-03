<?php
// login_system/verify_email_pending.php — Tela de verificação pendente com reenviar e corrigir e-mail.

declare(strict_types=1);
require_once __DIR__ . '/middleware.php';

require_login();
ensure_user_not_blocked();

$pageTitle = 'Verificação pendente — Classi Livre Autos';
$errors = [];
$info = null;

// Util
function current_base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'classilivreautos.com';
    return $scheme.'://'.$host;
}

$me = current_user();
if ($me && !empty($me['email_verified_at'])) {
    redirect('/');
}

// Post actions: resend or change email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido.';
    } else {
        $action = $_POST['action'] ?? '';
        $pdo = db();
        $now = now();

        if ($action === 'resend') {
            // rate limit: 1 tentativa a cada 120s
            if (!enforce_rate_limit('resend_verify', 1, 120)) {
                $errors[] = 'Aguarde 2 minutos antes de reenviar.';
            } else {
                // garantir token e enviar
                $token = bin2hex(random_bytes(32));
                $upd = $pdo->prepare('UPDATE users SET email_verify_token = :tkn, updated_at = :upd WHERE id = :id');
                $upd->execute([':tkn' => $token, ':upd' => $now, ':id' => (int)$me['id']]);

                $verifyUrl = current_base_url().'/login_system/verify_email.php?token='.$token;
                if (!function_exists('mail_send_immediate')) {
                    $try = [__DIR__.'/../api/helpers_mail.php', __DIR__.'/../../api/helpers_mail.php'];
                    foreach ($try as $p) { if (file_exists($p)) { require_once $p; break; } }
                }
                try {
                    if (function_exists('mail_send_immediate')) {
                        $sent = mail_send_immediate(
                            (string)$me['email'],
                            'Confirme seu e-mail - Classi Livre Autos',
                            'welcome_verify',
                            ['nome' => (string)($me['name'] ?? ''), 'verifyUrl' => $verifyUrl],
                            (string)($me['name'] ?? '')
                        );
                        if (!$sent) {
                            $errors[] = 'Falha ao enviar e-mail de verificação. Tente novamente.';
                        } else {
                            $info = 'Novo link de verificação enviado ao seu e-mail.';
                        }
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Falha ao enviar o e-mail de verificação.';
                }
            }
        } elseif ($action === 'change') {
            $newEmail = strtolower(trim((string)($_POST['new_email'] ?? '')));
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Informe um e-mail válido.';
            } else {
                // impede colidir com e-mail de outro usuário e com o próprio e-mail atual
                $dup = $pdo->prepare('SELECT 1 FROM users WHERE email = :e AND id <> :id LIMIT 1');
                $dup->execute([':e' => $newEmail, ':id' => (int)$me['id']]);
                if ($dup->fetchColumn()) {
                    $errors[] = 'Este e-mail já está em uso.';
                }
            }
            if (!$errors) {
                // aplica email_pending e gera novo token
                $token = bin2hex(random_bytes(32));
                $upd = $pdo->prepare('UPDATE users SET email_pending = :pending, email_verify_token = :tkn, updated_at = :upd WHERE id = :id');
                $upd->execute([
                    ':pending' => $newEmail,
                    ':tkn' => $token,
                    ':upd' => $now,
                    ':id' => (int)$me['id'],
                ]);

                $verifyUrl = current_base_url().'/login_system/verify_email.php?token='.$token;
                if (!function_exists('mail_send_immediate')) {
                    $try = [__DIR__.'/../api/helpers_mail.php', __DIR__.'/../../api/helpers_mail.php'];
                    foreach ($try as $p) { if (file_exists($p)) { require_once $p; break; } }
                }
                try {
                    if (function_exists('mail_send_immediate')) {
                        $sent = mail_send_immediate(
                            $newEmail,
                            'Confirme seu novo e-mail - Classi Livre Autos',
                            'welcome_verify',
                            ['nome' => (string)($me['name'] ?? ''), 'verifyUrl' => $verifyUrl],
                            (string)($me['name'] ?? '')
                        );
                        if (!$sent) {
                            $errors[] = 'Falha ao enviar e-mail de confirmação para o novo endereço.';
                        } else {
                            $info = 'Enviamos um link para o novo e-mail. Confirme para concluir a troca.';
                        }
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Falha ao enviar o e-mail para o novo endereço.';
                }
            }
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="form-card auth-card" style="max-width:680px;margin:2rem auto 3.5rem;">
    <header class="auth-card__header">
        <h2>Verifique seu e-mail</h2>
        <p>Enviamos um link de confirmação para o seu e-mail. Conclua a verificação para liberar todos os recursos.</p>
    </header>

    <?php if ($errors): ?>
        <div class="alert alert-error"><ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($info): ?>
        <div class="alert alert-success"><?= e($info) ?></div>
    <?php endif; ?>

    <div class="actions" style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-top:1rem;">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <input type="hidden" name="action" value="resend">
            <button type="submit" class="button" title="Disponível a cada 2 minutos">Reenviar confirmação</button>
        </form>
        <button type="button" class="button secondary" onclick="document.getElementById('modal-change-email').showModal()">Corrigir e-mail</button>
        <a href="/" class="button secondary">Início</a>
    </div>

    <dialog id="modal-change-email" style="border:none;border-radius:8px;max-width:520px;padding:0;">
        <form method="post" style="padding:1.25rem;">
            <h3 style="margin:0 0 0.75rem 0;">Corrigir e-mail</h3>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <input type="hidden" name="action" value="change">
            <div class="input-group">
                <label for="new_email">Novo e-mail</label>
                <input type="email" id="new_email" name="new_email" required>
            </div>
            <div style="display:flex; gap:0.5rem; margin-top:0.75rem;">
                <button type="submit" class="button">Enviar novo link</button>
                <button type="button" class="button secondary" onclick="document.getElementById('modal-change-email').close()">Cancelar</button>
            </div>
        </form>
    </dialog>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
