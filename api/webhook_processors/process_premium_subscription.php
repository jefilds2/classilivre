<?php
declare(strict_types=1);

require_once __DIR__ . '/../../sqlconnect.php';
require_once __DIR__ . '/../../login_system/middleware.php';

/**
 * Processa pagamento do Mercado Pago para assinaturas premium.
 * Este webhook somente ativa a assinatura quando o status for "approved".
 */
function process_premium_payment(array $data): void
{
    try {
        $paymentId = (int)($data['id'] ?? 0);
        $externalReference = (string)($data['external_reference'] ?? '');
        $status = (string)($data['status'] ?? '');
        $amount = isset($data['transaction_amount']) ? (float)$data['transaction_amount'] : 0.0;
        $currency = (string)($data['currency_id'] ?? $data['currency'] ?? 'BRL');
        if ($currency === '') {
            $currency = 'BRL';
        }
        $metadata = $data['metadata'] ?? [];

        error_log(sprintf('[premium] webhook recebido payment_id=%s status=%s ref=%s', $paymentId, $status, $externalReference));

        if ($paymentId <= 0 || $externalReference === '') {
            error_log('[premium] Pagamento ignorado: dados insuficientes.');
            return;
        }

        if ($status !== 'approved') {
            error_log('[premium] Pagamento ignorado. Status não aprovado: ' . $status);
            return;
        }

        $userId = isset($metadata['user_id']) ? (int)$metadata['user_id'] : 0;
        $planId = isset($metadata['plan_id']) ? (int)$metadata['plan_id'] : 0;
        $durationDays = isset($metadata['duration_days']) ? (int)$metadata['duration_days'] : 0;

        if (preg_match('/^premium_(\d+)_(\d+)_(\d+)_\d+$/', $externalReference, $matches)) {
            if ($userId <= 0) {
                $userId = (int)$matches[1];
            }
            if ($planId <= 0) {
                $planId = (int)$matches[2];
            }
            if ($durationDays <= 0) {
                $durationDays = (int)$matches[3];
            }
        }

        if ($userId <= 0) {
            error_log('[premium] Não foi possível determinar o usuário do pagamento.');
            return;
        }

        activate_premium_subscription($userId, $planId, $durationDays, $paymentId, $amount, $currency, $externalReference);
    } catch (Throwable $e) {
        error_log('[premium] Erro ao processar pagamento: ' . $e->getMessage());
    }
}

/**
 * Ativa ou estende a assinatura premium do usuário.
 */
