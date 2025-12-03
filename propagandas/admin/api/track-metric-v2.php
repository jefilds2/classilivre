<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Log file
$logFile = __DIR__ . '/../../logs/track-metric.log';

try {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " === REQUISIÇÃO INICIADA ===\n", FILE_APPEND);
    
    // Apenas POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " ❌ Método não POST\n", FILE_APPEND);
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Apenas POST', 'success' => false]);
        exit;
    }
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " ✓ POST recebido\n", FILE_APPEND);
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Ler JSON
    file_put_contents($logFile, date('Y-m-d H:i:s') . " Lendo input\n", FILE_APPEND);
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " ❌ JSON inválido\n", FILE_APPEND);
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido', 'success' => false]);
        exit;
    }
    
    $campaignId = (int)($input['campaign_id'] ?? 0);
    $eventType = (string)($input['event_type'] ?? '');
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " Dados: campaign_id=$campaignId, event_type=$eventType\n", FILE_APPEND);
    
    // Validar
    if (!$campaignId || !in_array($eventType, ['view', 'click'])) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " ❌ Dados inválidos\n", FILE_APPEND);
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos', 'success' => false]);
        exit;
    }
    
    // Incluir DB AQUI (depois de headers)
    file_put_contents($logFile, date('Y-m-d H:i:s') . " Incluindo sqlconnect\n", FILE_APPEND);
    require_once __DIR__ . '/../../../sqlconnect.php';
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . " Conectando ao DB\n", FILE_APPEND);
    $pdo = db();
    
    // Iniciar transação
    $pdo->beginTransaction();
    file_put_contents($logFile, date('Y-m-d H:i:s') . " Transação iniciada\n", FILE_APPEND);
    
    try {
        // Verificar se existe
        $checkStmt = $pdo->prepare('SELECT campaign_id FROM propaganda_metrics WHERE campaign_id = ?');
        $checkStmt->execute([$campaignId]);
        $exists = $checkStmt->rowCount() > 0;
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " Métrica existe? " . ($exists ? 'SIM' : 'NÃO') . "\n", FILE_APPEND);
        
        if ($exists) {
            // Update
            if ($eventType === 'view') {
                $stmt = $pdo->prepare('UPDATE propaganda_metrics SET views_count = views_count + 1, updated_at = NOW() WHERE campaign_id = ?');
            } else {
                $stmt = $pdo->prepare('UPDATE propaganda_metrics SET clicks_count = clicks_count + 1, updated_at = NOW() WHERE campaign_id = ?');
            }
            $result = $stmt->execute([$campaignId]);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " ✓ UPDATE: " . ($result ? 'OK' : 'ERRO') . "\n", FILE_APPEND);
        } else {
            // Insert
            if ($eventType === 'view') {
                $stmt = $pdo->prepare('INSERT INTO propaganda_metrics (campaign_id, views_count, clicks_count) VALUES (?, 1, 0)');
            } else {
                $stmt = $pdo->prepare('INSERT INTO propaganda_metrics (campaign_id, views_count, clicks_count) VALUES (?, 0, 1)');
            }
            $result = $stmt->execute([$campaignId]);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " ✓ INSERT: " . ($result ? 'OK' : 'ERRO') . "\n", FILE_APPEND);
        }
        
        $pdo->commit();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " ✓ Transação commitada\n", FILE_APPEND);
        
        // Retornar dados
        $getStmt = $pdo->prepare('SELECT views_count, clicks_count FROM propaganda_metrics WHERE campaign_id = ?');
        $getStmt->execute([$campaignId]);
        $metrics = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'campaign_id' => $campaignId,
            'event_type' => $eventType,
            'metrics' => $metrics
        ]);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " ✓ Resposta OK\n", FILE_APPEND);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " ❌ ERRO transação: " . $e->getMessage() . "\n", FILE_APPEND);
        throw $e;
    }
    
} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " ❌ ERRO GERAL: " . $e->getMessage() . " (Line " . $e->getLine() . ")\n", FILE_APPEND);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
