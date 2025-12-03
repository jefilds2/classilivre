<?php

declare(strict_types=1);

/**
 * Painel Admin de Propaganda - Página Principal
 * Caminho: /propagandas/admin/index.php
 */

// Incluir sqlconnect ANTES de middleware (para evitar "headers already sent")
require_once __DIR__ . '/../../sqlconnect.php';

// Incluir middleware de autenticação do site
require_once __DIR__ . '/../../login_system/middleware.php';

// Verificar se é admin
require_admin();

$user = current_user();

// Carregar configuração
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/CampaignManager.php';

// Obter dados
$pdo = db();
$campaign_manager = new PropagandaCampaignManager($pdo);

// Obter estatísticas (com tratamento de erro)
$stats = [];
$campaigns = [];

try {
    $stats = $campaign_manager->getGeneralStats();
    $campaigns = $campaign_manager->list(['status' => 'ativa']);
} catch (Throwable $e) {
    // Se erro, verificar se é problema de tabelas não criadas
    error_log('Erro ao obter campanhas: ' . $e->getMessage());
    $stats = [
        'total_campanhas' => 0,
        'campanhas_ativas' => 0,
        'total_visualizacoes' => 0,
        'total_cliques' => 0,
        'ctr_geral' => 0,
    ];
    $campaigns = [];
}

// Títulos
$pageTitle = 'Gerenciar Propaganda - Classi Livre Autos';
$pageDescription = 'Gerencie campanhas de propaganda e acompanhe estatísticas';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription); ?>">
    
    <!-- Estilos do Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos customizados -->
    <link rel="stylesheet" href="/propagandas/admin/assets/css/style.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            <i class="fas fa-bullhorn text-amber-500 mr-2"></i>
                            Gerenciar Propaganda
                        </h1>
                        <p class="text-gray-600 mt-1">Plataforma de Campanhas Publicitárias</p>
                    </div>
                    <a href="/admin/index.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar ao Admin
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">

            <!-- Botões de Ação -->
            <div class="mb-8 flex gap-4 flex-wrap">
                <a href="/propagandas/admin/campanhas/criar.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-link mr-2"></i>
                    Nova Campanha Web
                </a>
                <a href="/propagandas/admin/campanhas/criar-local.php" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-store mr-2"></i>
                    Nova Campanha Local
                </a>
                <a href="/propagandas/admin/campanhas/criar-embed.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-code mr-2"></i>
                    Nova Campanha EMBED
                </a>
                <a href="/propagandas/admin/campanhas/listar.php" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-pause-circle mr-2"></i>
                    Pausar/Gerenciar
                </a>
                <a href="/propagandas/analytics/dashboard.php" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-chart-line mr-2"></i>
                    📊 MÉTRICAS
                </a>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total de Campanhas</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['total_campanhas']; ?></p>
                        </div>
                        <i class="fas fa-chart-bar text-gray-400 text-3xl"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Campanhas Ativas</p>
                            <p class="text-3xl font-bold text-green-600 mt-2"><?= $stats['campanhas_ativas']; ?></p>
                        </div>
                        <i class="fas fa-play-circle text-green-400 text-3xl"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total de Views</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2"><?= $stats['total_visualizacoes'] ?? 0; ?></p>
                        </div>
                        <i class="fas fa-eye text-blue-400 text-3xl"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total de Cliques</p>
                            <p class="text-3xl font-bold text-amber-600 mt-2"><?= $stats['total_cliques'] ?? 0; ?></p>
                            <p class="text-xs text-gray-500 mt-1">CTR: <?= $stats['ctr_geral'] ?? 0; ?>%</p>
                        </div>
                        <i class="fas fa-mouse-pointer text-amber-400 text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Estatísticas por Tipo -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow p-6 border-l-4 border-blue-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-700 font-semibold">🌐 Campanhas WEB</p>
                            <p class="text-3xl font-bold text-blue-900 mt-2"><?= $stats['campanhas_web'] ?? 0; ?></p>
                            <p class="text-xs text-blue-600 mt-1">Links com imagem/descrição</p>
                        </div>
                        <i class="fas fa-link text-blue-400 text-3xl opacity-50"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow p-6 border-l-4 border-purple-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-700 font-semibold">📱 Campanhas LOCAL</p>
                            <p class="text-3xl font-bold text-purple-900 mt-2"><?= $stats['campanhas_local'] ?? 0; ?></p>
                            <p class="text-xs text-purple-600 mt-1">Contato com phone/WhatsApp</p>
                        </div>
                        <i class="fas fa-store text-purple-400 text-3xl opacity-50"></i>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg shadow p-6 border-l-4 border-indigo-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-indigo-700 font-semibold">🎨 Campanhas EMBED</p>
                            <p class="text-3xl font-bold text-indigo-900 mt-2"><?= $stats['campanhas_embed'] ?? 0; ?></p>
                            <p class="text-xs text-indigo-600 mt-1">HTML customizado</p>
                        </div>
                        <i class="fas fa-code text-indigo-400 text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Tabela de Campanhas -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Campanhas Ativas</h2>
                </div>

                <?php if (empty($campaigns)): ?>
                    <div class="px-6 py-12 text-center">
                        <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-600">Nenhuma campanha criada ainda</p>
                        <a href="/propagandas/admin/campanhas/criar.php" class="text-green-600 hover:text-green-700 font-medium mt-4 inline-block">
                            Criar primeira campanha
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Título</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Views</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Cliques</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">CTR</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($campaigns as $campaign): ?>
                                    <?php
                                        $ctr = $campaign['total_visualizacoes'] > 0 
                                            ? round(($campaign['total_cliques'] / $campaign['total_visualizacoes']) * 100, 2)
                                            : 0;
                                    ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($campaign['title']); ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars(substr($campaign['description'], 0, 50)); ?>...</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $campaign['status'] === 'ativa' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?= ucfirst($campaign['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= number_format($campaign['total_visualizacoes'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= number_format($campaign['total_cliques'], 0, ',', '.'); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= $ctr; ?>%
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-medium">
                                            <?php
                                                // Redirecionar para editor correto baseado em ad_type
                                                $adType = (int)($campaign['ad_type'] ?? 1);
                                                $editorFile = ($adType === 2) ? 'editar-local.php' : 'editar.php';
                                            ?>
                                            <a href="/propagandas/admin/campanhas/<?= $editorFile; ?>?id=<?= $campaign['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-4">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/propagandas/admin/campanhas/deletar.php?id=<?= $campaign['id']; ?>" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="/propagandas/admin/assets/js/script.js"></script>
</body>
</html>
