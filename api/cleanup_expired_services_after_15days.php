<?php
/**
 * api/cleanup_expired_services_after_15days.php
 * Script para excluir serviços que foram marcados como 'expired' há mais de 15 dias.
 * 
 * Fluxo:
 * 1. Serviços expiram quando passa expires_at
 * 2. Este script DELETA após 15 dias expirados
 * 
 * Executa via cron uma vez por dia (ou conforme necessário)
 * Registra deletados em auditoria e logs
 */

declare(strict_types=1);

// Validação de token apenas para chamadas HTTP. CLI pode rodar sem token.
if (PHP_SAPI !== 'cli') {
    $cronToken = $_GET['token'] ?? '';
    $expectedToken = getenv('CRON_SECRET_TOKEN') ?: '7d4e2f9c1a8b6e3d5f2c9a1b7e4d8a6f';

    if ($cronToken !== $expectedToken || empty($cronToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../servicos/service_renewal_helpers.php';

// Configurar error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$startTime = microtime(true);

try {
    $pdo = db();
    
    ensure_service_auto_renewal_schema($pdo);

    error_log('[SERVICE_CLEANUP] Iniciando limpeza de serviços após 15 dias expirados');

    // Buscar serviços cuja data de expiração somada a 15 dias já passou.
    // Independentemente do status atual, desde que não já tenham sido removidos.
    $stmt = $pdo->prepare('
        SELECT id, name, user_id, expires_at, status
        FROM user_service_listings
        WHERE expires_at IS NOT NULL
          AND DATE_ADD(expires_at, INTERVAL 15 DAY) <= UTC_TIMESTAMP()
    ');
    $stmt->execute();
    $expiredServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($expiredServices)) {
        $executionTime = microtime(true) - $startTime;
        
        error_log('[SERVICE_CLEANUP] Nenhum serviço para limpar');

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'deleted_services' => 0,
            'message' => 'Nenhum serviço expirado há mais de 15 dias para limpar.',
            'execution_time' => number_format($executionTime, 3)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $totalDeleted = 0;
    $deletedDetails = [];

    foreach ($expiredServices as $service) {
        $serviceId = (int)$service['id'];
        $userId = (int)$service['user_id'];

        try {
            $pdo->beginTransaction();

            $deleteStmt = $pdo->prepare('DELETE FROM user_service_listings WHERE id = :id');
            $deleteStmt->execute([':id' => $serviceId]);

            record_audit($userId, 'service_auto_deleted', 'user_service_listings', $serviceId, [
                'name' => $service['name'],
                'reason' => 'Automaticamente deletado 15 dias após expiração (baseado em expires_at)',
                'status' => $service['status'],
                'expires_at' => $service['expires_at'],
                'deleted_at' => now()
            ]);

            $pdo->commit();

            $totalDeleted++;
            $deletedDetails[] = [
                'id' => $serviceId,
                'name' => $service['name'],
                'user_id' => $userId,
                'expires_at' => $service['expires_at']
            ];

            error_log("[SERVICE_CLEANUP] Serviço {$serviceId} ({$service['name']}) deletado com sucesso");

        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log("[SERVICE_CLEANUP_ERROR] Erro ao deletar serviço {$serviceId}: " . $e->getMessage());
            throw $e;
        }
    }

    $executionTime = microtime(true) - $startTime;
    
    error_log("[SERVICE_CLEANUP] Limpeza concluída: {$totalDeleted} serviços deletados em " . number_format($executionTime, 3) . "s");

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'deleted_services' => $totalDeleted,
        'details' => $deletedDetails,
        'message' => "Total de {$totalDeleted} serviço(s) deletado(s) com sucesso.",
        'execution_time' => number_format($executionTime, 3)
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    $executionTime = microtime(true) - $startTime;
    
    error_log('[SERVICE_CLEANUP_FATAL] Erro fatal: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'execution_time' => number_format($executionTime, 3)
    ], JSON_UNESCAPED_UNICODE);
}
?>
