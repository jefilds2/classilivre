<?php
/**
 * api/toggle_service_auto_renewal.php
 *
 * Endpoint para ativar/desativar auto-renovacao de servicos automotivos.
 * Quando ativado em um servico expirado, realiza a renovacao imediata
 * descontando 1 Clivre Medal do usuario (configuravel via setting).
 */

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';
require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../servicos/service_renewal_helpers.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $user = current_user();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $serviceId = (int)($_POST['service_id'] ?? 0);
    $enabled = (int)($_POST['enabled'] ?? 0) === 1 ? 1 : 0;

    if ($serviceId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid service_id']);
        exit;
    }

    $pdo = db();
    ensure_service_auto_renewal_schema($pdo);
    ensure_coin_transaction_types($pdo);
    $stmt = $pdo->prepare('SELECT id, user_id, name, status, payment_status, expires_at, auto_renewal_enabled FROM user_service_listings WHERE id = :id AND user_id = :user LIMIT 1');
    $stmt->execute([
        ':id' => $serviceId,
        ':user' => (int)$user['id'],
    ]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Service not found']);
        exit;
    }

    try {
        $update = $pdo->prepare('UPDATE user_service_listings SET auto_renewal_enabled = :enabled, updated_at = NOW() WHERE id = :id');
        $update->execute([
            ':enabled' => $enabled,
            ':id' => $serviceId,
        ]);
    } catch (\PDOException $toggleError) {
        if (strpos($toggleError->getMessage(), 'Unknown column') !== false) {
            error_log('[toggle_service_auto_renewal] Coluna auto_renewal_enabled ausente. Execute as migracoes.');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Estrutura da tabela desatualizada. Execute as migracoes do banco.']);
            exit;
        }

        throw $toggleError;
    }

    $service['auto_renewal_enabled'] = $enabled;
    $response = [
        'success' => true,
        'auto_renewal_enabled' => $enabled,
    ];

    if ($enabled === 1 && (string)$service['status'] === 'expired') {
        $autoRenewCost = (int)get_setting('service_auto_renewal_coin_cost', '1');
        if ($autoRenewCost <= 0) {
            $autoRenewCost = 1;
        }

        $renewalResult = auto_renew_expired_service($service, $user, $autoRenewCost);
        if (!$renewalResult['success']) {
            // Reverter toggle
            $pdo->prepare('UPDATE user_service_listings SET auto_renewal_enabled = 0, updated_at = NOW() WHERE id = :id')
                ->execute([':id' => $serviceId]);

            http_response_code($renewalResult['status_code']);
            echo json_encode([
                'success' => false,
                'error' => $renewalResult['error'] ?? 'Falha ao renovar automaticamente.',
            ]);
            exit;
        }

        $response = array_merge($response, [
            'renewed' => true,
            'coins_spent' => $renewalResult['coins_spent'],
            'new_expires_at' => $renewalResult['expires_at'],
            'coin_balance' => $renewalResult['coin_balance'],
            'message' => $renewalResult['message'],
        ]);

        $service['status'] = 'active';
        $service['payment_status'] = 'paid';
        $service['expires_at'] = $renewalResult['expires_at'];
    } elseif ($enabled === 1) {
        $response['message'] = 'Renova��o autom�tica ativada. Ela consumir� 1 Clivre Medal na pr�xima expira��o.';
    } else {
        $response['message'] = 'Renova��o autom�tica desativada.';
    }

    record_audit((int)$user['id'], 'service_auto_renewal_toggle', 'user_service_listings', $serviceId, [
        'auto_renewal_enabled' => $enabled,
        'action' => $enabled ? 'enabled' : 'disabled',
        'status' => $service['status'],
        'payment_status' => $service['payment_status'],
    ]);

    echo json_encode($response);
} catch (Throwable $e) {
    error_log('[toggle_service_auto_renewal] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function auto_renew_expired_service(array $service, array $user, int $coinCost): array
{
    $userId = (int)$user['id'];
    $serviceId = (int)$service['id'];

    if ($coinCost <= 0) {
        $coinCost = 1;
    }

    try {
        $balance = get_user_coin_balance($userId);
        if ($balance < $coinCost) {
            return [
                'success' => false,
                'status_code' => 400,
                'error' => 'Saldo insuficiente. ? necess?rio possuir ao menos ' . $coinCost . ' Clivre Medal para renovar automaticamente.',
            ];
        }

        if (!adjust_user_coins($userId, -$coinCost, 'service_auto_renewal', $serviceId, 'user_service_listings', 'Renovacao automatica do servico #' . $serviceId)) {
            return [
                'success' => false,
                'status_code' => 500,
                'error' => 'Nao foi poss?vel debitar a moeda para renovar automaticamente. Tente novamente.',
            ];
        }

        invalidate_coin_cache_safe($userId);

        $serviceDurationDays = (int)get_setting('services_free_duration_days', '30');
        if ($serviceDurationDays <= 0) {
            $serviceDurationDays = 30;
        }

        $now = now();
        $newExpiresAt = date('Y-m-d H:i:s', strtotime($now . " +{$serviceDurationDays} days"));

        try {
            $pdo = db();
            apply_service_renewal_update($pdo, $userId, $serviceId, $newExpiresAt, $now);
        } catch (Throwable $updateError) {
            adjust_user_coins($userId, $coinCost, 'refund', $serviceId, 'user_service_listings', 'Estorno renovacao automatica falhou');
            invalidate_coin_cache_safe($userId);
            error_log(sprintf('[toggle_service_auto_renewal] Falha ao aplicar renovacao automatica (service_id=%d): %s', $serviceId, $updateError->getMessage()));

            return [
                'success' => false,
                'status_code' => 500,
                'error' => 'Nao foi poss?vel concluir a renovacao automatica: ' . summarize_exception_message($updateError),
            ];
        }

        record_audit($userId, 'service_auto_renewal', 'user_service_listings', $serviceId, [
            'coins_spent' => $coinCost,
            'expires_at' => $newExpiresAt,
            'mode' => 'auto_paid',
        ]);

        $newBalance = get_user_coin_balance($userId);

        return [
            'success' => true,
            'expires_at' => $newExpiresAt,
            'coins_spent' => $coinCost,
            'coin_balance' => $newBalance,
            'message' => 'Servico renovado automaticamente. Foi debitada ' . $coinCost . ' Clivre Medal.',
        ];
    } catch (Throwable $e) {
        error_log(sprintf('[toggle_service_auto_renewal] Erro inesperado na auto-renovacao (service_id=%d): %s', $serviceId, $e->getMessage()));
        return [
            'success' => false,
            'status_code' => 500,
            'error' => 'Falha ao renovar automaticamente. Tente novamente mais tarde.',
        ];
    }
}

