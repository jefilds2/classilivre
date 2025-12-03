<?php
/**
 * Debug: Verificar status de compressão de vídeo
 * Acesse: http://seu-servidor.com/api/debug_video_compression.php
 */

header('Content-Type: application/json; charset=utf-8');

$output = [];

// 1. Verificar se FFmpeg está instalado
$output['ffmpeg_status'] = [
    'label' => 'Status do FFmpeg',
    'test' => shell_exec('which ffmpeg 2>/dev/null || where ffmpeg 2>nul'),
];

// 2. Verificar versão do FFmpeg
$output['ffmpeg_version'] = [
    'label' => 'Versão do FFmpeg',
    'test' => shell_exec('ffmpeg -version 2>&1 | head -1'),
];

// 3. Verificar se exec() está habilitado
$output['php_exec'] = [
    'label' => 'Função exec() disponível',
    'test' => function_exists('exec') ? 'SIM ✅' : 'NÃO ❌',
];

// 4. Verificar se shell_exec() está habilitado
$output['php_shell_exec'] = [
    'label' => 'Função shell_exec() disponível',
    'test' => function_exists('shell_exec') ? 'SIM ✅' : 'NÃO ❌',
];

// 5. Verificar arquivo de log do PHP
$phpIni = php_ini_loaded_file();
$output['php_ini'] = [
    'label' => 'Arquivo php.ini',
    'test' => $phpIni ?: 'Não encontrado',
];

// 6. Obter últimas linhas do error_log
$phpErrorLog = ini_get('error_log');
if ($phpErrorLog && file_exists($phpErrorLog)) {
    $lines = file($phpErrorLog);
    $lastLines = array_slice($lines, -20);
    $output['error_log'] = [
        'label' => 'Últimas linhas do error_log (últimas 20)',
        'path' => $phpErrorLog,
        'logs' => $lastLines,
    ];
} else {
    $output['error_log'] = [
        'label' => 'Arquivo error_log',
        'test' => 'Não configurado ou não encontrado',
    ];
}

// 7. Simular compressão de teste (se houver arquivo de teste)
$testFile = __DIR__ . '/../assets/uploads/test_video.mp4';
if (file_exists($testFile)) {
    $size = filesize($testFile);
    $output['test_file'] = [
        'label' => 'Arquivo de teste encontrado',
        'size' => $size,
        'formatted' => format_bytes($size),
    ];
} else {
    $output['test_file'] = [
        'label' => 'Arquivo de teste',
        'test' => 'Nenhum arquivo de teste encontrado em ' . $testFile,
    ];
}

// 8. Verificar se diretório de upload é gravável
$uploadDir = __DIR__ . '/../assets/uploads';
$output['upload_dir'] = [
    'label' => 'Diretório de upload',
    'path' => $uploadDir,
    'exists' => is_dir($uploadDir) ? 'SIM ✅' : 'NÃO ❌',
    'writable' => is_writable($uploadDir) ? 'SIM ✅' : 'NÃO ❌',
];

function format_bytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
