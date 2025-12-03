<?php
/**
 * api/cleanup_expired_ads_after_15days.php
 * Script para excluir anúncios que foram marcados como 'expired' há mais de 15 dias.
 * 
 * Fluxo:
 * 1. check_ads_expiration.php marca anúncios como 'expired' quando passes expires_at
 * 2. Este script (cleanup_expired_ads_after_15days.php) DELETA após 15 dias expirados
 * 
 * Executa via cron uma vez por dia (ou conforme necessário)
 * Registra todos os deletados em logs/anuncios_excluidos/YYYY-MM-DD.log
 */

declare(strict_types=1);

// 🔒 MUTEX LOCK - Impede execução simultânea
$lockFile = '/tmp/classilivre_cron_cleanup_expired_ads_after_15days.lock';
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

// Validar token de segurança
$cronToken = $_GET['token'] ?? '';
$expectedToken = getenv('CRON_SECRET_TOKEN') ?: '7d4e2f9c1a8b6e3d5f2c9a1b7e4d8a6f';

if ($cronToken !== $expectedToken || empty($cronToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../logs/ExpiredAdsLogger.php';

// Configurar error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

$startTime = microtime(true);

try {
    $pdo = db();
    $logger = new ExpiredAdsLogger();
    
    $logger->logEvent('CLEANUP_START', 'Iniciando limpeza de anúncios após 15 dias expirados');

    // Buscar anúncios com status 'expired' há mais de 15 dias
    // Cálculo: expires_at + 15 dias <= NOW()
    // Ex: Se expires_at = 2025-10-31, deletar após 2025-11-15
    // Hoje é 2025-11-02, então threshold é 2025-10-18 (agora - 15 dias)
    // Se expires_at <= 2025-10-18, então já passou dos 15 dias

    $stmt = $pdo->prepare('
        SELECT id, title, user_id, expires_at, updated_at, status
        FROM ads
        WHERE status = "expired"
          AND DATE_ADD(expires_at, INTERVAL 15 DAY) <= NOW()
    ');
    $stmt->execute();
    $expiredAds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($expiredAds)) {
        $executionTime = microtime(true) - $startTime;
        
        $logger->logEvent('CLEANUP_NO_ADS', 'Nenhum anúncio para limpar');

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'deleted_ads' => 0,
            'message' => 'Nenhum anúncio expirado há mais de 15 dias para limpar.',
            'execution_time' => number_format($executionTime, 3)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $totalDeleted = 0;
    $deletedDetails = [];

    foreach ($expiredAds as $ad) {
        $adId = (int)$ad['id'];

        // Usar função centralizada para deletar ad + mídias
        // NOTA: delete_ad_with_media faz sua própria transação
        if (delete_ad_with_media($adId)) {
            $totalDeleted++;
            $deletedDetails[] = $ad;
            
            // Log imediato de deletado
            $logger->logAdDeleted(
                $adId,
                $ad['title'],
                (int)$ad['user_id'],
                'CLEANUP_15DAYS',
                [
                    'expires_at' => $ad['expires_at'],
                    'marked_expired_at' => $ad['updated_at'],
                ]
            );
            
            // Registrar auditoria
            record_audit(
                null,
                'ad_expired_deleted_after_15days',
                'ads',
                $adId,
                [
                    'title' => $ad['title'],
                    'user_id' => (int)$ad['user_id'],
                    'expires_at' => $ad['expires_at'],
                    'marked_expired_at' => $ad['updated_at'],
                ]
            );
        } else {
            $logger->logError('cleanup_delete_failed', "Falha ao deletar anúncio #$adId", [
                'ad_id' => $adId,
                'title' => $ad['title'],
            ]);
        }
    }

    $executionTime = microtime(true) - $startTime;
    
    $logger->logEvent('CLEANUP_COMPLETED', 'Processo finalizado com sucesso', [
        'anúncios_deletados' => $totalDeleted,
        'tempo_execução' => number_format($executionTime, 3) . 's',
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'deleted_ads' => $totalDeleted,
        'message' => "$totalDeleted anúncios expirados há 15+ dias excluídos com sucesso.",
        'execution_time' => number_format($executionTime, 3),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $errorMsg = 'Erro ao limpar anúncios expirados: ' . $exception->getMessage();
    
    if (isset($logger)) {
        $logger->logError('cleanup_execution_failed', $errorMsg, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
    
    error_log($errorMsg);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Falha ao executar limpeza de anúncios expirados.',
        'details' => $errorMsg,
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // Sempre remover lock ao sair
    @unlink($lockFile);
}
