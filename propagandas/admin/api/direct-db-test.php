<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');

// Tentar carregar sqlconnect diretamente (sem config)
try {
    $logFile = __DIR__ . '/../../logs/direct-db-test.log';
    file_put_contents($logFile, "=== Test DB direto iniciado\n", FILE_APPEND);
    
    file_put_contents($logFile, "1. Incluindo sqlconnect.php\n", FILE_APPEND);
    require_once __DIR__ . '/../../../sqlconnect.php';
    
    file_put_contents($logFile, "2. Chamando db()\n", FILE_APPEND);
    $pdo = db();
    
    file_put_contents($logFile, "3. Verificando tabela propaganda_metrics\n", FILE_APPEND);
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM propaganda_metrics');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    file_put_contents($logFile, "4. Total de registros: " . $result['total'] . "\n", FILE_APPEND);
    
    // Tentar inserir um registro de teste
    file_put_contents($logFile, "5. Testando INSERT\n", FILE_APPEND);
    $insertStmt = $pdo->prepare('
        INSERT INTO propaganda_metrics (campaign_id, views_count, clicks_count)
        VALUES (?, 1, 0)
        ON DUPLICATE KEY UPDATE views_count = views_count + 1
    ');
    $insertResult = $insertStmt->execute([40]);
    file_put_contents($logFile, "6. INSERT resultado: " . ($insertResult ? 'OK' : 'ERRO') . "\n", FILE_APPEND);
    
    // Verificar novamente
    $stmt = $pdo->prepare('SELECT * FROM propaganda_metrics WHERE campaign_id = 40');
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents($logFile, "7. Registro: " . json_encode($row) . "\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'DB test OK',
        'record' => $row
    ]);
    file_put_contents($logFile, "✓ Test completo!\n", FILE_APPEND);
    
} catch (Exception $e) {
    file_put_contents($logFile ?? '/tmp/error.log', "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>
