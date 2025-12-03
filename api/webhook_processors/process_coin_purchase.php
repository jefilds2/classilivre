<?php
/**
 * api/webhook_processors/process_coin_purchase.php
 *
 * Processador das compras de moedas CLivre Medal via webhook do Mercado Pago.
 *
 * Variáveis fornecidas por api/mercado_pago_webhook.php:
 * - $payment        Array completo do pagamento retornado pela API MP
 * - $payment_id     ID numérico do pagamento
 * - $external_reference  Referência externa enviada na criação da preference
 */

declare(strict_types=1);

try {
    // Formato esperado: coin_purchase_{user_id}_{package_id}_{timestamp}
    if (!preg_match('/^coin_purchase_(\d+)_(\d+)_(\d+)$/', $external_reference, $matches)) {
        webhook_log('External_reference invalido: ' . $external_reference);
        http_response_code(200);
        echo json_encode(['success' => true, 'reason' => 'Invalid reference format']);
        return;
    }

    $userId = (int)$matches[1];
    $packageId = (int)$matches[2];
    $referenceTimestamp = (int)$matches[3];
    $status = (string)($payment['status'] ?? '');
    $amountPaid = (float)($payment['transaction_amount'] ?? 0);
    $currencyId = (string)($payment['currency_id'] ?? 'BRL');
    $payerEmail = (string)($payment['payer']['email'] ?? '');
    $nowTs = now();

    webhook_log(sprintf('[coins] webhook recebido payment_id=%s status=%s external_ref=%s', $payment_id, $status, $external_reference));

    // Ignorar notificações muito antigas (±3 horas)
    if (abs(time() - $referenceTimestamp) > 10800) {
        webhook_log('[coins] Ignorado por timestamp antigo: ' . $referenceTimestamp);
        http_response_code(200);
        echo json_encode(['success' => true, 'reason' => 'Stale webhook']);
        return;
    }

    $db = db();

    // Pedido associado (pode não existir em fluxos antigos)
    $orderStmt = $db->prepare('SELECT * FROM coin_purchase_orders WHERE external_reference = :external_reference LIMIT 1');
    $orderStmt->execute([':external_reference' => $external_reference]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Atualizar pedidos não aprovados e encerrar
    if ($status !== 'approved') {
        if ($order) {
            $newStatus = $order['status'];
            $updateColumns = [
                'payment_status' => $status,
                'payment_id' => $payment_id,
                'raw_payload' => json_encode($payment, JSON_UNESCAPED_UNICODE),
                'updated_at' => $nowTs,
            ];

            if (in_array($status, ['cancelled', 'rejected', 'refunded', 'charged_back'], true)) {
                $newStatus = 'cancelled';
                $updateColumns['cancelled_at'] = $nowTs;
            } elseif ($newStatus === 'pending') {
                $newStatus = 'pending';
            }

            $setParts = [
                'payment_status = :payment_status',
                'payment_id = :payment_id',
                'raw_payload = :raw_payload',
                'updated_at = :updated_at',
                'status = :status',
            ];
            if (isset($updateColumns['cancelled_at'])) {
                $setParts[] = 'cancelled_at = :cancelled_at';
            }

            $updateSql = 'UPDATE coin_purchase_orders SET ' . implode(', ', $setParts) . ' WHERE id = :id';
            $updateStmt = $db->prepare($updateSql);
            $bindParams = [
                ':payment_status' => $updateColumns['payment_status'],
                ':payment_id' => (int)$payment_id,
                ':raw_payload' => $updateColumns['raw_payload'],
                ':updated_at' => $updateColumns['updated_at'],
                ':status' => $newStatus,
                ':id' => (int)$order['id'],
            ];
            if (isset($updateColumns['cancelled_at'])) {
                $bindParams[':cancelled_at'] = $updateColumns['cancelled_at'];
            }
            $updateStmt->execute($bindParams);
        }

        webhook_log('[coins] Pagamento não aprovado (status=' . $status . ')');
        http_response_code(200);
        echo json_encode(['success' => true, 'status' => $status]);
        return;
    }

    // Carregar usuário
    $userStmt = $db->prepare('SELECT id, email, coin_balance FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        webhook_log('[coins] Usuário não encontrado: ' . $userId);
        http_response_code(200);
        echo json_encode(['success' => true, 'reason' => 'User not found']);
        return;
    }

    if ($payerEmail && $payerEmail !== $user['email']) {
        webhook_log('[coins] Aviso: email diferente. Esperado=' . $user['email'] . ' recebido=' . $payerEmail);
    }

    // Catálogo de pacotes (mesma lógica do front)
    $packageCatalog = [
        1 => ['name' => 'Starter', 'base' => 1,  'bonus' => 0],
        2 => ['name' => 'Smart',   'base' => 5,  'bonus' => 1],
        3 => ['name' => 'Popular', 'base' => 10, 'bonus' => 2],
        4 => ['name' => 'Pro',     'base' => 20, 'bonus' => 7],
        5 => ['name' => 'Mega',    'base' => 50, 'bonus' => 20],
    ];

    $coinsBase = $order ? (int)$order['coins_base'] : null;
    $coinsBonus = $order ? (int)$order['coins_bonus'] : null;
    $coinsTotal = $order ? (int)$order['coins_total'] : null;
    $packageName = $order['package_label'] ?? null;

    if ($coinsBase === null || $coinsTotal === null) {
        $pkg = $packageCatalog[$packageId] ?? null;
        if (!$pkg) {
            webhook_log('[coins] Package ID inválido: ' . $packageId);
            http_response_code(200);
            echo json_encode(['success' => true, 'reason' => 'Invalid package']);
            return;
        }
        $coinsBase = (int)$pkg['base'];
        $coinsBonus = (int)$pkg['bonus'];
        $coinsTotal = $coinsBase + $coinsBonus;
        $packageName = $pkg['name'];
    } else {
        $packageName = $packageName ?: ($packageCatalog[$packageId]['name'] ?? ('Pacote ' . $packageId));
    }

    if ($coinsTotal <= 0) {
        webhook_log('[coins] Total de moedas inválido para user=' . $userId);
        http_response_code(200);
        echo json_encode(['success' => true, 'reason' => 'Invalid coin total']);
        return;
    }

    // Evitar processar duas vezes o mesmo pagamento (via coin_transactions)
    $dupStmt = $db->prepare('SELECT id FROM coin_transactions WHERE user_id = :user_id AND reference_id = :reference_id AND reference_type = :reference_type LIMIT 1');
    $dupStmt->execute([
        ':user_id' => $userId,
        ':reference_id' => (string)$payment_id,
        ':reference_type' => 'coin_purchase',
    ]);
    if ($dupStmt->fetch()) {
        webhook_log('[coins] Pagamento já processado anteriormente (payment_id=' . $payment_id . ')');
        http_response_code(200);
        echo json_encode(['success' => true, 'reason' => 'Payment already processed']);
        return;
    }

    $db->beginTransaction();

    // Travar pedido para evitar concorrência
    if ($order) {
        $lockStmt = $db->prepare('SELECT * FROM coin_purchase_orders WHERE id = :id FOR UPDATE');
        $lockStmt->execute([':id' => (int)$order['id']]);
        $order = $lockStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($order && $order['status'] === 'approved') {
            $db->rollBack();
            webhook_log('[coins] Pedido já aprovado anteriormente (order_id=' . $order['id'] . ')');
            http_response_code(200);
            echo json_encode(['success' => true, 'reason' => 'Order already approved']);
            return;
        }
    }

    // Garantir que exista um registro do pedido para auditoria
    if (!$order) {
        // Use VALUES() para referenciar valores do INSERT na cláusula ON DUPLICATE KEY
        $insertSql = 'INSERT INTO coin_purchase_orders (
                user_id,
                package_id,
                package_label,
                coins_base,
                coins_bonus,
                coins_total,
                amount,
                currency,
                external_reference,
                preference_id,
                status,
                created_at,
                updated_at
            ) VALUES (
                :user_id, :package_id, :package_label, :coins_base, :coins_bonus, :coins_total,
                :amount, :currency, :external_reference, :preference_id, :status, :created_at, :updated_at
            )
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                package_id = VALUES(package_id),
                package_label = VALUES(package_label),
                coins_base = VALUES(coins_base),
                coins_bonus = VALUES(coins_bonus),
                coins_total = VALUES(coins_total),
                amount = VALUES(amount),
                currency = VALUES(currency),
                preference_id = VALUES(preference_id),
                status = VALUES(status),
                updated_at = VALUES(updated_at)';
        try {
            $insertOrder = $db->prepare($insertSql);
            $orderParams = [
                ':user_id' => $userId,
                ':package_id' => $packageId,
                ':package_label' => $packageName,
                ':coins_base' => $coinsBase,
                ':coins_bonus' => $coinsBonus,
                ':coins_total' => $coinsTotal,
                ':amount' => $amountPaid,
                ':currency' => $currencyId,
                ':external_reference' => $external_reference,
                ':preference_id' => null,
                ':status' => 'pending',
                ':created_at' => $nowTs,
                ':updated_at' => $nowTs,
            ];
            $insertOrder->execute($orderParams);

            $order = [
                'id' => (int)$db->lastInsertId(),
                'coins_base' => $coinsBase,
                'coins_bonus' => $coinsBonus,
                'coins_total' => $coinsTotal,
            ];
        } catch (Throwable $insertErr) {
            webhook_log('[coins] Erro ao inserir pedido: ' . $insertErr->getMessage());
            throw $insertErr;
        }
    }

    // Atualizar saldo
    $balanceBefore = (int)$user['coin_balance'];
    $updateBalance = $db->prepare('UPDATE users SET coin_balance = coin_balance + :total WHERE id = :id');
    try {
        $updateBalance->execute([':total' => $coinsTotal, ':id' => $userId]);
    } catch (Throwable $balErr) {
        webhook_log('[coins] Erro ao atualizar saldo: ' . $balErr->getMessage());
        throw $balErr;
    }

    $balanceStmt = $db->prepare('SELECT coin_balance FROM users WHERE id = :id LIMIT 1');
    try {
        $balanceStmt->execute([':id' => $userId]);
    } catch (Throwable $balReadErr) {
        webhook_log('[coins] Erro ao ler novo saldo: ' . $balReadErr->getMessage());
        throw $balReadErr;
    }
    $balanceRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    $newBalance = (int)($balanceRow['coin_balance'] ?? ($balanceBefore + $coinsTotal));

    $txSql = 'INSERT INTO coin_transactions
        (user_id, amount, type, balance_before, balance_after, reference_id, reference_type, payment_method, external_reference, description, created_at)
        VALUES (:user_id, :amount, :type, :before, :after, :reference_id, :reference_type, :payment_method, :external_reference, :description, :created_at)';
    
    try {
        $txStmt = $db->prepare($txSql);
        $txStmt->execute([
            ':user_id' => $userId,
            ':amount' => $coinsBase,
            ':type' => 'buy',
            ':before' => $balanceBefore,
            ':after' => $balanceBefore + $coinsBase,
            ':reference_id' => (string)$payment_id,
            ':reference_type' => 'coin_purchase',
            ':payment_method' => 'mercado_pago',
            ':external_reference' => $external_reference,
            ':description' => sprintf('Pacote %s (%d moedas) - MP %s', $packageName, $coinsBase, $payment_id),
            ':created_at' => $nowTs,
        ]);
    } catch (Throwable $txErr) {
        webhook_log('[coins] Erro ao inserir transação base: ' . $txErr->getMessage());
        throw $txErr;
    }

    if ($coinsBonus > 0) {
        try {
            $txStmt2 = $db->prepare($txSql);
            $txStmt2->execute([
                ':user_id' => $userId,
                ':amount' => $coinsBonus,
                ':type' => 'bonus',
                ':before' => $balanceBefore + $coinsBase,
                ':after' => $newBalance,
                ':reference_id' => (string)$payment_id,
                ':reference_type' => 'coin_purchase',
                ':payment_method' => 'mercado_pago',
                ':external_reference' => $external_reference,
                ':description' => sprintf('Bônus do pacote %s (+%d moedas)', $packageName, $coinsBonus),
                ':created_at' => $nowTs,
            ]);
        } catch (Throwable $txBonusErr) {
            webhook_log('[coins] Erro ao inserir bônus: ' . $txBonusErr->getMessage());
            throw $txBonusErr;
        }
    }

    // Atualizar pedido como aprovado
    $updateOrder = $db->prepare('UPDATE coin_purchase_orders
        SET status = "approved",
            payment_status = "approved",
            payment_id = :payment_id,
            amount = :amount,
            currency = :currency,
            approved_at = :approved_at,
            updated_at = :updated_at,
            raw_payload = :raw_payload
        WHERE external_reference = :external_reference');
    try {
        $updateOrder->execute([
            ':payment_id' => (string)$payment_id,
            ':amount' => $amountPaid,
            ':currency' => $currencyId,
            ':approved_at' => $nowTs,
            ':updated_at' => $nowTs,
            ':raw_payload' => json_encode($payment, JSON_UNESCAPED_UNICODE),
            ':external_reference' => $external_reference,
        ]);
    } catch (Throwable $updateErr) {
        webhook_log('[coins] Erro ao atualizar pedido como aprovado: ' . $updateErr->getMessage());
        throw $updateErr;
    }

    $db->commit();

    webhook_log(sprintf(
        '[coins] SUCESSO → user=%d pacote=%s base=%d bônus=%d total=%d novo_saldo=%d',
        $userId,
        $packageName,
        $coinsBase,
        $coinsBonus,
        $coinsTotal,
        $newBalance
    ));

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'package_id' => $packageId,
        'package_name' => $packageName,
        'coins_base' => $coinsBase,
        'coins_bonus' => $coinsBonus,
        'coins_total' => $coinsTotal,
        'new_balance' => $newBalance,
        'payment_id' => $payment_id,
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    webhook_log('[coins] EXCEPTION: ' . $e->getMessage());
    webhook_log('[coins] TRACE: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'reason' => 'Server error']);
}
