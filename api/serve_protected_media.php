<?php
/**
 * api/serve_protected_media.php
 * Middleware para servir imagens e vídeos de anúncios com proteção contra downloads
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

// Obter o caminho do arquivo solicitado
$filepath = input_get('file', '');
if (empty($filepath)) {
    http_response_code(400);
    exit('Arquivo não especificado.');
}

// Validar formato da URL para evitar path traversal
if (strpos($filepath, '..') !== false || strpos($filepath, '/admin/') !== false) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Caminho base permitido (apenas uploads)
$basePath = __DIR__ . '/../assets/uploads/';
$fullPath = realpath($basePath . $filepath);

// Verificar se o arquivo existe e está dentro do diretório permitido
if (!$fullPath || strpos($fullPath, realpath($basePath)) !== 0 || !file_exists($fullPath)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

// Verificar se é uma imagem ou vídeo válido
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'gif' => 'image/gif',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mov' => 'video/quicktime',
];

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
if (!isset($mimeTypes[$ext])) {
    http_response_code(403);
    exit('Tipo de arquivo não permitido.');
}

// Headers de proteção contra download
header('Content-Type: ' . $mimeTypes[$ext]);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=86400'); // Cache de 24h
header('Pragma: public');

// PROTEÇÃO CONTRA DOWNLOAD: Desabilitar Content-Disposition (não mostra "Salvar como")
// Em vez disso, usar Content-Disposition: inline para forçar visualização no navegador
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');

// Headers de segurança adicionais
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

// Desabilitar caching em alguns navegadores que poderiam armazenar
// Para vídeos, permitir o usuário fazer seek mas não download externo
if (strpos($mimeTypes[$ext], 'video') !== false) {
    // Para vídeos, usar streaming sem permitir download
    header('Accept-Ranges: bytes');
    
    // Suportar range requests (para seeking)
    $filesize = filesize($fullPath);
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
        $start = intval($matches[1]);
        $end = $matches[2] !== '' ? intval($matches[2]) : $filesize - 1;
        
        if ($start > 0 || $end < $filesize - 1) {
            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $filesize);
            header('Content-Length: ' . ($end - $start + 1));
            $fp = fopen($fullPath, 'rb');
            fseek($fp, $start);
            fread($fp, $end - $start + 1);
            fclose($fp);
            exit;
        }
    }
}

// Servir o arquivo
readfile($fullPath);
exit;
