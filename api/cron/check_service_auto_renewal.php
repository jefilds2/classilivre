<?php
declare(strict_types=1);

/**
 * Cron responsável por renovar automaticamente serviços expirados
 * que estejam com auto_renewal_enabled = 1.
 *
 * Uso recomendado (crontab):
 */
//    Cron (a cada 5 minutos): /usr/bin/php /path/to/api/cron/check_service_auto_renewal.php >> /path/to/logs/service_auto_renewal.log 2>&1

// 🔒 MUTEX LOCK - Impede execução simultânea
$lockFile = '/tmp/classilivre_cron_check_service_auto_renewal.lock';
if (file_exists($lockFile)) {
    $fileAge = time() - filemtime($lockFile);
    if ($fileAge < 300) { // Se lock existe há menos de 5 minutos, sair
        error_log('[service_auto_renewal] Lock ativo - CRON já está rodando');
        exit(0);
    } else {
        @unlink($lockFile); // Lock expirou, limpar
    }
}
@file_put_contents($lockFile, time());

try {

require_once __DIR__ . '/../../servicos/service_renewal_helpers.php';

$pdo = db();

$startTime = microtime(true);
$processed = 0;
$renewed = 0;
$skippedPendingPayment = 0;
$insufficientBalance = 0;
$failures = 0;
$messages = [];

try {
    ensure_service_auto_renewal_schema($pdo);
    ensure_coin_transaction_types($pdo);

    $limit = (int)(getenv('SERVICE_AUTO_RENEW_CRON_LIMIT') ?: 50);
    if ($limit < 1) {
        $limit = 50;
    }

    $sql = <<<SQL
SELECT
    usl.id,
    usl.user_id,
    usl.name,
    usl.status,
    usl.payment_status,
    usl.expires_at,
    usl.auto_renewal_enabled
FROM user_service_listings usl
WHERE
    usl.auto_renewal_enabled = 1
    AND usl.deleted_at IS NULL
    AND usl.expires_at IS NOT NULL
    AND usl.expires_at <= NOW()
    AND usl.status IN ('active','expired')
ORDER BY usl.expires_at ASC
LIMIT :limit
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$services) {
        $messages[] = '[auto-renew] Nenhum serviço elegível encontrado.';
    }

    $coinCostSetting = (int)get_setting('service_auto_renewal_coin_cost', '1');
    if ($coinCostSetting <= 0) {
        $coinCostSetting = 1;
    }

    foreach ($services as $service) {
        $processed++;

        if (in_array((string)($service['payment_status'] ?? ''), ['pending', 'pending_gateway'], true)) {
            $skippedPendingPayment++;
            continue;
        }

        $user = ['id' => (int)$service['user_id']];
        $result = auto_renew_expired_service($service, $user, $coinCostSetting);

        if (!$result['success']) {
            if (($result['status_code'] ?? 0) === 400) {
                $insufficientBalance++;
            } else {
                $failures++;
            }

            $messages[] = sprintf(
                '[auto-renew][falha] service_id=%d user_id=%d detalhe=%s',
                (int)$service['id'],
                (int)$service['user_id'],
                $result['error'] ?? 'motivo não informado'
            );
            continue;
        }

        $renewed++;
        $messages[] = sprintf(
            '[auto-renew][ok] service_id=%d user_id=%d novo_expira=%s moedas=%d',
            (int)$service['id'],
            (int)$service['user_id'],
            $result['expires_at'] ?? '---',
            (int)($result['coins_spent'] ?? $coinCostSetting)
        );
    }
} catch (Throwable $e) {
    $failures++;
    $messages[] = '[auto-renew][erro] ' . $e->getMessage();
}

$duration = number_format(microtime(true) - $startTime, 4);

$summary = sprintf(
    "[auto-renew][resumo] processados=%d renovados=%d pendentes_pagamento=%d saldo_insuficiente=%d falhas=%d tempo=%ss",
    $processed,
    $renewed,
    $skippedPendingPayment,
    $insufficientBalance,
    $failures,
    $duration
);

$messages[] = $summary;

foreach ($messages as $message) {
    echo $message . PHP_EOL;
}

} finally {
    // Sempre remover lock ao sair
    @unlink($lockFile);
}