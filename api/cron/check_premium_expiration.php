<?php
/**
 * api/cron/check_premium_expiration.php
 * Verifica assinaturas premium expiradas e remove o status premium do usuário.
 *
 * Recomenda-se agendar este script a cada 30 minutos ou 1 hora:
 a cada 30 minutos: /usr/local/bin/php /home/seu_usuario/public_html/api/cron/check_premium_expiration.php
 */

declare(strict_types=1);

// 🔒 MUTEX LOCK - Impede execução simultânea
$lockFile = '/tmp/classilivre_cron_check_premium_expiration.lock';
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

// Quando executado via web, exigir token de segurança.
if (PHP_SAPI !== 'cli') {
    $cronToken = $_GET['token'] ?? '';
    $expectedToken = getenv('CRON_SECRET_TOKEN') ?: '7d4e2f9c1a8b6e3d5f2c9a1b7e4d8a6f';

    if ($cronToken !== $expectedToken || $cronToken === '') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

require_once __DIR__ . '/../../sqlconnect.php';
require_once __DIR__ . '/../../login_system/middleware.php';

$startedAt = microtime(true);
$affected = 0;
$processed = 0;
$messages = [];

try {
    $pdo = db();

    $sql = '
        SELECT ps.id, ps.user_id, ps.expires_at
        FROM premium_subscriptions ps
        INNER JOIN users u ON u.id = ps.user_id
        WHERE ps.expires_at IS NOT NULL
          AND ps.expires_at <= UTC_TIMESTAMP()
          AND u.is_premium = 1
    ';

    $stmt = $pdo->query($sql);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$subscriptions) {
        $messages[] = '[premium_expiration] Nenhum usuário premium expirado encontrado.';
    } else {
        $now = now();
        $updateUser = $pdo->prepare('UPDATE users SET is_premium = 0, updated_at = :updated_at WHERE id = :id');
        $updateSubscription = $pdo->prepare('UPDATE premium_subscriptions SET payment_status = "expired", updated_at = :updated_at WHERE id = :id');

        foreach ($subscriptions as $subscription) {
            $processed++;
            $userId = (int)$subscription['user_id'];
            $subscriptionId = (int)$subscription['id'];

            try {
                $pdo->beginTransaction();

                $updateUser->execute([
                    ':updated_at' => $now,
                    ':id' => $userId,
                ]);

                $updateSubscription->execute([
                    ':updated_at' => $now,
                    ':id' => $subscriptionId,
                ]);

                record_audit(
                    $userId,
                    'premium_expired',
                    'premium_subscriptions',
                    $subscriptionId,
                    [
                        'expires_at' => $subscription['expires_at'],
                        'updated_at' => $now,
                    ]
                );

                $pdo->commit();
                $affected++;
                $messages[] = sprintf('[premium_expiration] Usuário %d rebaixado para plano gratuito (sub %d).', $userId, $subscriptionId);
            } catch (Throwable $transactionError) {
                $pdo->rollBack();
                $messages[] = sprintf('[premium_expiration][erro] Falha ao atualizar usuário %d: %s', $userId, $transactionError->getMessage());
                error_log(end($messages));
            }
        }
    }

    $duration = number_format(microtime(true) - $startedAt, 4);

    if (PHP_SAPI !== 'cli') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'processed' => $processed,
            'affected' => $affected,
            'messages' => $messages,
            'execution_time' => $duration,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        foreach ($messages as $message) {
            echo $message . PHP_EOL;
        }
        echo sprintf('[premium_expiration] processados=%d afetados=%d tempo=%ss', $processed, $affected, $duration) . PHP_EOL;
    }
} catch (Throwable $e) {
    error_log('[cron] Erro ao verificar expiração premium: ' . $e->getMessage());

    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo '[premium_expiration][erro] ' . $e->getMessage() . PHP_EOL;
    }
} finally {
    // Sempre remover lock ao sair
    @unlink($lockFile);
}
