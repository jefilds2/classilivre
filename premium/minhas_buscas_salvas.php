<?php
/**
 * premium/minhas_buscas_salvas.php
 * Página para visualizar, gerenciar e deletar buscas salvas pelo usuário premium
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../sqlconnect.php';

require_login();
ensure_user_not_blocked();

$user = current_user();

// Verificar se usuário é premium
$is_premium = user_is_premium($user);

// Se não for premium, redirecionar para página de planos
if (!$is_premium) {
    $is_not_premium = true;
} else {
    $is_not_premium = false;
}

// Buscar buscas salvas apenas se for premium
$user_id = (int) $user['id'];
if ($is_premium) {
    $sql = "SELECT id FROM saved_searches WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = db()->prepare($sql);
    $stmt->execute([$user_id]);
    $saved_searches = $stmt->fetchAll();
} else {
    $saved_searches = [];
}

// Processar deleção via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'delete' && isset($_POST['search_id'])) {
        $search_id = (int) $_POST['search_id'];
        
        // Verificar se a busca pertence ao usuário
        $check = db()->prepare("SELECT user_id FROM saved_searches WHERE id = ?");
        $check->execute([$search_id]);
        $search = $check->fetch();
        
        if ($search && $search['user_id'] === $user_id) {
            $delete = db()->prepare("DELETE FROM saved_searches WHERE id = ?");
            if ($delete->execute([$search_id])) {
                echo json_encode(['success' => true, 'message' => 'Busca deletada com sucesso']);
                exit;
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Erro ao deletar busca']);
        exit;
    }
}

// Configurar página
$pageTitle = 'Minhas Buscas Salvas — Site Carros';
?>
<?php require_once __DIR__ . '/../partials/header.php'; ?>

<style>
    .fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }
    @keyframes fadeOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(20px); }
    }
</style>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <!-- Tarja Verde de Destaque -->
        <div class="mb-8 pb-6 border-b-4 border-green-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-bookmark mr-3 text-amber-500"></i>
                        Minhas Buscas Salvas
                    </h1>
                    <p class="text-gray-600 mt-2">Gerencie suas buscas favoritas e receba notificações quando novos anúncios forem encontrados</p>
                </div>
                <a href="/anuncios/listar.php" class="bg-amber-500 text-green-900 px-6 py-2 rounded-lg font-semibold text-sm shadow-sm hover:bg-amber-600 transition flex items-center">
                    <i class="fas fa-search mr-2"></i> Nova Busca
                </a>
            </div>
        </div>

        <!-- Mensagem de Não Premium -->
        <?php if ($is_not_premium): ?>
            <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border-2 border-amber-400 rounded-lg shadow-lg p-12 text-center max-w-2xl mx-auto">
                <div class="mb-6">
                    <i class="fas fa-star text-6xl text-amber-500"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-3">Benefício Exclusivo Premium</h2>
                <p class="text-gray-700 text-lg mb-6">
                    A funcionalidade de <strong>Salvar Buscas</strong> com notificações automáticas é um benefício exclusivo de usuários Premium. 
                    Aproveite para atualizar sua conta e nunca mais perder um anúncio que combina com o que você procura!
                </p>
                
                <div class="bg-white rounded-lg p-6 mb-6 shadow-md">
                    <h3 class="font-semibold text-green-700 text-lg mb-4">O que você ganha sendo Premium:</h3>
                    <ul class="text-left space-y-2 text-gray-700 mb-4">
                        <li class="flex items-center"><i class="fas fa-check text-green-600 mr-3"></i> Salve até 10 buscas personalizadas</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-600 mr-3"></i> Receba notificações por email quando novos anúncios forem publicados</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-600 mr-3"></i> Acesse suas buscas em qualquer momento</li>
                        <li class="flex items-center"><i class="fas fa-check text-green-600 mr-3"></i> Gerenciamento completo de preferências</li>
                    </ul>
                </div>

                <a href="/premium/status.php" class="inline-block bg-gradient-to-r from-amber-500 to-amber-600 text-green-900 px-10 py-4 rounded-lg font-bold text-lg shadow-lg hover:shadow-xl hover:from-amber-600 hover:to-amber-700 transition flex items-center justify-center gap-2 mb-4">
                    <i class="fas fa-crown"></i> Assinar Premium agora
                </a>
                
                <p class="text-gray-600 text-sm">
                    <i class="fas fa-info-circle mr-2"></i>
                    Enquanto isso, você pode continuar explorando e descobrindo veículos no nosso catálogo completo.
                </p>
            </div>

            <div class="mt-6 text-center">
                <a href="/anuncios/listar.php" class="text-amber-600 hover:text-amber-700 font-semibold flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> Voltar para Buscar Veículos
                </a>
            </div>

        <!-- Mensagem de Nenhuma Busca Salva (Usuário Premium) -->
        <?php elseif (empty($saved_searches)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <div class="mb-4">
                    <i class="fas fa-search text-6xl text-gray-300"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Nenhuma busca salva ainda</h2>
                <p class="text-gray-600 mb-6">Comece a explorar veículos e salve suas buscas favoritas para receber atualizações automáticas!</p>
                <a href="/anuncios/listar.php" class="inline-block bg-amber-500 text-green-900 px-8 py-3 rounded-lg font-semibold hover:bg-amber-600 transition">
                    <i class="fas fa-search mr-2"></i> Buscar Veículos
                </a>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($saved_searches as $search): ?>
                    <?php
                    // Buscar dados completos da busca
                    $detail_sql = "SELECT * FROM saved_searches WHERE id = ?";
                    $detail_stmt = db()->prepare($detail_sql);
                    $detail_stmt->execute([$search['id']]);
                    $search_detail = $detail_stmt->fetch();
                    
                    // Decodificar filtros (search_params é JSON)
                    $filters = json_decode($search_detail['search_params'] ?? '{}', true);
                    ?>
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-amber-500 hover:shadow-lg transition search-item" data-search-id="<?= $search['id'] ?>">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                    <i class="fas fa-bookmark text-amber-500 mr-2"></i>
                                    <?= e($search_detail['search_name']); ?>
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Salva em <?= date('d/m/Y \à\s H:i', strtotime($search_detail['created_at'])) ?>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" onclick="loadSearchResults(<?= $search['id'] ?>)" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition flex items-center">
                                    <i class="fas fa-eye mr-2"></i> Ver Resultados
                                </button>
                                <button type="button" onclick="deleteSearch(<?= $search['id'] ?>)" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600 transition flex items-center">
                                    <i class="fas fa-trash mr-2"></i> Deletar
                                </button>
                            </div>
                        </div>

                        <!-- Filtros Salvos -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                <i class="fas fa-sliders-h mr-2 text-amber-500"></i>
                                Filtros Salvos
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <!-- Tipo de Veículo -->
                                <?php if (!empty($filters['tipo'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Tipo</p>
                                        <p class="text-gray-900"><?= ucfirst(e($filters['tipo'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Marca -->
                                <?php if (!empty($filters['marca'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Marca</p>
                                        <p class="text-gray-900"><?= e($filters['marca']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Modelo -->
                                <?php if (!empty($filters['modelo'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Modelo</p>
                                        <p class="text-gray-900"><?= e($filters['modelo']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Localização -->
                                <?php if (!empty($filters['cidade'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Localização</p>
                                        <p class="text-gray-900"><?= e($filters['cidade']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Preço -->
                                <?php if (!empty($filters['preco_min']) || !empty($filters['preco_max'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Faixa de Preço</p>
                                        <p class="text-gray-900">
                                            R$ <?= number_format((float)($filters['preco_min'] ?? 0), 2, ',', '.') ?> 
                                            - 
                                            R$ <?= number_format((float)($filters['preco_max'] ?? 0), 2, ',', '.') ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <!-- Ano -->
                                <?php if (!empty($filters['ano_min']) || !empty($filters['ano_max'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Ano</p>
                                        <p class="text-gray-900"><?= e($filters['ano_min'] ?? ''); ?> - <?= e($filters['ano_max'] ?? ''); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Quilometragem -->
                                <?php if (!empty($filters['km_min']) || !empty($filters['km_max'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Quilometragem</p>
                                        <p class="text-gray-900">
                                            <?= number_format((float)($filters['km_min'] ?? 0), 0, ',', '.') ?> 
                                            - 
                                            <?= number_format((float)($filters['km_max'] ?? 0), 0, ',', '.') ?> km
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <!-- Câmbio -->
                                <?php if (!empty($filters['cambio'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Câmbio</p>
                                        <p class="text-gray-900"><?= e($filters['cambio']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Combustível -->
                                <?php if (!empty($filters['combustivel'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Combustível</p>
                                        <p class="text-gray-900"><?= e($filters['combustivel']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Cor -->
                                <?php if (!empty($filters['cor'])): ?>
                                    <div class="text-sm">
                                        <p class="text-gray-600 font-semibold">Cor</p>
                                        <p class="text-gray-900"><?= e($filters['cor']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status de Notificações -->
                        <div class="flex items-center justify-between text-sm">
                            <div class="text-gray-600">
                                <i class="fas fa-bell mr-2 text-amber-500"></i>
                                Notificações automáticas
                                <span class="ml-2 inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">
                                    ✓ Ativadas
                                </span>
                            </div>
                            <p class="text-gray-500">
                                Você receberá um email quando novos anúncios corresponderem a esta busca
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Resumo -->
            <div class="mt-8 bg-amber-50 border border-amber-200 rounded-lg p-6">
                <h3 class="font-bold text-gray-900 mb-2 flex items-center">
                    <i class="fas fa-info-circle text-amber-500 mr-2"></i>
                    Como funcionam as buscas salvas?
                </h3>
                <ul class="text-gray-700 space-y-2 ml-6">
                    <li><i class="fas fa-check text-green-500 mr-2"></i> Suas preferências de busca são armazenadas com segurança</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i> Receba notificações por email quando novos anúncios forem publicados</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i> Acesse rapidamente suas buscas favoritas sem digitar os filtros novamente</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i> Você tem um total de <strong><?= count($saved_searches); ?></strong> buscas salvas</li>
                </ul>
            </div>
        <?php endif; // Fechamento: if ($is_not_premium) elseif (empty($saved_searches)) else ?>
    </main>

    <?php require_once __DIR__ . '/../partials/footer.php'; ?>

    <script>
        function deleteSearch(searchId) {
            if (!confirm('Tem certeza que deseja deletar esta busca salva? Esta ação não pode ser desfeita.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('search_id', searchId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const searchItem = document.querySelector(`[data-search-id="${searchId}"]`);
                    if (searchItem) {
                        searchItem.classList.add('fade-out');
                        setTimeout(() => {
                            searchItem.remove();
                            // Se não houver mais buscas, recarregar página
                            const remaining = document.querySelectorAll('.search-item').length;
                            if (remaining === 0) {
                                window.location.reload();
                            }
                        }, 300);
                    }
                } else {
                    alert('Erro ao deletar busca: ' + data.message);
                }
            })
            .catch(error => console.error('Erro:', error));
        }

        function loadSearchResults(searchId) {
            // Será implementado para recarregar a busca com os mesmos filtros
            window.location.href = '/anuncios/listar.php?search_id=' + searchId;
        }
    </script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
