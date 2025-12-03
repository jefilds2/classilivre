<?php
// admin/config.php - Ajustes de configuracoes globais do marketplace.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$pageTitle = 'Configuracoes - Administracao';
$activeNav = 'settings';
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
if (!validate_csrf($_POST['csrf_token'] ?? null)) {
$errors[] = 'Token CSRF invalido.';
} else {
$maxImages = max(1, (int)($_POST['max_images'] ?? 12));
$allowVideo = isset($_POST['allow_video_premium']) ? '1' : '0';
$maxVideo = max(1, (int)($_POST['max_video_size_mb'] ?? 30));
$maxImage = max(1, (int)($_POST['max_image_size_mb'] ?? 2));

update_setting('max_images', (string)$maxImages);
            update_setting('allow_video_premium', $allowVideo);
update_setting('max_video_size_mb', (string)$maxVideo);
update_setting('max_image_size_mb', (string)$maxImage);

record_audit((int)current_user()['id'], 'settings_updated', 'settings', null, [
    'max_images' => $maxImages,
    'allow_video_premium' => $allowVideo,
            ]);
$success = 'Configuracoes salvas com sucesso.';
}
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section class="form-card" style="max-width:680px;margin:2.5rem auto;">
    <h1>Configuracoes gerais</h1>
    <p>Defina limites e recursos disponiveis para os usuarios.</p>

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
        <div class="alert alert-success"><?= e($success); ?></div>
    <?php endif; ?>

    <form method="post" class="admin-settings-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        
        <div class="input-group">
            <label for="max_images">Limite de imagens por anuncio</label>
            <input type="number" id="max_images" name="max_images" min="1" value="<?= e(get_setting('max_images', '12')); ?>">
        </div>
        <div class="input-group">
            <label for="max_image_size_mb">Tamanho maximo de imagem (MB)</label>
            <input type="number" id="max_image_size_mb" name="max_image_size_mb" min="1" value="<?= e(get_setting('max_image_size_mb', '2')); ?>">
        </div>
        <div class="input-group">
            <label for="max_video_size_mb">Tamanho maximo de video (MB)</label>
            <input type="number" id="max_video_size_mb" name="max_video_size_mb" min="1" value="<?= e(get_setting('max_video_size_mb', '30')); ?>">
        </div>
        <label style="display:flex;gap:0.5rem;align-items:center;">
            <input type="checkbox" name="allow_video_premium" value="1" <?= get_setting('allow_video_premium', '1') === '1' ? 'checked' : ''; ?>> Permitir videos para premium
        </label>
        <button type="submit" class="button button-admin admin-settings-submit">Salvar alteracoes</button>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
