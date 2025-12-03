<?php
// login_system/verify_email.php — Confirmação de e-mail por token (versão mínima, sem depender de coluna name).

declare(strict_types=1);
require_once __DIR__ . '/middleware.php';

$pageTitle = 'Verificar e-mail — Classi Livre Autos';

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
function get_param(string $k): ?string {
    return isset($_GET[$k]) ? trim((string)$_GET[$k]) : null;
}

$token = get_param('token');
$feedback = null;
$feedbackClass = 'success';

try {
    if ($token === null || $token === '') {
        throw new RuntimeException('Token ausente ou inválido.');
    }

    $pdo = db();
    $sql = "SELECT id, email, email_pending, email_verified_at
            FROM users
            WHERE email_verify_token = :t
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':t' => $token]);
    $user = $st->fetch();

    if (!$user) {
        throw new RuntimeException('Link inválido ou já utilizado.');
    }

    if (!empty($user['email_verified_at'])) {
        $feedback = 'Seu e-mail já está verificado. Você já pode entrar.';
        $feedbackClass = 'info';
    } else {
        // Confirma o e-mail e promove email_pending se houver
        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            if (!empty($user['email_pending'])) {
                // valida unicidade antes de promover
                $dup = $pdo->prepare('SELECT 1 FROM users WHERE email = :email AND id <> :id LIMIT 1');
                $dup->execute([':email' => $user['email_pending'], ':id' => (int)$user['id']]);
                if ($dup->fetchColumn()) {
                    throw new RuntimeException('Este e-mail já está em uso. Tente outro endereço.');
                }
                $promote = $pdo->prepare('UPDATE users SET email = :email, email_pending = NULL WHERE id = :id');
                $promote->execute([':email' => $user['email_pending'], ':id' => (int)$user['id']]);
            }
            $confirm = $pdo->prepare('UPDATE users SET email_verified_at = :verified_at, email_verify_token = NULL, updated_at = :updated_at WHERE id = :id');
            $confirm->execute([':verified_at' => $now, ':updated_at' => $now, ':id' => (int)$user['id']]);
            $pdo->commit();
            $feedback = 'E-mail confirmado com sucesso! Você já pode entrar.';
            $feedbackClass = 'success';
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
    }
} catch (Throwable $e) {
    $feedback = $e->getMessage();
    $feedbackClass = 'error';
}

// View
include __DIR__ . '/../partials/header.php';
?>
<section class="container mx-auto max-w-3xl py-10">
    <h1 class="text-2xl font-bold mb-6">Verificação de e-mail</h1>

    <?php if ($feedback): ?>
        <div class="alert alert-<?= e($feedbackClass) ?> mb-4">
            <?= e($feedback) ?>
        </div>
    <?php endif; ?>

    <div class="mt-6">
        <a href="/login_system/login.php" class="button">Ir para o login</a>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
