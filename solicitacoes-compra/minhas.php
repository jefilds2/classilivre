<?php
// solicitacoes-compra/minhas.php - Gestao das solicitacoes do usuario logado.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../propagandas/analytics/tracker.php';
require_once __DIR__ . '/helpers.php';

require_login();
ensure_user_not_blocked();
ensure_verified_email();

AnalyticsTracker::trackPageLoad('solicitacoes_compra_minhas');

$pdo = db();
if (!purchase_request_table_exists($pdo)) {
    flash_add('error', 'A tabela de solicitacoes de compra ainda nao foi criada.');
    redirect('/solicitacoes-compra/');
}

$user = current_user();
$userId = (int) $user['id'];
$pageTitle = 'Minhas Solicitacoes de Compra | Classi Livre Autos';
$deleteReasons = purchase_request_delete_reasons();
$closureTableExists = purchase_request_closure_log_table_exists($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalido. Recarregue a pagina e tente novamente.';
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    if ($action !== 'close_request') {
        $errors[] = 'Acao invalida.';
    }

    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId <= 0) {
        $errors[] = 'Solicitacao invalida.';
    }

    $reasonCode = trim((string) ($_POST['reason_code'] ?? ''));
    $details = trim((string) ($_POST['reason_details'] ?? ''));

    if (!array_key_exists($reasonCode, $deleteReasons)) {
        $errors[] = 'Selecione um motivo valido para exclusao.';
    }
    if ($reasonCode === 'other' && mb_strlen($details) < 5) {
        $errors[] = 'Descreva o motivo em pelo menos 5 caracteres.';
    }

    if (!$errors) {
        $findStmt = $pdo->prepare('
            SELECT id, user_id, title, status
            FROM purchase_requests
            WHERE id = :id AND user_id = :user_id
            LIMIT 1
        ');
        $findStmt->execute([
            ':id' => $requestId,
            ':user_id' => $userId,
        ]);
        $requestRow = $findStmt->fetch();

        if (!$requestRow) {
            $errors[] = 'Solicitacao nao encontrada.';
        } elseif ((string) $requestRow['status'] !== 'active') {
            $errors[] = 'A solicitacao ja esta encerrada.';
        } else {
            $updateStmt = $pdo->prepare('
                UPDATE purchase_requests
                SET status = "closed", updated_at = :updated_at, expires_at = :expires_at
                WHERE id = :id AND user_id = :user_id AND status = "active"
                LIMIT 1
            ');
            $now = now();
            $updateStmt->execute([
                ':updated_at' => $now,
                ':expires_at' => $now,
                ':id' => $requestId,
                ':user_id' => $userId,
            ]);

            if ($updateStmt->rowCount() === 0) {
                $errors[] = 'Nao foi possivel encerrar a solicitacao. Tente novamente.';
            } else {
                purchase_request_log_closure(
                    $pdo,
                    $requestId,
                    $userId,
                    $userId,
                    'user',
                    $reasonCode,
                    $details
                );

                record_audit($userId, 'purchase_request_closed_by_user', 'purchase_requests', $requestId, [
                    'reason_code' => $reasonCode,
                    'reason_label' => purchase_request_delete_reason_label($reasonCode),
                    'details' => purchase_request_trim_or_null($details),
                ]);

                flash_add('success', 'Solicitacao encerrada com sucesso.');
                redirect('/solicitacoes-compra/minhas.php');
            }
        }
    }
}

if ($errors) {
    foreach ($errors as $error) {
        flash_add('error', $error);
    }
    redirect('/solicitacoes-compra/minhas.php');
}

$requests = [];
if ($closureTableExists) {
    $listSql = '
        SELECT
            pr.*,
            cl.reason_code AS closure_reason_code,
            cl.reason_label AS closure_reason_label,
            cl.details AS closure_details,
            cl.created_at AS closure_created_at
        FROM purchase_requests pr
        LEFT JOIN purchase_request_closure_logs cl
            ON cl.id = (
                SELECT cl2.id
                FROM purchase_request_closure_logs cl2
                WHERE cl2.request_id = pr.id
                ORDER BY cl2.created_at DESC, cl2.id DESC
                LIMIT 1
            )
        WHERE pr.user_id = :user_id
        ORDER BY pr.created_at DESC
    ';
} else {
    $listSql = '
        SELECT pr.*
        FROM purchase_requests pr
        WHERE pr.user_id = :user_id
        ORDER BY pr.created_at DESC
    ';
}

$listStmt = $pdo->prepare($listSql);
$listStmt->execute([':user_id' => $userId]);
$requests = $listStmt->fetchAll();

$total = count($requests);
$activeCount = 0;
$closedCount = 0;
foreach ($requests as $request) {
    if ((string) ($request['status'] ?? '') === 'active') {
        $activeCount++;
    } elseif ((string) ($request['status'] ?? '') === 'closed') {
        $closedCount++;
    }
}

require __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-gradient-to-b from-orange-50 to-white">
    <div class="max-w-screen-2xl mx-auto px-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Minhas solicitacoes de compra</h1>
                <p class="text-gray-600 mt-1">Edite ou encerre suas solicitacoes com controle de motivo.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="/solicitacoes-compra/criar.php"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
                    <i class="fas fa-plus-circle mr-2"></i> Nova solicitacao
                </a>
                <a href="/solicitacoes-compra/"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-list mr-2"></i> Ver mural publico
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-xs uppercase text-gray-500">Total</p>
                <p class="text-2xl font-bold text-gray-800"><?= $total; ?></p>
            </div>
            <div class="bg-white border border-green-200 rounded-xl p-4">
                <p class="text-xs uppercase text-green-600">Ativas</p>
                <p class="text-2xl font-bold text-green-700"><?= $activeCount; ?></p>
            </div>
            <div class="bg-white border border-red-200 rounded-xl p-4">
                <p class="text-xs uppercase text-red-600">Encerradas</p>
                <p class="text-2xl font-bold text-red-700"><?= $closedCount; ?></p>
            </div>
        </div>

        <?php if (!$requests): ?>
            <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-10 text-center">
                <h2 class="text-xl font-semibold text-gray-800">Voce ainda nao criou solicitacoes</h2>
                <p class="text-gray-600 mt-2">Crie sua primeira solicitacao para receber ofertas.</p>
                <a href="/solicitacoes-compra/criar.php"
                    class="inline-flex items-center justify-center mt-5 px-5 py-3 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700">
                    Criar solicitacao
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <?php foreach ($requests as $request): ?>
                    <?php
                    $status = (string) ($request['status'] ?? '');
                    $isActive = $status === 'active';
                    $title = (string) ($request['title'] ?? 'Solicitacao');
                    $createdAtLabel = format_date((string) ($request['created_at'] ?? ''));
                    $cityState = trim((string) (($request['city'] ?? '') . ' / ' . ($request['state'] ?? '')), ' /');
                    $reasonLabel = purchase_request_delete_reason_label((string) ($request['closure_reason_code'] ?? ''));
                    $reasonDetails = trim((string) ($request['closure_details'] ?? ''));
                    ?>
                    <article class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-lg font-semibold text-gray-800"><?= e($title); ?></h2>
                                <?php if ($isActive): ?>
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        Ativa
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        Encerrada
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3 text-sm text-gray-600 space-y-1">
                                <p><strong>Veiculo:</strong> <?= e((string) ($request['brand'] ?? '')); ?> <?= e((string) ($request['model'] ?? '')); ?></p>
                                <p><strong>Local:</strong> <?= e($cityState !== '' ? $cityState : 'Nao informado'); ?></p>
                                <p><strong>Criada em:</strong> <?= e($createdAtLabel); ?></p>
                            </div>

                            <?php if (!$isActive): ?>
                                <div class="mt-4 bg-red-50 border border-red-100 rounded-lg p-3">
                                    <p class="text-sm text-red-700"><strong>Motivo do encerramento:</strong> <?= e($reasonLabel); ?></p>
                                    <?php if ($reasonDetails !== ''): ?>
                                        <p class="text-sm text-red-700 mt-1"><strong>Detalhes:</strong> <?= e($reasonDetails); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($isActive): ?>
                            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 flex flex-wrap gap-2">
                                <a href="/solicitacoes-compra/criar.php?id=<?= (int) $request['id']; ?>"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-amber-500 text-white font-semibold hover:bg-amber-600">
                                    <i class="fas fa-pen mr-2"></i> Editar
                                </a>
                                <button type="button"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700"
                                    data-close-trigger
                                    data-request-id="<?= (int) $request['id']; ?>"
                                    data-request-title="<?= e($title); ?>">
                                    <i class="fas fa-trash-alt mr-2"></i> Excluir
                                </button>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div id="closeRequestModal" class="fixed inset-0 hidden bg-black/60 z-50 items-end sm:items-center justify-center px-4">
    <div class="w-full max-w-xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 sm:p-6">
        <div class="flex items-start justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-800">Encerrar solicitacao</h3>
            <button type="button" id="closeModalButton" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <p class="text-sm text-gray-600 mt-2">
            Qual motivo da exclusao da solicitacao <strong id="closeRequestTitle"></strong>?
        </p>

        <form method="post" class="mt-4 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <input type="hidden" name="action" value="close_request">
            <input type="hidden" name="request_id" id="closeRequestId" value="">

            <div>
                <label for="reason_code" class="block text-sm font-semibold text-gray-700 mb-1">Motivo *</label>
                <select id="reason_code" name="reason_code" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                    <option value="">Selecione</option>
                    <?php foreach ($deleteReasons as $reasonCode => $reasonLabel): ?>
                        <option value="<?= e($reasonCode); ?>"><?= e($reasonLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="reason_details" class="block text-sm font-semibold text-gray-700 mb-1">Detalhes (opcional)</label>
                <textarea id="reason_details" name="reason_details" rows="3"
                    placeholder="Ex: vendi para loja da minha cidade..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" id="cancelCloseModal"
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
        const modal = document.getElementById('closeRequestModal');
        const requestIdInput = document.getElementById('closeRequestId');
        const requestTitleElement = document.getElementById('closeRequestTitle');
        const closeButton = document.getElementById('closeModalButton');
        const cancelButton = document.getElementById('cancelCloseModal');

        if (!modal || !requestIdInput || !requestTitleElement) {
            return;
        }

        function openModal(requestId, requestTitle) {
            requestIdInput.value = requestId;
            requestTitleElement.textContent = requestTitle || '';
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

        document.querySelectorAll('[data-close-trigger]').forEach((button) => {
            button.addEventListener('click', () => {
                openModal(button.getAttribute('data-request-id') || '', button.getAttribute('data-request-title') || '');
            });
        });

        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }
        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../propagandas/load_web.php'; ?>
<?php require __DIR__ . '/../propagandas/load_local.php'; ?>
<?php require __DIR__ . '/../propagandas/load_embed.php'; ?>

