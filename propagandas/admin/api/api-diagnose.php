<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');

// Tentar carregar config
echo "1. Iniciando...\n";

try {
    $logFile = __DIR__ . '/../../logs/api-diagnose.log';
    file_put_contents($logFile, "=== Test iniciado\n", FILE_APPEND);
    echo "2. Log file: $logFile\n";
    
    file_put_contents($logFile, "3. Incluindo config\n", FILE_APPEND);
    require_once __DIR__ . '/../../config.php';
    echo "3. Config incluído\n";
    
    file_put_contents($logFile, "4. Chamando db()\n", FILE_APPEND);
    $pdo = db();
    echo "4. PDO OK\n";
    
    file_put_contents($logFile, "5. Testando query\n", FILE_APPEND);
    $stmt = $pdo->prepare('SELECT 1 as test');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "5. Query OK: " . json_encode($result) . "\n";
    
    file_put_contents($logFile, "✓ Tudo OK!\n", FILE_APPEND);
    echo "6. ✓ Sucesso!\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
    
    if (isset($logFile)) {
        file_put_contents($logFile, "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>