function activate_premium_subscription(
    int $userId,
    int $planId,
    int $durationDays,
    int $paymentId,
    float $amount,
    string $currency,
    string $externalReference
): void
{
    $pdo = db();

    try {
        // Garantir que o usuário existe
        $userStmt = $pdo->prepare('SELECT id, coin_balance FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute([':id' => $userId]);
        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$userRow) {
            error_log('[premium] Usuário não encontrado: user_id=' . $userId);
            return;
        }
        $coinBalance = isset($userRow['coin_balance']) ? (int)$userRow['coin_balance'] : 0;

        // Evitar processar o mesmo pagamento duas vezes
        $existingPayment = $pdo->prepare('SELECT id FROM premium_subscriptions WHERE payment_id = :payment_id LIMIT 1');
        $existingPayment->execute([':payment_id' => $paymentId]);
        if ($existingPayment->fetchColumn()) {
            error_log('[premium] Pagamento já processado anteriormente. payment_id=' . $paymentId);
            return;
        }

        // Buscar informações do plano (caso não tenham vindo do metadata)
        $planStmt = $pdo->prepare('SELECT id, name, duration_days FROM premium_plans WHERE id = :id LIMIT 1');
        $planStmt->execute([':id' => $planId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        if (!$plan) {
            $planId = 1;
            $planStmt->execute([':id' => $planId]);
            $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($durationDays <= 0) {
            $durationDays = (int)($plan['duration_days'] ?? 30);
        }
        if ($durationDays <= 0) {
            $durationDays = 30;
        }

        $now = now();

        // Verificar se já existe assinatura ativa para estender
        $expiryStmt = $pdo->prepare('SELECT expires_at FROM premium_subscriptions WHERE user_id = :user_id AND payment_status = "approved" ORDER BY expires_at DESC LIMIT 1');
        $expiryStmt->execute([':user_id' => $userId]);
        $currentExpiry = $expiryStmt->fetchColumn();

        $baseDate = $now;
        if ($currentExpiry && strtotime($currentExpiry) > time()) {
            $baseDate = $currentExpiry;
        }
        $expiresAt = date('Y-m-d H:i:s', strtotime($baseDate . " +{$durationDays} days"));

        $pdo->beginTransaction();

        $amountPaid = round($amount, 2);
        $currency = strtoupper($currency ?: 'BRL');

        $insert = $pdo->prepare('
            INSERT INTO premium_subscriptions
            (user_id, plan_id, started_at, expires_at, payment_id, payment_status, amount_paid, payment_currency, created_at, updated_at)
            VALUES (:user_id, :plan_id, :started_at, :expires_at, :payment_id, :payment_status, :amount_paid, :payment_currency, :created_at, :updated_at)
        ');
        $insert->execute([
            ':user_id' => $userId,
            ':plan_id' => $planId,
            ':started_at' => $now,
            ':expires_at' => $expiresAt,
            ':payment_id' => $paymentId,
            ':payment_status' => 'approved',
            ':amount_paid' => $amountPaid,
            ':payment_currency' => $currency,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
        $subscriptionId = (int)$pdo->lastInsertId();

        $userUpdate = $pdo->prepare('UPDATE users SET is_premium = 1, updated_at = :updated_at WHERE id = :id');
        $userUpdate->execute([':updated_at' => $now, ':id' => $userId]);

        record_audit($userId, 'premium_activated', 'premium_subscriptions', $subscriptionId, [
            'plan_id' => $planId,
            'duration_days' => $durationDays,
            'expires_at' => $expiresAt,
            'payment_id' => $paymentId,
            'amount' => $amountPaid,
            'currency' => $currency,
        ]);

        // Registrar no histórico de transações (informativo)
        try {
            $planName = $plan['name'] ?? ('Plano ' . $planId);
            $transactionStmt = $pdo->prepare('
                INSERT INTO coin_transactions
                (user_id, type, amount, balance_before, balance_after, reference_id, reference_type, payment_method, external_reference, payment_id, payment_status, description, created_at)
                VALUES (:user_id, :type, :amount, :before, :after, :reference_id, :reference_type, :payment_method, :external_reference, :payment_id, :payment_status, :description, :created_at)
            ');
            $transactionStmt->execute([
                ':user_id' => $userId,
                ':type' => 'bonus',
                ':amount' => 0,
                ':before' => $coinBalance,
                ':after' => $coinBalance,
                ':reference_id' => $subscriptionId,
                ':reference_type' => 'premium_subscription',
                ':payment_method' => 'mercado_pago',
                ':external_reference' => $externalReference ?: ('premium_' . $userId . '_' . $paymentId),
                ':payment_id' => $paymentId,
                ':payment_status' => 'approved',
                ':description' => sprintf(
                    'Premium %s - Pagamento confirmado (%s %0.2f)',
                    $planName,
                    $currency,
                    $amountPaid
                ),
                ':created_at' => $now,
            ]);
        } catch (Throwable $txErr) {
            error_log('[premium] Falha ao registrar transação (não crítico): ' . $txErr->getMessage());
        }

        $pdo->commit();
        error_log(sprintf('[premium] Premium ativado para user_id=%d até %s', $userId, $expiresAt));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[premium] Erro ao ativar premium: ' . $e->getMessage());
    }
}
