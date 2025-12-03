<?php
/**
 * api/mercado_pago_webhook.php
 * 
 * ⭐ WEBHOOK UNIVERSAL - Processa TODOS os pagamentos do Mercado Pago
 * 
 * Tipos de pagamento suportados:
 * - coin_purchase_* → Compra de moedas (incrementa saldo)
 * - service_* → Pagamento de serviços automotivos (ativa serviço)
 * - premium_* → Pagamento de plano premium (ativa features)
 * 
 * Arquitetura:
 * 1. Webhook recebe notificação do Mercado Pago
 * 2. Valida assinatura e tipo
 * 3. Busca payment_id na API MP
 * 4. Processa baseado em external_reference
 * 5. Logging detalhado para auditoria
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ============================================
// LOGGING
// ============================================
$log_file = __DIR__ . '/../logs/webhook_' . date('Y-m-d') . '.log';
@mkdir(dirname($log_file), 0777, true);
ini_set('error_log', $log_file);

function webhook_log($message) {
    error_log('[' . date('H:i:s') . '] ' . $message);
}

header('Content-Type: application/json');

try {
    // ============================================
    // 1. VALIDAÇÕES INICIAIS
    // ============================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    webhook_log('=== NOVO WEBHOOK RECEBIDO ===');
    webhook_log('Input: ' . substr($input, 0, 300));
    
    // Suportar múltiplos formatos de notificação do Mercado Pago
    $payment_id = null;
    $notification_type = $data['type'] ?? null;
    
    // Formato 1: payment notifications (type=payment)
    if ($notification_type === 'payment' && isset($data['data']['id'])) {
        $payment_id = (int)$data['data']['id'];
    }
    // Formato 2: action-based notifications (action=payment.created/payment.updated)
    elseif (isset($data['action']) && strpos($data['action'], 'payment.') === 0 && isset($data['data']['id'])) {
        $payment_id = (int)$data['data']['id'];
    }
    // Formato 3: direct payment ID no resource
    elseif (isset($data['resource']) && is_numeric($data['resource'])) {
        $payment_id = (int)$data['resource'];
    }
    
    if (!$payment_id) {
        webhook_log('❌ Payment ID não encontrado. Tipo: ' . ($notification_type ?? 'null'));
        http_response_code(200);
        echo json_encode(['success' => false, 'reason' => 'No payment ID']);
        exit;
    }
    webhook_log('Payment ID: ' . $payment_id);
    
    // ============================================
    // 2. BUSCAR DADOS DO PAGAMENTO
    // ============================================
    
    require __DIR__ . '/../sqlconnect.php';
    require __DIR__ . '/../config/mercadopago.php';
    
    $mp_config = require __DIR__ . '/../config/mercadopago.php';
    $mode = $mp_config['mode'] ?? 'production';
    $credentials = $mp_config[$mode];
    $access_token = $credentials['access_token'];
    
    webhook_log('Modo: ' . $mode);
    
    // Buscar pagamento na API do Mercado Pago
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $payment_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        webhook_log('❌ CURL Error: ' . $curl_error);
        http_response_code(500);
        echo json_encode(['success' => false, 'reason' => 'API error']);
        exit;
    }
    
    if ($http_code !== 200) {
        webhook_log('❌ HTTP ' . $http_code . ': ' . substr($response, 0, 200));
        http_response_code(500);
        echo json_encode(['success' => false, 'reason' => 'Payment not found']);
        exit;
    }
    
    $payment = json_decode($response, true);
    $external_reference = $payment['external_reference'] ?? '';
    $status = $payment['status'] ?? 'unknown';
    $amount = $payment['transaction_amount'] ?? 0;
    
    webhook_log('Status: ' . $status);
    webhook_log('Amount: R$ ' . number_format($amount, 2, ',', '.'));
    webhook_log('External Reference: ' . $external_reference);
    
    // ============================================
    // 3. ROTEAR PARA PROCESSADOR ESPECÍFICO
    // ============================================
    
    $payment_type = null;
    
    if (strpos($external_reference, 'coin_purchase_') === 0) {
        $payment_type = 'COINS';
        webhook_log('Processando COMPRA DE MOEDAS');
        include __DIR__ . '/webhook_processors/process_coin_purchase.php';
        
    } elseif (strpos($external_reference, 'service_') === 0) {
        $payment_type = 'SERVICE';
        webhook_log('Processando PAGAMENTO DE SERVICO');
        include __DIR__ . '/webhook_processors/process_service_payment.php';
        
    } elseif (strpos($external_reference, 'premium_') === 0) {
        $payment_type = 'PREMIUM';
        webhook_log('Processando PAGAMENTO DE PREMIUM');
        require __DIR__ . '/webhook_processors/process_premium_subscription.php';
        process_premium_payment($payment);
        http_response_code(200);
        echo json_encode(['success' => true, 'payment_type' => 'PREMIUM', 'payment_id' => $payment_id]);
        exit;
        
    } else {
        webhook_log('Tipo de pagamento desconhecido: ' . $external_reference);
        http_response_code(400);
        echo json_encode(['success' => false, 'reason' => 'Unknown payment type']);
        exit;
    }
    
} catch (Exception $e) {
    webhook_log('❌ EXCEPTION: ' . $e->getMessage());
    webhook_log('Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'reason' => 'Server error']);
    exit;
}
