<?php
// admin/faturamento.php - Painel financeiro com assinaturas premium e receita.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$pageTitle = 'Faturamento - Administracao';
$activeNav = 'billing';

$totals = [
    'revenue_total' => 0.0,
    'revenue_30_days' => 0.0,
    'subscriptions_total' => 0,
    'subscriptions_active' => 0,
];

try {
    $totals['revenue_total'] = (float)db()->query('SELECT COALESCE(SUM(COALESCE(ps.amount_paid, pp.price_decimal)),0) FROM premium_subscriptions ps JOIN premium_plans pp ON ps.plan_id = pp.id WHERE ps.expires_at IS NULL OR ps.expires_at > NOW()')->fetchColumn();
    $totals['subscriptions_total'] = (int)db()->query('SELECT COUNT(*) FROM premium_subscriptions')->fetchColumn();
    $totals['subscriptions_active'] = (int)db()->query('SELECT COUNT(*) FROM premium_subscriptions WHERE expires_at IS NULL OR expires_at > NOW()')->fetchColumn();
    $revenueStmt = db()->prepare('SELECT COALESCE(SUM(COALESCE(ps.amount_paid, pp.price_decimal)),0) FROM premium_subscriptions ps JOIN premium_plans pp ON ps.plan_id = pp.id WHERE ps.started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $revenueStmt->execute();
    $totals['revenue_30_days'] = (float)$revenueStmt->fetchColumn();
} catch (Throwable $exception) {
    $totals['revenue_total'] = 0.0;
    $totals['revenue_30_days'] = 0.0;
    $totals['subscriptions_total'] = 0;
    $totals['subscriptions_active'] = 0;
}

$plans = [];
try {
    $plansStmt = db()->prepare('SELECT 
            pp.id,
            pp.name,
            pp.price_decimal,
            pp.is_active,
            COUNT(ps.id) AS subscribers,
            SUM(COALESCE(ps.amount_paid, pp.price_decimal)) AS revenue
        FROM premium_plans pp
        LEFT JOIN premium_subscriptions ps ON ps.plan_id = pp.id
        GROUP BY pp.id, pp.name, pp.price_decimal, pp.is_active
        ORDER BY pp.id ASC
    ');
    $plansStmt->execute();
    $plans = $plansStmt->fetchAll();
} catch (Throwable $exception) {
    $plans = [];
}

$recentSubscriptions = [];
try {
$recentStmt = db()->prepare('SELECT ps.*, u.name AS user_name, u.email, pp.name AS plan_name, COALESCE(ps.amount_paid, pp.price_decimal) AS amount_paid_display, pp.price_decimal AS plan_price
        FROM premium_subscriptions ps
        LEFT JOIN premium_plans pp ON pp.id = ps.plan_id
        JOIN users u ON u.id = ps.user_id
        ORDER BY ps.started_at DESC
        LIMIT 20');
    $recentStmt->execute();
    $recentSubscriptions = $recentStmt->fetchAll();
} catch (Throwable $exception) {
    $recentSubscriptions = [];
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section style="margin:2rem 0;">
    <h1>Faturamento e assinaturas</h1>
    <p>Acompanhe a evolucao financeira do marketplace e os planos premium.</p>

    <div class="cards-grid" style="margin-top:2rem;">
        <article class="card">
            <div class="card-content">
                <h3>Receita total</h3>
                <span class="price-tag" style="font-size:2rem;"><?= format_currency($totals['revenue_total']); ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Receita 30 dias</h3>
                <span class="price-tag" style="font-size:2rem;"><?= format_currency($totals['revenue_30_days']); ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Assinaturas ativas</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $totals['subscriptions_active']; ?></span>
            </div>
        </article>
        <article class="card">
            <div class="card-content">
                <h3>Assinaturas totais</h3>
                <span class="price-tag" style="font-size:2rem;"><?= $totals['subscriptions_total']; ?></span>
            </div>
        </article>
    </div>

    <section class="form-card" style="margin-top:2.5rem;">
        <h2>Resumo por plano</h2>
        <div style="margin-top:1rem; overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Plano</th>
                        <th>Preco</th>
                        <th>Assinantes</th>
                        <th>Receita estimada</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?= e($plan['name']); ?></td>
                            <td><?= format_currency((float)$plan['price_decimal']); ?></td>
                            <td><?= (int)$plan['subscribers']; ?></td>
                            <td><?= format_currency((float)$plan['revenue']); ?></td>
                            <td><?= $plan['is_active'] ? 'Ativo' : 'Inativo'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$plans): ?>
                        <tr><td colspan="5">Nenhum plano cadastrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="form-card" style="margin-top:2.5rem;">
        <h2>Assinaturas recentes</h2>
        <div style="margin-top:1rem; overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Usuario</th>
                        <th>Plano</th>
                        <th>Validade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSubscriptions as $subscription): ?>
                        <?php
                            $planLabel = $subscription['plan_name'] ?? ('Plano ' . ($subscription['plan_id'] ?? '?'));
                            $amountDisplay = isset($subscription['amount_paid_display']) ? (float)$subscription['amount_paid_display'] : (float)($subscription['plan_price'] ?? 0);
                        ?>
                        <tr>
                            <td><?= format_date($subscription['started_at']); ?></td>
                            <td><?= e($subscription['user_name']); ?><br><small><?= e($subscription['email']); ?></small></td>
                            <td><?= e($planLabel); ?> - <?= format_currency($amountDisplay); ?></td>
                            <td><?= $subscription['expires_at'] ? format_date($subscription['expires_at']) : 'Sem data'; ?></td>
                            <td><?= ($subscription['expires_at'] === null || $subscription['expires_at'] >= date('Y-m-d H:i:s')) ? 'Ativa' : 'Expirada'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentSubscriptions): ?>
                        <tr><td colspan="5">Nenhuma assinatura registrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
