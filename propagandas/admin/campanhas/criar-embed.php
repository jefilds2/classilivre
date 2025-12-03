<?php
require_once __DIR__ . '/../../../login_system/middleware.php';
require_admin();

$user = current_user();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/CampaignManager.php';

$pdo = db();
$manager = new PropagandaCampaignManager($pdo);
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $html_code = trim($_POST['html_code'] ?? '');
    $pages = $_POST['pages'] ?? [];

    if (empty($title)) $errors[] = 'Título é obrigatório';
    if (empty($html_code)) $errors[] = 'Código HTML é obrigatório';
    if (empty($pages)) $errors[] = 'Selecione pelo menos uma página';

    if (empty($errors)) {
        try {
            $data = [
                'title' => $title,
                'html_code' => $html_code,
                'pages' => $pages,
                'ad_type' => 3,
                'initial_delay_ms' => (int)(($_POST['initial_delay'] ?? 0) * 1000),
                'reopen_delay_ms' => (int)(($_POST['reopen_delay'] ?? 0) * 1000),
                'position' => $_POST['position'] ?? 'center',
                'display_frequency' => $_POST['frequency'] ?? 'por_sessao',
                'display_mode' => $_POST['display_mode'] ?? 'normal',
                'rotation_type' => $_POST['rotation_type'] ?? 'random',
                'priority_weight' => (int)($_POST['priority'] ?? 1),
                'status' => 'ativa',
                'created_by' => $user['id'],
                'description' => 'Campanha EMBED'
            ];

            $campaign_id = $manager->create($data);
            $success = "✅ Campanha EMBED criada! ID: {$campaign_id}";

        } catch (Exception $e) {
            $errors[] = 'Erro ao criar campanha: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Campanha EMBED - Sistema de Propaganda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
<div class="min-h-screen">
    <header class="bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold"><i class="fas fa-code mr-2"></i>Nova Campanha EMBED</h1>
                <p class="text-purple-100 text-sm">Código HTML customizado</p>
            </div>
            <a href="/propagandas/admin/index.php" class="bg-white text-purple-600 px-4 py-2 rounded font-semibold hover:bg-purple-50">
                ← Voltar
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-800 rounded border border-green-300">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-800 rounded border border-red-300">
                <ul class="list-disc ml-4">
                    <?php foreach ($errors as $erro): ?>
                        <li><?php echo htmlspecialchars($erro); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <form method="POST" class="bg-white rounded-lg shadow p-8 space-y-8">

                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-info-circle text-purple-600 mr-2"></i>Informações Básicas
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                                <input type="text" name="title" required class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Ex: Banner Especial">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Código HTML *</label>
                                <textarea name="html_code" required rows="12" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 font-mono text-sm" placeholder="Cole seu código HTML aqui..."></textarea>
                                <p class="text-xs text-gray-500 mt-1">Insira HTML válido. Scripts inline são permitidos.</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-globe text-green-600 mr-2"></i>Páginas de Exibição *
                        </h2>
                        <div class="space-y-3">
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-purple-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="home" class="w-4 h-4 text-purple-600">
                                <span class="ml-3 text-gray-700">🏠 Home</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-purple-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="servicos" class="w-4 h-4 text-purple-600">
                                <span class="ml-3 text-gray-700">🛠️ Serviços</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-purple-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="anuncios" class="w-4 h-4 text-purple-600">
                                <span class="ml-3 text-gray-700">📢 Anúncios</span>
                            </label>
                        </div>
                    </div>

                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-sliders-h text-purple-600 mr-2"></i>Configurações de Exibição
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Posição</label>
                                <select name="position" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="center" selected>🎯 Centro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Frequência</label>
                                <select name="frequency" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="por_sessao">Uma vez por Sessão</option>
                                    <option value="por_dia">Uma vez por Dia</option>
                                    <option value="sempre">Sempre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Modo</label>
                                <select name="display_mode" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="normal">🔄 Normal</option>
                                    <option value="exclusive">⭐ Exclusiva</option>
                                    <option value="prioritized">📍 Prioritária</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Rotação</label>
                                <select name="rotation_type" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="random">🎲 Aleatória</option>
                                    <option value="sequential">▶️ Sequencial</option>
                                    <option value="both">🔀 Ambas</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Prioridade</label>
                                <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="1">⭐ Normal (1x)</option>
                                    <option value="2">⭐⭐ Alto (2x)</option>
                                    <option value="3">⭐⭐⭐ Muito Alto (3x)</option>
                                    <option value="5">⭐⭐⭐⭐⭐ Máximo (5x)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-clock text-orange-600 mr-2"></i>Timing e Delays
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Atraso Inicial</label>
                                <div class="flex gap-2">
                                    <input type="number" name="initial_delay" min="0" max="60" step="0.1" value="0" class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <select class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option selected>segundos</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Atraso para Reabrir</label>
                                <div class="flex gap-2">
                                    <input type="number" name="reopen_delay" min="0" max="3600" step="1" value="0" class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <select class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option selected>segundos</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded font-semibold hover:bg-purple-700 transition flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i> Criar Campanha
                        </button>
                        <a href="/propagandas/admin/index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded font-semibold hover:bg-gray-300 transition">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Dicas
                </h3>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li><strong>Código HTML:</strong> Você pode incluir qualquer HTML + CSS + JavaScript</li>
                    <li><strong>Responsividade:</strong> Teste em mobile também</li>
                    <li><strong>Tamanho:</strong> O pop-up tem max-width 500px</li>
                    <li><strong>Exemplo simples:</strong> <code class="bg-gray-100 px-2 py-1">&lt;h1&gt;Olá!&lt;/h1&gt;</code></li>
                </ul>
            </div>
        </div>
    </main>
</div>
</body>
</html>
