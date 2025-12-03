<?php
// Tentar descobrir onde está o sqlconnect.php
$paths = [
    __DIR__ . '/../sqlconnect.php',
    __DIR__ . '/../../sqlconnect.php',
    __DIR__ . '/../../../sqlconnect.php',
    '/home2/hg8cf561/public_html/sqlconnect.php',
    $_SERVER['DOCUMENT_ROOT'] . '/sqlconnect.php',
];

echo "Procurando sqlconnect.php...\n\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "__DIR__: " . __DIR__ . "\n\n";

foreach ($paths as $path) {
    $exists = file_exists($path);
    $status = $exists ? '✓' : '✗';
    echo "$status $path\n";
    if ($exists) {
        echo "  → ENCONTRADO!\n";
    }
}

// Tentar verificar se há um index.php que carrega config
echo "\n\nProcurando index.php...\n";
$indexPaths = [
    __DIR__ . '/../../../../index.php',
    '/home2/hg8cf561/public_html/index.php',
];

foreach ($indexPaths as $path) {
    $exists = file_exists($path);
    $status = $exists ? '✓' : '✗';
    echo "$status $path\n";
}
?>
