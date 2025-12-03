<?php
// admin/login.php — Tela de login exclusiva para administradores.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

if (current_user() && current_user()['is_admin']) {
    redirect('/admin/index.php');
}

$pageTitle = 'Login administrativo — Site Carros';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!enforce_rate_limit('admin_login', 8, 60)) {
        $errors[] = 'Muitas tentativas. Aguarde um minuto e tente novamente.';
    }
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido.';
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }
    if ($password === '') {
        $errors[] = 'Informe a senha.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_admin'] || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Credenciais inválidas para administração.';
        } elseif ($user['status'] === 'blocked') {
            $errors[] = 'Usuário bloqueado. Contate outro administrador.';
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            session_regenerate_id(true);
            reset_rate_limit('admin_login');
            record_audit((int)$user['id'], 'admin_login', 'users', (int)$user['id']);
            redirect('/admin/index.php');
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="form-card" style="max-width:460px;margin:3rem auto;">
    <h1>Acesso administrativo</h1>
    <p style="margin-top:0.5rem;">Somente usuários com privilégios administrativos podem entrar.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <div class="input-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Entrar</button>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
