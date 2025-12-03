<?php
// Editar Campanha EMBED
require_once __DIR__ . '/../../../login_system/middleware.php';
require_admin();

$user = current_user();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/CampaignManager.php';

$pdo = db();
$manager = new PropagandaCampaignManager($pdo);
$campaign_id = (int)($_GET['id'] ?? 0);

if ($campaign_id <= 0) {
    header('Location: /propagandas/admin/index.php');
    exit;
}

try {
    $campaign = $manager->getById($campaign_id);
    
    error_log('DEBUG EMBED EDIT: campaign_id=' . $campaign_id);
    error_log('DEBUG EMBED EDIT: campaign is_null=' . ($campaign === null ? 'YES' : 'NO'));
    if ($campaign) {
        error_log('DEBUG EMBED EDIT: ad_type=' . var_export($campaign['ad_type'], true));
        error_log('DEBUG EMBED EDIT: ad_type type=' . gettype($campaign['ad_type']));
        error_log('DEBUG EMBED EDIT: (int)ad_type=' . (int)$campaign['ad_type']);
        error_log('DEBUG EMBED EDIT: ad_type !== 3 = ' . ((int)$campaign['ad_type'] !== 3 ? 'YES' : 'NO'));
    }
    
    if (!$campaign) {
        error_log('DEBUG EMBED EDIT: REDIRECTING - campaign not found (id=' . $campaign_id . ')');
        header('Location: /propagandas/admin/index.php');
        exit;
    }
    
    // Se é outro tipo, redireciona para o arquivo correto
    if ((int)$campaign['ad_type'] === 1) {
        error_log('DEBUG EMBED EDIT: REDIRECTING - campaign is WEB type, redirecting to editar.php');
        header('Location: /propagandas/admin/campanhas/editar.php?id=' . $campaign_id);
        exit;
    } elseif ((int)$campaign['ad_type'] === 2) {
        error_log('DEBUG EMBED EDIT: REDIRECTING - campaign is LOCAL type, redirecting to editar-local.php');
        header('Location: /propagandas/admin/campanhas/editar-local.php?id=' . $campaign_id);
        exit;
    } elseif ((int)$campaign['ad_type'] !== 3) {
        error_log('DEBUG EMBED EDIT: REDIRECTING - campaign has unknown ad_type=' . (int)$campaign['ad_type']);
        header('Location: /propagandas/admin/index.php');
        exit;
    }
} catch (Exception $e) {
    error_log('Erro ao carregar campanha EMBED: ' . $e->getMessage());
    header('Location: /propagandas/admin/index.php');
    exit;
}

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $html_code = trim($_POST['html_code'] ?? '');
    
    if (!$title) $errors[] = 'Título é obrigatório';
    if (!$html_code) $errors[] = 'Código HTML é obrigatório';
    
    if (empty($errors)) {
        try {
            $update_data = [
                'title' => $title,
                'html_code' => $html_code,
                'status' => $_POST['status'] ?? 'ativa',
                'position' => $_POST['position'] ?? 'center',
                'display_frequency' => $_POST['frequency'] ?? 'por_sessao',
                'display_mode' => $_POST['display_mode'] ?? 'normal',
                'rotation_type' => $_POST['rotation_type'] ?? 'random',
                'priority_weight' => (int)($_POST['priority'] ?? 1),
                'initial_delay_ms' => (int)(($_POST['initial_delay'] ?? 3) * 1000),
                'reopen_delay_ms' => (int)(($_POST['reopen_delay'] ?? 300) * 1000)
            ];
            
            if ($manager->update($campaign_id, $update_data)) {
                $success = "✅ Campanha atualizada com sucesso!";
                $campaign = $manager->getById($campaign_id);
            } else {
                $errors[] = 'Erro ao atualizar campanha';
            }
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Campanha EMBED - Sistema de Propaganda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
<div class="min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold"><i class="fas fa-code mr-2"></i>Editar Campanha EMBED</h1>
                <p class="text-indigo-100 text-sm">Código HTML customizado - campanha #<?php echo $campaign_id; ?></p>
            </div>
            <a href="/propagandas/admin/index.php" class="bg-white text-indigo-600 px-4 py-2 rounded font-semibold hover:bg-indigo-50">
                ← Voltar
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">
        <!-- Mensagens -->
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-800 rounded border border-green-300">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-800 rounded border border-red-300">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <ul class="list-disc ml-4">
                    <?php foreach ($errors as $erro): ?>
                        <li><?php echo htmlspecialchars($erro); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <form method="POST" class="bg-white rounded-lg shadow p-8 space-y-8">
            
            <!-- SEÇÃO: Informações Básicas -->
            <div class="border-b pb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-info-circle text-indigo-600 mr-2"></i>Informações Básicas
                </h2>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Título da Campanha *</label>
                    <input type="text" name="title" id="title" 
                           value="<?php echo htmlspecialchars($campaign['title'] ?? ''); ?>"
                           placeholder="Ex: Banner Especial"
                           class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
            </div>

            <!-- SEÇÃO: Código HTML -->
            <div class="border-b pb-6 bg-indigo-50 p-4 rounded">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-code text-indigo-600 mr-2"></i>Código HTML Customizado
                </h2>
                <p class="text-sm text-gray-600 mb-3">Cole o código HTML completo do seu anúncio aqui.</p>
                <textarea name="html_code" id="html_code" rows="12" 
                          placeholder="<div class='popup'>...seu HTML aqui...</div>"
                          class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono text-sm" required><?php echo htmlspecialchars($campaign['html_code'] ?? ''); ?></textarea>
                
                <div class="mt-4 p-3 bg-yellow-100 border border-yellow-300 rounded text-sm text-gray-700">
                    <i class="fas fa-lightbulb mr-2 text-yellow-600"></i>
                    <strong>Dicas:</strong>
                    <ul class="list-disc ml-5 mt-2">
                        <li>Você pode incluir qualquer HTML + CSS + JavaScript</li>
                        <li>Tamanho máximo recomendado: 500px de largura</li>
                        <li>Teste em mobile também</li>
                        <li>Scripts inline são permitidos</li>
                    </ul>
                </div>
            </div>

            <!-- SEÇÃO: Configurações de Exibição -->
            <div class="border-b pb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-sliders-h text-indigo-600 mr-2"></i>Configurações de Exibição
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Posição</label>
                        <input type="hidden" name="position" value="center">
                        <div class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-100 text-gray-700">
                            🎯 Centro
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Frequência</label>
                        <select name="frequency" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="por_sessao" <?php echo ($campaign['display_frequency'] ?? 'por_sessao') === 'por_sessao' ? 'selected' : ''; ?>>Uma vez por Sessão</option>
                            <option value="por_dia" <?php echo ($campaign['display_frequency'] ?? '') === 'por_dia' ? 'selected' : ''; ?>>Uma vez por Dia</option>
                            <option value="sempre" <?php echo ($campaign['display_frequency'] ?? '') === 'sempre' ? 'selected' : ''; ?>>Sempre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Modo</label>
                        <select name="display_mode" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="normal" <?php echo ($campaign['display_mode'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>🔄 Normal</option>
                            <option value="exclusive" <?php echo ($campaign['display_mode'] ?? '') === 'exclusive' ? 'selected' : ''; ?>>⭐ Exclusiva</option>
                            <option value="prioritized" <?php echo ($campaign['display_mode'] ?? '') === 'prioritized' ? 'selected' : ''; ?>>📍 Prioritária</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Rotação</label>
                        <select name="rotation_type" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="random" <?php echo ($campaign['rotation_type'] ?? 'random') === 'random' ? 'selected' : ''; ?>>🎲 Aleatória</option>
                            <option value="sequential" <?php echo ($campaign['rotation_type'] ?? '') === 'sequential' ? 'selected' : ''; ?>>▶️ Sequencial</option>
                            <option value="both" <?php echo ($campaign['rotation_type'] ?? '') === 'both' ? 'selected' : ''; ?>>🔀 Ambas</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Prioridade</label>
                        <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="1" <?php echo ($campaign['priority_weight'] ?? 1) == 1 ? 'selected' : ''; ?>>⭐ Normal (1x)</option>
                            <option value="2" <?php echo ($campaign['priority_weight'] ?? '') == 2 ? 'selected' : ''; ?>>⭐⭐ Alto (2x)</option>
                            <option value="3" <?php echo ($campaign['priority_weight'] ?? '') == 3 ? 'selected' : ''; ?>>⭐⭐⭐ Muito Alto (3x)</option>
                            <option value="5" <?php echo ($campaign['priority_weight'] ?? '') == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ Máximo (5x)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: Timing e Delays -->
            <div class="border-b pb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-clock text-orange-600 mr-2"></i>Timing e Delays
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Atraso Inicial</label>
                        <div class="flex gap-2">
                            <input type="number" name="initial_delay" min="0" max="60" step="0.1" value="<?php echo ($campaign['initial_delay_ms'] ?? 3000) / 1000; ?>" class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <select class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option selected>segundos</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Atraso para Reabrir</label>
                        <div class="flex gap-2">
                            <input type="number" name="reopen_delay" min="0" max="3600" step="1" value="<?php echo ($campaign['reopen_delay_ms'] ?? 300000) / 1000; ?>" class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <select class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option selected>segundos</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: Status -->
            <div class="border-b pb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-toggle-on text-green-600 mr-2"></i>Status
                </h2>
                <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="ativa" <?php echo ($campaign['status'] ?? 'ativa') === 'ativa' ? 'selected' : ''; ?>>✅ Ativa</option>
                    <option value="pausada" <?php echo ($campaign['status'] ?? '') === 'pausada' ? 'selected' : ''; ?>>⏸️ Pausada</option>
                </select>
            </div>

            <!-- BOTÕES -->
            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i>💾 SALVAR ALTERAÇÕES
                </button>
                <a href="/propagandas/admin/index.php" class="flex-1 px-6 py-3 bg-gray-500 text-white font-bold rounded-lg hover:bg-gray-600 transition text-center">
                    <i class="fas fa-times mr-2"></i>❌ CANCELAR
                </a>
            </div>

        </form>
    </main>
</div>

</body>
</html>
