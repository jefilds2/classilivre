<?php
// anuncios/confirmar_publicacao.php - Confirma publicação gratuita de anúncio

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json');
require_post();

if (!validate_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$adId = (int)($_POST['ad_id'] ?? 0);

if ($adId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

// Verificar se anúncio pertence ao usuário e está em draft
$stmt = db()->prepare('SELECT * FROM ads WHERE id = :id AND user_id = :user_id AND status = "draft" LIMIT 1');
$stmt->execute([':id' => $adId, ':user_id' => (int)$user['id']]);
$ad = $stmt->fetch();

if (!$ad) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Anúncio não encontrado ou já processado']);
    exit;
}

try {
    // Iniciar transação
    db()->beginTransaction();

    // Atualizar anúncio para pending
    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400)); // 30 dias
    
    // Verificar se é free for all ou se precisa de moedas
    $isFreeForAll = (int)get_setting('ads_free_for_all', '0') === 1;
    
    if ($isFreeForAll) {
        // Gratuito para todos
        $paymentStatus = 'free';
        $paymentAmount = 0.00;
    } else {
        // Será cobrado via moedas - por enquanto, marcar como pendente
        // TODO: Implementar desconto de moedas aqui
        $paymentStatus = 'pending_coins';
        $paymentAmount = 0.00;
    }
    
    $update = db()->prepare('UPDATE ads SET
        status = "pending",
        payment_status = :payment_status,
        payment_amount = :payment_amount,
        paid_at = :paid_at,
        expires_at = :expires_at,
        updated_at = :updated_at
        WHERE id = :id');
    $update->execute([
        ':payment_status' => $paymentStatus,
        ':payment_amount' => $paymentAmount,
        ':paid_at' => $isFreeForAll ? date('Y-m-d H:i:s') : null,
        ':expires_at' => $expiresAt,
        ':updated_at' => now(),
        ':id' => $adId
    ]);

    // Commit
    db()->commit();

    record_audit((int)$user['id'], 'ad_published', 'ads', $adId, [
        'title' => $ad['title'],
        'expires_at' => $expiresAt,
        'payment_status' => $paymentStatus,
        'amount' => $paymentAmount
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Anúncio publicado e enviado para moderação!',
        'redirect' => '/anuncios/meus.php'
    ]);

} catch (Throwable $e) {
    db()->rollBack();
    error_log('Ad publish error: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao publicar anúncio: ' . $e->getMessage()]);
}
?>
