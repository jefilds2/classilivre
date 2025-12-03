<?php
/**
 * API: Gerenciar limite de sessões simultâneas (Admin)
 * Endpoint: POST /api/admin/set_user_concurrent_sessions.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

// Validar acesso admin
$user = current_user();
if (empty($user['is_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Validar CSRF
if (!validate_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF inválido']);
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);
$maxSessions = (int)($_POST['max_sessions'] ?? 1);

// Validar entrada
if ($userId <= 0 || $maxSessions < 1 || $maxSessions > 10) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros inválidos. max_sessions deve estar entre 1 e 10.']);
    exit;
}

try {
    $pdo = db();
    
    // Verificar se usuário existe
    $check = $pdo->prepare('SELECT id FROM users WHERE id = :user_id LIMIT 1');
    $check->execute([':user_id' => $userId]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit;
    }
    
    // Atualizar limite
    $update = $pdo->prepare('
        UPDATE users 
        SET max_concurrent_sessions = :max_sessions, updated_at = NOW()
        WHERE id = :user_id
    ');
    $update->execute([
        ':max_sessions' => $maxSessions,
        ':user_id' => $userId,
    ]);
    
    // Log de auditoria
    record_audit(
        (int)current_user()['id'],
        'user_concurrent_sessions_updated',
        'users',
        $userId,
        ['max_sessions' => $maxSessions]
    );
    
    echo json_encode([
        'success' => true,
        'message' => "Limite atualizado para {$maxSessions} sessões",
        'user_id' => $userId,
        'max_sessions' => $maxSessions,
    ]);
    
} catch (Throwable $e) {
    error_log('[API] Erro ao atualizar limite de sessões: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
    exit;
}
?>
