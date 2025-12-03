<?php
/**
 * api/premium/criar_preferencia.php
 * Cria preferência de pagamento no Mercado Pago para Premium
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../login_system/middleware.php';

try {
    // Verificar autenticação
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuário não autenticado', 401);
    }
    
    $user = current_user();
    $user_id = (int)$user['id'];
    
    // Validar entrada
    if (empty($_POST['period']) || empty($_POST['price'])) {
        throw new Exception('Período e preço são obrigatórios', 400);
    }
    
    $period = $_POST['period']; // M, T, ou S
    $price = (float)$_POST['price'];
    
    if (!in_array($period, ['M', 'T', 'S'])) {
        throw new Exception('Período inválido', 400);
    }
    
    if ($price <= 0) {
        throw new Exception('Preço inválido', 400);
    }
    
    // Carregar config do Mercado Pago
    $mp_config = require_once __DIR__ . '/../../config/mercadopago.php';
    $mode = $mp_config['mode'] ?? 'production';
    $credentials = $mp_config[$mode] ?? [];
    $access_token = $credentials['access_token'] ?? '';
    
    if (empty($access_token)) {
        throw new Exception('Configuração do Mercado Pago não encontrada', 500);
    }
    
    // Mapear período para descrição e duração
    $periodMap = [
        'M' => ['nome' => 'Mensal', 'meses' => 1],
        'T' => ['nome' => 'Trimestral', 'meses' => 3],
        'S' => ['nome' => 'Semestral', 'meses' => 6],
    ];

    $periodInfo = $periodMap[$period];

    // Mapear período para plan_id e duração em dias
    $planMap = ['M' => 1, 'T' => 2, 'S' => 3];
    $durationMap = [
        'M' => 30,  // Mensal
        'T' => 90,  // Trimestral (3 meses)
        'S' => 180, // Semestral (6 meses)
    ];

    $planId = $planMap[$period] ?? 1;
    $durationDays = $durationMap[$period] ?? 30;

    // Remover tentativas pendentes antigas sem pagamento
    try {
        $cleanup = db()->prepare('DELETE FROM premium_subscriptions WHERE user_id = :user_id AND (payment_status IS NULL OR payment_status = "pending" OR payment_status = "") AND (payment_id IS NULL OR payment_id = 0)');
        $cleanup->execute([':user_id' => $user_id]);
    } catch (Throwable $cleanupError) {
        error_log('[premium/criar_preferencia] Falha ao limpar pendências antigas: ' . $cleanupError->getMessage());
    }

    // Gerar external_reference único com dados do plano
    $external_reference = sprintf('premium_%d_%d_%d_%d', $user_id, $planId, $durationDays, time());

    // Preparar dados para Mercado Pago
    $preference_data = [
        'items' => [
            [
                'id' => 'premium_' . $period,
                'title' => 'Premium ' . $periodInfo['nome'] . ' - Classi Livre Autos',
                'description' => 'Acesso Premium por ' . $periodInfo['meses'] . ' mes(es) com todos os benefícios',
                'quantity' => 1,
                'unit_price' => $price,
                'currency_id' => 'BRL',
                'picture_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/assets/logo',
            ]
        ],
        'external_reference' => $external_reference,
        'payer' => [
            'email' => $user['email'] ?? '',
            'name' => $user['full_name'] ?? $user['username'] ?? 'User',
        ],
        'back_urls' => [
            'success' => 'https://' . $_SERVER['HTTP_HOST'] . '/premium/sucesso.php',
            'pending' => 'https://' . $_SERVER['HTTP_HOST'] . '/premium/pendente.php',
            'failure' => 'https://' . $_SERVER['HTTP_HOST'] . '/premium/falha.php',
        ],
        'auto_return' => 'approved',
        'notification_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/mercado_pago_webhook.php',
        'statement_descriptor' => 'CLASSI LIVRE PREMIUM',
        'expires' => false,
        'metadata' => [
            'user_id' => $user_id,
            'plan_id' => $planId,
            'duration_days' => $durationDays,
            'price' => $price,
            'period' => $period,
        ],
    ];

// Fazer requisição para Mercado Pago
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($preference_data),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 10,
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        throw new Exception('Erro ao conectar com Mercado Pago: ' . $curl_error, 500);
    }
    
    $result = json_decode($response, true);
    
    if ($http_code !== 201) {
        error_log('[premium/criar_preferencia] MP Error: ' . $response);
        throw new Exception('Erro ao criar preferência no Mercado Pago: ' . ($result['message'] ?? 'Erro desconhecido'), 500);
    }
    
    if (empty($result['id']) || empty($result['init_point'])) {
        throw new Exception('Resposta inválida do Mercado Pago', 500);
    }
    
    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'init_point' => $result['init_point'],
        'preference_id' => $result['id'],
        'external_reference' => $external_reference,
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
    
    error_log('[api/premium/criar_preferencia] Exception: ' . $e->getMessage());
}
