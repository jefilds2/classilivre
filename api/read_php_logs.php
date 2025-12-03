<?php
// Ler logs de erro PHP diretamente

header('Content-Type: text/plain; charset=utf-8');

// Locais comuns de logs no cPanel
$possibleLogPaths = [
    '/home2/hg8cf561/public_html/error_log',
    '/home2/hg8cf561/error_log',
    '/var/log/php-fpm/error.log',
    '/var/log/apache2/error.log',
    '/home2/hg8cf561/public_html/logs/error.log',
    dirname(__FILE__) . '/../error_log',
];

echo "=== PROCURANDO ARQUIVOS DE LOG ===\n\n";

foreach ($possibleLogPaths as $path) {
    if (file_exists($path) && is_readable($path)) {
        echo "✅ ENCONTRADO: $path\n";
        echo "Tamanho: " . filesize($path) . " bytes\n";
        echo "Últimas 100 linhas:\n";
        echo str_repeat("=", 60) . "\n";
        
        $lines = file($path);
        $lastLines = array_slice($lines, -100);
        foreach ($lastLines as $line) {
            // Mostrar apenas linhas com [meus.php]
            if (strpos($line, '[meus.php]') !== false) {
                echo $line;
            }
        }
        echo str_repeat("=", 60) . "\n\n";
    }
}

// Tentar usando PHP ini_get
$errorLog = ini_get('error_log');
echo "\n=== PHP ERROR_LOG CONFIG ===\n";
echo "error_log setting: " . $errorLog . "\n";

if ($errorLog && file_exists($errorLog) && is_readable($errorLog)) {
    echo "✅ Arquivo configurado é acessível\n";
    echo "Tamanho: " . filesize($errorLog) . " bytes\n";
    echo "Últimas 50 linhas:\n";
    echo str_repeat("=", 60) . "\n";
    
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -50);
    foreach ($lastLines as $line) {
        echo $line;
    }
    echo str_repeat("=", 60) . "\n";
}

// Tentar usar tail command
echo "\n=== TENTANDO TAIL COMMAND ===\n";
if (function_exists('shell_exec')) {
    $tail = shell_exec('tail -50 ' . escapeshellarg($errorLog ?? '/home2/hg8cf561/public_html/error_log') . ' 2>&1');
    if ($tail) {
        echo $tail;
    }
}

?>
