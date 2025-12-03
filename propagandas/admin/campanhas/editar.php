<?php
// EDITAR Campanha Web - Cópia de criar.php com dados pré-carregados
require_once __DIR__ . '/../../../login_system/middleware.php';
require_admin();

$user = current_user();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/CampaignManager.php';

$pdo = db();
$manager = new PropagandaCampaignManager($pdo);
$success = '';
$errors = [];
$campaign = null;

// Obter ID da campanha para editar
$campaign_id = (int)($_GET['id'] ?? 0);
if ($campaign_id <= 0) {
    header('Location: /propagandas/admin/index.php');
    exit;
}

// Carregar dados da campanha
try {
    $campaign = $manager->getById($campaign_id);
    error_log('DEBUG WEB EDIT: campaign_id=' . $campaign_id . ', found=' . ($campaign ? 'yes' : 'no'));
    if ($campaign) {
        error_log('DEBUG WEB EDIT: ad_type=' . (int)$campaign['ad_type']);
    }
    
    if (!$campaign) {
        error_log('DEBUG WEB EDIT: REDIRECTING - campaign not found');
        header('Location: /propagandas/admin/index.php');
        exit;
    }
    
    // Se é outro tipo, redireciona para o arquivo correto
    if ((int)$campaign['ad_type'] === 2) {
        error_log('DEBUG WEB EDIT: REDIRECTING - campaign is LOCAL type');
        header('Location: /propagandas/admin/campanhas/editar-local.php?id=' . $campaign_id);
        exit;
    } elseif ((int)$campaign['ad_type'] === 3) {
        error_log('DEBUG WEB EDIT: REDIRECTING - campaign is EMBED type');
        header('Location: /propagandas/admin/campanhas/editar-embed.php?id=' . $campaign_id);
        exit;
    } elseif ((int)$campaign['ad_type'] !== 1) {
        error_log('DEBUG WEB EDIT: REDIRECTING - campaign has unknown ad_type=' . (int)$campaign['ad_type']);
        header('Location: /propagandas/admin/index.php');
        exit;
    }
} catch (Exception $e) {
    error_log('Erro ao carregar campanha: ' . $e->getMessage());
    die('Erro: ' . htmlspecialchars($e->getMessage()));
}

