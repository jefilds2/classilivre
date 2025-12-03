<?php
// admin/anuncios_todos.php - Gestao completa de anuncios com filtros.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../anuncios/upload_handler.php';

require_admin();

$pageTitle = 'Todos os anuncios - Administracao';
$activeNav = 'all-ads';
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalido.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Se for bulk_delete, processa diferente
        if ($action === 'bulk_delete') {
            $selectedIds = array_map('intval', $_POST['selected_ads'] ?? []);
            if (empty($selectedIds)) {
                $errors[] = 'Nenhum anúncio selecionado.';
            } else {
                $deletedCount = 0;
                $errorsCount = 0;
                foreach ($selectedIds as $adId) {
                    try {
                        // Buscar anúncio
                        $adStmt = db()->prepare('SELECT ads.*, users.name AS owner_name FROM ads JOIN users ON users.id = ads.user_id WHERE ads.id = :id LIMIT 1');
                        $adStmt->execute([':id' => $adId]);
                        $ad = $adStmt->fetch();
                        if (!$ad) {
                            $errorsCount++;
                            continue;
                        }

                        // Buscar mídias
                        $mediaStmt = db()->prepare('SELECT filepath FROM ad_media WHERE ad_id = :ad_id');
                        $mediaStmt->execute([':ad_id' => $adId]);
                        $mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Remover arquivos de mídia
                        foreach ($mediaFiles as $media) {
                            delete_media_file($media['filepath']);
                        }

                        // Deletar as pastas de mídia do anúncio
                        $imageDir = __DIR__ . '/../assets/uploads/images/' . $adId;
                        if (is_dir($imageDir)) {
                            foreach ((array)glob($imageDir . '/*') as $file) {
                                @unlink($file);
                            }
                            @rmdir($imageDir);
                        }

                        $videoDir = __DIR__ . '/../assets/uploads/videos/' . $adId;
                        if (is_dir($videoDir)) {
                            foreach ((array)glob($videoDir . '/*') as $file) {
                                @unlink($file);
                            }
                            @rmdir($videoDir);
                        }

                        // Deletar registros de mídia da BD
                        db()->prepare('DELETE FROM ad_media WHERE ad_id = :ad_id')->execute([':ad_id' => $adId]);

                        // Deletar anúncio
                        $delete = db()->prepare('DELETE FROM ads WHERE id = :id');
                        $delete->execute([':id' => $adId]);

                        record_audit((int)current_user()['id'], 'ad_bulk_deleted_admin', 'ads', $adId, ['title' => $ad['title'], 'media_deleted' => count($mediaFiles)]);
                        $deletedCount++;
                    } catch (Throwable $e) {
                        $errorsCount++;
                        error_log('Bulk delete error for ad ' . $adId . ': ' . $e->getMessage());
                    }
                }
                $success = "Exclusão em lote concluída: $deletedCount anúncios excluídos";
                if ($errorsCount > 0) {
                    $success .= ", $errorsCount erros";
                }
            }
        } else {
            // Para outras ações, precisa de um anúncio específico
            $adId = (int)($_POST['ad_id'] ?? 0);

            $stmt = db()->prepare('SELECT * FROM ads WHERE id = :id');
            $stmt->execute([':id' => $adId]);
            $ad = $stmt->fetch();

            if (!$ad) {
                $errors[] = 'Anuncio nao encontrado.';
            } else {
                if ($action === 'edit') {
                $title = trim($_POST['title'] ?? $ad['title']);
                $price = (float)($_POST['price'] ?? $ad['price']);
                $description = trim($_POST['description'] ?? $ad['description']);
                if (strlen($title) < 10 || strlen($description) < 30) {
                    $errors[] = 'Título deve ter pelo menos 10 caracteres e descrição 30.';
                } elseif ($price <= 0) {
                    $errors[] = 'Preço deve ser maior que zero.';
                } else {
                    $update = db()->prepare('UPDATE ads SET title = :title, slug = :slug, price = :price, description = :description, updated_at = :updated_at WHERE id = :id');
                    $update->execute([
                        ':title' => $title,
                        ':slug' => generate_unique_ad_slug(db(), $title, $adId),
                        ':price' => $price,
                        ':description' => $description,
                        ':updated_at' => now(),
                        ':id' => $adId,
                    ]);
                    record_audit((int)current_user()['id'], 'ad_edited_admin', 'ads', $adId);
                    $success = 'Anúncio editado com sucesso.';
                }
            } elseif ($action === 'delete') {
                try {
                    error_log("Admin delete start: adId=$adId, title={$ad['title']}");

                    // Buscar mídias e remover arquivos físicos
                    $mediaStmt = db()->prepare('SELECT filepath FROM ad_media WHERE ad_id = :ad_id');
                    $mediaStmt->execute([':ad_id' => $adId]);
                    $mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Found " . count($mediaFiles) . " media files");

                    foreach ($mediaFiles as $media) {
                        delete_media_file($media['filepath']);
                        error_log("Deleted file {$media['filepath']}");
                    }

                    // Deletar as pastas de mídia do anúncio
                    $imageDir = __DIR__ . '/../assets/uploads/images/' . $adId;
                    if (is_dir($imageDir)) {
                        foreach ((array)glob($imageDir . '/*') as $file) {
                            @unlink($file);
                        }
                        @rmdir($imageDir);
                        error_log("Deleted image folder: $imageDir");
                    }

                    $videoDir = __DIR__ . '/../assets/uploads/videos/' . $adId;
                    if (is_dir($videoDir)) {
                        foreach ((array)glob($videoDir . '/*') as $file) {
                            @unlink($file);
                        }
                        @rmdir($videoDir);
                        error_log("Deleted video folder: $videoDir");
                    }

                    // Deletar registros de mídia da BD
                    db()->prepare('DELETE FROM ad_media WHERE ad_id = :ad_id')->execute([':ad_id' => $adId]);

                    // Deletar anúncio (CASCADE remove do banco)
                    $delete = db()->prepare('DELETE FROM ads WHERE id = :id');
                    $delete->execute([':id' => $adId]);
                    error_log("Deleted ad from DB");

                    record_audit((int)current_user()['id'], 'ad_deleted_admin', 'ads', $adId, ['title' => $ad['title'], 'media_deleted' => count($mediaFiles)]);
                    $success = 'Anúncio e mídias excluídos permanentemente.';
                    error_log("Delete completed successfully");
                } catch (Throwable $e) {
                    $errors[] = 'Erro ao excluir anúncio: ' . $e->getMessage();
                    error_log('Admin delete error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
                }
            } elseif ($action === 'block') {
                $reason = trim($_POST['reason'] ?? '');
                if ($reason === '') {
                    $errors[] = 'Informe um motivo para bloquear.';
                } else {
                    $update = db()->prepare('UPDATE ads SET status = "blocked", reason_rejected = :reason, updated_at = :updated_at WHERE id = :id');
                    $update->execute([
                        ':reason' => $reason,
                        ':updated_at' => now(),
                        ':id' => $adId,
                    ]);
                    record_audit((int)current_user()['id'], 'ad_blocked', 'ads', $adId, ['reason' => $reason]);
                    $success = 'Anuncio bloqueado com sucesso.';
                }
            } elseif ($action === 'unblock') {
                // TODOS os anúncios têm duração de 30 dias (premium ou gratuito)
                $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
                
                $update = db()->prepare('UPDATE ads SET status = "approved", reason_rejected = NULL, approved_at = :approved_at, updated_at = :updated_at, expires_at = :expires_at WHERE id = :id');
                $update->execute([
                    ':approved_at' => now(),
                    ':updated_at' => now(),
                    ':expires_at' => $expiresAt,
                    ':id' => $adId,
                ]);
                record_audit((int)current_user()['id'], 'ad_unblocked', 'ads', $adId);
                $success = 'Anuncio reativado como aprovado.';
            }
            }
        }
    }
}

