<?php
/**
 * anuncios/renovar.php
 * 
 * Endpoint para renovação manual de anúncios
 * Custa 1 moeda = +30 dias de expiração
 * 
 * POST /anuncios/renovar.php
 * Parâmetros:
 *   - id: ID do anúncio
 *   - csrf_token: Token CSRF
 */

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';
require_once __DIR__ . '/../login_system/middleware.php';

require_post();
require_login();
ensure_user_not_blocked();

if (!validate_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    flash_add('error', 'Token de segurança inválido.');
    redirect('/anuncios/meus.php');
}

$user = current_user();
$adId = (int)($_POST['id'] ?? 0);

if ($adId <= 0) {
    http_response_code(400);
    flash_add('error', 'ID do anúncio inválido.');
    redirect('/anuncios/meus.php');
}

// 1. Verificar se anúncio pertence ao usuário
$stmt = db()->prepare('
    SELECT id, title, status, expires_at, user_id 
    FROM ads 
    WHERE id = :id AND user_id = :user_id LIMIT 1
');
$stmt->execute([':id' => $adId, ':user_id' => (int)$user['id']]);
$ad = $stmt->fetch();

if (!$ad) {
    http_response_code(404);
    flash_add('error', 'Anúncio não encontrado ou você não tem permissão para renovar.');
    redirect('/anuncios/meus.php');
}

// 2. Validar saldo do usuário (precisa de pelo menos 1 moeda)
$balanceStmt = db()->prepare('
    SELECT coin_balance FROM users WHERE id = :id LIMIT 1
');
$balanceStmt->execute([':id' => (int)$user['id']]);
$userBalance = $balanceStmt->fetchColumn();

if ((int)$userBalance < 1) {
    http_response_code(402); // Payment Required
    flash_add('error', 'Saldo insuficiente. Você precisa de 1 moeda para renovar um anúncio.');
    redirect('/anuncios/meus.php');
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // 3. Obter saldo atual (para os campos balance_before e balance_after)
    $balanceData = $pdo->prepare('SELECT coin_balance FROM users WHERE id = :id FOR UPDATE');
    $balanceData->execute([':id' => (int)$user['id']]);
    $currentBalance = (int)$balanceData->fetchColumn();
    $balanceAfter = $currentBalance - 1;

    // 4. Debitar 1 moeda do usuário
    $pdo->prepare('
        UPDATE users 
        SET coin_balance = :balance, updated_at = NOW()
        WHERE id = :id
    ')->execute([
        ':balance' => $balanceAfter,
        ':id' => (int)$user['id']
    ]);

    // 5. Registrar transação de moeda (com campos corretos)
    $pdo->prepare('
        INSERT INTO coin_transactions 
        (user_id, type, amount, balance_before, balance_after, reference_id, reference_type, description, created_at)
        VALUES (:user_id, :type, :amount, :balance_before, :balance_after, :reference_id, :reference_type, :description, NOW())
    ')->execute([
        ':user_id' => (int)$user['id'],
        ':type' => 'ad_renewal',
        ':amount' => -1,
        ':balance_before' => $currentBalance,
        ':balance_after' => $balanceAfter,
        ':reference_id' => $adId,
        ':reference_type' => 'ads',
        ':description' => 'Renovação de anúncio: ' . $ad['title']
    ]);

    // 5. Atualizar expires_at (adicionar 30 dias)
    $newExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days', strtotime($ad['expires_at'] ?? 'now')));
    
    $pdo->prepare('
        UPDATE ads 
        SET expires_at = :expires_at,
            last_renewed_at = NOW(),
            status = "approved",
            updated_at = NOW()
        WHERE id = :id
    ')->execute([
        ':expires_at' => $newExpiresAt,
        ':id' => $adId
    ]);

    // 6. Registrar auditoria
    record_audit((int)$user['id'], 'ad_renewed', 'ads', $adId, [
        'title' => $ad['title'],
        'old_expires_at' => $ad['expires_at'],
        'new_expires_at' => $newExpiresAt,
        'cost_coins' => 1
    ]);

    $pdo->commit();

    flash_add('success', sprintf(
        'Anúncio "%s" renovado com sucesso! Expira em: %s',
        $ad['title'],
        date('d/m/Y', strtotime($newExpiresAt))
    ));

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('[renovar.php] Erro ao renovar anúncio #' . $adId . ': ' . $e->getMessage());
    flash_add('error', 'Erro ao renovar anúncio. Tente novamente.');
}

redirect('/anuncios/meus.php');
?>
