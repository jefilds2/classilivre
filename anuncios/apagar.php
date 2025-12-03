<?php
// anuncios/apagar.php — Exclui anúncios do usuário de forma segura.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/upload_handler.php';

require_post();
require_login();
ensure_user_not_blocked();

if (!validate_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Token CSRF inválido.');
}

$user = current_user();
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

$stmt = db()->prepare('SELECT id FROM ads WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $id, ':user_id' => (int)$user['id']]);
$ad = $stmt->fetch();
if (!$ad) {
    http_response_code(404);
    exit('Anúncio não encontrado.');
}

$mediaStmt = db()->prepare('SELECT filepath FROM ad_media WHERE ad_id = :ad_id');
$mediaStmt->execute([':ad_id' => $id]);
foreach ($mediaStmt->fetchAll() as $media) {
    delete_media_file($media['filepath']);
}

// Deletar as pastas de mídia do anúncio
$imageDir = __DIR__ . '/../assets/uploads/images/' . $id;
if (is_dir($imageDir)) {
    foreach ((array)glob($imageDir . '/*') as $file) {
        @unlink($file);
    }
    @rmdir($imageDir);
}

$videoDir = __DIR__ . '/../assets/uploads/videos/' . $id;
if (is_dir($videoDir)) {
    foreach ((array)glob($videoDir . '/*') as $file) {
        @unlink($file);
    }
    @rmdir($videoDir);
}

// Deletar registros de mídia da BD
db()->prepare('DELETE FROM ad_media WHERE ad_id = :ad_id')->execute([':ad_id' => $id]);

$delete = db()->prepare('DELETE FROM ads WHERE id = :id AND user_id = :user_id');
$delete->execute([':id' => $id, ':user_id' => (int)$user['id']]);

record_audit((int)$user['id'], 'ad_deleted', 'ads', $id);
flash_add('success', 'Anúncio removido com sucesso.');
redirect('/anuncios/meus.php');
