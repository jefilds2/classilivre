<?php
// api/coin_transactions.php - Retorna histórico de transações de moedas do usuário

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';
require_once __DIR__ . '/../login_system/middleware.php';

require_login();

header('Content-Type: application/json');

try {
    $user_id = (int)current_user()['id'];
    $limit = (int)($_GET['limit'] ?? 20);
    $limit = min($limit, 100); // Máximo 100 para segurança
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Validar offset
    if ($offset < 0) {
        $offset = 0;
    }
    
    // Query para contar total de transações
    $countQuery = "SELECT COUNT(*) as total FROM coin_transactions WHERE user_id = :user_id";
    $countStmt = db()->prepare($countQuery);
    $countStmt->execute([':user_id' => $user_id]);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = (int)$countResult['total'];
    
    // Query para buscar transações
    $query = "SELECT 
        id,
        amount,
        type,
        description,
        balance_after,
        created_at
    FROM coin_transactions
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset";
    
    $stmt = db()->prepare($query);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar resposta
    $response = [
        'success' => true,
        'count' => count($transactions),
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + $limit) < $total,
        'current_balance' => get_user_coin_balance($user_id),
        'transactions' => $transactions
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar transações de moedas',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
