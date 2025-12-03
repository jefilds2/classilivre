<?php
/**
 * api/check_service_payment_status.php
 * Verifica o status atual do pagamento de um serviço
 * Usado por pagamento_confirmando.php para fazer polling
 * 
 * Retorna JSON com:
 * - is_paid: boolean
 * - is_active: boolean
 * - status: string
 * - payment_status: string
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json; charset=utf-8');

require_login();

$user = current_user();
$serviceId = (int)($_GET['serviceId'] ?? $_GET['service_id'] ?? 0);
$transactionType = $_GET['type'] ?? 'new'; // 'new' ou 'renewal'

if ($serviceId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID do serviço inválido']);
    exit;
}

try {
    // Verificar que o serviço pertence ao usuário
    $stmt = db()->prepare('
        SELECT 
            id, 
            status, 
            payment_status, 
            published_at,
            expires_at,
            name
        FROM user_service_listings 
        WHERE id = ? AND user_id = ? 
        LIMIT 1
    ');
    $stmt->execute([$serviceId, (int)$user['id']]);
    $service = $stmt->fetch();

    if (!$service) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'error' => 'Serviço não encontrado'
        ]);
        exit;
    }

    $isPaid = ($service['payment_status'] === 'paid');
    $isActive = ($service['status'] === 'active');
    $hasPublishedAt = !empty($service['published_at']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'is_paid' => $isPaid,
        'is_active' => $isActive,
        'has_published_at' => $hasPublishedAt,
        'status' => $service['status'],
        'payment_status' => $service['payment_status'],
        'service_name' => $service['name'],
        'expires_at' => $service['expires_at'],
        'transaction_type' => $transactionType
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao verificar status: ' . $e->getMessage()
    ]);
}

?>
