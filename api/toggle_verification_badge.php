<?php
/**
 * API para toggle de badge de verificação
 * POST /api/toggle_verification_badge.php
 * 
 * Parâmetros:
 * - ad_id (int): ID do anúncio
 * - action: 'activate' ou 'deactivate'
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

// Apenas admin
require_admin();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $adId = (int)($_POST['ad_id'] ?? $_GET['ad_id'] ?? 0);
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if (!$adId) {
        throw new Exception('ID do anúncio inválido');
    }
    
    if (!in_array($action, ['activate', 'deactivate'])) {
        throw new Exception('Ação inválida');
    }
    
    // Validar CSRF
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        throw new Exception('Token CSRF inválido');
    }
    
    // Buscar anúncio
    $stmt = db()->prepare('SELECT id, title, user_id, verified_badge FROM ads WHERE id = :id');
    $stmt->execute([':id' => $adId]);
    $ad = $stmt->fetch();
    
    if (!$ad) {
        throw new Exception('Anúncio não encontrado');
    }
    
    $currentStatus = (int)$ad['verified_badge'];
    
    if ($action === 'activate') {
        if ($currentStatus === 1) {
            throw new Exception('Anúncio já possui badge de verificação ativada');
        }
        
        // Verificar se há documento
        $docStmt = db()->prepare('SELECT verification_document_path FROM ads WHERE id = :id');
        $docStmt->execute([':id' => $adId]);
        $docAd = $docStmt->fetch();
        
        if (empty($docAd['verification_document_path'])) {
            throw new Exception('Nenhum documento de verificação foi anexado');
        }
        
        // Ativar badge
        $update = db()->prepare('UPDATE ads SET verified_badge = 1, verification_reviewed_at = :reviewed_at, verification_reviewed_by = :reviewed_by WHERE id = :id');
        $update->execute([
            ':reviewed_at' => now(),
            ':reviewed_by' => (int)current_user()['id'],
            ':id' => $adId,
        ]);
        
        record_audit((int)current_user()['id'], 'ad_verification_activated', 'ads', $adId, ['title' => $ad['title']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Badge de verificação ativada com sucesso!',
            'new_status' => 1,
            'badge_text' => '✓ Verificado',
            'new_action' => 'deactivate',
            'new_button_text' => '✗ Remover Verificação',
            'new_button_class' => 'danger'
        ]);
        
    } elseif ($action === 'deactivate') {
        if ($currentStatus === 0) {
            throw new Exception('Anúncio já não possui badge de verificação');
        }
        
        // Desativar badge
        $update = db()->prepare('UPDATE ads SET verified_badge = 0, verification_reviewed_at = NULL, verification_reviewed_by = NULL WHERE id = :id');
        $update->execute([':id' => $adId]);
        
        record_audit((int)current_user()['id'], 'ad_verification_deactivated', 'ads', $adId, ['title' => $ad['title']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Badge de verificação removida com sucesso!',
            'new_status' => 0,
            'badge_text' => '○ Não Verificado',
            'new_action' => 'activate',
            'new_button_text' => '✓ Ativar Verificado',
            'new_button_class' => 'success'
        ]);
    }
    
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
