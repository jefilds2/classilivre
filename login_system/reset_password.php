<?php
// login_system/reset_password.php - Permite redefinir a senha a partir de um token válido.

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';

$pageTitle = 'Redefinir senha - Site Carros';
$token = input_get('token');
$errors = [];
$success = false;

if (!$token) {
    http_response_code(400);
    exit('Token inválido.');
}

$stmt = db()->prepare('SELECT pr.*, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token = :token LIMIT 1');
$stmt->execute([':token' => $token]);
$reset = $stmt->fetch();

if (!$reset || $reset['used_at']) {
    http_response_code(400);
    exit('Token inválido ou já utilizado.');
}

if (strtotime($reset['expires_at']) < time()) {
    http_response_code(400);
    exit('Token expirado. Solicite uma nova redefinição.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido. Atualize a página.';
    }
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');

    if (strlen($password) < 8) {
        $errors[] = 'A nova senha deve ter ao menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/u', $password)) {
        $errors[] = 'A nova senha deve conter pelo menos uma letra maiúscula.';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'A nova senha deve conter pelo menos um número.';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'As senhas informadas não conferem.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = db()->prepare('UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            ':password_hash' => $hash,
            ':updated_at' => now(),
            ':id' => (int)$reset['user_id'],
        ]);

        $mark = db()->prepare('UPDATE password_resets SET used_at = :used_at WHERE id = :id');
        $mark->execute([
            ':used_at' => now(),
            ':id' => (int)$reset['id'],
        ]);

        record_audit((int)$reset['user_id'], 'password_reset_completed', 'users', (int)$reset['user_id']);
        $success = true;
        flash_add('success', 'Senha redefinida com sucesso! Faça login com a nova senha.');
        redirect('/login_system/login.php');
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="form-card auth-card" style="max-width:520px;margin:3rem auto 4rem;">
    <header class="auth-card__header">
        <h2>Definir nova senha</h2>
        <p>Token válido para o e-mail <strong><?= e($reset['email']); ?></strong>. Informe sua nova senha abaixo.</p>
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

    <form method="post" class="auth-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <div class="input-group input-group--password">
            <label for="password">Nova senha</label>
            <div class="password-field">
                <input type="password" id="password" name="password" minlength="8" required data-password-toggle-target>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Mostrar senha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <span class="input-hint">Mínimo de 8 caracteres, com 1 letra maiúscula e 1 número.</span>
        </div>
        <div class="input-group input-group--password">
            <label for="password_confirmation">Confirmar nova senha</label>
            <div class="password-field">
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required data-password-toggle-target>
                <button type="button" class="password-toggle" data-password-toggle aria-label="Mostrar senha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="button button-primary auth-submit">Atualizar senha</button>
        <p class="auth-switch"><a href="/login_system/login.php">Voltar ao login</a></p>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
