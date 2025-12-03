<?php
/**
 * api/logs_viewer.php
 * Visualizar logs de anúncios excluídos
 * Uso: /api/logs_viewer.php?date=2025-11-02
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../logs/ExpiredAdsLogger.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $logger = new ExpiredAdsLogger();
    
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Validar formato da data
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Formato de data inválido. Use: YYYY-MM-DD',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $logs = $logger->getLogsByDate($date);

    $stats = $logger->getStatistics();

    // Contar diferentes tipos de eventos
    $summary = [
        'total_logs' => count($logs),
        'ads_deleted' => 0,
        'errors' => 0,
        'events' => [],
    ];

    foreach ($logs as $log) {
        $event = $log['event'] ?? 'UNKNOWN';
        
        if ($event === 'AD_DELETED') {
            $summary['ads_deleted']++;
        } elseif ($event === 'ERROR') {
            $summary['errors']++;
        }

        if (!isset($summary['events'][$event])) {
            $summary['events'][$event] = 0;
        }
        $summary['events'][$event]++;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'date' => $date,
        'summary' => $summary,
        'logs' => $logs,
        'statistics' => $stats,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao ler logs',
        'details' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
