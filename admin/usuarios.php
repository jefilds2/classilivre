<?php
// admin/usuarios.php - Administracao de usuarios com bloqueio e alternancia de premium.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../anuncios/upload_handler.php';

require_admin();

$pageTitle = 'Usuarios - Administracao';
$activeNav = 'users';
$errors = [];
$success = null;

$page = max(1, (int)(input_get('page') ?? 1));
$limit = min(500, max(10, (int)(input_get('limit') ?? 100))); // limit between 10 and 500
$offset = ($page - 1) * $limit;

$query = input_get('q');
$status = input_get('status');
$premium = input_get('premium');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalido.';
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $stmt = db()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Usuario nao encontrado.';
        } elseif ($user['id'] === current_user()['id']) {
            $errors[] = 'Voce nao pode alterar a si mesmo.';
        } elseif ($user['is_admin'] && $action === 'ban_user') {
            $errors[] = 'Voce nao pode banir outro administrador.';
        } else {
            if ($action === 'block') {
                $update = db()->prepare('UPDATE users SET status = "blocked", updated_at = :updated_at WHERE id = :id');
                $update->execute([':updated_at' => now(), ':id' => $userId]);
                record_audit((int)current_user()['id'], 'user_blocked', 'users', $userId);
                $success = 'Usuario bloqueado.';
            } elseif ($action === 'unblock') {
                // Desbloquear usuário
                $update = db()->prepare('UPDATE users SET status = "active", updated_at = :updated_at WHERE id = :id');
                $update->execute([':updated_at' => now(), ':id' => $userId]);

                // Reativar anúncios (definir status baseado no premium)
                $isPremium = $user['is_premium'];
                $newAdStatus = $isPremium ? 'approved' : 'pending';
                $expiresAt = $isPremium ? null : date('Y-m-d H:i:s', time() + (30 * 86400));

                db()->prepare('UPDATE ads SET status = :status, updated_at = :updated_at, expires_at = :expires_at WHERE user_id = :user_id AND status = "blocked"')
                    ->execute([':status' => $newAdStatus, ':updated_at' => now(), ':expires_at' => $expiresAt, ':user_id' => $userId]);

                // Reativar serviços
                db()->prepare('UPDATE user_service_listings SET status = "active", updated_at = :updated_at WHERE user_id = :user_id AND status = "suspended"')
                    ->execute([':updated_at' => now(), ':user_id' => $userId]);

                record_audit((int)current_user()['id'], 'user_unblocked_with_content', 'users', $userId);
                
                // Enviar email de reativação IMEDIATAMENTE
                if (!function_exists('mail_send_immediate')) {
                    $try = [__DIR__.'/../api/helpers_mail.php', __DIR__.'/../../api/helpers_mail.php'];
                    foreach ($try as $p) { if (file_exists($p)) { require_once $p; break; } }
                }
                try {
                    $sent = function_exists('mail_send_immediate') ? mail_send_immediate(
                        (string)$user['email'],
                        'Sua conta foi reativada! - Classi Livre Autos',
                        'user_reactivated',
                        ['nome' => (string)($user['name'] ?? '')],
                        (string)($user['name'] ?? '')
                    ) : false;
                    if (!$sent) {
                        error_log('[MAIL][unblock] Falha ao enviar email de reativação');
                    }
                } catch (Throwable $e) {
                    error_log('[MAIL][unblock] '.$e->getMessage());
                }
                
                $success = 'Usuario desbloqueado e seus anuncios/servicos reativados.';
            } elseif ($action === 'toggle_premium') {
                $newValue = $user['is_premium'] ? 0 : 1;
                $update = db()->prepare('UPDATE users SET is_premium = :premium, updated_at = :updated_at WHERE id = :id');
                $update->execute([':premium' => $newValue, ':updated_at' => now(), ':id' => $userId]);
                record_audit((int)current_user()['id'], 'user_toggle_premium', 'users', $userId, ['new_value' => $newValue]);
                $success = $newValue ? 'Usuario promovido a premium.' : 'Premium removido do usuario.';
            } elseif ($action === 'block_user') {
                $reason = trim($_POST['reason'] ?? '');
                if ($reason === '') {
                    $errors[] = 'Informe o motivo do bloqueio.';
                } else {
                    // Bloquear usuário
                    $update = db()->prepare('UPDATE users SET status = "blocked", updated_at = :updated_at WHERE id = :id');
                    $update->execute([':updated_at' => now(), ':id' => $userId]);

                    // Suspender anúncios
                    db()->prepare('UPDATE ads SET status = "blocked", updated_at = :updated_at WHERE user_id = :user_id')->execute([':updated_at' => now(), ':user_id' => $userId]);

                    // Suspender serviços
                    db()->prepare('UPDATE user_service_listings SET status = "suspended", updated_at = :updated_at WHERE user_id = :user_id')->execute([':updated_at' => now(), ':user_id' => $userId]);

                    record_audit((int)current_user()['id'], 'user_suspended_with_content', 'users', $userId, ['reason' => $reason]);
                    
                    // Enviar email de suspensão IMEDIATAMENTE
                    if (!function_exists('mail_send_immediate')) {
                        $try = [__DIR__.'/../api/helpers_mail.php', __DIR__.'/../../api/helpers_mail.php'];
                        foreach ($try as $p) { if (file_exists($p)) { require_once $p; break; } }
                    }
                    try {
                        $sent = function_exists('mail_send_immediate') ? mail_send_immediate(
                            (string)$user['email'],
                            'Sua conta foi suspensa - Classi Livre Autos',
                            'user_suspended',
                            [
                                'nome' => (string)($user['name'] ?? ''),
                                'motivo' => $reason,
                                'tipo' => 'suspension',
                                'dataReativacao' => null
                            ],
                            (string)($user['name'] ?? '')
                        ) : false;
                        if (!$sent) {
                            error_log('[MAIL][block_user] Falha ao enviar email de suspensão');
                        }
                    } catch (Throwable $e) {
                        error_log('[MAIL][block_user] '.$e->getMessage());
                    }
                    
                    $success = 'Usuario suspenso e todos os seus anuncios/servicos suspensos.';
                }
            } elseif ($action === 'ban_user') {
                $reason = trim($_POST['reason'] ?? '');
                if ($reason === '') {
                    $errors[] = 'Informe o motivo do banimento.';
                } else {
                    $pdo = db();
                    try {
                        $pdo->beginTransaction();

                        // ✅ PASSO 1: Inserir na blacklist (impedindo novo login/conta)
                        $insert = $pdo->prepare('INSERT INTO user_blacklist (original_user_id, name, email, phone, document_type, document_number, reason, banned_by, banned_at, ip_address, user_agent) VALUES (:original_user_id, :name, :email, :phone, :document_type, :document_number, :reason, :banned_by, :banned_at, :ip_address, :user_agent)');
                        $insert->execute([
                            ':original_user_id' => $userId,
                            ':name' => $user['name'],
                            ':email' => $user['email'],
                            ':phone' => $user['phone'],
                            ':document_type' => $user['document_type'],
                            ':document_number' => $user['document_number'],
                            ':reason' => $reason,
                            ':banned_by' => current_user()['id'],
                            ':banned_at' => now(),
                            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                        ]);

                        // ✅ PASSO 2: Buscar todos os anúncios do usuário
                        $adsStmt = $pdo->prepare('SELECT id FROM ads WHERE user_id = :user_id');
                        $adsStmt->execute([':user_id' => $userId]);
                        $adsIds = $adsStmt->fetchAll(PDO::FETCH_COLUMN);
                        $deletedAdsCount = 0;
                        $deletedFilesCount = 0;

                        // ✅ PASSO 3: Deletar cada anúncio e suas mídias
                        foreach ($adsIds as $adId) {
                            // Buscar mídias associadas ao anúncio
                            $mediaStmt = $pdo->prepare('SELECT filepath FROM ad_media WHERE ad_id = :ad_id');
                            $mediaStmt->execute([':ad_id' => $adId]);
                            $mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);
                            $deletedFilesCount += count($mediaFiles);
                            
                            // Deletar cada arquivo de mídia do servidor (com supressão de erros)
                            foreach ($mediaFiles as $media) {
                                @delete_media_file($media['filepath']);
                            }

                            // Deletar registros de mídia da BD
                            $pdo->prepare('DELETE FROM ad_media WHERE ad_id = :ad_id')->execute([':ad_id' => $adId]);

                            // Deletar anúncio
                            $deleteAd = $pdo->prepare('DELETE FROM ads WHERE id = :id');
                            $deleteAd->execute([':id' => $adId]);
                            $deletedAdsCount++;
                        }

                        // ✅ PASSO 4: Deletar serviços do usuário (se houver)
                        $pdo->prepare('DELETE FROM user_service_listings WHERE user_id = :user_id')->execute([':user_id' => $userId]);

                        // ✅ PASSO 5: Deletar usuário
                        $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);

                        // Commit transaction
                        $pdo->commit();

                        // Log auditoria
                        record_audit((int)current_user()['id'], 'user_banned', 'users', $userId, [
                            'reason' => $reason,
                            'ads_deleted' => $deletedAdsCount,
                            'files_deleted' => $deletedFilesCount
                        ]);

                        $success = "Usuário banido permanentemente. Dados: Blacklist registrada, $deletedAdsCount anúncio(s) deletado(s), $deletedFilesCount arquivo(s) removido(s).";
                    } catch (Throwable $e) {
                        $pdo->rollBack();
                        $errors[] = 'Erro ao banir usuário: ' . $e->getMessage();
                        error_log('Ban user error for user_id ' . $userId . ': ' . $e->getMessage());
                    }
                }
            }
        }
    }
}

