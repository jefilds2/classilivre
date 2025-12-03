<?php
/**
 * api/webhook_processors/process_service_payment.php
 * 
 * Processador de PAGAMENTO DE SERVIÇOS AUTOMOTIVOS
 * Chamado pelo webhook universal quando external_reference começa com 'service_'
 * 
 * Responsabilidades:
 * - Validar status do pagamento
 * - Extrair service_id da external_reference
 * - Atualizar payment_status para 'paid'
 * - Registrar pagamento
 */

// Variáveis disponibilizadas pelo webhook.php
// $payment - Array com dados do pagamento (já verificado HTTP 200)
// $payment_id - ID do pagamento
// $external_reference - Referência externa
// $status - Status do pagamento
// $amount - Valor do pagamento

try {
    // ============================================
    // 1. VALIDAR STATUS
    // ============================================
    
    if ($payment['status'] !== 'approved') {
        webhook_log('Status nao aprovado: ' . $status);
        http_response_code(200);
        echo json_encode(['success' => true, 'status' => $status]);
        exit;
    }
    
    // ============================================
    // 2. EXTRAIR SERVICE_ID
    // ============================================
    
    // Formato: service_{service_id}
    $parts = explode('_', $external_reference);
    if (count($parts) < 2) {
        webhook_log('External_reference invalido: ' . $external_reference);
        http_response_code(200);
        echo json_encode(['success' => true, 'reason' => 'Invalid reference format']);
        exit;
    }
    
    $service_id = (int)$parts[1];
    webhook_log('Service ID: ' . $service_id);
    
    // ============================================
    // 3. BUSCAR SERVIÇO
    // ============================================
    
    $sql_service = "SELECT id, user_id, external_reference, payment_status FROM user_service_listings WHERE id = ?";
    $stmt = db()->prepare($sql_service);
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();
    
    if (!$service) {
        webhook_log('Servico nao encontrado: ' . $service_id);
        http_response_code(200);
        echo json_encode(['success' => true, 'reason' => 'Service not found']);
        exit;
    }
    
    webhook_log('User ID: ' . $service['user_id']);
    webhook_log('Current payment_status: ' . $service['payment_status']);
    
    // ============================================
    // 4. VERIFICAR SE JÁ FOI PROCESSADO
    // ============================================
    
    if ($service['payment_status'] === 'paid') {
        webhook_log('Pagamento ja foi marcado como paid');
        http_response_code(200);
        echo json_encode(['success' => true, 'reason' => 'Already paid']);
        exit;
    }
    
    // ============================================
    // 5. ATUALIZAR STATUS DO PAGAMENTO
    // ============================================
    
    try {
        $payment_amount = (float)$payment['transaction_amount'];
        $payer_email = $payment['payer']['email'] ?? '';
        
        // Obter duração padrão dos serviços (em dias)
        $serviceDurationDays = (int)get_setting('services_free_duration_days', '30');
        if ($serviceDurationDays <= 0) {
            $serviceDurationDays = 30;
        }
        
        // Atualizar payment_status para 'paid' E ativar o serviço
        // Também adiciona dias de expiração e publica
        $sql_update = "UPDATE user_service_listings 
                       SET payment_status = 'paid', 
                           status = 'active',
                           published_at = CASE WHEN published_at IS NULL THEN NOW() ELSE published_at END,
                           expires_at = DATE_ADD(NOW(), INTERVAL ? DAY),
                           last_renewed_at = NOW(),
                           payment_method = 'mercado_pago',
                           updated_at = NOW()
                       WHERE id = ?";
        $stmt = db()->prepare($sql_update);
        $stmt->execute([$serviceDurationDays, $service_id]);
        
        webhook_log('SUCESSO! Servico ' . $service_id . ' marcado como PAID e ACTIVE');
        webhook_log('Valor: R$ ' . number_format($payment_amount, 2, ',', '.'));
        webhook_log('Expiração: +' . $serviceDurationDays . ' dias a partir de agora');
        
        // ============================================
        // 6. REGISTRAR TRANSAÇÃO NA TABELA coin_transactions
        // ============================================
        
        try {
            $txSql = 'INSERT INTO coin_transactions 
                      (user_id, type, amount, balance_before, balance_after, reference_id, reference_type, 
                       payment_method, external_reference, payment_id, payment_status, description, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
            
            // Obter saldo atual
            $balanceStmt = db()->prepare('SELECT coin_balance FROM users WHERE id = ?');
            $balanceStmt->execute([$service['user_id']]);
            $balanceRow = $balanceStmt->fetch();
            $balanceBefore = (int)($balanceRow['coin_balance'] ?? 0);
            $balanceAfter = $balanceBefore; // Serviços não alteram saldo de moedas
            
            $txStmt = db()->prepare($txSql);
            $txStmt->execute([
                $service['user_id'],
                'debit',  // tipo: debit (gasto com serviço)
                (int)round($payment_amount * 100),  // valor em centavos (0.01 = 1 centavo)
                $balanceBefore,
                $balanceAfter,
                $service_id,
                'user_service_listings',  // reference_type
                'mercado_pago',
                $external_reference,
                $payment_id,
                'approved',
                'Publicação de serviço automotivo - R$ ' . number_format($payment_amount, 2, ',', '.')
            ]);
            
            webhook_log('Transacao registrada na tabela coin_transactions');
            webhook_log('Valor em centavos: ' . round($payment_amount * 100));
        } catch (Exception $txErr) {
            webhook_log('Aviso: Erro ao registrar transacao: ' . $txErr->getMessage());
            // Não quebra o fluxo principal - o importante é que o serviço foi ativado
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'service_id' => $service_id,
            'payment_id' => $payment_id,
            'amount' => $payment_amount,
            'status' => 'paid',
            'expires_in_days' => $serviceDurationDays,
        ]);
        
    } catch (Exception $e) {
        webhook_log('DB Error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'reason' => 'Database error']);
        exit;
    }
    
} catch (Exception $e) {
    webhook_log('EXCEPTION: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'reason' => 'Server error']);
    exit;
}
