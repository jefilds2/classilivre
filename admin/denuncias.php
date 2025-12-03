<?php
// admin/denuncias.php - Centro de tratamento de denuncias de anuncios.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$pageTitle = 'Denuncias de anuncios - Administracao';
$activeNav = 'reports';
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalido.';
    } else {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $notes = trim($_POST['admin_notes'] ?? '');

        $stmt = db()->prepare('SELECT * FROM ad_reports WHERE id = :id');
        $stmt->execute([':id' => $reportId]);
        $report = $stmt->fetch();

        if (!$report) {
            $errors[] = 'Denuncia nao encontrada.';
        } else {
            $now = now();
            $adminId = (int)current_user()['id'];

            if ($action === 'review') {
                $update = db()->prepare('UPDATE ad_reports SET status = "under_review", admin_notes = :notes, handled_by = :admin, updated_at = :updated_at WHERE id = :id');
                $update->execute([
                    ':notes' => $notes !== '' ? $notes : $report['admin_notes'],
                    ':admin' => $adminId,
                    ':updated_at' => $now,
                    ':id' => $reportId,
                ]);
                record_audit($adminId, 'report_under_review', 'ad_reports', $reportId);
                $success = 'Denuncia marcada como em analise.';
            } elseif ($action === 'resolve') {
                $update = db()->prepare('UPDATE ad_reports SET status = "resolved", admin_notes = :notes, handled_by = :admin, resolved_at = :resolved_at, updated_at = :updated_at WHERE id = :id');
                $update->execute([
                    ':notes' => $notes !== '' ? $notes : $report['admin_notes'],
                    ':admin' => $adminId,
                    ':resolved_at' => $now,
                    ':updated_at' => $now,
                    ':id' => $reportId,
                ]);
                record_audit($adminId, 'report_resolved', 'ad_reports', $reportId);
                $success = 'Denuncia marcada como resolvida.';
            } elseif ($action === 'dismiss') {
                $update = db()->prepare('UPDATE ad_reports SET status = "dismissed", admin_notes = :notes, handled_by = :admin, resolved_at = :resolved_at, updated_at = :updated_at WHERE id = :id');
                $update->execute([
                    ':notes' => $notes !== '' ? $notes : $report['admin_notes'],
                    ':admin' => $adminId,
                    ':resolved_at' => $now,
                    ':updated_at' => $now,
                    ':id' => $reportId,
                ]);
                record_audit($adminId, 'report_dismissed', 'ad_reports', $reportId);
                $success = 'Denuncia arquivada como improcedente.';
            } elseif ($action === 'reopen') {
                $update = db()->prepare('UPDATE ad_reports SET status = "open", admin_notes = :notes, handled_by = NULL, resolved_at = NULL, updated_at = :updated_at WHERE id = :id');
                $update->execute([
                    ':notes' => $notes !== '' ? $notes : $report['admin_notes'],
                    ':updated_at' => $now,
                    ':id' => $reportId,
                ]);
                record_audit($adminId, 'report_reopened', 'ad_reports', $reportId);
                $success = 'Denuncia reaberta.';
            }
        }
    }
}

$statusFilter = input_get('status') ?? 'open';
$allowedStatuses = ['open', 'under_review', 'resolved', 'dismissed', 'all'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'open';
}

$statusCountsRaw = [];
try {
    $statusCountsStmt = db()->query('SELECT status, COUNT(*) AS total FROM ad_reports GROUP BY status');
    $statusCountsRaw = $statusCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
} catch (Throwable $exception) {
    $statusCountsRaw = [];
}
$statusCounts = [
    'open' => (int)($statusCountsRaw['open'] ?? 0),
    'under_review' => (int)($statusCountsRaw['under_review'] ?? 0),
    'resolved' => (int)($statusCountsRaw['resolved'] ?? 0),
    'dismissed' => (int)($statusCountsRaw['dismissed'] ?? 0),
];

