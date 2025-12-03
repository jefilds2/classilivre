<?php
/**
 * Dashboard Público de Métricas de Propaganda
 * Teste sem autenticação para verificar se os dados estão sendo registrados
 */

// Incluir conexão de BD
require_once __DIR__ . '/../../../sqlconnect.php';

// Headers
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db();
    
    // 1. Obter total de visualizações e cliques
    $stmt = $pdo->prepare('
        SELECT 
            SUM(views_count) as total_views,
            SUM(clicks_count) as total_clicks,
            COUNT(*) as total_campaigns
        FROM propaganda_metrics
    ');
    $stmt->execute();
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Obter detalhes por campanha
    $stmt = $pdo->prepare('
        SELECT 
            pc.id,
            pc.title,
            pc.ad_type,
            pc.status,
            COALESCE(pm.views_count, 0) as views_count,
            COALESCE(pm.clicks_count, 0) as clicks_count,
            CASE 
                WHEN COALESCE(pm.views_count, 0) > 0 
                THEN ROUND((COALESCE(pm.clicks_count, 0) / pm.views_count) * 100, 2)
                ELSE 0
            END as ctr,
            pm.updated_at
        FROM propagandas_campanhas pc
        LEFT JOIN propaganda_metrics pm ON pc.id = pm.campaign_id
        ORDER BY pm.updated_at DESC
    ');
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Mapear tipos
    $typeMap = [1 => 'WEB', 2 => 'LOCAL', 3 => 'EMBED'];
    $statusMap = ['ativa' => '✅ Ativa', 'inativa' => '⛔ Inativa', 'pausada' => '⏸️ Pausada'];
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'totals' => [
            'total_views' => (int)($totals['total_views'] ?? 0),
            'total_clicks' => (int)($totals['total_clicks'] ?? 0),
            'total_campaigns' => (int)($totals['total_campaigns'] ?? 0),
            'overall_ctr' => (int)($totals['total_views'] ?? 0) > 0 
                ? round(((int)($totals['total_clicks'] ?? 0) / (int)($totals['total_views'] ?? 0)) * 100, 2)
                : 0
        ],
        'campaigns' => array_map(function($c) use ($typeMap, $statusMap) {
            return [
                'id' => (int)$c['id'],
                'title' => $c['title'],
                'type' => $typeMap[$c['ad_type']] ?? 'UNKNOWN',
                'status' => $statusMap[$c['status']] ?? $c['status'],
                'views' => (int)$c['views_count'],
                'clicks' => (int)$c['clicks_count'],
                'ctr' => (float)$c['ctr'],
                'updated_at' => $c['updated_at'] ?? 'Nunca'
            ];
        }, $campaigns)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>
