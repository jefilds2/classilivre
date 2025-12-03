<?php
/**
 * api/recover_middleware.php — Script de recuperação do middleware.php
 * Acesse: https://seu-site.com/api/recover_middleware.php
 */

// NÃO require middleware, pois está quebrado
require_once __DIR__ . '/../sqlconnect.php';

header('Content-Type: application/json');

$middlewarePath = __DIR__ . '/../login_system/middleware.php';

// Verificar se pode ser corrigido
if (!file_exists($middlewarePath)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Arquivo middleware.php não encontrado',
    ]);
    exit;
}

// Ler o arquivo
$content = file_get_contents($middlewarePath);

if (!$content) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Não foi possível ler o arquivo middleware.php',
    ]);
    exit;
}

// Validar sintaxe
$output = [];
$returnCode = 0;
exec(sprintf('php -l %s 2>&1', escapeshellarg($middlewarePath)), $output, $returnCode);

if ($returnCode === 0) {
    echo json_encode([
        'status' => 'ok',
        'message' => 'Arquivo middleware.php está correto',
        'size' => strlen($content),
        'output' => $output,
    ]);
    exit;
}

// Se houver erro, tentar identificar o problema
$error = implode("\n", $output);

// Procurar por chaves desemparelhadas
$openBraces = substr_count($content, '{');
$closeBraces = substr_count($content, '}');

$diagnosis = [
    'status' => 'error',
    'message' => $error,
    'diagnosis' => [
        'open_braces' => $openBraces,
        'close_braces' => $closeBraces,
        'balanced' => $openBraces === $closeBraces,
    ],
    'file_info' => [
        'size' => strlen($content),
        'lines' => count(explode("\n", $content)),
        'last_modified' => date('Y-m-d H:i:s', filemtime($middlewarePath)),
    ],
];

echo json_encode($diagnosis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