$conditions = [];
$params = [];
if ($statusFilter !== 'all') {
    $conditions[] = 'ar.status = :status';
    $params[':status'] = $statusFilter;
}
$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$reports = [];
try {
    $reportsStmt = db()->prepare("SELECT ar.*, ads.title AS ad_title, ads.id AS ad_id, users.name AS reporter_name, users.email AS reporter_email
        FROM ad_reports ar
        JOIN ads ON ads.id = ar.ad_id
        JOIN users ON users.id = ar.reporter_user_id
        $whereSql
        ORDER BY ar.created_at DESC
        LIMIT 50");
    $reportsStmt->execute($params);
    $reports = $reportsStmt->fetchAll();
} catch (Throwable $exception) {
    $reports = [];
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section style="margin:2rem 0;">
    <h1>Denuncias de anuncios</h1>
    <p>Centralize a triagem das denuncias enviadas pelos usuarios.</p>

    <div class="cards-grid" style="margin-top:2rem;">
        <article class="card">
            <div class="card-content">
                <h3>Abertas</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $statusCounts['open']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Em analise</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $statusCounts['under_review']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Resolvidas</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $statusCounts['resolved']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Arquivadas</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $statusCounts['dismissed']; ?></span>
            </div>
        </article>
    </div>

    <form method="get" class="search-card admin-filter-form" style="margin-top:1.5rem;">
        <div class="input-group" style="max-width:260px;">
            <label for="status">Filtrar por status</label>
            <select id="status" name="status">
                <option value="open" <?= $statusFilter === 'open' ? 'selected' : ''; ?>>Abertas</option>
                <option value="under_review" <?= $statusFilter === 'under_review' ? 'selected' : ''; ?>>Em analise</option>
                <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolvidas</option>
                <option value="dismissed" <?= $statusFilter === 'dismissed' ? 'selected' : ''; ?>>Arquivadas</option>
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : ''; ?>>Todas</option>
            </select>
        </div>
        <div class="admin-filter-actions">
            <button type="submit" class="button button-admin admin-filter-button">Aplicar filtro</button>
        </div>
    </form>

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

    <?php if (!$reports): ?>
        <div class="alert alert-success" style="margin-top:1.5rem;">Nenhuma denuncia encontrada para o filtro selecionado.</div>
    <?php endif; ?>

    <?php foreach ($reports as $report): ?>
        <article class="form-card" style="margin-top:2rem;">
            <div class="flex-between" style="align-items:flex-start;">
                <div>
                    <h2><?= e($report['reason']); ?></h2>
                    <p>Denunciante: <?= e($report['reporter_name']); ?> — <?= e($report['reporter_email']); ?></p>
                    <p>Anuncio: <a href="/anuncios/ver.php?id=<?= (int)$report['ad_id']; ?>" class="link" target="_blank" rel="noopener"><?= e($report['ad_title']); ?></a></p>
                    <p>Enviada em: <?= format_date($report['created_at']); ?> — Status: <?= e(strtoupper($report['status'])); ?></p>
                    <?php if (!empty($report['evidence_url'])): ?>
                        <p>Prova fornecida: <a href="<?= e($report['evidence_url']); ?>" target="_blank" rel="noopener">Ver evidencias</a></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($report['description'])): ?>
                <div class="alert alert-warning" style="margin-top:1rem;">
                    <strong>Relato do usuario:</strong><br>
                    <?= nl2br(e($report['description'])); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($report['admin_notes'])): ?>
                <div class="alert alert-info" style="margin-top:1rem;">
                    <strong>Notas internas:</strong><br>
                    <?= nl2br(e($report['admin_notes'])); ?>
                </div>
            <?php endif; ?>

            <form method="post" style="margin-top:1.5rem; display:grid; gap:1rem;">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="report_id" value="<?= (int)$report['id']; ?>">
                <div class="input-group">
                    <label for="admin_notes_<?= (int)$report['id']; ?>">Notas internas</label>
                    <textarea id="admin_notes_<?= (int)$report['id']; ?>" name="admin_notes" rows="3" placeholder="Adicione observacoes para outros moderadores"><?= e($report['admin_notes'] ?? ''); ?></textarea>
                </div>
                <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                    <button type="submit" name="action" value="review" class="button secondary">Marcar como em analise</button>
                    <button type="submit" name="action" value="resolve" class="button">Marcar como resolvida</button>
                    <button type="submit" name="action" value="dismiss" class="button secondary" style="background:#fff;color:#e63946;border:1px solid rgba(230,57,70,0.3);">Arquivar</button>
                    <?php if ($report['status'] !== 'open'): ?>
                        <button type="submit" name="action" value="reopen" class="button secondary" style="background:#fff;border:1px solid rgba(45,106,79,0.3);color:#2d6a4f;">Reabrir</button>
                    <?php endif; ?>
                </div>
            </form>
        </article>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
