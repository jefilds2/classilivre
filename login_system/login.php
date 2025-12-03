<?php
// login_system/login.php - Autenticação de usuários com suporte a lembrar acesso e Google OAuth.

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/google_oauth.php';
require_once __DIR__ . '/session_manager.php';

$pageTitle = 'Entrar - Site Carros';
$errors = [];
$googleAvailable = google_oauth_configured();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!enforce_rate_limit('login', 10, 60)) {
        $errors[] = 'Muitas tentativas. Aguarde um minuto e tente novamente.';
    }

    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido. Atualize a página e tente novamente.';
    }

    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }
    if ($password === '') {
        $errors[] = 'Informe sua senha.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Credenciais inválidas.';
        } elseif (empty($user['email_verified_at'])) {
            $errors[] = 'Confirme seu e-mail antes de fazer login. Verifique sua caixa de entrada.';
        } elseif ($user['status'] === 'blocked') {
            $errors[] = 'Seu acesso está bloqueado. Contate o suporte.';
        } else {
            if (login_user($user, $remember)) {
                reset_rate_limit('login');

                $redirect = $_GET['redirect'] ?? null;
                if ($redirect && str_starts_with($redirect, '/')) {
                    redirect($redirect);
                }

                if (!empty($user['is_admin'])) {
                    redirect('/admin/index.php');
                }

                redirect('/anuncios/meus.php');
            } else {
                $errors[] = 'Erro ao iniciar sessão. Tente novamente em instantes.';
            }
        }
    }
}

include __DIR__ . '/../partials/header.php';

// Verificar se houve logout forçado
$expiredMsg = null;
if (isset($_GET['expired'])) {
    $reason = $_GET['reason'] ?? 'unknown';
    
    if ($reason === 'invalidated') {
        $expiredMsg = '⚠️ Sua conta foi acessada em outro navegador e você foi automaticamente desconectado.';
    } elseif ($reason === 'timeout') {
        $expiredMsg = '⏰ Sua sessão expirou por inatividade (30 minutos sem usar a página).';
    } elseif ($reason === 'max_lifetime') {
        $expiredMsg = '📋 Sua sessão expirou (limite máximo de 30 dias).';
    } else {
        $expiredMsg = 'Sua sessão expirou. Por favor, faça login novamente.';
    }
}
?>
<section class="form-card auth-card" style="max-width:560px;margin:3rem auto 4rem;">
    <header class="auth-card__header">
        <h2>Bem-vindo de volta</h2>
        <p>Entre com seus dados para acessar e gerenciar seus anúncios.</p>
    </header>

    <?php if ($expiredMsg): ?>
        <div class="alert alert-warning" style="margin-bottom: 1.5rem; background-color: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 12px 16px; border-radius: 4px;">
            <?= e($expiredMsg); ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="auth-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

        <div class="input-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? ''); ?>" required autofocus>
        </div>

        <div class="input-group input-group--password">
            <label for="password">Senha</label>
            <div class="password-field">
                <input type="password" id="password" name="password" required data-password-toggle-target>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Mostrar senha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <div class="form-meta">
            <label class="checkbox-inline">
                <input type="checkbox" name="remember" value="1" <?= isset($_POST['remember']) ? 'checked' : ''; ?>>
                Lembrar de mim
            </label>
            <a href="/login_system/forgot_password.php">Esqueceu a senha?</a>
        </div>

        <button type="submit" class="button button-primary auth-submit">Entrar</button>

        <?php if ($googleAvailable): ?>
            <div class="auth-divider">
                <span>ou entre com</span>
            </div>
            <a href="/login_system/google_login.php" class="button button-google">
                <i class="fab fa-google"></i> Continuar com Google
            </a>
        <?php endif; ?>

        <p class="auth-switch">Ainda não tem conta? <a href="/login_system/register.php">Criar uma conta</a></p>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
