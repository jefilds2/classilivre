<?php
/**
 * Handler de Upload de Imagens para Campanhas
 * Localização: /propagandas/admin/api/upload-image.php
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../login_system/middleware.php';
require_admin();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/CampaignManager.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido']);
        exit;
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['erro' => 'Arquivo não foi enviado corretamente']);
        exit;
    }

    $pdo = db();
    $campaign_manager = new PropagandaCampaignManager($pdo);

    // Fazer upload da imagem
    $image_path = $campaign_manager->uploadImage($_FILES['image']);

    echo json_encode([
        'sucesso' => true,
        'caminho' => $image_path,
        'mensagem' => 'Imagem enviada com sucesso'
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'erro' => $e->getMessage(),
        'sucesso' => false
    ]);
    error_log('Erro ao fazer upload: ' . $e->getMessage());
}
?>