$status = input_get('status');
$query = input_get('q');
$page = max(1, (int)(input_get('page') ?? 1));
$limit = min(100, max(10, (int)(input_get('limit') ?? 50))); // limit between 10 and 100
$offset = ($page - 1) * $limit;

$conditions = [];
$params = [];
if ($status) {
    $conditions[] = 'ads.status = :status';
    $params[':status'] = $status;
}
if ($query) {
    $conditions[] = '(ads.title LIKE :q OR users.email LIKE :q OR users.name LIKE :q OR users.phone LIKE :q OR users.document_number LIKE :q OR ads.city LIKE :q OR ads.state LIKE :q)';
    $params[':q'] = '%' . $query . '%';
}
$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$countStmt = db()->prepare("SELECT COUNT(*) as total FROM ads JOIN users ON users.id = ads.user_id $whereSql");
$countStmt->execute($params);
$totalAds = (int)$countStmt->fetch()['total'];
$totalPages = ceil($totalAds / $limit);

// Get paginated results
$stmt = db()->prepare("SELECT ads.*, users.name AS owner_name, users.email AS owner_email FROM ads
    JOIN users ON users.id = ads.user_id $whereSql ORDER BY ads.created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$ads = $stmt->fetchAll();

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section style="margin:2rem 0;">
    <h1>Todos os anuncios</h1>
    <p>Gerencie o conteudo publicado pelos usuarios. Total: <strong><?= number_format($totalAds); ?></strong> anúncios encontrados.</p>

    <form method="get" class="search-card admin-filter-form" style="margin-top:1rem;">
        <div class="search-grid">
            <div class="input-group">
                <label for="q">Busca por título, e-mail, nome, telefone, CPF, cidade ou estado</label>
                <input type="text" id="q" name="q" value="<?= e($query ?? ''); ?>" placeholder="Digite título, email, nome, telefone, CPF, cidade ou estado...">
            </div>
            <div class="input-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Pendentes</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : ''; ?>>Aprovados</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : ''; ?>>Rejeitados</option>
                    <option value="blocked" <?= $status === 'blocked' ? 'selected' : ''; ?>>Bloqueados</option>
                </select>
            </div>
            <div class="input-group">
                <label for="limit">Itens por página</label>
                <select id="limit" name="limit">
                    <option value="10" <?= $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?= $limit == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
        </div>
        <div class="admin-filter-actions">
            <button type="submit" class="button button-admin admin-filter-button">Filtrar</button>
            <a href="?" class="button secondary">Limpar Filtros</a>
        </div>
    </form>

    <!-- Bulk Actions -->
    <form id="bulkForm" method="post" style="margin-top:1rem;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="bulk_delete">
        <div class="bulk-actions" style="display:flex; gap:1rem; align-items:center; margin-bottom:1rem;">
            <button type="button" onclick="selectAll()" class="button secondary">Selecionar Todos</button>
            <button type="button" onclick="selectNone()" class="button secondary">Selecionar Nenhum</button>
            <button type="submit" onclick="return confirmBulkDelete()" class="button secondary" style="background:#dc3545;color:#fff;">Excluir Selecionados</button>
            <span id="selectedCount" style="font-weight:bold;">0 selecionados</span>
        </div>

    <?php if ($errors): ?>
        <div class="alert alert-error" style="margin-top:1rem;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-top:1rem;"><?= e($success); ?></div>
    <?php endif; ?>

    <div style="margin-top:1.5rem; overflow-x:auto;">
        <table class="table">
        <thead>
        <tr>
        <th><input type="checkbox" id="selectAllCheckbox" onclick="toggleAll(this)"></th>
        <th>#</th>
        <th>Título</th>
        <th>Usuário</th>
        <th>Status</th>
        <th>Cadastro</th>
        <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ads as $ad): ?>
        <tr>
                         <td><input type="checkbox" name="selected_ads[]" value="<?= (int)$ad['id']; ?>" class="ad-checkbox" onchange="updateSelectedCount()"></td>
                         <td><?= (int)$ad['id']; ?></td>
                        <td><?= e($ad['title']); ?></td>
                        <td><?= e($ad['owner_name']); ?><br><small><?= e($ad['owner_email']); ?></small></td>
                        <td><span class="status-pill status-<?= e($ad['status']); ?>"><?= e(strtoupper($ad['status'])); ?></span></td>
                        <td><?= format_date($ad['created_at']); ?></td>
                        <td style="min-width:320px;">
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:flex-start;">
                            <a class="button secondary" href="/anuncios/ver.php?id=<?= (int)$ad['id']; ?>" target="_blank" rel="noopener">Ver</a>
                        <button type="button" class="button secondary" onclick="editAd(<?= (int)$ad['id']; ?>, '<?= e($ad['title']); ?>', '<?= e($ad['price']); ?>', '<?= e(addslashes($ad['description'])); ?>')">Editar</button>
                        <?php if ($ad['status'] !== 'blocked'): ?>
                        <button type="button" class="button secondary" onclick="blockAd(<?= (int)$ad['id']; ?>)">Bloquear</button>
                        <?php else: ?>
                            <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                <input type="hidden" name="ad_id" value="<?= (int)$ad['id']; ?>">
                            <input type="hidden" name="action" value="unblock">
                            <button type="submit" class="button">Reativar</button>
                        </form>
                        <?php endif; ?>
                        <button type="button" class="button secondary" style="background:#dc3545;color:#fff;" onclick="deleteAd(<?= (int)$ad['id']; ?>, '<?= e($ad['title']); ?>')">Excluir</button>
                        </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$ads): ?>
                    <tr><td colspan="7">Nenhum anuncio encontrado.</td></tr>
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

            <span style="margin:0 1rem;">Página <?= $page; ?> de <?= $totalPages; ?> (<?= number_format($totalAds); ?> total)</span>

            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page + 1])); ?>" class="button secondary">Próxima →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    </form> <!-- End bulk form -->

    <!-- Modal de Edição -->
    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Editar Anúncio</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="ad_id" id="editAdId">
                <input type="hidden" name="action" value="edit">
                <div class="input-group">
                    <label for="editTitle">Título</label>
                    <input type="text" id="editTitle" name="title" required>
                </div>
                <div class="input-group">
                    <label for="editPrice">Preço (R$)</label>
                    <input type="number" id="editPrice" name="price" min="0" step="0.01" required>
                </div>
                <div class="input-group">
                    <label for="editDescription">Descrição</label>
                    <textarea id="editDescription" name="description" rows="5" required></textarea>
                </div>
                <button type="submit" class="button">Salvar Alterações</button>
            </form>
        </div>
    </div>

    <!-- Modal de Bloqueio -->
    <div id="blockModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('blockModal')">&times;</span>
            <h2>Bloquear Anúncio</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="ad_id" id="blockAdId">
                <input type="hidden" name="action" value="block">
                <div class="input-group">
                    <label for="blockReason">Motivo do Bloqueio</label>
                    <textarea id="blockReason" name="reason" rows="3" required></textarea>
                </div>
                <button type="submit" class="button secondary" style="background:#e63946;color:#fff;">Bloquear Anúncio</button>
            </form>
        </div>
    </div>

    <script>
        function editAd(id, title, price, description) {
            document.getElementById('editAdId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editPrice').value = price;
            document.getElementById('editDescription').value = description;
            document.getElementById('editModal').style.display = 'block';
        }

        function blockAd(id) {
            document.getElementById('blockAdId').value = id;
            document.getElementById('blockModal').style.display = 'block';
        }

        function deleteAd(id, title) {
            if (confirm(`Tem certeza que deseja excluir permanentemente o anúncio "${title}"? Esta ação não pode ser desfeita e removerá todas as mídias associadas.`)) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <input type="hidden" name="ad_id" value="${id}">
                    <input type="hidden" name="action" value="delete">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function selectAll() {
            document.querySelectorAll('.ad-checkbox').forEach(cb => cb.checked = true);
            updateSelectedCount();
        }

        function selectNone() {
            document.querySelectorAll('.ad-checkbox').forEach(cb => cb.checked = false);
            updateSelectedCount();
        }

        function toggleAll(source) {
            document.querySelectorAll('.ad-checkbox').forEach(cb => cb.checked = source.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = document.querySelectorAll('.ad-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count + ' selecionados';
        }

        function confirmBulkDelete() {
            const checkedBoxes = document.querySelectorAll('.ad-checkbox:checked');
            const count = checkedBoxes.length;
            if (count === 0) {
                alert('Nenhum anúncio selecionado.');
                return false;
            }
            
            if (!confirm(`Tem certeza que deseja excluir permanentemente ${count} anúncios? Esta ação não pode ser desfeita e removerá todas as mídias associadas.`)) {
                return false;
            }

            // Limpar inputs anteriores (se existirem)
            const form = document.getElementById('bulkForm');
            const oldInputs = form.querySelectorAll('input[name="selected_ads[]"]');
            oldInputs.forEach(input => input.remove());

            // Adicionar IDs selecionados ao formulário
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_ads[]';
                input.value = checkbox.value;
                form.appendChild(input);
            });

            return true;
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
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
