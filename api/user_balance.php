<?php
// api/user_balance.php - DESCONTINUADO
// Este arquivo foi descontinuado após remover o sistema antigo de tokens
// O novo sistema de moedas Clivre será implementado em um novo arquivo: api/coin_balance.php

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';
require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json');

try {
    $user = current_user();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Não autenticado', 'message' => 'Sistema de saldo descontinuado. Use /api/coin_balance.php para o novo sistema de moedas.']);
        exit;
    }

    // Retornar erro informativo
    http_response_code(410); // Gone
    echo json_encode([
        'error' => 'Sistema de saldo descontinuado',
        'message' => 'O sistema de tokens antigo foi removido. Use /api/coin_balance.php para consultar seu saldo de moedas Clivre.',
        'balance' => 0,
        'has_balance' => false
    ]);
    exit;

} catch (\Exception $e) {
    error_log('[user_balance_deprecated] Erro: ' . $e->getMessage());
    http_response_code(410);
    echo json_encode([
        'error' => 'Sistema descontinuado',
        'message' => 'Use /api/coin_balance.php para o novo sistema'
    ]);
    exit;
}
?>
