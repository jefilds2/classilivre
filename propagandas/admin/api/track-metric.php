<?php

declare(strict_types=1);

/**
 * API: Rastrear Métricas de Campanhas
 * Arquivo: /propagandas/admin/api/track-metric.php
 * Método: POST
 * Body: { campaign_id, event_type }
 * 
 * event_type pode ser: 'view' ou 'click'
 * 
 * Incrementa os contadores na tabela propaganda_metrics
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

// Log file para debug
$logFile = __DIR__ . '/../../logs/track-metric.log';

// Incluir configuração de propagandas ANTES de enviar headers
require_once __DIR__ . '/../../../sqlconnect.php';

// Agora sim enviar header
header('Content-Type: application/json; charset=utf-8');

try {
    
    // Apenas POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ Método não POST: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
        http_response_code(405);
        echo json_encode([
            'error' => 'Método não permitido',
            'success' => false
        ]);
        exit;
    }

    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✓ POST recebido\n", FILE_APPEND);

    // Ler JSON do corpo
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ JSON inválido\n", FILE_APPEND);
        http_response_code(400);
        echo json_encode([
            'error' => 'JSON inválido',
            'success' => false
        ]);
        exit;
    }

    $campaignId = (int)($input['campaign_id'] ?? 0);
    $eventType = (string)($input['event_type'] ?? '');

    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Dados: campaign_id=$campaignId, event_type=$eventType\n", FILE_APPEND);

    // Validar entrada
    if (!$campaignId || !in_array($eventType, ['view', 'click'])) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ Dados inválidos\n", FILE_APPEND);
        http_response_code(400);
        echo json_encode([
            'error' => 'Dados inválidos: campaign_id e event_type (view|click) são obrigatórios',
            'success' => false
        ]);
        exit;
    }

    $pdo = db();

    // Iniciar transação
    $pdo->beginTransaction();

    try {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 🔄 Iniciando transação...\n", FILE_APPEND);
        
        // Verificar se métrica existe para essa campanha
        $checkStmt = $pdo->prepare('
            SELECT campaign_id FROM propaganda_metrics WHERE campaign_id = ?
        ');
        $checkStmt->execute([$campaignId]);
        $exists = $checkStmt->rowCount() > 0;

        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Métrica existe? " . ($exists ? 'SIM' : 'NÃO') . "\n", FILE_APPEND);

        if ($exists) {
            // Incrementar o contador apropriado
            if ($eventType === 'view') {
                $updateStmt = $pdo->prepare('
                    UPDATE propaganda_metrics 
                    SET views_count = views_count + 1, updated_at = NOW()
                    WHERE campaign_id = ?
                ');
            } else {
                $updateStmt = $pdo->prepare('
                    UPDATE propaganda_metrics 
                    SET clicks_count = clicks_count + 1, updated_at = NOW()
                    WHERE campaign_id = ?
                ');
            }
            $result = $updateStmt->execute([$campaignId]);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✓ Métrica atualizada. Resultado: " . ($result ? 'OK' : 'ERRO') . "\n", FILE_APPEND);
        } else {
            // Criar nova métrica (inicializar com 1)
            if ($eventType === 'view') {
                $insertStmt = $pdo->prepare('
                    INSERT INTO propaganda_metrics (campaign_id, views_count, clicks_count)
                    VALUES (?, 1, 0)
                ');
            } else {
                $insertStmt = $pdo->prepare('
                    INSERT INTO propaganda_metrics (campaign_id, views_count, clicks_count)
                    VALUES (?, 0, 1)
                ');
            }
            $result = $insertStmt->execute([$campaignId]);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✓ Métrica criada. Resultado: " . ($result ? 'OK' : 'ERRO') . "\n", FILE_APPEND);
        }

        $pdo->commit();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✓ Transação commitada\n", FILE_APPEND);

        // Retornar contadores atualizados
        $getStmt = $pdo->prepare('
            SELECT views_count, clicks_count FROM propaganda_metrics WHERE campaign_id = ?
        ');
        $getStmt->execute([$campaignId]);
        $metrics = $getStmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Métrica registrada com sucesso',
            'event_type' => $eventType,
            'campaign_id' => $campaignId,
            'metrics' => $metrics
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ ERRO na transação: " . $e->getMessage() . "\n", FILE_APPEND);
        throw $e;
    }

} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ ERRO GERAL: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao registrar métrica: ' . $e->getMessage(),
        'success' => false
    ]);
}

?>
