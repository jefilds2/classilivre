<?php
/**
 * Listar Campanhas Locais - Gerenciamento
 * Localização: /propagandas/admin/campanhas/listar-local.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../login_system/middleware.php';
require_admin();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/CampaignManager.php';

$pdo = db();
$campaign_manager = new PropagandaCampaignManager($pdo);
$user = current_user();

$action = $_GET['action'] ?? null;
$campaign_id = (int)($_GET['id'] ?? 0);

// Deletar campanha
if ($action === 'delete' && $campaign_id > 0) {
    try {
        $campaign = $campaign_manager->getById($campaign_id);
        if (!$campaign || (int)$campaign['ad_type'] !== 2) {
            header('Location: /propagandas/admin/campanhas/listar-local.php?msg=nao-encontrada');
            exit;
        }
        
        // Deletar campanha
        if ($campaign_manager->delete($campaign_id)) {
            header('Location: /propagandas/admin/campanhas/listar-local.php?msg=deletada');
            exit;
        } else {
            header('Location: /propagandas/admin/campanhas/listar-local.php?msg=erro');
            exit;
        }
    } catch (Throwable $e) {
        error_log('Erro ao deletar: ' . $e->getMessage());
        header('Location: /propagandas/admin/campanhas/listar-local.php?msg=erro');
        exit;
    }
}

// Listar campanhas locais
$campaigns = $campaign_manager->list(['ad_type' => 2]);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campanhas Locais - Sistema de Propaganda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-gradient-to-r from-purple-600 to-pink-600 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            <i class="fas fa-list mr-3"></i>Campanhas Locais
                        </h1>
                        <p class="text-purple-100 mt-1">Gerenciar suas propagandas de empresa</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="/propagandas/admin/campanhas/criar-local.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>Nova Campanha Local
                        </a>
                        <a href="/propagandas/admin/index.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-arrow-left mr-2"></i>Voltar
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'deletada'): ?>
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                        <p class="text-green-800"><i class="fas fa-check mr-2"></i>Campanha deletada com sucesso!</p>
                    </div>
                <?php elseif ($_GET['msg'] === 'erro'): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                        <p class="text-red-800"><i class="fas fa-times mr-2"></i>Erro ao deletar campanha. Tente novamente.</p>
                    </div>
                <?php elseif ($_GET['msg'] === 'nao-encontrada'): ?>
                    <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                        <p class="text-yellow-800"><i class="fas fa-exclamation mr-2"></i>Campanha não encontrada.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (empty($campaigns)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-700 mb-2">Nenhuma campanha local</h2>
                    <p class="text-gray-600 mb-6">Você ainda não criou nenhuma campanha local.</p>
                    <a href="/propagandas/admin/campanhas/criar-local.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-plus mr-2"></i>Criar Primeira Campanha
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($campaigns as $campaign): ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                            <!-- Imagem -->
                            <?php if ($campaign['company_image']): ?>
                                <div class="w-full h-40 bg-gray-300 overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($campaign['company_image']); ?>" alt="<?php echo htmlspecialchars($campaign['company_name']); ?>" class="w-full h-full object-cover">
                                </div>
                            <?php else: ?>
                                <div class="w-full h-40 bg-gradient-to-br from-purple-300 to-pink-300 flex items-center justify-center">
                                    <i class="fas fa-image text-white text-4xl opacity-50"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Conteúdo -->
                            <div class="p-4">
                                <h3 class="text-lg font-bold text-gray-900 truncate"><?php echo htmlspecialchars($campaign['title']); ?></h3>
                                <p class="text-sm text-gray-600 flex items-center mb-2">
                                    <i class="fas fa-store mr-2 text-purple-600"></i>
                                    <?php echo htmlspecialchars($campaign['company_name']); ?>
                                </p>
                                
                                <!-- Status -->
                                <div class="mb-3">
                                    <?php if ($campaign['status'] === 'ativa'): ?>
                                        <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-semibold">
                                            <i class="fas fa-check-circle mr-1"></i>Ativa
                                        </span>
                                    <?php elseif ($campaign['status'] === 'inativa'): ?>
                                        <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full font-semibold">
                                            <i class="fas fa-pause-circle mr-1"></i>Inativa
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-semibold">
                                            <i class="fas fa-archive mr-1"></i>Arquivada
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Contato -->
                                <div class="space-y-2 mb-4">
                                    <?php if ($campaign['whatsapp']): ?>
                                        <p class="text-xs text-gray-600">
                                            <i class="fab fa-whatsapp text-green-500 mr-2"></i>
                                            <?php echo htmlspecialchars($campaign['whatsapp']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($campaign['phone']): ?>
                                        <p class="text-xs text-gray-600">
                                            <i class="fas fa-phone text-blue-500 mr-2"></i>
                                            <?php echo htmlspecialchars($campaign['phone']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Stats -->
                                <div class="grid grid-cols-2 gap-2 mb-4 text-center bg-gray-50 p-2 rounded">
                                    <div>
                                        <p class="text-xl font-bold text-blue-600"><?php echo isset($campaign['total_visualizacoes']) ? (int)$campaign['total_visualizacoes'] : 0; ?></p>
                                        <p class="text-xs text-gray-600">Visualizações</p>
                                    </div>
                                    <div>
                                        <p class="text-xl font-bold text-green-600"><?php echo isset($campaign['total_cliques']) ? (int)$campaign['total_cliques'] : 0; ?></p>
                                        <p class="text-xs text-gray-600">Cliques</p>
                                    </div>
                                </div>

                                <!-- Ações -->
                                <div class="flex gap-2">
                                    <a href="/propagandas/admin/campanhas/editar-local.php?id=<?php echo $campaign['id']; ?>" class="flex-1 px-3 py-2 bg-blue-500 text-white text-sm font-bold rounded hover:bg-blue-600 transition text-center">
                                        <i class="fas fa-edit mr-1"></i>Editar
                                    </a>
                                    <a href="/propagandas/admin/campanhas/listar-local.php?action=delete&id=<?php echo $campaign['id']; ?>" onclick="return confirm('Tem certeza? As imagens também serão deletadas.')" class="flex-1 px-3 py-2 bg-red-500 text-white text-sm font-bold rounded hover:bg-red-600 transition text-center">
                                        <i class="fas fa-trash mr-1"></i>Deletar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
