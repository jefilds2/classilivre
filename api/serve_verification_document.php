<?php
/**
 * api/serve_verification_document.php
 * Serve documentos de verificação de anúncios com proteção
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

// Apenas admin pode acessar
require_admin();

$adId = (int)(input_get('ad_id') ?? 0);

if ($adId <= 0) {
    http_response_code(400);
    exit('ID de anúncio inválido.');
}

try {
    $stmt = db()->prepare('SELECT verification_document_path FROM ads WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $adId]);
    $ad = $stmt->fetch();

    if (!$ad || empty($ad['verification_document_path'])) {
        http_response_code(404);
        exit('Documento não encontrado.');
    }

    $docPath = $ad['verification_document_path'];
    $fullPath = __DIR__ . '/../' . ltrim($docPath, '/');

    // Validar que o arquivo existe e está no diretório esperado
    if (!file_exists($fullPath) || !is_file($fullPath)) {
        http_response_code(404);
        exit('Arquivo não encontrado no servidor.');
    }

    // Validar que está dentro da pasta de uploads (segurança)
    $realPath = realpath($fullPath);
    $uploadsDir = realpath(__DIR__ . '/../assets/uploads');
    
    if ($realPath === false || strpos($realPath, $uploadsDir) !== 0) {
        http_response_code(403);
        exit('Acesso negado.');
    }

    // Determinar tipo MIME
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

    // Headers para visualização no navegador
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($fullPath));
    header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
    header('Cache-Control: public, max-age=3600');

    // Enviar arquivo
    readfile($fullPath);
    exit;

} catch (Throwable $e) {
    error_log('[serve_verification_document] Erro: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao servir documento.');
}
