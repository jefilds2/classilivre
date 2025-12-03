<?php

echo "1. PHP básico funcionando!\n";
echo "2. REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";

$logFile = __DIR__ . '/../../logs/test.log';
echo "3. Log file path: $logFile\n";

$dirPath = dirname($logFile);
echo "4. Log dir: $dirPath\n";
echo "5. Log dir exists? " . (is_dir($dirPath) ? 'SIM' : 'NÃO') . "\n";
echo "6. Log dir writable? " . (is_writable($dirPath) ? 'SIM' : 'NÃO') . "\n";

// Try to write
$result = @file_put_contents($logFile, "teste\n");
echo "7. Write result: " . ($result ? 'OK (' . $result . ' bytes)' : 'ERRO') . "\n";

// Try to load config
echo "8. Config path: " . __DIR__ . '/../../config.php' . "\n";
echo "9. Config exists? " . (file_exists(__DIR__ . '/../../config.php') ? 'SIM' : 'NÃO') . "\n";

if (file_exists(__DIR__ . '/../../config.php')) {
    try {
        require_once __DIR__ . '/../../config.php';
        echo "10. Config loaded OK\n";
        
        // Try to get DB connection
        $pdo = db();
        echo "11. DB connection OK\n";
        
        // Test simple query
        $stmt = $pdo->prepare('SELECT 1');
        $stmt->execute();
        echo "12. DB query OK\n";
    } catch (Exception $e) {
        echo "10+. ERRO ao carregar config/DB: " . $e->getMessage() . "\n";
    }
}

?>
