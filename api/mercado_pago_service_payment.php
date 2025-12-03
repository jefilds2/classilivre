<?php
/**
 * api/mercado_pago_service_payment.php
 * 
 * Cria uma preferência de pagamento no Mercado Pago para um serviço automotivo
 * Retorna o init_point (URL de checkout) para o cliente fazer o pagamento
 * 
 * Fluxo:
 * 1. Cliente clica "Pagar Serviço" em anuncios/pagamento.php
 * 2. Este endpoint cria um item no Mercado Pago via API REST
 * 3. Retorna o init_point para abrir o modal/popup
 * 4. Após pagamento confirmado, o webhook é acionado
 * 
 * Usa API REST diretamente (sem SDK Composer)
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../config/mercadopago.php';

header('Content-Type: application/json; charset=utf-8');
require_post();

// Suportar POST form-encoded e JSON
$input = $_POST;
if (empty($_POST) && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

if (!validate_csrf($input['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

// Validar se usuário está bloqueado
if ($user['status'] === 'blocked') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sua conta está bloqueada. Você não pode publicar serviços.']);
    exit;
}

    $serviceId = (int)($input['serviceId'] ?? $input['service_id'] ?? 0);

    if ($serviceId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID do serviço inválido']);
        exit;
    }

    try {
        // Carregar configuração do Mercado Pago
        $mpConfig = include __DIR__ . '/../config/mercadopago.php';
        $mode = $mpConfig['mode'] ?? 'production';
        $credentials = $mpConfig[$mode] ?? [];
        
        if (empty($credentials['access_token'])) {
            throw new Exception('Credenciais do Mercado Pago não configuradas. Verifique config/mercadopago.php');
        }

        // Verificar se serviço pertence ao usuário e está em pending_payment
        $stmt = db()->prepare('SELECT id, name, category, address_city FROM user_service_listings WHERE id = :id AND user_id = :user_id AND status = "pending_payment" LIMIT 1');
        $stmt->execute([':id' => $serviceId, ':user_id' => (int)$user['id']]);
        $service = $stmt->fetch();

        if (!$service) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Serviço não encontrado ou já foi processado']);
            exit;
        }

        // Obter valor do serviço de settings
        $servicePrice = (float)get_setting('service_price', '15.00');

        // Gerar external_reference único
        $externalReference = "service_{$serviceId}_{$user['id']}_" . time();

        // Capturar se é renovação
        $renewalType = (string)($_GET['type'] ?? 'new');    // Preparar dados da preferência para API REST do Mercado Pago
    $confirmUrl = app_url("/servicos/pagamento_confirmando.php?serviceId={$serviceId}&payment_status=approved&type={$renewalType}");
    $preference = [
        "items" => [
            [
                "id" => "service_{$serviceId}",
                "title" => substr((string)$service['name'], 0, 256),
                "description" => "Publicação de serviço automotivo",
                "quantity" => 1,
                "currency_id" => "BRL",
                "unit_price" => $servicePrice
            ]
        ],
        "payer" => [
            "email" => substr((string)$user['email'], 0, 256),
            "name" => substr((string)$user['name'], 0, 256)
        ],
        "external_reference" => $externalReference,
        "back_urls" => [
            "success" => $confirmUrl,
            "failure" => app_url("/servicos/pagamento.php?serviceId={$serviceId}&payment=failure"),
            "pending" => $confirmUrl
        ],
        "return_url" => $confirmUrl,
        "auto_return" => "approved",
        "notification_url" => app_url("/api/mercado_pago_webhook.php"),
        "statement_descriptor" => "CLASSI LIVRE SERVICOS",
        "expires" => false
    ];

    // Fazer requisição HTTP para criar preferência
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $credentials['access_token']
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($preference)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("Erro de conexão: {$curlError}");
    }

    $result = json_decode($response, true);

    if ($httpCode !== 201 && $httpCode !== 200) {
        $errorMsg = $result['message'] ?? $result['error'] ?? "HTTP {$httpCode}";
        throw new Exception("Mercado Pago API Error: {$errorMsg}");
    }

    if (!isset($result['id']) || !isset($result['init_point'])) {
        throw new Exception('Resposta inválida do Mercado Pago: faltam id ou init_point');
    }

    // Armazenar external_reference no BD para futuro rastreamento
    $updateStmt = db()->prepare('UPDATE user_service_listings SET external_reference = :ref, updated_at = NOW() WHERE id = :id');
    $updateStmt->execute([
        ':ref' => $externalReference,
        ':id' => $serviceId
    ]);

    // Log da transação
    record_audit((int)$user['id'], 'service_payment_initiated', 'user_service_listings', $serviceId, [
        'external_reference' => $externalReference,
        'mercado_pago_id' => $result['id'],
        'amount' => $servicePrice,
        'mode' => $mode
    ]);

    // Retornar init_point para checkout
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'init_point' => $result['init_point'],
        'preference_id' => $result['id'],
        'external_reference' => $externalReference,
        'service_id' => $serviceId,
        'amount' => $servicePrice
    ]);
    exit;

} catch (Throwable $e) {
    error_log('[mercado_pago_service_payment] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar pagamento: ' . $e->getMessage()
    ]);
    exit;
}
?>
