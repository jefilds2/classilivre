<?php
/**
 * api/cron_expire_services.php
 * 
 * CRON job que marca serviços como expirados após 30 dias
 * Executar: A CADA MINUTO
 * 
 * Configuração Hostgator (cPanel):
 * Minuto: *
 * Hora: *
 * Dia: *
 * Mês: *
 * Dia da semana: *
 * Comando: curl https://seu-dominio.com/api/cron_expire_services.php?token=7d4e2f9c1a8b6e3d5f2c9a1b7e4d8a6f
 * 
 * Fluxo:
 * 1. Procura serviços com status='active' e expires_at < NOW()
 * 2. Muda status para 'expired'
 * 3. Registra auditoria
 */

declare(strict_types=1);

// 🔒 MUTEX LOCK - Impede execução simultânea
$lockFile = '/tmp/classilivre_cron_expire_services.lock';
if (file_exists($lockFile)) {
    $fileAge = time() - filemtime($lockFile);
    if ($fileAge < 600) { // Se lock existe há menos de 10 minutos, sair
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'locked', 'message' => 'CRON já está rodando']);
        exit(0);
    } else {
        @unlink($lockFile); // Lock expirou, limpar
    }
}
@file_put_contents($lockFile, time());

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
    // Buscar serviços expirados
    $stmt = $pdo->prepare('
        SELECT id, user_id, name, expires_at 
        FROM user_service_listings 
        WHERE status = "active" 
        AND expires_at IS NOT NULL 
        AND expires_at < NOW()
        LIMIT 1000
    ');
    $stmt->execute();
    $expiredServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($expiredServices)) {
        error_log('[cron_expire_services] Nenhum serviço expirado encontrado');
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Nenhum serviço expirado',
            'count' => 0,
            'execution_time' => time() - $startTime . 's'
        ]);
        exit;
    }

    // Atualizar status para expired
    $updateStmt = $pdo->prepare('
        UPDATE user_service_listings 
        SET status = "expired", updated_at = NOW() 
        WHERE status = "active" 
        AND expires_at IS NOT NULL 
        AND expires_at < NOW()
    ');
    $updateStmt->execute();
    $affectedRows = $updateStmt->rowCount();

    // Registrar cada um
    foreach ($expiredServices as $service) {
        record_audit(
            (int)$service['user_id'],
            'service_expired',
            'user_service_listings',
            (int)$service['id'],
            ['name' => $service['name'], 'expires_at' => $service['expires_at']]
        );
    }

    error_log("[cron_expire_services] {$affectedRows} serviços marcados como expirados");

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Serviços expirados processados',
        'count' => $affectedRows,
        'execution_time' => (time() - $startTime) . 's'
    ]);

} catch (Throwable $e) {
    error_log('[cron_expire_services] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'execution_time' => (time() - $startTime) . 's'
    ]);
} finally {
    // Sempre remover lock ao sair
    @unlink($lockFile);
}
?>
