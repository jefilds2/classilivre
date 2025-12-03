<?php
// Arquivo para limpar a sessão de venda pendente

session_start();

// Validar CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Limpar a sessão
unset($_SESSION['pending_sold_ad']);

http_response_code(200);
echo json_encode(['success' => true]);
