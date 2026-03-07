<?php
// admin/solicitacoes_compra.php - Dashboard administrativo das solicitacoes de compra.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../solicitacoes-compra/helpers.php';

require_admin();

$pdo = db();
$user = current_user();
$adminId = (int) ($user['id'] ?? 0);
$activeNav = 'purchase-requests';
$pageTitle = 'Admin - Solicitacoes de Compra | Classi Livre Autos';

$tableExists = purchase_request_table_exists($pdo);
$closureTableExists = purchase_request_closure_log_table_exists($pdo);

$adminReasonOptions = purchase_request_delete_reasons();
$adminReasonOptions['policy_violation'] = 'Violacao de politica';
$adminReasonOptions['duplicate'] = 'Solicitacao duplicada';
$adminReasonOptions['admin_cleanup'] = 'Limpeza administrativa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    if (!$tableExists) {
        $errors[] = 'Tabela de solicitacoes nao encontrada.';
    }

    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalido.';
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    if ($action !== 'close_request_admin') {
        $errors[] = 'Acao invalida.';
    }

    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId <= 0) {
        $errors[] = 'Solicitacao invalida.';
    }

    $reasonCode = trim((string) ($_POST['reason_code'] ?? ''));
    $details = trim((string) ($_POST['reason_details'] ?? ''));
    if (!array_key_exists($reasonCode, $adminReasonOptions)) {
        $errors[] = 'Selecione um motivo valido.';
    }
    if ($reasonCode === 'other' && mb_strlen($details) < 5) {
        $errors[] = 'Detalhe o motivo com ao menos 5 caracteres.';
    }

    if (!$errors) {
        $findStmt = $pdo->prepare('
            SELECT id, user_id, status, title
            FROM purchase_requests
            WHERE id = :id
            LIMIT 1
        ');
        $findStmt->execute([':id' => $requestId]);
        $requestRow = $findStmt->fetch();

        if (!$requestRow) {
            $errors[] = 'Solicitacao nao encontrada.';
        } elseif ((string) $requestRow['status'] !== 'active') {
            $errors[] = 'A solicitacao ja esta encerrada.';
        } else {
            $now = now();
            $updateStmt = $pdo->prepare('
                UPDATE purchase_requests
                SET status = "closed", updated_at = :updated_at, expires_at = :expires_at
                WHERE id = :id AND status = "active"
                LIMIT 1
            ');
            $updateStmt->execute([
                ':updated_at' => $now,
                ':expires_at' => $now,
                ':id' => $requestId,
            ]);

            if ($updateStmt->rowCount() > 0) {
                purchase_request_log_closure(
                    $pdo,
                    $requestId,
                    (int) $requestRow['user_id'],
                    $adminId > 0 ? $adminId : null,
                    'admin',
                    $reasonCode,
                    $details,
                    (string) ($adminReasonOptions[$reasonCode] ?? $reasonCode)
                );

                record_audit($adminId > 0 ? $adminId : null, 'purchase_request_closed_by_admin', 'purchase_requests', $requestId, [
                    'reason_code' => $reasonCode,
                    'reason_label' => purchase_request_delete_reason_label($reasonCode),
                    'details' => purchase_request_trim_or_null($details),
                ]);

                flash_add('success', 'Solicitacao encerrada pelo admin.');
            } else {
                $errors[] = 'Nao foi possivel encerrar a solicitacao.';
            }
        }
    }

    foreach ($errors as $error) {
        flash_add('error', $error);
    }

    $redirectQuery = [];
    $status = input_get('status', 'all');
    $q = input_get('q', '');
    if ($status !== null && $status !== '') {
        $redirectQuery['status'] = $status;
    }
    if ($q !== null && $q !== '') {
        $redirectQuery['q'] = $q;
    }
    $redirectUrl = '/admin/solicitacoes_compra.php' . ($redirectQuery ? '?' . http_build_query($redirectQuery) : '');
    redirect($redirectUrl);
}

$metrics = [
    'total' => 0,
    'active' => 0,
    'closed' => 0,
    'created_last_30_days' => 0,
    'closed_last_30_days' => 0,
];

$reasonStats = [];
$closureLogs = [];
$requests = [];
$totalRows = 0;
$totalPages = 1;
$page = max(1, (int) input_get('page', '1'));
$perPage = 20;

$statusFilter = trim((string) input_get('status', 'all'));
if (!in_array($statusFilter, ['all', 'active', 'closed'], true)) {
    $statusFilter = 'all';
}
$searchFilter = trim((string) input_get('q', ''));

if ($tableExists) {
    $metrics['total'] = (int) $pdo->query('SELECT COUNT(*) FROM purchase_requests')->fetchColumn();
    $metrics['active'] = (int) $pdo->query('SELECT COUNT(*) FROM purchase_requests WHERE status = "active"')->fetchColumn();
    $metrics['closed'] = (int) $pdo->query('SELECT COUNT(*) FROM purchase_requests WHERE status = "closed"')->fetchColumn();
    $metrics['created_last_30_days'] = (int) $pdo->query('
        SELECT COUNT(*) FROM purchase_requests
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ')->fetchColumn();

    if ($closureTableExists) {
        $metrics['closed_last_30_days'] = (int) $pdo->query('
            SELECT COUNT(*) FROM purchase_request_closure_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ')->fetchColumn();

        $reasonStmt = $pdo->query('
            SELECT reason_code, COALESCE(reason_label, reason_code) AS reason_label, COUNT(*) AS total
            FROM purchase_request_closure_logs
            GROUP BY reason_code, reason_label
            ORDER BY total DESC
            LIMIT 10
        ');
        $reasonStats = $reasonStmt->fetchAll();

        $logsStmt = $pdo->query('
            SELECT
                l.*,
                a.name AS actor_name,
                o.name AS owner_name,
                pr.title AS request_title
            FROM purchase_request_closure_logs l
            LEFT JOIN users a ON a.id = l.actor_user_id
            LEFT JOIN users o ON o.id = l.owner_user_id
            LEFT JOIN purchase_requests pr ON pr.id = l.request_id
            ORDER BY l.created_at DESC, l.id DESC
            LIMIT 12
        ');
        $closureLogs = $logsStmt->fetchAll();
    }

    $whereParts = ['1=1'];
    $params = [];

    if ($statusFilter !== 'all') {
        $whereParts[] = 'pr.status = :status';
        $params[':status'] = $statusFilter;
    }
    if ($searchFilter !== '') {
        $searchValue = '%' . $searchFilter . '%';
        $whereParts[] = '(
            pr.title LIKE :search_title
            OR pr.brand LIKE :search_brand
            OR pr.model LIKE :search_model
            OR pr.city LIKE :search_city
            OR u.name LIKE :search_owner
            OR u.email LIKE :search_email
        )';
        $params[':search_title'] = $searchValue;
        $params[':search_brand'] = $searchValue;
        $params[':search_model'] = $searchValue;
        $params[':search_city'] = $searchValue;
        $params[':search_owner'] = $searchValue;
        $params[':search_email'] = $searchValue;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM purchase_requests pr
        JOIN users u ON u.id = pr.user_id
        $whereSql
    ");
    $countStmt->execute($params);
    $totalRows = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    if ($closureTableExists) {
        $listSql = "
            SELECT
                pr.*,
                u.name AS owner_name,
                u.email AS owner_email,
                cl.reason_code AS closure_reason_code,
                cl.reason_label AS closure_reason_label,
                cl.details AS closure_details,
                cl.source AS closure_source,
                cl.created_at AS closure_created_at
            FROM purchase_requests pr
            JOIN users u ON u.id = pr.user_id
            LEFT JOIN purchase_request_closure_logs cl
                ON cl.id = (
                    SELECT cl2.id
                    FROM purchase_request_closure_logs cl2
                    WHERE cl2.request_id = pr.id
                    ORDER BY cl2.created_at DESC, cl2.id DESC
                    LIMIT 1
                )
            $whereSql
            ORDER BY pr.created_at DESC, pr.id DESC
            LIMIT :limit OFFSET :offset
        ";
    } else {
        $listSql = "
            SELECT
                pr.*,
                u.name AS owner_name,
                u.email AS owner_email
            FROM purchase_requests pr
            JOIN users u ON u.id = pr.user_id
            $whereSql
            ORDER BY pr.created_at DESC, pr.id DESC
            LIMIT :limit OFFSET :offset
        ";
    }

    $listStmt = $pdo->prepare($listSql);
    foreach ($params as $key => $value) {
        $listStmt->bindValue($key, $value);
    }
    $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStmt->execute();
    $requests = $listStmt->fetchAll();
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section class="mb-10">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard de solicitacoes de compra</h1>
            <p class="text-gray-600">Painel administrativo para acompanhar e moderar solicitacoes de compra.</p>
        </div>
        <a href="/solicitacoes-compra/"
            class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 text-white font-semibold hover:bg-black">
            <i class="fas fa-arrow-up-right-from-square mr-2"></i> Abrir vitrine publica
        </a>
    </div>

    <?php if (!$tableExists): ?>
        <div class="mt-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4">
            Tabela de solicitacoes de compra nao encontrada. Execute a migration 011.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mt-6">
            <article class="bg-white border rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500">Total</p>
                <p class="text-2xl font-bold text-gray-800"><?= $metrics['total']; ?></p>
            </article>
            <article class="bg-white border border-green-200 rounded-xl p-4">
                <p class="text-xs uppercase text-green-600">Ativas</p>
                <p class="text-2xl font-bold text-green-700"><?= $metrics['active']; ?></p>
            </article>
            <article class="bg-white border border-red-200 rounded-xl p-4">
                <p class="text-xs uppercase text-red-600">Encerradas</p>
                <p class="text-2xl font-bold text-red-700"><?= $metrics['closed']; ?></p>
            </article>
            <article class="bg-white border rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500">Criadas (30 dias)</p>
                <p class="text-2xl font-bold text-gray-800"><?= $metrics['created_last_30_days']; ?></p>
            </article>
            <article class="bg-white border rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500">Encerradas (30 dias)</p>
                <p class="text-2xl font-bold text-gray-800"><?= $metrics['closed_last_30_days']; ?></p>
            </article>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-6">
            <div class="xl:col-span-2 bg-white border rounded-xl p-4">
                <form method="get" action="/admin/solicitacoes_compra.php" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label for="q" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input id="q" name="q" value="<?= e($searchFilter); ?>" placeholder="titulo, marca, cidade, usuario..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="all" <?= $statusFilter === 'all' ? 'selected' : ''; ?>>Todos</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : ''; ?>>Ativas</option>
                            <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : ''; ?>>Encerradas</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 rounded-lg bg-amber-500 text-green-900 font-semibold hover:bg-amber-600">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white border rounded-xl p-4">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Motivos de encerramento</h2>
                <?php if (!$reasonStats): ?>
                    <p class="text-sm text-gray-500">Sem dados ainda.</p>
                <?php else: ?>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <?php foreach ($reasonStats as $reason): ?>
                            <li class="flex items-center justify-between">
                                <span><?= e((string) ($reason['reason_label'] ?? $reason['reason_code'])); ?></span>
                                <strong><?= (int) ($reason['total'] ?? 0); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-4 mt-6 overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="py-2 pr-3">ID</th>
                        <th class="py-2 pr-3">Solicitacao</th>
                        <th class="py-2 pr-3">Proprietario</th>
                        <th class="py-2 pr-3">Local</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Motivo encerramento</th>
                        <th class="py-2 pr-3">Acao</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$requests): ?>
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-500">Nenhuma solicitacao encontrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <?php $isActive = (string) ($request['status'] ?? '') === 'active'; ?>
                            <tr class="border-b align-top">
                                <td class="py-3 pr-3 font-semibold text-gray-700">#<?= (int) $request['id']; ?></td>
                                <td class="py-3 pr-3">
                                    <div class="font-semibold text-gray-800"><?= e((string) $request['title']); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?= e((string) ($request['brand'] ?? '')); ?> <?= e((string) ($request['model'] ?? '')); ?>
                                        • <?= e(format_date((string) ($request['created_at'] ?? ''))); ?>
                                    </div>
                                </td>
                                <td class="py-3 pr-3">
                                    <div class="text-gray-800"><?= e((string) $request['owner_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?= e((string) $request['owner_email']); ?></div>
                                </td>
                                <td class="py-3 pr-3 text-gray-700">
                                    <?= e((string) ($request['city'] ?? '')); ?> / <?= e((string) ($request['state'] ?? '')); ?>
                                </td>
                                <td class="py-3 pr-3">
                                    <?php if ($isActive): ?>
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Ativa</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Encerrada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 pr-3 text-gray-700">
                                    <?php if (!$isActive): ?>
                                        <div class="text-sm">
                                            <?= e(purchase_request_delete_reason_label((string) ($request['closure_reason_code'] ?? ''))); ?>
                                        </div>
                                        <?php if (!empty($request['closure_details'])): ?>
                                            <div class="text-xs text-gray-500 mt-1"><?= e((string) $request['closure_details']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 pr-3">
                                    <?php if ($isActive): ?>
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700"
                                            data-admin-close-trigger
                                            data-request-id="<?= (int) $request['id']; ?>"
                                            data-request-title="<?= e((string) $request['title']); ?>">
                                            Excluir
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Sem acao</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="mt-4 flex flex-wrap gap-2 justify-center">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php
                        $query = ['page' => $p];
                        if ($statusFilter !== 'all') {
                            $query['status'] = $statusFilter;
                        }
                        if ($searchFilter !== '') {
                            $query['q'] = $searchFilter;
                        }
                        ?>
                        <a href="/admin/solicitacoes_compra.php?<?= e(http_build_query($query)); ?>"
                            class="px-3 py-1.5 rounded-lg border <?= $p === $page
                                ? 'bg-amber-500 text-green-900 border-amber-500'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                            <?= $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white border rounded-xl p-4 mt-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Ultimos encerramentos</h2>
            <?php if (!$closureLogs): ?>
                <p class="text-sm text-gray-500">Sem registros de encerramento.</p>
            <?php else: ?>
                <ul class="space-y-2 text-sm text-gray-700">
                    <?php foreach ($closureLogs as $log): ?>
                        <li class="border-b border-gray-100 pb-2">
                            <strong><?= e((string) ($log['request_title'] ?? ('Solicitacao #' . (int) $log['request_id']))); ?></strong>
                            — <?= e((string) ($log['owner_name'] ?? 'Sem dono')); ?>
                            <br>
                            <span class="text-xs text-gray-500">
                                Motivo: <?= e((string) ($log['reason_label'] ?? $log['reason_code'])); ?>
                                • Origem: <?= e((string) ($log['source'] ?? '-')); ?>
                                • Data: <?= e(format_date((string) ($log['created_at'] ?? ''))); ?>
                                <?php if (!empty($log['actor_name'])): ?>
                                    • Ator: <?= e((string) $log['actor_name']); ?>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<div id="adminCloseRequestModal" class="fixed inset-0 hidden bg-black/60 z-50 items-end sm:items-center justify-center px-4">
    <div class="w-full max-w-xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 sm:p-6">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-800">Excluir solicitacao (admin)</h3>
            <button type="button" id="adminCloseModalButton" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <p class="text-sm text-gray-600 mt-2">
            Informe o motivo da exclusao da solicitacao <strong id="adminCloseRequestTitle"></strong>.
        </p>

        <form method="post" class="mt-4 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <input type="hidden" name="action" value="close_request_admin">
            <input type="hidden" name="request_id" id="adminCloseRequestId" value="">

            <div>
                <label for="admin_reason_code" class="block text-sm font-semibold text-gray-700 mb-1">Motivo *</label>
                <select id="admin_reason_code" name="reason_code" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                    <option value="">Selecione</option>
                    <?php foreach ($adminReasonOptions as $reasonCode => $reasonLabel): ?>
                        <option value="<?= e($reasonCode); ?>"><?= e($reasonLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="admin_reason_details" class="block text-sm font-semibold text-gray-700 mb-1">Detalhes (opcional)</label>
                <textarea id="admin_reason_details" name="reason_details" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" id="adminCancelCloseModal"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
                    Confirmar exclusao
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('adminCloseRequestModal');
        const requestIdInput = document.getElementById('adminCloseRequestId');
        const requestTitleElement = document.getElementById('adminCloseRequestTitle');
        const closeButton = document.getElementById('adminCloseModalButton');
        const cancelButton = document.getElementById('adminCancelCloseModal');

        if (!modal || !requestIdInput || !requestTitleElement) return;

        function openModal(requestId, title) {
            requestIdInput.value = requestId;
            requestTitleElement.textContent = title || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            requestIdInput.value = '';
            requestTitleElement.textContent = '';
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-admin-close-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                openModal(button.getAttribute('data-request-id') || '', button.getAttribute('data-request-title') || '');
            });
        });

        if (closeButton) closeButton.addEventListener('click', closeModal);
        if (cancelButton) cancelButton.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    })();
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
