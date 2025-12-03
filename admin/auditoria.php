<?php
// admin/auditoria.php - Consulta aos registros de auditoria.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$pageTitle = 'Auditoria - Administracao';
$activeNav = 'audit';
$page = max(1, (int)(input_get('page') ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$total = (int)db()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = db()->prepare('SELECT al.*, u.name AS user_name
    FROM audit_logs al LEFT JOIN users u ON u.id = al.actor_user_id
    ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section style="margin:2rem 0;">
    <h1>Auditoria</h1>
    <p>Monitoramento das operacoes relevantes realizadas no sistema.</p>

    <div style="margin-top:1.5rem; overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Acao</th>
                    <th>Usuario</th>
                    <th>Entidade</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= format_date($log['created_at']); ?></td>
                        <td><?= e($log['action']); ?></td>
                        <td><?= e($log['user_name'] ?? 'Sistema'); ?></td>
                        <td><?= e($log['entity'] ?? '-'); ?> #<?= $log['entity_id'] ? (int)$log['entity_id'] : '-'; ?></td>
                        <td><code><?= e($log['meta'] ?? ''); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?>
                    <tr><td colspan="5">Nenhum registro disponivel.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i; ?>" class="<?= $i === $page ? 'active' : ''; ?>"><?= $i; ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
