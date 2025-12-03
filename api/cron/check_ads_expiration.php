<?php
/**
 * api/cron/check_ads_expiration.php
 * Script de cron para verificar e marcar anúncios como expirados
 * 
 * Executa: A CADA MINUTO
 * 
 * Configuração Hostgator (cPanel):
 * Minuto: *
 * Hora: *
 * Dia: *
 * Mês: *
 * Dia da semana: *
 * Comando: curl https://seu-dominio.com/api/cron/check_ads_expiration.php?token=7d4e2f9c1a8b6e3d5f2c9a1b7e4d8a6f
 * 
 * O que faz:
 * 1. Executa procedure check_ads_expiration()
 * 2. Marca anúncios com expires_at < NOW() como 'expired'
 * 3. Retorna estatísticas
 */

declare(strict_types=1);

// 🔒 MUTEX LOCK - Impede execução simultânea
$lockFile = '/tmp/classilivre_cron_check_ads_expiration.lock';
if (file_exists($lockFile)) {
    $fileAge = time() - filemtime($lockFile);
    if ($fileAge < 300) { // Se lock existe há menos de 5 minutos, sair
        http_response_code(200);
        echo json_encode(['status' => 'locked', 'message' => 'CRON já está rodando']);
        exit(0);
    } else {
        @unlink($lockFile); // Lock expirou, limpar
    }
}
@file_put_contents($lockFile, time());

// Incluir sqlconnect PRIMEIRO para ter acesso às funções
require_once __DIR__ . '/../../sqlconnect.php';

// Validar token de segurança
$cronToken = $_GET['token'] ?? '';
$expectedToken = getenv('CRON_SECRET_TOKEN') ?: get_setting('cron_secret_token');

