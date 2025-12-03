<?php
/**
 * api/cron_cleanup_expired_services.php
 * 
 * CRON job que deleta serviços expirados após 15 dias
 * Executar: a cada 12 horas
 * 
 * Fluxo:
 * 1. Procura serviços com status='expired' e DATEDIFF(NOW(), expires_at) >= 15
 * 2. Deleta do BD (DELETE)
 * 3. Registra auditoria
 */

declare(strict_types=1);

// ✅ SINCRONIZAÇÃO: Garantir UTC em qualquer servidor/timezone
date_default_timezone_set('UTC');

// Sem autenticação (é CRON)
require_once __DIR__ . '/../login_system/middleware.php';

// ✅ SINCRONIZAÇÃO: Forçar MySQL usar UTC também
$pdo = db();
$pdo->exec("SET SESSION time_zone = '+00:00'");

// Início do script
$startTime = time();

try {
    // Buscar serviços expirados por mais de 15 dias
    $stmt = $pdo->prepare('
        SELECT id, user_id, name, expires_at 
        FROM user_service_listings 
        WHERE status = "expired" 
        AND expires_at IS NOT NULL 
        AND DATEDIFF(NOW(), expires_at) >= 15
        LIMIT 1000
    ');
    $stmt->execute();
    $oldExpiredServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($oldExpiredServices)) {
        error_log('[cron_cleanup_expired_services] Nenhum serviço para limpeza');
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Nenhum serviço para limpeza',
            'count' => 0,
            'execution_time' => (time() - $startTime) . 's'
        ]);
        exit;
    }

    // Registrar dados antes de deletar (para auditoria)
    foreach ($oldExpiredServices as $service) {
        record_audit(
            (int)$service['user_id'],
            'service_deleted_expired',
            'user_service_listings',
            (int)$service['id'],
            ['name' => $service['name'], 'expires_at' => $service['expires_at'], 'days_expired' => 'more_than_15']
        );
    }

    // Deletar serviços expirados
    $deleteStmt = $pdo->prepare('
        DELETE FROM user_service_listings 
        WHERE status = "expired" 
        AND expires_at IS NOT NULL 
        AND DATEDIFF(NOW(), expires_at) >= 15
    ');
    $deleteStmt->execute();
    $deletedRows = $deleteStmt->rowCount();

    error_log("[cron_cleanup_expired_services] {$deletedRows} serviços deletados");

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Serviços expirados deletados',
        'count' => $deletedRows,
        'execution_time' => (time() - $startTime) . 's'
    ]);

} catch (Throwable $e) {
    error_log('[cron_cleanup_expired_services] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'execution_time' => (time() - $startTime) . 's'
    ]);
    exit;
}
?>