// Preparar páginas para display (já é array do getById)
$pages_db = is_array($campaign['pages']) ? $campaign['pages'] : [];
$initial_delay_display = (int)($campaign['initial_delay_ms'] ?? 0) / 1000;
$reopen_delay_display = (int)($campaign['reopen_delay_ms'] ?? 0) / 1000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $cta_text = trim($_POST['cta_text'] ?? 'Saiba Mais');
    $pages = $_POST['pages'] ?? [];
    
    // Sem validações obrigatórias - tudo é opcional!
    // Usuário pode colocar apenas HTML, ou apenas link, ou só título, etc.
    
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
            
            // Converter páginas para array se necessário, senão deixar vazio
            if (!is_array($pages)) {
                $pages = [];
            }
            
            $data = [
                'title' => $title ?: 'Sem Título',
                'description' => $description ?: 'Sem descrição',
                'affiliate_link' => $link ?: null,
                'cta_text' => $cta_text ?: 'Saiba Mais',
                'pages' => $pages,
                'initial_delay_ms' => (int)(($_POST['initial_delay'] ?? 0) * 1000),
                'reopen_delay_ms' => (int)(($_POST['reopen_delay'] ?? 0) * 1000),
                'position' => $_POST['position'] ?? 'center',
                'display_frequency' => $_POST['frequency'] ?? 'por_sessao',
                'display_mode' => $_POST['display_mode'] ?? 'normal',
                'rotation_type' => $_POST['rotation_type'] ?? 'random',
                'priority_weight' => (int)($_POST['priority'] ?? 1),
                'status' => $_POST['status'] ?? 'ativa',
                'image_url' => $image_url
            ];
            
            if ($manager->update($campaign_id, $data)) {
                $success = "✅ Campanha atualizada com sucesso!";
                $campaign = $manager->getById($campaign_id);
                $pages_db = is_array($campaign['pages']) ? $campaign['pages'] : [];
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
    <title>Nova Campanha - Sistema de Propaganda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
<div class="min-h-screen">
    <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold"><i class="fas fa-bullhorn mr-2"></i>Editar Campanha Web</h1>
                <p class="text-blue-100 text-sm">Atualize a campanha #<?php echo $campaign_id; ?></p>
            </div>
            <a href="/propagandas/admin/index.php" class="bg-white text-blue-600 px-4 py-2 rounded font-semibold hover:bg-blue-50">
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
                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-8 space-y-8">
                    
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>Informações Básicas
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Título</label>
                                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($campaign['title'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                                <textarea name="description" id="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($campaign['description'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Imagem</label>
                                <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Máx: 5MB</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Link</label>
                                    <input type="url" name="link" id="link" value="<?php echo htmlspecialchars($campaign['affiliate_link'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Texto do Botão</label>
                                    <input type="text" name="cta_text" id="cta_text" value="<?php echo htmlspecialchars($campaign['cta_text'] ?? 'Saiba Mais'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-globe text-green-600 mr-2"></i>Páginas de Exibição
                        </h2>
                        <div class="space-y-3">
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-blue-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="home" class="w-4 h-4 text-blue-600 pages-checkbox" <?php echo in_array('home', $pages_db) ? 'checked' : ''; ?>>
                                <span class="ml-3 text-gray-700">🏠 Home</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-blue-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="servicos" class="w-4 h-4 text-blue-600 pages-checkbox" <?php echo in_array('servicos', $pages_db) ? 'checked' : ''; ?>>
                                <span class="ml-3 text-gray-700">🛠️ Serviços</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded hover:bg-blue-50 cursor-pointer">
                                <input type="checkbox" name="pages[]" value="anuncios" class="w-4 h-4 text-blue-600 pages-checkbox" <?php echo in_array('anuncios', $pages_db) ? 'checked' : ''; ?>>
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
                                <input type="hidden" name="position" value="center">
                                <div class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-100 text-gray-700">
                                    🎯 Centro
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Frequência</label>
                                <select name="frequency" id="frequency" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="por_sessao" <?php echo ($campaign['display_frequency'] ?? 'por_sessao') === 'por_sessao' ? 'selected' : ''; ?>>Uma vez por Sessão</option>
                                    <option value="por_dia" <?php echo ($campaign['display_frequency'] ?? '') === 'por_dia' ? 'selected' : ''; ?>>Uma vez por Dia</option>
                                    <option value="sempre" <?php echo ($campaign['display_frequency'] ?? '') === 'sempre' ? 'selected' : ''; ?>>Sempre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Modo</label>
                                <select name="display_mode" id="display_mode" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="normal" <?php echo ($campaign['display_mode'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>🔄 Normal</option>
                                    <option value="exclusive" <?php echo ($campaign['display_mode'] ?? '') === 'exclusive' ? 'selected' : ''; ?>>⭐ Exclusiva</option>
                                    <option value="prioritized" <?php echo ($campaign['display_mode'] ?? '') === 'prioritized' ? 'selected' : ''; ?>>📍 Prioritária</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Rotação</label>
                                <select name="rotation_type" id="rotation_type" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="random" <?php echo ($campaign['rotation_type'] ?? 'random') === 'random' ? 'selected' : ''; ?>>🎲 Aleatória</option>
                                    <option value="sequential" <?php echo ($campaign['rotation_type'] ?? '') === 'sequential' ? 'selected' : ''; ?>>▶️ Sequencial</option>
                                    <option value="both" <?php echo ($campaign['rotation_type'] ?? '') === 'both' ? 'selected' : ''; ?>>🔀 Ambas</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Prioridade</label>
                                <select name="priority" id="priority" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="1" <?php echo ($campaign['priority_weight'] ?? 1) == 1 ? 'selected' : ''; ?>>⭐ Normal (1x)</option>
                                    <option value="2" <?php echo ($campaign['priority_weight'] ?? '') == 2 ? 'selected' : ''; ?>>⭐⭐ Alto (2x)</option>
                                    <option value="3" <?php echo ($campaign['priority_weight'] ?? '') == 3 ? 'selected' : ''; ?>>⭐⭐⭐ Muito Alto (3x)</option>
                                    <option value="5" <?php echo ($campaign['priority_weight'] ?? '') == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ Máximo (5x)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                                <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="ativa" <?php echo ($campaign['status'] ?? 'ativa') === 'ativa' ? 'selected' : ''; ?>>✅ Ativa</option>
                                    <option value="pausada" <?php echo ($campaign['status'] ?? '') === 'pausada' ? 'selected' : ''; ?>>⏸️ Pausada</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-clock text-orange-600 mr-2"></i>Timing e Delays
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Atraso Inicial</label>
                                <div class="flex gap-2">
                                    <input type="number" name="initial_delay" id="initial_delay" min="0" max="60" step="0.1" value="<?php echo $initial_delay_display; ?>" class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0">
                                    <select class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option selected>segundos</option>
                                    </select>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">0 = Instantâneo | Máx: 60s</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Atraso para Reabrir</label>
                                <div class="flex gap-2">
                                    <input type="number" name="reopen_delay" id="reopen_delay" min="0" max="3600" step="1" value="<?php echo $reopen_delay_display; ?>" class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0">
                                    <select class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option selected>segundos</option>
                                    </select>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">0 = Sempre pode reabrir | Máx: 1 hora</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-save mr-2"></i>💾 SALVAR ALTERAÇÕES
                        </button>
                        <a href="/propagandas/admin/index.php" class="flex-1 px-6 py-3 bg-gray-500 text-white font-bold rounded-lg hover:bg-gray-600 transition text-center">
                            <i class="fas fa-times mr-2"></i>❌ CANCELAR
                        </a>
                    </div>

                </form>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-gradient-to-b from-blue-50 to-blue-100 rounded-lg shadow-lg p-6 sticky top-6 border-4 border-blue-300">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-eye mr-2 text-blue-600"></i>👁️ PREVIEW
                    </h3>
                    
                    <div class="bg-gray-300 rounded-lg p-4 min-h-96 flex items-center justify-center">
                        <!-- Preview Padrão -->
                        <div id="preview_container_standard" class="bg-white rounded-lg shadow-2xl max-w-sm w-full p-6 space-y-4">
                            <div id="preview_image_container" class="bg-gray-200 rounded h-32 flex items-center justify-center text-gray-400">
                                <div class="text-center">
                                    <i class="fas fa-image text-2xl mb-2"></i>
                                    <p class="text-xs">Imagem aqui</p>
                                </div>
                            </div>
                            <p id="preview_title" class="font-bold text-gray-900 text-lg">Promoção Especial</p>
                            <p id="preview_description" class="text-sm text-gray-600">Descrição aparecerá aqui...</p>
                            <button type="button" class="w-full px-4 py-3 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700 transition text-sm">
                                <span id="preview_cta">Saiba Mais</span> <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                            <button type="button" class="w-full text-center text-xs text-gray-500 hover:text-gray-700">
                                ✕ Fechar
                            </button>
                        </div>

                        <!-- Preview HTML Customizado -->
                        <div id="preview_container_html" style="display: none; width: 100%; background: white; border-radius: 8px; padding: 16px; overflow: auto; max-height: 400px;">
                            <p class="text-xs text-gray-500 mb-3" style="border-bottom: 1px solid #eee; padding-bottom: 8px;">
                                <i class="fas fa-code mr-2"></i><strong>Modelo Incorporado (Preview)</strong>
                            </p>
                            <div id="preview_html_content" style="border: 2px dashed #3b82f6; padding: 12px; border-radius: 6px; background: #f3f4f6;">
                                <!-- HTML customizado será renderizado aqui -->
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-white rounded p-4 space-y-3 text-sm">
                        <div class="border-b pb-2">
                            <strong class="text-blue-700">⚙️ Configurações</strong>
                        </div>
                        <div class="text-xs space-y-2">
                            <p class="text-gray-600">Posição: <span id="preview_position" class="font-semibold">Centro</span></p>
                            <p class="text-gray-600">Frequência: <span id="preview_frequency" class="font-semibold">Uma vez por Sessão</span></p>
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

    function updatePreview() {
        const titleEl = document.getElementById('preview_title');
        const descEl = document.getElementById('preview_description');
        const ctaEl = document.getElementById('preview_cta');
        const freqEl = document.getElementById('preview_frequency');
        const pagesEl = document.getElementById('preview_pages');
        
        if (titleEl) titleEl.textContent = document.getElementById('title').value || 'Promoção';
        if (descEl) descEl.textContent = document.getElementById('description').value || 'Descrição...';
        if (ctaEl) ctaEl.textContent = document.getElementById('cta_text').value || 'Saiba Mais';
        if (freqEl) freqEl.textContent = frequencyLabels[document.getElementById('frequency').value] || 'Uma vez por Sessão';
        
        if (pagesEl) {
            const checked = Array.from(document.querySelectorAll('.pages-checkbox:checked')).length;
            pagesEl.textContent = checked > 0 ? checked + ' selecionadas' : 'Nenhuma';
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
                        container.innerHTML = '<img src="' + event.target.result + '" class="w-full h-32 object-cover rounded">';
                    }
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }

    // Add listeners to form fields
    ['title', 'description', 'cta_text', 'frequency'].forEach(id => {
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
    
    // Load existing image preview if exists
    const existingImageUrl = '<?php echo $campaign["image_url"] ?? ""; ?>';
    if (existingImageUrl) {
        const container = document.getElementById('preview_image_container');
        if (container) {
            container.innerHTML = '<img src="' + existingImageUrl + '" class="w-full h-32 object-cover rounded">';
        }
    }
});
</script>

</body>
</html>
