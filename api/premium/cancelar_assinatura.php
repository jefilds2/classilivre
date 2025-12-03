<?php
/**
 * api/premium/cancelar_assinatura.php
 * Endpoint para cancelar assinatura premium ativa
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../login_system/middleware.php';
require_once __DIR__ . '/../webhook_processors/process_premium_subscription.php';

try {
    // Verificar autenticação
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuário não autenticado', 401);
    }
    
    // Apenas POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido', 405);
    }
    
    $user = current_user();
    $user_id = (int)$user['id'];
    
    // Chamar função de cancelamento
    $result = cancel_premium_subscription($user_id);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
    
    error_log('[api/premium/cancelar_assinatura] Exception: ' . $e->getMessage());
}