// Build filters
$conditions = [];
$params = [];

if ($query) {
    $conditions[] = '(u.name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q OR u.document_number LIKE :q)';
    $params[':q'] = '%' . $query . '%';
}

if ($status) {
    $conditions[] = 'u.status = :status';
    $params[':status'] = $status;
}

if ($premium !== null && $premium !== '') {
    $conditions[] = 'u.is_premium = :premium';
    $params[':premium'] = (int)$premium;
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$countStmt = db()->prepare("SELECT COUNT(*) as total FROM users u $whereSql");
$countStmt->execute($params);
$totalUsers = (int)$countStmt->fetch()['total'];
$totalPages = ceil($totalUsers / $limit);

// Get paginated results with ad count
$stmt = db()->prepare("SELECT u.* FROM users u $whereSql ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$users = $stmt->fetchAll();

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section style="margin:2rem 0;">
<h1>Usuarios cadastrados</h1>
<p>Gerencie acesso, bloqueios e status premium. Total: <strong><?= number_format($totalUsers); ?></strong> usuários encontrados.</p>

    <form method="get" class="search-card admin-filter-form" style="margin-top:1rem;">
        <div class="search-grid">
            <div class="input-group">
                <label for="q">Busca por nome, e-mail, telefone ou CPF</label>
                <input type="text" id="q" name="q" value="<?= e($query ?? ''); ?>" placeholder="Digite nome, email, telefone ou CPF...">
            </div>
            <div class="input-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="blocked" <?= $status === 'blocked' ? 'selected' : ''; ?>>Bloqueados</option>
                </select>
            </div>
            <div class="input-group">
                <label for="premium">Premium</label>
                <select id="premium" name="premium">
                    <option value="">Todos</option>
                    <option value="1" <?= $premium === '1' ? 'selected' : ''; ?>>Sim</option>
                    <option value="0" <?= $premium === '0' ? 'selected' : ''; ?>>Não</option>
                </select>
            </div>
            <div class="input-group">
                <label for="limit">Itens por página</label>
                <select id="limit" name="limit">
                    <option value="10" <?= $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?= $limit == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="250" <?= $limit == 250 ? 'selected' : ''; ?>>250</option>
                    <option value="500" <?= $limit == 500 ? 'selected' : ''; ?>>500</option>
                </select>
            </div>
        </div>
        <div class="admin-filter-actions">
            <button type="submit" class="button button-admin admin-filter-button">Filtrar</button>
            <a href="?" class="button secondary">Limpar Filtros</a>
        </div>
    </form>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success); ?></div>
    <?php endif; ?>

    <div style="margin-top:1.5rem; overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th>Premium</th>
                    <th>Sessões</th>
                    <th>Admin</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $item): ?>
                    <tr>
                        <td><?= (int)$item['id']; ?></td>
                        <td><?= e($item['name']); ?></td>
                        <td><?= e($item['email']); ?><br><small><?= e(mask_phone($item['phone'] ?? '')); ?></small></td>
                        <td><span class="status-pill status-<?= e($item['status']); ?>"><?= e(strtoupper($item['status'])); ?></span></td>
                        <td><?= $item['is_premium'] ? 'Sim' : 'Nao'; ?></td>
                        <td>
                            <select onchange="updateConcurrentSessions(<?= (int)$item['id']; ?>, this.value)" style="padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid #ddd;">
                                <option value="1" <?= ((int)($item['max_concurrent_sessions'] ?? 1) === 1) ? 'selected' : ''; ?>>1 sessão</option>
                                <option value="2" <?= ((int)($item['max_concurrent_sessions'] ?? 1) === 2) ? 'selected' : ''; ?>>2 sessões</option>
                                <option value="3" <?= ((int)($item['max_concurrent_sessions'] ?? 1) === 3) ? 'selected' : ''; ?>>3 sessões</option>
                                <option value="5" <?= ((int)($item['max_concurrent_sessions'] ?? 1) === 5) ? 'selected' : ''; ?>>5 sessões</option>
                                <option value="10" <?= ((int)($item['max_concurrent_sessions'] ?? 1) === 10) ? 'selected' : ''; ?>>10 sessões</option>
                            </select>
                        </td>
                        <td><?= $item['is_admin'] ? 'Sim' : 'Nao'; ?></td>
                        <td style="min-width:280px; display:flex; flex-direction:column; gap:0.5rem;">
                        <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$item['id']; ?>">
                        <input type="hidden" name="action" value="toggle_premium">
                        <button type="submit" class="button secondary">Alternar premium</button>
                        </form>
                        <?php if ($item['status'] === 'active'): ?>
                        <?php if (!$item['is_admin']): ?>
                        <button type="button" class="button secondary" onclick="blockUser(<?= (int)$item['id']; ?>, '<?= e($item['name']); ?>')">Suspender</button>
                        <button type="button" class="button secondary" style="background:#dc3545;color:#fff;" onclick="banUser(<?= (int)$item['id']; ?>, '<?= e($item['name']); ?>')">Banir</button>
                        <?php else: ?>
                        <span style="color:#666;font-style:italic;">Administrador</span>
                        <?php endif; ?>
                        <?php else: ?>
                        <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$item['id']; ?>">
                        <input type="hidden" name="action" value="unblock">
                        <button type="submit" class="button">Reativar</button>
                        </form>
                        <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                    <tr><td colspan="9">Nenhum usuario cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top:2rem; text-align:center;">
            <?php
            $queryParams = $_GET;
            unset($queryParams['page']); // Remove page from query for clean links

            if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page - 1])); ?>" class="button secondary">← Anterior</a>
            <?php endif; ?>

            <span style="margin:0 1rem;">Página <?= $page; ?> de <?= $totalPages; ?> (<?= number_format($totalUsers); ?> total)</span>

            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page + 1])); ?>" class="button secondary">Próxima →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Modal de Bloqueio -->
    <div id="blockModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('blockModal')">&times;</span>
            <h2>Suspender Usuário</h2>
            <p id="blockUserName"></p>
            <p><strong>Atenção:</strong> O usuário será bloqueado e todos os seus anúncios/serviços serão suspensos automaticamente.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="user_id" id="blockUserId">
                <input type="hidden" name="action" value="block_user">
                <div class="input-group">
                    <label for="blockReason">Motivo do Bloqueio</label>
                    <textarea id="blockReason" name="reason" rows="3" required placeholder="Descreva o motivo do bloqueio..."></textarea>
                </div>
                <button type="submit" class="button secondary" style="background:#e63946;color:#fff;">Suspender Usuário</button>
            </form>
        </div>
    </div>

    <!-- Modal de Banimento -->
    <div id="banModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('banModal')">&times;</span>
            <h2>Banir Usuário Permanentemente</h2>
            <p id="banUserName"></p>
            <p><strong>Atenção:</strong> Esta ação é irreversível! O usuário será banido, seus dados movidos para blacklist, e todos os anúncios/serviços excluídos permanentemente.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="user_id" id="banUserId">
                <input type="hidden" name="action" value="ban_user">
                <div class="input-group">
                    <label for="banReason">Motivo do Banimento</label>
                    <textarea id="banReason" name="reason" rows="3" required placeholder="Descreva o motivo do banimento..."></textarea>
                </div>
                <button type="submit" class="button secondary" style="background:#dc3545;color:#fff;">Banir Permanentemente</button>
            </form>
        </div>
    </div>

    <script>
        function blockUser(id, name) {
            document.getElementById('blockUserId').value = id;
            document.getElementById('blockUserName').textContent = 'Suspender: ' + name;
            document.getElementById('blockReason').value = '';
            document.getElementById('blockModal').style.display = 'block';
        }

        function banUser(id, name) {
            document.getElementById('banUserId').value = id;
            document.getElementById('banUserName').textContent = 'Banir: ' + name;
            document.getElementById('banReason').value = '';
            document.getElementById('banModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
    </style>

<script>
async function updateConcurrentSessions(userId, maxSessions) {
    try {
        const response = await fetch('/api/admin_set_concurrent_sessions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                user_id: userId,
                max_sessions: maxSessions,
                csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
            })
        });

        const data = await response.json();

        if (data.success) {
            // Exibir mensagem de sucesso
            alert(`Limite de sessões atualizado para ${maxSessions} sessão(ões)`);
        } else {
            alert(`Erro: ${data.message}`);
            // Recarregar página para resetar o select
            location.reload();
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao atualizar limite de sessões');
        location.reload();
    }
}
</script>

</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
