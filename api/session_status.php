<?php
// api/session_status.php - Retorna status de inatividade da sessão

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../login_system/session_manager.php';

header('Content-Type: application/json');

$user = current_user();

if (!$user || empty($_SESSION['session_token'])) {
    http_response_code(401);
    echo json_encode([
        'logged_in' => false,
        'message' => 'Não autenticado'
    ]);
    exit;
}

try {
    $remaining = get_session_inactivity_remaining(
        db(), 
        (int)$user['id'], 
        $_SESSION['session_token']
    );
    
    if ($remaining === null) {
        // Sessão inválida - verificar o motivo
        $stmt = db()->prepare('
            SELECT is_active, expires_at, 
                   TIMESTAMPDIFF(SECOND, last_activity_at, NOW()) AS inactivity_seconds
            FROM user_sessions 
            WHERE user_id = :user_id AND session_token = :token
            LIMIT 1
        ');
        $stmt->execute([
            ':user_id' => (int)$user['id'],
            ':token' => $_SESSION['session_token'],
        ]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $reason = 'unknown';
        if (!$session) {
            $reason = 'invalidated'; // Não encontrada = foi invalidada por novo login
        } elseif (!$session['is_active']) {
            $reason = 'invalidated'; // Marcada como inativa
        } elseif ($session['expires_at'] && strtotime($session['expires_at']) < time()) {
            $reason = 'max_lifetime_exceeded'; // 30 dias passou
        } elseif ((int)$session['inactivity_seconds'] > 1800) {
            $reason = 'inactivity_timeout'; // 30 min inatividade
        }
        
        http_response_code(401);
        echo json_encode([
            'logged_in' => false,
            'message' => 'Sessão inválida',
            'reason' => $reason
        ]);
        exit;
    }
    
    // Calcular minutos e segundos para exibição
    $minutes = (int)($remaining / 60);
    $seconds = $remaining % 60;
    
    echo json_encode([
        'logged_in' => true,
        'remaining_seconds' => $remaining,
        'remaining_formatted' => sprintf('%d:%02d', $minutes, $seconds),
        'timeout_warning_threshold' => 5 * 60, // Avisar com 5 minutos
        'should_warn' => $remaining <= (5 * 60),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'logged_in' => false,
        'message' => 'Erro ao verificar sessão'
    ]);
}