if ($cronToken !== $expectedToken || empty($cronToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized', 'received' => $cronToken, 'expected' => substr($expectedToken, 0, 5) . '...']);
    exit;
}

require_once __DIR__ . '/../../logs/anuncios_expirados/ExpiredAdsLogger.php';

$startTime = microtime(true);
header('Content-Type: application/json');

try {
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
    
    // 2. OPÇÃO 1: Tentar usar procedure se existir
    $procExists = false;
    try {
        $stmt = $pdo->query("SHOW PROCEDURE STATUS LIKE 'check_ads_expiration'");
        $procExists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // Procedure check falhou, prosseguir com query direta
    }
    
    if ($procExists && !empty($adsToExpire)) {
        try {
            $pdo->exec('CALL check_ads_expiration()');
            $logger->logEvent('PROCEDURE_EXECUTED', 'Procedure check_ads_expiration() executado com sucesso');
        } catch (PDOException $e) {
            // Se procedure falhar, usar query direta
            error_log('[cron_ads] Procedure falhou, usando query direta: ' . $e->getMessage());
            
            $updateStmt = $pdo->prepare('
                UPDATE ads 
                SET status = "expired", updated_at = NOW()
                WHERE id = ?
            ');
            
            foreach ($adsToExpire as $ad) {
                try {
                    $updateStmt->execute([$ad['id']]);
                    $adsMarked++;
                } catch (PDOException $ex) {
                    error_log('[cron_ads] Erro ao atualizar anúncio ' . $ad['id'] . ': ' . $ex->getMessage());
                }
            }
        }
    } elseif (!empty($adsToExpire)) {
        // Usar query direta (sem procedure)
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
                error_log('[cron_ads] Erro ao atualizar anúncio ' . $ad['id'] . ': ' . $e->getMessage());
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
        $logger->logAdsMarkedAsExpired(count($adsToExpire), $adsToExpire);
    }
    
    // ========================================================================
    // 5. LÓGICA DE AUTO-RENOVAÇÃO
    // Anúncios com auto_renewal_enabled = 1 e saldo disponível são renovados
    // ========================================================================
    
    $autoRenewed = 0;
    $autoRenewalFailed = [];
    
    if (!empty($adsToExpire)) {
        // Buscar quais destes anúncios têm auto_renewal_enabled = 1
        $autoRenewSql = 'SELECT id, user_id, title, expires_at FROM ads WHERE status IN (?, ?)';
        $autoRenewStmt = $pdo->prepare($autoRenewSql);
        $autoRenewStmt->execute(['expired', 'approved']);
        $potentialAutoRenewal = $autoRenewStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($potentialAutoRenewal as $ad) {
            try {
                // Verificar se tem auto_renewal habilitado
                $checkStmt = $pdo->prepare('
                    SELECT auto_renewal_enabled, user_id FROM ads WHERE id = :id LIMIT 1
                ');
                $checkStmt->execute([':id' => (int)$ad['id']]);
                $adData = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$adData || (int)$adData['auto_renewal_enabled'] !== 1) {
                    continue;
                }
                
                // Verificar saldo do usuário
                $balanceStmt = $pdo->prepare('SELECT coin_balance FROM users WHERE id = :id LIMIT 1');
                $balanceStmt->execute([':id' => (int)$ad['user_id']]);
                $balance = (int)$balanceStmt->fetchColumn();
                
                if ($balance < 1) {
                    // Sem saldo, pular esta renovação
                    $autoRenewalFailed[] = [
                        'ad_id' => $ad['id'],
                        'reason' => 'insufficient_balance'
                    ];
                    continue;
                }
                
                // ✅ Renovar automaticamente
                $pdo->beginTransaction();
                
                // Obter saldo atual para balance_before e balance_after
                $balanceCheckStmt = $pdo->prepare('SELECT coin_balance FROM users WHERE id = :id FOR UPDATE');
                $balanceCheckStmt->execute([':id' => (int)$ad['user_id']]);
                $currentBalance = (int)$balanceCheckStmt->fetchColumn();
                $balanceAfter = $currentBalance - 1;
                
                // Debitar 1 moeda
                $pdo->prepare('
                    UPDATE users 
                    SET coin_balance = :balance, updated_at = NOW()
                    WHERE id = :id
                ')->execute([
                    ':balance' => $balanceAfter,
                    ':id' => (int)$ad['user_id']
                ]);
                
                // Registrar transação (com campos corretos da tabela)
                $pdo->prepare('
                    INSERT INTO coin_transactions 
                    (user_id, type, amount, balance_before, balance_after, reference_id, reference_type, description, created_at)
                    VALUES (:user_id, :type, :amount, :balance_before, :balance_after, :reference_id, :reference_type, :description, NOW())
                ')->execute([
                    ':user_id' => (int)$ad['user_id'],
                    ':type' => 'ad_auto_renewal',
                    ':amount' => -1,
                    ':balance_before' => $currentBalance,
                    ':balance_after' => $balanceAfter,
                    ':reference_id' => (int)$ad['id'],
                    ':reference_type' => 'ads',
                    ':description' => 'Auto-renovação de anúncio: ' . $ad['title']
                ]);
                
                // Atualizar anúncio: status back to approved, expires_at += 30
                $newExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                $pdo->prepare('
                    UPDATE ads 
                    SET status = "approved",
                        expires_at = :expires_at,
                        last_renewed_at = NOW(),
                        updated_at = NOW()
                    WHERE id = :id
                ')->execute([
                    ':expires_at' => $newExpiresAt,
                    ':id' => (int)$ad['id']
                ]);
                
                // Registrar auditoria
                record_audit((int)$ad['user_id'], 'ad_auto_renewed', 'ads', (int)$ad['id'], [
                    'title' => $ad['title'],
                    'new_expires_at' => $newExpiresAt,
                    'cost_coins' => 1,
                    'triggered_by' => 'cron_auto_renewal'
                ]);
                
                $pdo->commit();
                $autoRenewed++;
                
                $logger->logEvent('AD_AUTO_RENEWED', sprintf(
                    'Anúncio #%d "%s" renovado automaticamente. Novo expiration: %s',
                    $ad['id'],
                    $ad['title'],
                    $newExpiresAt
                ));
                
            } catch (Throwable $e) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                error_log('[cron_auto_renewal] Erro ao renovar ad #' . $ad['id'] . ': ' . $e->getMessage());
                
                $autoRenewalFailed[] = [
                    'ad_id' => $ad['id'],
                    'reason' => 'transaction_error',
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    $executionTime = microtime(true) - $startTime;
    $logger->logEvent('CHECK_ADS_EXPIRATION', 'Verificação de expiração e auto-renovação concluída', [
        'anúncios_encontrados' => count($adsToExpire),
        'anúncios_marcados_expirados' => $adsMarked > 0 ? $adsMarked : count($adsToExpire),
        'anúncios_auto_renovados' => $autoRenewed,
        'auto_renovação_falhadas' => count($autoRenewalFailed),
        'total_expirados_sistema' => $expiredCount,
        'procedure_disponível' => $procExists ? 'SIM' : 'NÃO',
        'tempo_execução' => number_format($executionTime, 3) . 's'
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Ads expiration check and auto-renewal completed',
        'ads_found' => count($adsToExpire),
        'ads_marked_expired' => count($adsToExpire),
        'ads_auto_renewed' => $autoRenewed,
        'auto_renewal_failed' => count($autoRenewalFailed),
        'expired_ads_total' => $expiredCount,
        'timestamp' => date('Y-m-d H:i:s'),
        'execution_time' => number_format($executionTime, 3) . 's'
    ]);
    
} catch (Throwable $e) {
    error_log('[cron_ads] Erro ao verificar expiração: ' . $e->getMessage());
    if (isset($logger)) {
        $logger->logError('Erro ao verificar expiração', $e->getMessage());
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Sempre remover lock ao sair
    @unlink($lockFile);
}
