<?php
// admin/partials/admin_nav.php - Navegacao centralizada do portal administrativo.

declare(strict_types=1);

if (!function_exists('current_user')) {
    require_once __DIR__ . '/../../login_system/middleware.php';
}

$activeNav = $activeNav ?? '';

$adminNavItems = [
    'dashboard' => [
        'label' => 'Visao geral',
        'icon' => 'fa-chart-line',
        'href' => '/admin/index.php',
    ],
    'pending-ads' => [
        'label' => 'Anuncios pendentes',
        'icon' => 'fa-clipboard-check',
        'href' => '/admin/anuncios_pendentes.php',
    ],
    'all-ads' => [
        'label' => 'Todos anuncios',
        'icon' => 'fa-car',
        'href' => '/admin/anuncios_todos.php',
    ],
    'services' => [
        'label' => 'Serviços',
        'icon' => 'fa-briefcase',
        'href' => '/admin/servicos.php',
    ],
    'users' => [
        'label' => 'Usuarios',
        'icon' => 'fa-users',
        'href' => '/admin/usuarios.php',
    ],
    'reports' => [
        'label' => 'Denuncias',
        'icon' => 'fa-triangle-exclamation',
        'href' => '/admin/denuncias.php',
    ],
    'billing' => [
        'label' => 'Faturamento',
        'icon' => 'fa-sack-dollar',
        'href' => '/admin/faturamento.php',
    ],
    'moedas' => [
        'label' => 'Moedas CLivre',
        'icon' => 'fa-coins',
        'href' => '/admin/moedas.php',
    ],
    'audit' => [
        'label' => 'Auditoria',
        'icon' => 'fa-scroll',
        'href' => '/admin/auditoria.php',
    ],
    'prices' => [
        'label' => 'Preços',
        'icon' => 'fa-dollar-sign',
        'href' => '/admin/precos.php',
    ],
    'propaganda' => [
        'label' => 'Propagandas',
        'icon' => 'fa-bullhorn',
        'href' => '/propagandas/admin/index.php',
    ],
    'settings' => [
        'label' => 'Configuracoes',
        'icon' => 'fa-sliders',
        'href' => '/admin/config.php',
    ],
];
?>
<nav class="admin-nav bg-white border border-gray-100 rounded-2xl shadow-sm mt-6 mb-8">
    <ul class="flex flex-wrap gap-2 px-4 py-3">
        <?php foreach ($adminNavItems as $key => $item): ?>
            <?php $isActive = $activeNav === $key; ?>
            <li>
                <a
                    href="<?= e($item['href']); ?>"
                    class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $isActive ? 'bg-amber-500 text-green-900 shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>"
                >
                    <i class="fas <?= e($item['icon']); ?> mr-2"></i>
                    <?= e($item['label']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
