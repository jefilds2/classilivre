<?php
/**
 * api/toggle_auto_renewal.php
 * 
 * Endpoint AJAX para ativar/desativar auto-renovação de anúncios
 * 
 * POST /api/toggle_auto_renewal.php
 * Parâmetros:
 *   - ad_id: ID do anúncio
 *   - enabled: 1 ou 0
 *   - csrf_token: Token CSRF
 */

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';
require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json');

try {
    // Validar que é POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Validar login
    if (!current_user()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    // Validar CSRF
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    $user = current_user();
    $adId = (int)($_POST['ad_id'] ?? 0);
    $enabled = (int)($_POST['enabled'] ?? 0);
    
    if ($adId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid ad_id']);
        exit;
    }
    
    // Verificar se anúncio pertence ao usuário
    $stmt = db()->prepare('
        SELECT id, user_id FROM ads WHERE id = :id AND user_id = :user_id LIMIT 1
    ');
    $stmt->execute([':id' => $adId, ':user_id' => (int)$user['id']]);
    $ad = $stmt->fetch();
    
    if (!$ad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ad not found or not owned by you']);
        exit;
    }
    
    // Atualizar auto_renewal_enabled
    $updateStmt = db()->prepare('
        UPDATE ads 
        SET auto_renewal_enabled = :enabled, updated_at = NOW()
        WHERE id = :id
    ');
    $updateStmt->execute([
        ':enabled' => $enabled,
        ':id' => $adId
    ]);
    
    // Registrar auditoria
    record_audit((int)$user['id'], 'ad_auto_renewal_toggle', 'ads', $adId, [
        'auto_renewal_enabled' => $enabled,
        'action' => $enabled ? 'enabled' : 'disabled'
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'auto_renewal_enabled' => $enabled,
        'message' => 'Preferência de auto-renovação atualizada com sucesso'
    ]);
    
} catch (Throwable $e) {
    error_log('[toggle_auto_renewal.php] Erro: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>
