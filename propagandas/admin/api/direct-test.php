<?php

/**
 * Teste direto da API de track-metric
 * Arquivo: /propagandas/admin/api/direct-test.php
 */

echo "<h1>🧪 Teste Direto da API</h1>";

// Incluir configuração
require_once __DIR__ . '/../../config.php';

echo "<p>✓ Config carregado</p>";

// Testar conexão
try {
    $pdo = db();
    echo "<p>✓ Banco de dados conectado</p>";
    
    // Verificar tabela
    $result = $pdo->query("SELECT COUNT(*) as count FROM propaganda_metrics");
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>✓ Tabela propaganda_metrics encontrada (registros: $count)</p>";
    
} catch (Exception $e) {
    echo "<p>✗ Erro ao conectar: " . $e->getMessage() . "</p>";
    exit;
}

// Simular uma requisição POST
echo "<h2>Simulando POST...</h2>";

$_SERVER['REQUEST_METHOD'] = 'POST';
$campaign_id = 40; // ID da campanha de teste
$event_type = 'view';

// Criar JSON
$data = ['campaign_id' => $campaign_id, 'event_type' => $event_type];
echo "<p>Dados: " . json_encode($data) . "</p>";

// Testar inserção direta na BD
try {
    echo "<h2>Testando inserção direta...</h2>";
    
    // Verificar se existe
    $checkStmt = $pdo->prepare('SELECT * FROM propaganda_metrics WHERE campaign_id = ?');
    $checkStmt->execute([$campaign_id]);
    $exists = $checkStmt->rowCount() > 0;
    
    echo "<p>Métrica existe? " . ($exists ? 'SIM' : 'NÃO') . "</p>";
    
    if (!$exists) {
        // Inserir nova
        $insertStmt = $pdo->prepare('
            INSERT INTO propaganda_metrics (campaign_id, views_count, clicks_count)
            VALUES (?, 1, 0)
        ');
        $result = $insertStmt->execute([$campaign_id]);
        echo "<p>✓ Inserção: " . ($result ? 'OK' : 'ERRO') . "</p>";
    } else {
        // Atualizar existente
        $updateStmt = $pdo->prepare('
            UPDATE propaganda_metrics 
            SET views_count = views_count + 1, updated_at = NOW()
            WHERE campaign_id = ?
        ');
        $result = $updateStmt->execute([$campaign_id]);
        echo "<p>✓ Atualização: " . ($result ? 'OK' : 'ERRO') . "</p>";
    }
    
    // Verificar resultado
    $getStmt = $pdo->prepare('SELECT views_count, clicks_count FROM propaganda_metrics WHERE campaign_id = ?');
    $getStmt->execute([$campaign_id]);
    $metrics = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>✓ Resultado final:</p>";
    echo "<pre>" . json_encode($metrics, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>✗ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='../../../propagandas/check-table.php'>← Voltar ao check-table.php</a></p>";

?>
