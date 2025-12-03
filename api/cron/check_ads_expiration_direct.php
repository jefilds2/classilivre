<?php
/**
 * api/cron/check_ads_expiration_direct.php
 * 
 * Versão DIRETA - sem usar procedure SQL
 * Marca anúncios como expirados executando a query diretamente
 * 
 * Executa: A CADA MINUTO
 * 
 * Configuração Hostgator (cPanel):
 * Comando: curl https://seu-dominio.com/api/cron/check_ads_expiration_direct.php?token=7d4e2f9c1a8b6e3d5f2c9a1b7e4d8a6f
 */

declare(strict_types=1);

// Incluir sqlconnect PRIMEIRO
require_once __DIR__ . '/../../sqlconnect.php';

// Validar token de segurança
$cronToken = $_GET['token'] ?? '';
$expectedToken = getenv('CRON_SECRET_TOKEN') ?: get_setting('cron_secret_token');

if ($cronToken !== $expectedToken || empty($cronToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../../logs/anuncios_expirados/ExpiredAdsLogger.php';
header('Content-Type: application/json');

$startTime = microtime(true);try {
    $pdo = db();
    $logger = new ExpiredAdsLogger();
    
    // 1. Buscar anúncios que vão ser marcados como expirados ANTES de atualizar
    $stmtBefore = $pdo->prepare('
        SELECT id, title, expires_at 
        FROM ads 
        WHERE status NOT IN (\'expired\', \'rejected\', \'blocked\', \'sold\')
        AND expires_at IS NOT NULL 
        AND expires_at < NOW()
    ');
    $stmtBefore->execute();
    $adsToExpire = $stmtBefore->fetchAll(PDO::FETCH_ASSOC);
    
    $adsMarked = 0;
    
    // 2. Marcar cada anúncio como expirado (executando a query diretamente)
    if (!empty($adsToExpire)) {
        $updateStmt = $pdo->prepare('
            UPDATE ads 
            SET status = "expired", updated_at = NOW()
            WHERE id = ?
        ');
        
        foreach ($adsToExpire as $ad) {
            try {
                $updateStmt->execute([$ad['id']]);
                $adsMarked++;
            } catch (PDOException $e) {
                error_log('[cron_ads_direct] Erro ao marcar anúncio ' . $ad['id'] . ': ' . $e->getMessage());
            }
        }
    }
    
    // 3. Contar total de anúncios expirados agora
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE status = "expired"
    ');
    $stmt->execute();
    $expiredCount = (int)$stmt->fetchColumn();
    
    // 4. Registrar no log
    if (!empty($adsToExpire)) {
        $logger->logAdsMarkedAsExpired($adsMarked, $adsToExpire);
    }
    
    $executionTime = microtime(true) - $startTime;
    $logger->logEvent('CHECK_ADS_EXPIRATION_DIRECT', 'Anúncios marcados como expirados (query direta)', [
        'anúncios_processados' => count($adsToExpire),
        'anúncios_atualizados' => $adsMarked,
        'total_expirados_sistema' => $expiredCount,
        'tempo_execução' => number_format($executionTime, 3) . 's'
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Ads expiration check completed (direct method)',
        'ads_found' => count($adsToExpire),
        'ads_marked' => $adsMarked,
        'expired_ads_total' => $expiredCount,
        'timestamp' => date('Y-m-d H:i:s'),
        'execution_time' => number_format($executionTime, 3) . 's'
    ]);
    
} catch (Throwable $e) {
    error_log('[cron_ads_direct] Erro ao verificar expiração: ' . $e->getMessage());
    if (isset($logger)) {
        $logger->logError('Erro ao verificar expiração (direct)', $e->getMessage());
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
