<?php
// admin/anuncios_pendentes.php - Fila de moderacao com aprovacao ou rejeicao de anuncios.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$pageTitle = 'Anuncios pendentes - Administracao';
$activeNav = 'pending-ads';
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalido.';
    } else {
        $adId = (int)($_POST['ad_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        $stmt = db()->prepare('SELECT ads.*, users.name AS owner_name, users.email AS email FROM ads JOIN users ON users.id = ads.user_id WHERE ads.id = :id LIMIT 1');
        $stmt->execute([':id' => $adId]);
        $ad = $stmt->fetch();

        if (!$ad) {
            $errors[] = 'Anuncio nao encontrado.';
        } else {
            if ($action === 'activate_verification') {
                // Ativar badge de verificado
                if (empty($ad['verification_document_path'])) {
                    $errors[] = 'O anúncio deve ter um documento de verificação enviado para ser marcado como verificado.';
                } else {
                    $update = db()->prepare('UPDATE ads SET verified_badge = 1, verification_reviewed_at = :reviewed_at, verification_reviewed_by = :reviewed_by WHERE id = :id');
                    $update->execute([
                        ':reviewed_at' => now(),
                        ':reviewed_by' => (int)current_user()['id'],
                        ':id' => $adId,
                    ]);
                    record_audit((int)current_user()['id'], 'ad_verification_activated', 'ads', $adId, ['title' => $ad['title']]);
                    $success = 'Badge de verificação ativada com sucesso!';
                }
            } elseif ($action === 'approve') {
                // TODOS os anúncios têm duração de 30 dias (premium ou gratuito)
                $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
                
                $update = db()->prepare('UPDATE ads SET status = "approved", approved_at = :approved_at, reason_rejected = NULL, updated_at = :updated_at, expires_at = :expires_at, payment_status = "paid" WHERE id = :id');
                $update->execute([
                    ':approved_at' => now(),
                    ':updated_at' => now(),
                    ':expires_at' => $expiresAt,
                    ':id' => $adId,
                ]);
                
                // Deletar documento de verificação após aprovação (se existir)
                if (!empty($ad['verification_document_path'])) {
                    if (!function_exists('delete_media_file')) {
                        require_once __DIR__ . '/../anuncios/upload_handler.php';
                    }
                    delete_media_file($ad['verification_document_path']);
                    // Limpar o path do banco de dados
                    $clearDocStmt = db()->prepare('UPDATE ads SET verification_document_path = NULL WHERE id = :id');
                    $clearDocStmt->execute([':id' => $adId]);
                }
                
                record_audit((int)current_user()['id'], 'ad_approved', 'ads', $adId, ['title' => $ad['title']]);
                // [EMAIL][ad_status][approved]
                if (!function_exists('mail_enqueue')) {
                    $__paths = [__DIR__.'/../api/helpers_mail.php', __DIR__.'/../../api/helpers_mail.php'];
                    foreach ($__paths as $__p) { if (file_exists($__p)) { require_once $__p; break; } }
                }
                try {
                    $__pdo = function_exists('db') ? db() : (isset($pdo) ? $pdo : null);
                    if ($__pdo instanceof PDO && function_exists('mail_enqueue')) {
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host   = $_SERVER['HTTP_HOST'] ?? 'classilivreautos.com';
                        $publicUrl = $scheme.'://'.$host.ad_public_url($ad);
                        mail_enqueue(
                            $__pdo,
                            (string)$ad['email'],
                            'Seu anúncio foi aprovado - Classi Livre Autos',
                            'ad_status',
                            [
                                'nome'   => (string)($ad['owner_name'] ?? ''),
                                'titulo' => (string)$ad['title'],
                                'status' => 'aprovado',
                                'motivo' => null,
                                'url'    => $publicUrl,
                            ],
                            (string)($ad['owner_name'] ?? '')
                        );
                    }
                } catch (Throwable $e) { error_log('[MAIL][ad_approved] '.$e->getMessage()); }
                
                // F11: Verificar buscas salvas que combinam com este anúncio
                try {
                    if (!function_exists('mail_enqueue')) {
                        $__paths = [__DIR__.'/../api/helpers_mail.php', __DIR__.'/../../api/helpers_mail.php'];
                        foreach ($__paths as $__p) { if (file_exists($__p)) { require_once $__p; break; } }
                    }
                    
                    $pdo = db();
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host   = $_SERVER['HTTP_HOST'] ?? 'classilivreautos.com';
                    
                    // Buscar todas as buscas salvas
                    $savedSearchStmt = $pdo->query('SELECT id, user_id, search_params FROM saved_searches WHERE notify = 1');
                    $savedSearches = $savedSearchStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($savedSearches as $search) {
                        $filters = json_decode($search['search_params'], true);
                        if (!is_array($filters)) continue;
                        
                        // Verificar se o anúncio combina com os filtros
                        $matches = true;
                        
                        // Filtro: brand (marca)
                        if (!empty($filters['brand'])) {
                            if (stripos($ad['brand'], $filters['brand']) === false) {
                                $matches = false;
                            }
                        }
                        
                        // Filtro: model (modelo)
                        if ($matches && !empty($filters['model'])) {
                            if (stripos($ad['model'], $filters['model']) === false) {
                                $matches = false;
                            }
                        }
                        
                        // Filtro: city (cidade)
                        if ($matches && !empty($filters['city'])) {
                            $filterCity = trim($filters['city']);
                            // Pode estar em "Cidade/Estado" ou "Cidade"
                            $parts = preg_split('/[,\s-]+/', $filterCity);
                            $cityMatch = false;
                            foreach ($parts as $part) {
                                $part = trim($part);
                                if (!empty($part) && stripos($ad['city'], $part) !== false) {
                                    $cityMatch = true;
                                    break;
                                }
                            }
                            if (!$cityMatch && stripos($filterCity, 'minas gerais') === false && stripos($filterCity, 'mg') === false) {
                                $matches = false;
                            }
                        }
                        
                        // Filtro: price_min e price_max
                        if ($matches && (isset($filters['price_min']) || isset($filters['price_max']))) {
                            $minPrice = isset($filters['price_min']) ? (int)preg_replace('/\D/', '', $filters['price_min']) : 0;
                            $maxPrice = isset($filters['price_max']) ? (int)preg_replace('/\D/', '', $filters['price_max']) : PHP_INT_MAX;
                            $adPrice = (int)$ad['price'];
                            
                            if ($adPrice < $minPrice || $adPrice > $maxPrice) {
                                $matches = false;
                            }
                        }
                        
                        // Filtro: year_min e year_max
                        if ($matches && (isset($filters['year_min']) || isset($filters['year_max']))) {
                            $minYear = isset($filters['year_min']) ? (int)preg_replace('/\D/', '', $filters['year_min']) : 0;
                            $maxYear = isset($filters['year_max']) ? (int)preg_replace('/\D/', '', $filters['year_max']) : PHP_INT_MAX;
                            $adYear = (int)$ad['manufacture_year'];
                            
                            if ($adYear < $minYear || $adYear > $maxYear) {
                                $matches = false;
                            }
                        }
                        
                        // Filtro: transmission
                        if ($matches && !empty($filters['transmission'])) {
                            if (stripos($ad['transmission'], $filters['transmission']) === false) {
                                $matches = false;
                            }
                        }
                        
                        // Filtro: fuel_type
                        if ($matches && !empty($filters['fuel_type'])) {
                            if (stripos($ad['fuel_type'], $filters['fuel_type']) === false) {
                                $matches = false;
                            }
                        }
                        
                        // Se combinou, enfileirar email para o usuário
                        if ($matches) {
                            try {
                                // Verificar se já foi notificado sobre este anúncio
                                $notifCheck = $pdo->prepare('SELECT id FROM search_notifications WHERE saved_search_id = ? AND ad_id = ?');
                                $notifCheck->execute([$search['id'], $adId]);
                                if ($notifCheck->rowCount() === 0) {
                                    // Registrar notificação
                                    $notifInsert = $pdo->prepare('INSERT INTO search_notifications (saved_search_id, ad_id, notified_at) VALUES (?, ?, NOW())');
                                    $notifInsert->execute([$search['id'], $adId]);
                                    
                                    // Enfileirar email
                                    $userStmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
                                    $userStmt->execute([$search['user_id']]);
                                    $targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($targetUser && function_exists('mail_enqueue')) {
                                        $publicUrl = $scheme.'://'.$host.ad_public_url($ad);
                                        mail_enqueue(
                                            $pdo,
                                            (string)$targetUser['email'],
                                            'Novo anúncio encontrado para sua busca - Classi Livre Autos',
                                            'saved_search_match',
                                            [
                                                'nome'   => (string)($targetUser['name'] ?? ''),
                                                'titulo' => (string)$ad['title'],
                                                'preco'  => 'R$ '.number_format((int)$ad['price'], 2, ',', '.'),
                                                'url'    => $publicUrl,
                                            ],
                                            (string)($targetUser['name'] ?? '')
                                        );
                                    }
                                }
                            } catch (Throwable $e) {
                                error_log('[F11][saved_search_match] Erro: '.$e->getMessage());
                            }
                        }
                    }
                } catch (Throwable $e) {
                    error_log('[F11][saved_search_notify] Erro geral: '.$e->getMessage());
                }
                
                $success = 'Anuncio aprovado com sucesso.';
            } elseif ($action === 'reject') {
                $reason = trim($_POST['reason'] ?? '');
                if ($reason === '') {
                    $errors[] = 'Informe o motivo da rejeicao.';
                } else {
                    $update = db()->prepare('UPDATE ads SET status = "rejected", reason_rejected = :reason, approved_at = NULL, updated_at = :updated_at WHERE id = :id');
                    $update->execute([
                        ':reason' => $reason,
                        ':updated_at' => now(),
                        ':id' => $adId,
                    ]);
                    record_audit((int)current_user()['id'], 'ad_rejected', 'ads', $adId, ['reason' => $reason]);
                    // [EMAIL][ad_status][rejected]
                    if (!function_exists('mail_enqueue')) {
                        $__paths = [__DIR__.'/../api/helpers_mail.php', __DIR__.'/../../api/helpers_mail.php'];
                        foreach ($__paths as $__p) { if (file_exists($__p)) { require_once $__p; break; } }
                    }
                    try {
                        $__pdo = function_exists('db') ? db() : (isset($pdo) ? $pdo : null);
                        if ($__pdo instanceof PDO && function_exists('mail_enqueue')) {
                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $host   = $_SERVER['HTTP_HOST'] ?? 'classilivreautos.com';
                            $editUrl = $scheme.'://'.$host.'/anuncios/editar.php?id='.(int)$adId;
                            mail_enqueue(
                                $__pdo,
                                (string)$ad['email'],
                                'Seu anúncio foi rejeitado - Classi Livre Autos',
                                'ad_status',
                                [
                                    'nome'   => (string)($ad['owner_name'] ?? ''),
                                    'titulo' => (string)$ad['title'],
                                    'status' => 'rejeitado',
                                    'motivo' => (string)$reason,
                                    'url'    => $editUrl,
                                ],
                                (string)($ad['owner_name'] ?? '')
                            );
                        }
                    } catch (Throwable $e) { error_log('[MAIL][ad_rejected] '.$e->getMessage()); }
                    $success = 'Anuncio rejeitado e usuario notificado.';
                }
            } elseif ($action === 'update') {
                $title = trim($_POST['title'] ?? $ad['title']);
                $price = (float)($_POST['price'] ?? $ad['price']);
                $description = trim($_POST['description'] ?? $ad['description']);
                if (strlen($title) < 10 || strlen($description) < 30) {
                    $errors[] = 'Titulo ou descricao abaixo do minimo permitido.';
                } else {
                    $update = db()->prepare('UPDATE ads SET title = :title, slug = :slug, price = :price, description = :description, updated_at = :updated_at WHERE id = :id');
                    $update->execute([
                        ':title' => $title,
                        ':slug' => slugify($title),
                        ':price' => $price,
                        ':description' => $description,
                        ':updated_at' => now(),
                        ':id' => $adId,
                    ]);
                    record_audit((int)current_user()['id'], 'ad_updated_admin', 'ads', $adId);
                    $success = 'Anuncio atualizado para analise.';
                }
            }
        }
    }
}

$stmt = db()->query('SELECT ads.*, users.name AS owner_name, users.email AS owner_email, users.is_premium AS owner_is_premium
    FROM ads JOIN users ON users.id = ads.user_id
    WHERE ads.status = "pending" ORDER BY ads.created_at ASC');
$pendingAds = $stmt->fetchAll();

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<style>
.admin-ad-list { display: grid; gap: 1.5rem; margin-top: 2rem; }
.admin-ad-card { 
    background: #fff; 
    border: 1px solid #e0e0e0; 
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.admin-ad-card__header {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    background: #fafafa;
}
.admin-ad-card__title { margin: 0; font-size: 1.2rem; font-weight: 600; color: #333; }
.admin-ad-card__meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}
.admin-ad-card__meta-item { display: flex; gap: 0.5rem; }
.admin-ad-card__meta-label { font-weight: 600; color: #333; }
.admin-ad-card__content { padding: 1.5rem; display: grid; gap: 1.5rem; }
.admin-ad-card__section { border: 1px solid #e0e0e0; border-radius: 6px; padding: 1rem; background: #fafafa; }
.admin-ad-card__section-title { margin: 0 0 1rem 0; font-size: 0.95rem; font-weight: 600; color: #333; border-bottom: 2px solid #166534; padding-bottom: 0.5rem; }
.admin-ad-card__actions {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
}
.admin-ad-card__form-group { display: grid; gap: 0.5rem; }
.admin-ad-card__label { font-weight: 600; color: #333; font-size: 0.9rem; }
.admin-ad-card__input { padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; }
.admin-ad-card__textarea { padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
.admin-verification-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #e8f5e9;
    border: 1px solid #4caf50;
    border-radius: 4px;
    color: #1b5e20;
    font-size: 0.9rem;
    font-weight: 600;
}
.admin-verification-badge.empty { background: #fff3e0; border-color: #ff9800; color: #e65100; }
.admin-action-buttons { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.admin-action-buttons button { padding: 0.75rem 1.5rem; border-radius: 4px; font-weight: 600; cursor: pointer; border: none; font-size: 0.9rem; }
.admin-action-buttons .button.primary { background: #166534; color: #fff; }
.admin-action-buttons .button.primary:hover { background: #0d4429; }
.admin-action-buttons .button.secondary { background: #e0e0e0; color: #333; }
.admin-action-buttons .button.secondary:hover { background: #d0d0d0; }
.admin-action-buttons .button.danger { background: #d32f2f; color: #fff; }
.admin-action-buttons .button.danger:hover { background: #b71c1c; }
</style>

<section style="margin:2rem 0;">
    <div style="display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 2rem; margin-bottom: 2rem;">
        <div>
            <h1 style="margin: 0; font-size: 1.8rem;">Anúncios Pendentes</h1>
            <p style="margin: 0.5rem 0 0 0; color: #666;">Revise e aprove ou rejeite anúncios para publicação</p>
        </div>
        <div style="text-align: right;">
            <span style="font-size: 2rem; font-weight: bold; color: #166534;"><?= count($pendingAds); ?></span>
            <p style="margin: 0; color: #666; font-size: 0.9rem;">aguardando</p>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success); ?></div>
    <?php endif; ?>

    <?php if (!$pendingAds): ?>
        <div class="alert alert-success" style="text-align: center; padding: 3rem; margin-top: 2rem;">
            <strong>✓ Nenhum anúncio aguardando aprovação</strong>
            <p style="margin-top: 0.5rem; color: #666;">Todos os anúncios foram revisados e processados.</p>
        </div>
    <?php else: ?>
        <div class="admin-ad-list">
            <?php foreach ($pendingAds as $ad): 
                $isPremium = (int)$ad['owner_is_premium'] === 1 || user_is_premium(['id' => (int)$ad['user_id']]);
                $hasVerificationDoc = !empty($ad['verification_document_path']);
            ?>
            <article class="admin-ad-card">
                <div class="admin-ad-card__header">
                    <div>
                        <h2 class="admin-ad-card__title"><?= e($ad['title']); ?></h2>
                        <div class="admin-ad-card__meta">
                            <div class="admin-ad-card__meta-item">
                                <span class="admin-ad-card__meta-label">ID:</span>
                                <span>#<?= (int)$ad['id']; ?></span>
                            </div>
                            <div class="admin-ad-card__meta-item">
                                <span class="admin-ad-card__meta-label">Proprietário:</span>
                                <span><?= e($ad['owner_name']); ?></span>
                            </div>
                            <div class="admin-ad-card__meta-item">
                                <span class="admin-ad-card__meta-label">Local:</span>
                                <span><?= e($ad['city']); ?>/<?= e($ad['state']); ?></span>
                            </div>
                            <div class="admin-ad-card__meta-item">
                                <span class="admin-ad-card__meta-label">Enviado:</span>
                                <span><?= format_date($ad['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                    <a class="button secondary" href="/anuncios/ver.php?id=<?= (int)$ad['id']; ?>" target="_blank" rel="noopener" style="height: fit-content;">Ver anúncio</a>
                </div>

                <div class="admin-ad-card__content">
                    <!-- Informações do Anúncio -->
                    <div class="admin-ad-card__section">
                        <h3 class="admin-ad-card__section-title">📋 Informações do Anúncio</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                            <div>
                                <strong style="color: #333;">Tipo:</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #666;"><?= ucfirst($ad['vehicle_type']); ?></p>
                            </div>
                            <div>
                                <strong style="color: #333;">Marca/Modelo:</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #666;"><?= e($ad['brand']); ?> <?= e($ad['model']); ?></p>
                            </div>
                            <div>
                                <strong style="color: #333;">Preço:</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #166534; font-weight: 600;"><?= format_currency((float)$ad['price']); ?></p>
                            </div>
                            <div>
                                <strong style="color: #333;">Km:</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #666;"><?= number_format((int)$ad['mileage'], 0, ',', '.'); ?></p>
                            </div>
                            <div>
                                <strong style="color: #333;">Ano:</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #666;"><?= (int)$ad['manufacture_year']; ?>/<?= (int)$ad['model_year']; ?></p>
                            </div>
                            <div>
                                <strong style="color: #333;">Combustível:</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #666;"><?= e($ad['fuel_type']); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($isPremium && $hasVerificationDoc): ?>
                    <!-- Documento de Verificação -->
                    <div class="admin-ad-card__section" style="background: #e8f5e9; border-color: #4caf50;">
                        <h3 class="admin-ad-card__section-title" style="border-bottom-color: #4caf50;">✓ Verificação Premium</h3>
                        <div style="display: flex; align-items: center; gap: 1rem; justify-content: space-between;">
                            <div>
                                <p style="margin: 0; color: #1b5e20; font-weight: 600;">Documento anexado para verificação</p>
                                <p style="margin: 0.5rem 0 0 0; color: #558b2f; font-size: 0.9rem;">Clique para revisar antes de ativar a badge</p>
                            </div>
                            <a href="/api/serve_verification_document.php?ad_id=<?= (int)$ad['id']; ?>" target="_blank" rel="noopener" class="button primary" style="background: #4caf50;">📄 Visualizar documento</a>
                        </div>
                    </div>
                    <?php elseif ($isPremium): ?>
                    <!-- Sem documento -->
                    <div class="admin-ad-card__section" style="background: #fff3e0; border-color: #ff9800;">
                        <h3 class="admin-ad-card__section-title" style="border-bottom-color: #ff9800;">ℹ️ Usuário Premium</h3>
                        <p style="margin: 0; color: #e65100;">Nenhum documento de verificação foi anexado.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Edição de Informações -->
                    <form method="post" class="admin-ad-card__section">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="ad_id" value="<?= (int)$ad['id']; ?>">
                        <input type="hidden" name="action" value="update">
                        <h3 class="admin-ad-card__section-title">✏️ Editar Informações</h3>
                        <div style="display: grid; gap: 1rem;">
                            <div class="admin-ad-card__form-group">
                                <label class="admin-ad-card__label">Título</label>
                                <input type="text" name="title" value="<?= e($ad['title']); ?>" class="admin-ad-card__input" required>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="admin-ad-card__form-group">
                                    <label class="admin-ad-card__label">Preço</label>
                                    <input type="number" min="0" step="0.01" name="price" value="<?= e($ad['price']); ?>" class="admin-ad-card__input" required>
                                </div>
                            </div>
                            <div class="admin-ad-card__form-group">
                                <label class="admin-ad-card__label">Descrição</label>
                                <textarea name="description" rows="4" class="admin-ad-card__textarea" required><?= e($ad['description']); ?></textarea>
                            </div>
                            <button type="submit" class="button primary" style="background: #166534; justify-self: flex-start;">💾 Salvar ajustes</button>
                        </div>
                    </form>

                    <!-- Ações de Aprovação/Rejeição -->
                    <div style="display: grid; gap: 1rem;">
                        <?php if ($isPremium && $hasVerificationDoc): ?>
                        <div style="display: grid; gap: 1rem;">
                            <!-- Botões de Ação Rápida -->
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: stretch;">
                                <!-- Badge de Verificação (TOGGLE COM AJAX) -->
                                <div data-badge-container style="flex: 1; min-width: 150px; display: flex; align-items: center;">
                                    <button 
                                        type="button"
                                        class="button primary" 
                                        style="width: 100%; background: <?= (int)$ad['verified_badge'] === 1 ? '#d32f2f' : '#4caf50'; ?>"
                                        data-toggle-badge="<?= (int)$ad['verified_badge'] === 1 ? 'deactivate' : 'activate'; ?>"
                                        data-ad-id="<?= (int)$ad['id']; ?>"
                                        data-csrf="<?= e(csrf_token()); ?>"
                                    >
                                        <?php if ((int)$ad['verified_badge'] === 1): ?>
                                            ✗ Remover Verificação
                                        <?php else: ?>
                                            ✓ Ativar Verificado
                                        <?php endif; ?>
                                    </button>
                                </div>
                                
                                <!-- Aprovar -->
                                <form method="post" style="flex: 1; min-width: 150px;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                    <input type="hidden" name="ad_id" value="<?= (int)$ad['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="button primary" style="width: 100%; background: #166534;">✓ Aprovar</button>
                                </form>
                            </div>
                            <!-- Rejeitar -->
                            <form method="post" style="display: grid; gap: 0.5rem;">
                                <textarea name="reason" rows="2" class="admin-ad-card__textarea" placeholder="Motivo da rejeição..." required></textarea>
                                <button type="submit" class="button danger" style="width: 100%; background: #d32f2f;">✕ Rejeitar anúncio</button>
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                <input type="hidden" name="ad_id" value="<?= (int)$ad['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                            </form>
                        </div>
                        <?php else: ?>
                        <div style="display: grid; gap: 1rem;">
                            <!-- Botão de Aprovar -->
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                <input type="hidden" name="ad_id" value="<?= (int)$ad['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="button primary" style="width: 100%; background: #166534;">✓ Aprovar anúncio</button>
                            </form>
                            <!-- Rejeitar -->
                            <form method="post" style="display: grid; gap: 0.5rem;">
                                <textarea name="reason" rows="2" class="admin-ad-card__textarea" placeholder="Motivo da rejeição..." required></textarea>
                                <button type="submit" class="button danger" style="width: 100%; background: #d32f2f;">✕ Rejeitar anúncio</button>
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                <input type="hidden" name="ad_id" value="<?= (int)$ad['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script src="/assets/js/admin-verification-badge.js"></script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
