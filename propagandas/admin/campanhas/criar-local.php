<?php
// Criar Campanha Local - Com Todas as Opções de Configuração
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
    $city = trim($_POST['city'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (!$title) $errors[] = 'Título é obrigatório';
    if (!$city) $errors[] = 'Cidade é obrigatória';
    if (!$phone && !$whatsapp) $errors[] = 'Telefone ou WhatsApp é obrigatório';
    if (!$description) $errors[] = 'Descrição é obrigatória';
    
    if (empty($errors)) {
        try {
            $image_url = null;
            
            if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($_FILES['image']['type'], $allowed)) {
                    throw new Exception('Tipo de imagem não permitido');
                }
                if ($_FILES['image']['size'] > 5242880) {
                    throw new Exception('Máximo 5MB');
                }
                
                $upload_dir = __DIR__ . '/../../uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'campaign_' . time() . '_' . uniqid() . '.' . $ext;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                    $image_url = '/propagandas/uploads/' . $filename;
                }
            }
            
            $data = [
                'title' => $title,
                'city' => $city,
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'description' => $description,
                'ad_type' => 2,  // ✅ CAMPANHA LOCAL
                'pages' => $_POST['pages'] ?? [],
                'position' => $_POST['position'] ?? 'center',
                'display_frequency' => $_POST['frequency'] ?? 'por_sessao',
                'display_mode' => $_POST['display_mode'] ?? 'normal',
                'rotation_type' => $_POST['rotation_type'] ?? 'random',
                'priority_weight' => (int)($_POST['priority'] ?? 1),
                'initial_delay_ms' => (int)(($_POST['initial_delay'] ?? 0) * 1000),
                'reopen_delay_ms' => (int)(($_POST['reopen_delay'] ?? 0) * 1000),
                'status' => 'ativa',
                'created_by' => $user['id'],
                'image_url' => $image_url
            ];
            
            $campaign_id = $manager->create($data);
            $success = "✅ Campanha local criada! ID: {$campaign_id}";
            
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
    <title>Nova Campanha Local - Sistema de Propaganda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
<div class="min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold"><i class="fas fa-map-marker-alt mr-2"></i>Nova Campanha Local</h1>
                <p class="text-purple-100 text-sm">Crie uma campanha para sua localidade com contato direto</p>
            </div>
            <a href="/propagandas/admin/index.php" class="bg-white text-purple-600 px-4 py-2 rounded font-semibold hover:bg-purple-50">
                ← Voltar
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
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

        <!-- Grid: Formulário + Preview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Coluna 1-2: Formulário -->
            <div class="lg:col-span-2">
                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-8 space-y-8">
                    
                    <!-- SEÇÃO: Informações Básicas -->
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-info-circle text-purple-600 mr-2"></i>Informações Básicas
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Título da Campanha *</label>
                                <input type="text" name="title" id="title" placeholder="Ex: Limpeza de Carros"
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição *</label>
                                <textarea name="description" id="description" placeholder="Descreva o serviço ou promoção..."
                                          rows="4" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Cidade *</label>
                                <input type="text" name="city" id="city" placeholder="Ex: Guanhães, MG"
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Imagem</label>
                                <input type="file" name="image" id="image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <p class="text-xs text-gray-500 mt-1">Máx: 5MB (JPEG, PNG, GIF, WebP)</p>
                            </div>
                        </div>
                    </div>

                    <!-- SEÇÃO: Contato -->
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-phone text-green-600 mr-2"></i>Informações de Contato
                        </h2>
                        <p class="text-sm text-gray-600 mb-4">Informe pelo menos um meio de contato</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-phone text-blue-600 mr-1"></i>Telefone
                                </label>
                                <input type="tel" name="phone" id="phone" placeholder="(31) 99999-9999"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fab fa-whatsapp text-green-500 mr-1"></i>WhatsApp
                                </label>
                                <input type="tel" name="whatsapp" id="whatsapp" placeholder="(31) 99999-9999"
                                       value="<?php echo htmlspecialchars($_POST['whatsapp'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>
                    </div>

                    <!-- SEÇÃO: Páginas de Exibição -->
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-globe text-green-600 mr-2"></i>Páginas de Exibição *
                        </h2>
                        <div class="space-y-3">
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-purple-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="home" 
                                       <?php echo in_array('home', $_POST['pages'] ?? []) ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-purple-600 pages-checkbox">
                                <span class="ml-3 text-gray-700">🏠 Home</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-purple-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="servicos"
                                       <?php echo in_array('servicos', $_POST['pages'] ?? []) ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-purple-600 pages-checkbox">
                                <span class="ml-3 text-gray-700">🛠️ Serviços</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-purple-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="anuncios"
                                       <?php echo in_array('anuncios', $_POST['pages'] ?? []) ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-purple-600 pages-checkbox">
                                <span class="ml-3 text-gray-700">📢 Anúncios</span>
                            </label>
                        </div>
                    </div>

                    <!-- SEÇÃO: Configurações de Exibição -->
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-sliders-h text-purple-600 mr-2"></i>Configurações de Exibição
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Posição</label>
                                <select name="position" id="position" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="center" selected>🎯 Centro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Frequência</label>
                                <select name="frequency" id="frequency" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="por_sessao">Uma vez por Sessão</option>
                                    <option value="por_dia">Uma vez por Dia</option>
                                    <option value="sempre">Sempre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Modo</label>
                                <select name="display_mode" id="display_mode" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="normal">🔄 Normal</option>
                                    <option value="exclusive">⭐ Exclusiva</option>
                                    <option value="prioritized">📍 Prioritária</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Rotação</label>
                                <select name="rotation_type" id="rotation_type" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="random">🎲 Aleatória</option>
                                    <option value="sequential">▶️ Sequencial</option>
                                    <option value="both">🔀 Ambas</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Prioridade</label>
                                <select name="priority" id="priority" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="1">⭐ Normal (1x)</option>
                                    <option value="2">⭐⭐ Alto (2x)</option>
                                    <option value="3">⭐⭐⭐ Muito Alto (3x)</option>
                                    <option value="5">⭐⭐⭐⭐⭐ Máximo (5x)</option>
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
                                    <input type="number" name="initial_delay" id="initial_delay" min="0" max="60" step="0.1" value="0" class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <select class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option selected>segundos</option>
                                    </select>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">0 = Instantâneo | Máx: 60s</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Atraso para Reabrir</label>
                                <div class="flex gap-2">
                                    <input type="number" name="reopen_delay" id="reopen_delay" min="0" max="3600" step="1" value="0" class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <select class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option selected>segundos</option>
                                    </select>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">0 = Sempre pode reabrir | Máx: 1 hora</p>
                            </div>
                        </div>
                    </div>

                    <!-- BOTÕES -->
                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="flex-1 px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-check-circle mr-2"></i>✅ CRIAR CAMPANHA
                        </button>
                        <a href="/propagandas/admin/index.php" class="flex-1 px-6 py-3 bg-gray-500 text-white font-bold rounded-lg hover:bg-gray-600 transition text-center">
                            <i class="fas fa-times mr-2"></i>❌ CANCELAR
                        </a>
                    </div>

                </form>
            </div>

            <!-- Coluna 3: Preview -->
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-b from-purple-50 to-purple-100 rounded-lg shadow-lg p-6 sticky top-6 border-4 border-purple-300">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-eye mr-2 text-purple-600"></i>👁️ PREVIEW
                    </h3>
                    
                    <!-- Mockup do Pop-up Local -->
                    <div class="bg-gray-300 rounded-lg p-4 min-h-96 flex items-center justify-center">
                        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full p-6 space-y-4">
                            <div id="preview_image_container" class="bg-purple-100 rounded h-24 flex items-center justify-center text-purple-600">
                                <div class="text-center">
                                    <i class="fas fa-image text-2xl mb-1"></i>
                                    <p class="text-xs">Imagem</p>
                                </div>
                            </div>
                            <p id="preview_title" class="font-bold text-gray-900 text-lg text-center">Serviço Local</p>
                            <div class="bg-purple-50 rounded p-2 text-center">
                                <p class="text-xs text-gray-600">📍 Localidade:</p>
                                <p id="preview_city" class="font-semibold text-purple-700">Guanhães, MG</p>
                            </div>
                            <p id="preview_description" class="text-sm text-gray-600 text-center">Descrição do serviço...</p>
                            <div class="space-y-2 border-t pt-3">
                                <div id="preview_phone_block" class="text-center">
                                    <a href="#" class="inline-block w-full px-4 py-2 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700 transition text-sm">
                                        <i class="fas fa-phone mr-2"></i><span id="preview_phone">Telefone</span>
                                    </a>
                                </div>
                                <div id="preview_whatsapp_block" class="text-center">
                                    <a href="#" class="inline-block w-full px-4 py-2 bg-green-600 text-white rounded font-semibold hover:bg-green-700 transition text-sm">
                                        <i class="fab fa-whatsapp mr-2"></i><span id="preview_whatsapp">WhatsApp</span>
                                    </a>
                                </div>
                            </div>
                            <button type="button" class="w-full text-center text-xs text-gray-500 hover:text-gray-700 border-t pt-2">
                                ✕ Fechar
                            </button>
                        </div>
                    </div>

                    <!-- Informações Exibidas -->
                    <div class="mt-6 bg-white rounded p-4 space-y-3 text-sm">
                        <div class="border-b pb-2">
                            <strong class="text-purple-700">⚙️ Configurações</strong>
                        </div>
                        <div class="text-xs space-y-2">
                            <p class="text-gray-600">Posição: <span id="preview_position" class="font-semibold">Centro</span></p>
                            <p class="text-gray-600">Frequência: <span id="preview_frequency" class="font-semibold">Sessão</span></p>
                            <p class="text-gray-600">Modo: <span id="preview_mode" class="font-semibold">Normal</span></p>
                            <p class="text-gray-600">Páginas: <span id="preview_pages" class="font-semibold">Nenhuma</span></p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const frequencyLabels = {
        'por_sessao': 'Uma vez por Sessão',
        'por_dia': 'Uma vez por Dia',
        'sempre': 'Sempre'
    };

    const modeLabels = {
        'normal': '🔄 Normal',
        'exclusive': '⭐ Exclusiva',
        'prioritized': '📍 Prioritária'
    };

    const positionLabels = {
        'center': '🎯 Centro',
        'bottom-right': '↙️ Inferior Direito',
        'bottom-left': '↘️ Inferior Esquerdo'
    };

    function updatePreview() {
        const titleEl = document.getElementById('preview_title');
        const descEl = document.getElementById('preview_description');
        const cityEl = document.getElementById('preview_city');
        const phoneBlockEl = document.getElementById('preview_phone_block');
        const whatsappBlockEl = document.getElementById('preview_whatsapp_block');
        const phoneEl = document.getElementById('preview_phone');
        const whatsappEl = document.getElementById('preview_whatsapp');
        const freqEl = document.getElementById('preview_frequency');
        const modeEl = document.getElementById('preview_mode');
        const pagesEl = document.getElementById('preview_pages');
        
        if (titleEl) titleEl.textContent = document.getElementById('title').value || 'Serviço Local';
        if (descEl) descEl.textContent = document.getElementById('description').value || 'Descrição do serviço...';
        if (cityEl) cityEl.textContent = document.getElementById('city').value || 'Guanhães, MG';

        const phone = document.getElementById('phone').value || '';
        const whatsapp = document.getElementById('whatsapp').value || '';

        if (phoneBlockEl) phoneBlockEl.style.display = phone ? 'block' : 'none';
        if (whatsappBlockEl) whatsappBlockEl.style.display = whatsapp ? 'block' : 'none';
        
        if (phone && phoneEl) phoneEl.textContent = phone;
        if (whatsapp && whatsappEl) whatsappEl.textContent = whatsapp;

        if (freqEl) freqEl.textContent = frequencyLabels[document.getElementById('frequency').value] || 'Sessão';
        if (modeEl) modeEl.textContent = modeLabels[document.getElementById('display_mode').value] || 'Normal';

        if (pagesEl) {
            const checked = Array.from(document.querySelectorAll('.pages-checkbox:checked'))
                .map(cb => {
                    const map = {'home': '🏠', 'servicos': '🛠️', 'anuncios': '📢'};
                    return map[cb.value];
                })
                .join(', ');
            pagesEl.textContent = checked || 'Nenhuma';
        }
    }

    // Image preview
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const container = document.getElementById('preview_image_container');
                    if (container) {
                        container.innerHTML = '<img src="' + event.target.result + '" class="w-full h-24 object-cover rounded">';
                    }
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }

    // Add listeners to form fields
    ['title', 'description', 'city', 'phone', 'whatsapp', 'position', 'frequency', 'display_mode'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        }
    });

    // Add listeners to checkboxes
    document.querySelectorAll('.pages-checkbox').forEach(cb => {
        cb.addEventListener('change', updatePreview);
    });

    // Initial update
    updatePreview();
});
</script>

</body>
</html>
