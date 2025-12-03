<?php
// admin/index.php - Dashboard principal com indicadores do sistema.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$pageTitle = 'Painel administrativo - Site Carros';
$activeNav = 'dashboard';

$metrics = [
    'total_users' => (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'premium_users' => (int)db()->query('SELECT COUNT(*) FROM users WHERE is_premium = 1')->fetchColumn(),
    'blocked_users' => (int)db()->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetchColumn(),
    'pending_ads' => (int)db()->query("SELECT COUNT(*) FROM ads WHERE status = 'pending'")->fetchColumn(),
    'approved_ads' => (int)db()->query("SELECT COUNT(*) FROM ads WHERE status = 'approved'")->fetchColumn(),
    'rejected_ads' => (int)db()->query("SELECT COUNT(*) FROM ads WHERE status = 'rejected'")->fetchColumn(),
    'ads_month' => (int)db()->query('SELECT COUNT(*) FROM ads WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())')->fetchColumn(),
    'open_reports' => 0,
    'revenue_total' => 0.0,
    'revenue_month' => 0.0,
    'active_subscriptions' => 0,
];

try {
    $metrics['open_reports'] = (int)db()->query("SELECT COUNT(*) FROM ad_reports WHERE status IN ('open','under_review')")->fetchColumn();
} catch (Throwable $exception) {
    $metrics['open_reports'] = 0;
}

try {
    $metrics['revenue_total'] = (float)db()->query('SELECT COALESCE(SUM(COALESCE(ps.amount_paid, pp.price_decimal)),0) FROM premium_subscriptions ps JOIN premium_plans pp ON ps.plan_id = pp.id WHERE ps.expires_at IS NULL OR ps.expires_at > NOW()')->fetchColumn();
    $revenueMonthStmt = db()->prepare('SELECT COALESCE(SUM(COALESCE(ps.amount_paid, pp.price_decimal)),0) FROM premium_subscriptions ps JOIN premium_plans pp ON ps.plan_id = pp.id WHERE ps.started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $revenueMonthStmt->execute();
    $metrics['revenue_month'] = (float)$revenueMonthStmt->fetchColumn();
    $metrics['active_subscriptions'] = (int)db()->query('SELECT COUNT(*) FROM premium_subscriptions WHERE expires_at IS NULL OR expires_at > NOW()')->fetchColumn();
} catch (Throwable $exception) {
    $metrics['revenue_total'] = 0.0;
    $metrics['revenue_month'] = 0.0;
    $metrics['active_subscriptions'] = 0;
}

$topCities = [];
$latestLogs = [];
$latestSubscriptions = [];
$recentReports = [];

try {
    $topCitiesStmt = db()->query("SELECT city, state, COUNT(*) AS total FROM ads WHERE status = 'approved' GROUP BY city, state ORDER BY total DESC LIMIT 5");
    $topCities = $topCitiesStmt->fetchAll();
} catch (Throwable $exception) {
    $topCities = [];
}

try {
    $latestLogsStmt = db()->prepare('SELECT al.*, u.name AS user_name FROM audit_logs al LEFT JOIN users u ON u.id = al.actor_user_id ORDER BY al.created_at DESC LIMIT 8');
    $latestLogsStmt->execute();
    $latestLogs = $latestLogsStmt->fetchAll();
} catch (Throwable $exception) {
    $latestLogs = [];
}

try {
    $latestSubscriptionsStmt = db()->prepare('SELECT ps.*, u.name AS user_name, u.email, pp.name AS plan_name, COALESCE(ps.amount_paid, pp.price_decimal) AS amount_paid_display, pp.price_decimal AS plan_price
        FROM premium_subscriptions ps
        LEFT JOIN premium_plans pp ON pp.id = ps.plan_id
        JOIN users u ON u.id = ps.user_id
        ORDER BY ps.started_at DESC
        LIMIT 6');
    $latestSubscriptionsStmt->execute();
    $latestSubscriptions = $latestSubscriptionsStmt->fetchAll();
} catch (Throwable $exception) {
    $latestSubscriptions = [];
}

try {
    $recentReportsStmt = db()->prepare('SELECT ar.*, ads.title AS ad_title, ads.id AS ad_id, u.name AS reporter_name
        FROM ad_reports ar
        JOIN ads ON ads.id = ar.ad_id
        JOIN users u ON u.id = ar.reporter_user_id
        ORDER BY ar.created_at DESC
        LIMIT 6');
    $recentReportsStmt->execute();
    $recentReports = $recentReportsStmt->fetchAll();
} catch (Throwable $exception) {
    $recentReports = [];
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section style="margin:2rem 0;">
    <h1>Painel administrativo</h1>
    <p>Visao geral dos principais indicadores do marketplace.</p>

    <div class="cards-grid" style="margin-top:2rem;">
        <article class="card">
            <div class="card-content">
                <h3>Total de usuarios</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $metrics['total_users']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Usuarios premium</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $metrics['premium_users']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Usuarios bloqueados</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $metrics['blocked_users']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Anuncios pendentes</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $metrics['pending_ads']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Anuncios aprovados</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $metrics['approved_ads']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Anuncios rejeitados</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $metrics['rejected_ads']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Anuncios criados (mes)</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $metrics['ads_month']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Denuncias abertas</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $metrics['open_reports']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Receita (30 dias)</h3>
                <span class="price-tag" style="font-size:2rem;"><?= format_currency($metrics['revenue_month']); ?></span>
            </div>
        </article>
    </div>

    <div class="grid-2" style="margin-top:2.5rem;">
        <div class="form-card">
            <h2>Receita e assinaturas</h2>
            <ul style="margin-top:1rem; display:grid; gap:0.6rem;">
                <li><strong>Receita total:</strong> <?= format_currency($metrics['revenue_total']); ?></li>
                <li><strong>Receita ultimos 30 dias:</strong> <?= format_currency($metrics['revenue_month']); ?></li>
                <li><strong>Assinaturas ativas:</strong> <?= $metrics['active_subscriptions']; ?></li>
            </ul>
            <a href="/admin/faturamento.php" class="button button-admin action admin-card-button">Abrir painel de faturamento</a>
        </div>
        <div class="form-card">
            <h2>Acoes rapidas</h2>
            <div class="admin-quick-actions">
                <a href="/admin/anuncios_pendentes.php" class="button button-admin">Revisar anuncios pendentes</a>
                <a href="/admin/denuncias.php" class="button button-admin">Gerenciar denuncias</a>
                <a href="/admin/usuarios.php" class="button button-admin">Gerenciar usuarios</a>
                <a href="/admin/config.php" class="button button-admin">Ajustar configuracoes</a>
            </div>
        </div>
    </div>

    <div class="grid-2" style="margin-top:2.5rem;">
        <div class="form-card">
            <h2>Cidades com mais anuncios aprovados</h2>
            <ul style="margin-top:1rem; display:grid; gap:0.6rem;">
                <?php foreach ($topCities as $city): ?>
                    <li><?= e($city['city']); ?>/<?= e($city['state']); ?> — <?= (int)$city['total']; ?> anuncios</li>
                <?php endforeach; ?>
                <?php if (!$topCities): ?>
                    <li>Nenhum dado disponivel ainda.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="form-card">
            <h2>Ultimas acoes registradas</h2>
            <ul style="margin-top:1rem; display:grid; gap:0.6rem;">
                <?php foreach ($latestLogs as $log): ?>
                    <li>
                        <strong><?= e($log['action']); ?></strong> — <?= e($log['user_name'] ?? 'Sistema'); ?>
                        <br><small><?= format_date($log['created_at']); ?></small>
                    </li>
                <?php endforeach; ?>
                <?php if (!$latestLogs): ?>
                    <li>Ainda nao ha logs.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="grid-2" style="margin-top:2.5rem;">
        <div class="form-card">
            <h2>Denuncias recentes</h2>
            <ul style="margin-top:1rem; display:grid; gap:0.75rem;">
                <?php foreach ($recentReports as $report): ?>
                    <li>
                        <strong><?= e($report['reason']); ?></strong> em
                        <a href="/anuncios/ver.php?id=<?= (int)$report['ad_id']; ?>" class="link" target="_blank"><?= e($report['ad_title']); ?></a><br>
                        <small>Denunciante: <?= e($report['reporter_name']); ?> — <?= format_date($report['created_at']); ?> — Status: <?= e(strtoupper($report['status'])); ?></small>
                    </li>
                <?php endforeach; ?>
                <?php if (!$recentReports): ?>
                    <li>Nenhuma denuncia registrada.</li>
                <?php endif; ?>
            </ul>
            <a href="/admin/denuncias.php" class="button button-admin admin-card-button">Gerenciar denuncias</a>
        </div>
        <div class="form-card">
            <h2>Assinaturas premium recentes</h2>
            <ul style="margin-top:1rem; display:grid; gap:0.75rem;">
                <?php foreach ($latestSubscriptions as $subscription): ?>
                    <?php
                        $planLabel = $subscription['plan_name'] ?? ('Plano ' . ($subscription['plan_id'] ?? '?'));
                        $amountDisplay = isset($subscription['amount_paid_display']) ? (float)$subscription['amount_paid_display'] : (float)($subscription['plan_price'] ?? 0);
                    ?>
                    <li>
                        <strong><?= e($planLabel); ?></strong> - <?= format_currency($amountDisplay); ?><br>
                        <small><?= e($subscription['user_name']); ?> (<?= e($subscription['email']); ?>) em <?= format_date($subscription['started_at']); ?></small>
                    </li>
                <?php endforeach; ?>
                <?php if (!$latestSubscriptions): ?>
                    <li>Ainda nao ha assinaturas registradas.</li>
                <?php endif; ?>
            </ul>
            <a href="/admin/faturamento.php" class="button button-admin admin-card-button">Ver faturamento completo</a>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
