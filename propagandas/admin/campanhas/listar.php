<?php
/**
 * Listar e Gerenciar TODAS as Campanhas
 * Localização: /propagandas/admin/campanhas/listar.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../login_system/middleware.php';
require_admin();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/CampaignManager.php';

$pdo = db();
$manager = new PropagandaCampaignManager($pdo);
$user = current_user();

$msg = $_GET['msg'] ?? null;
$action = $_GET['action'] ?? null;
$campaign_id = (int)($_GET['id'] ?? 0);

// Processar ações
if ($action && $campaign_id > 0) {
    $msg = 'erro'; // valor padrão
    
    try {
        switch ($action) {
            case 'pausar':
                $stmt = $pdo->prepare("UPDATE propagandas_campanhas SET status = 'inativa' WHERE id = ?");
                if ($stmt->execute([$campaign_id])) {
                    $msg = 'pausada';
                }
                break;
            
            case 'ativar':
                $stmt = $pdo->prepare("UPDATE propagandas_campanhas SET status = 'ativa' WHERE id = ?");
                if ($stmt->execute([$campaign_id])) {
                    $msg = 'ativada';
                }
                break;
            
            case 'deletar':
                $stmt = $pdo->prepare("DELETE FROM propagandas_campanhas WHERE id = ?");
                if ($stmt->execute([$campaign_id])) {
                    $msg = 'deletada';
                }
                break;
        }
    } catch (Exception $e) {
        error_log('Erro ao processar ação: ' . $e->getMessage());
        $msg = 'erro';
    }
    
    header('Location: /propagandas/admin/campanhas/listar.php?msg=' . $msg);
    exit;
}

// Listar todas as campanhas
$campaigns = $manager->list();

// Categorizar por tipo
$web = array_filter($campaigns, fn($c) => (int)$c['ad_type'] === 1);
$local = array_filter($campaigns, fn($c) => (int)$c['ad_type'] === 2);
$embed = array_filter($campaigns, fn($c) => (int)$c['ad_type'] === 3);

$tipos = [
    1 => ['nome' => '🌐 WEB', 'cor' => 'blue', 'icon' => 'fa-link', 'campanhas' => $web],
    2 => ['nome' => '📱 LOCAL', 'cor' => 'purple', 'icon' => 'fa-store', 'campanhas' => $local],
    3 => ['nome' => '🎨 EMBED', 'cor' => 'indigo', 'icon' => 'fa-code', 'campanhas' => $embed],
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Campanhas - Sistema de Propaganda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        div[data-color="blue"] { --color-bg: #eff6ff; --color-border: #1e40af; --color-text: #1e3a8a; }
        div[data-color="purple"] { --color-bg: #faf5ff; --color-border: #7c3aed; --color-text: #6b21a8; }
        div[data-color="indigo"] { --color-bg: #eef2ff; --color-border: #4f46e5; --color-text: #312e81; }
    </style>
</head>
<body class="bg-gray-50">
<div class="min-h-screen">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold"><i class="fas fa-list mr-2"></i>Gerenciar Campanhas</h1>
                <p class="text-gray-600 text-sm">Pausar, ativar ou deletar campanhas</p>
            </div>
            <a href="/propagandas/admin/index.php" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">
                ← Voltar
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($msg === 'pausada'): ?>
            <div class="mb-6 p-4 bg-yellow-100 text-yellow-800 rounded border border-yellow-300">
                <i class="fas fa-pause-circle mr-2"></i>Campanha pausada com sucesso!
            </div>
        <?php elseif ($msg === 'ativada'): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-800 rounded border border-green-300">
                <i class="fas fa-check-circle mr-2"></i>Campanha ativada com sucesso!
            </div>
        <?php elseif ($msg === 'deletada'): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-800 rounded border border-red-300">
                <i class="fas fa-trash mr-2"></i>Campanha deletada com sucesso!
            </div>
        <?php elseif ($msg === 'erro'): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-800 rounded border border-red-300">
                <i class="fas fa-exclamation-circle mr-2"></i>Erro ao processar ação
            </div>
        <?php endif; ?>

        <?php if (empty($campaigns)): ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-600 text-lg">Nenhuma campanha criada</p>
                <a href="/propagandas/admin/index.php" class="text-blue-600 hover:text-blue-700 mt-4 inline-block">
                    Criar primeira campanha →
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($tipos as $tipo_id => $tipo_info): ?>
                <?php if (!empty($tipo_info['campanhas'])): ?>
                    <div class="mb-8">
                        <div class="border-l-4 p-4 mb-4 rounded" style="border-color: var(--color-border); background-color: var(--color-bg);" data-color="<?= $tipo_info['cor']; ?>">
                            <h2 class="text-xl font-bold" style="color: var(--color-text);">
                                <i class="fas <?= $tipo_info['icon']; ?> mr-2"></i>
                                <?= $tipo_info['nome']; ?> (<?= count($tipo_info['campanhas']); ?>)
                            </h2>
                        </div>

                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-100 border-b">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Título</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Criado em</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Views</th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <?php foreach ($tipo_info['campanhas'] as $campaign): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4">
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($campaign['title']); ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars(substr($campaign['description'] ?? '', 0, 40)); ?>...</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php if ($campaign['status'] === 'ativa'): ?>
                                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                                            ✅ Ativa
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                                                            ⏸️ Inativa
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <?= date('d/m/Y H:i', strtotime($campaign['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <?= $campaign['total_visualizacoes'] ?? 0; ?>
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="flex justify-end gap-2">
                                                        <?php if ($campaign['status'] === 'ativa'): ?>
                                                            <a href="?action=pausar&id=<?= $campaign['id']; ?>" class="px-3 py-1 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600 transition" title="Pausar campanha">
                                                                <i class="fas fa-pause"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="?action=ativar&id=<?= $campaign['id']; ?>" class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 transition" title="Ativar campanha">
                                                                <i class="fas fa-play"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php 
                                                        $ad_type = (int)$campaign['ad_type'];
                                                        $edit_url = 'editar.php';
                                                        if ($ad_type === 2) {
                                                            $edit_url = 'editar-local.php';
                                                        } elseif ($ad_type === 3) {
                                                            $edit_url = 'editar-embed.php';
                                                        }
                                                        $full_edit_url = "/propagandas/admin/campanhas/{$edit_url}?id={$campaign['id']}";
                                                        error_log('DEBUG LISTAR: Campanha ID=' . $campaign['id'] . ' ad_type=' . $ad_type . ' (type=' . gettype($campaign['ad_type']) . ') -> URL=' . $full_edit_url);
                                                        ?>
                                                        <a href="<?= $full_edit_url; ?>" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        <a href="?action=deletar&id=<?= $campaign['id']; ?>" class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition" onclick="return confirm('Tem certeza que deseja deletar?')" title="Deletar">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
