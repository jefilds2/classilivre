<?php
/**
 * api/coin_balance.php
 * 
 * Retorna o saldo de moedas CLivre Medal do usuário atual
 * Usado pelo formulário de criar anúncio (tab 7) para atualizar saldo em tempo real
 * 
 * Resposta:
 * {
 *   "balance": 5,
 *   "user_id": 123,
 *   "success": true
 * }
 */

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';
require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json');

try {
    $user = current_user();
    if (!$user || !isset($user['id'])) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Não autenticado',
            'success' => false
        ]);
        exit;
    }

    $userId = (int)$user['id'];
    
    // ✅ Sempre buscar do BD (não usar cache) para garantir valor atualizado
    $stmt = db()->prepare('SELECT coin_balance FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $userRow = $stmt->fetch();
    $balance = (int)($userRow['coin_balance'] ?? 0);
    
    echo json_encode([
        'balance' => $balance,
        'user_id' => $userId,
        'success' => true
    ]);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno',
        'message' => $e->getMessage(),
        'success' => false
    ]);
    exit;
}
?>
