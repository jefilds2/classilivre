<?php

declare(strict_types=1);

require_once __DIR__ . '/login_system/middleware.php';
require_once __DIR__ . '/propagandas/analytics/tracker.php';

// Rastrear acesso à página
AnalyticsTracker::trackPageLoad('index');

$user = current_user();
$pageTitle = 'Classi Livre Autos';

$searchPageUrl = '/anuncios/listar.php';
$servicesPageUrl = file_exists(__DIR__ . '/servicos/index.php') ? '/servicos/index.php' : '#';
$accountPageUrl = '/anuncios/meus.php';
$advertisePageUrl = $user ? '/anuncios/criar.php' : '/login_system/login.php';
$homeJsPath = __DIR__ . '/assets/js/homepage.js';
$homeJsVersion = file_exists($homeJsPath) ? (string)filemtime($homeJsPath) : (string)time();

function fetch_featured_ads(int $limit = 6): array
{
    $pdo = db();

        $stmt = $pdo->prepare('
                SELECT ads.*, users.name AS owner_name
                FROM ads
                JOIN users ON users.id = ads.user_id
                WHERE ads.status = "approved"
                    AND (ads.expires_at IS NULL OR ads.expires_at > NOW())
                ORDER BY COALESCE(ads.approved_at, ads.updated_at, ads.created_at) DESC
                LIMIT :limit
        ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $ads = $stmt->fetchAll();

    if (!$ads) {
        return [];
    }

    $adIds = array_map(static fn(array $ad): int => (int)$ad['id'], $ads);
    $placeholders = implode(',', array_fill(0, count($adIds), '?'));

    $mediaStmt = $pdo->prepare("SELECT ad_id, filepath FROM ad_media WHERE ad_id IN ($placeholders) AND type = 'image' ORDER BY id ASC");
    $mediaStmt->execute($adIds);

    $mediaMap = [];
    while ($row = $mediaStmt->fetch()) {
        $mediaMap[(int)$row['ad_id']][] = $row['filepath'];
    }

    foreach ($ads as &$ad) {
        $id = (int)$ad['id'];
        $ad['images'] = $mediaMap[$id] ?? ['assets/uploads/images/sample-car-1.jpg'];
        $ad['is_verified'] = (int)$ad['verified_badge'] === 1;
    }
    unset($ad);

    return $ads;
}

function asset_path(string $path): string
{
    return '/' . ltrim($path, '/');
}

$featuredAds = fetch_featured_ads(6);

// Incluir o header
require __DIR__ . '/partials/header.php';
?>

<style>
    * {
        font-family: 'Inter', sans-serif;
    }

    .nav-link {
        transition: all 0.2s ease;
    }

    .nav-link:hover {
        background: #ffd44d;
        transform: translateY(-1px);
    }

    .autocomplete-list {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-top: none;
        z-index: 50;
        border-radius: 0 0 0.5rem 0.5rem;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .autocomplete-list div {
        padding: 0.75rem 1rem;
        cursor: pointer;
        font-size: 0.875rem;
        color: #374151;
        transition: background-color 0.2s;
    }

    .autocomplete-list div:hover {
        background-color: #f3f4f6;
    }

    .price-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: #2e7d32;
        color: white;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .verified-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(255, 255, 255, 0.92);
        color: #2e7d32;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        box-shadow: 0 1px 3px rgba(34, 197, 94, 0.25);
    }

    .vehicle-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .vehicle-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 28px -10px rgba(15, 118, 110, 0.35);
    }

    .brand-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .brand-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px -15px rgba(15, 118, 110, 0.3);
    }

    .tipo-btn {
        transition: all 0.2s ease;
    }

    .tipo-btn.ativo {
        background: #2e7d32;
        color: white;
        border-color: #2e7d32;
    }

    .search-btn {
        transition: all 0.2s ease;
    }

    .search-btn:hover {
        background: #e58f00;
        transform: scale(1.02);
    }

    .hero-gradient {
        background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
    }

    .floating {
        animation: floating 3s ease-in-out infinite;
    }

    @keyframes floating {
        0% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-5px);
        }
        100% {
            transform: translateY(0px);
        }
    }

    .loading-spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        border-top: 4px solid #2e7d32;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 2rem auto;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>

<section class="hero-gradient text-white py-12 md:py-20">
    <div class="max-w-screen-2xl mx-auto px-4 text-center">
        <h1 class="text-3xl md:text-4xl font-bold mb-4">Encontre o veículo seminovo perfeito para você</h1>
        <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto">Mais de 10.000 veículos anunciados em toda Minas Gerais</p>

        <div class="flex flex-wrap justify-center gap-2 mb-8" data-type-selector data-selected="carro">
            <button type="button" data-type="carro" class="tipo-btn ativo bg-white text-green-800 px-4 py-2 rounded-lg font-semibold border-2 border-green-800 flex items-center">
                <i class="fas fa-car mr-2"></i> Carro
            </button>
            <button type="button" data-type="moto" class="tipo-btn bg-white text-green-800 px-4 py-2 rounded-lg font-semibold border-2 border-green-800 flex items-center">
                <i class="fas fa-motorcycle mr-2"></i> Moto
            </button>
            <button type="button" data-type="caminhao" class="tipo-btn bg-white text-green-800 px-4 py-2 rounded-lg font-semibold border-2 border-green-800 flex items-center">
                <i class="fas fa-truck mr-2"></i> Caminhão
            </button>
        </div>

        <div class="max-w-3xl mx-auto relative" data-search-wrapper data-search-base="<?= e($searchPageUrl); ?>">
            <div class="autocomplete-container flex">
                <input id="heroCityInput" type="text" placeholder="Busque veículos por cidade..." class="flex-1 px-4 py-3 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-amber-500 text-gray-800" autocomplete="off" data-city-input>
                <button type="button" class="search-btn bg-amber-500 text-white px-6 py-3 rounded-r-lg font-semibold flex items-center justify-center gap-2" data-search-button>
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
                <div class="autocomplete-list hidden" data-suggestions></div>
            </div>
        </div>
    </div>
</section>

<section class="py-12 bg-white">
    <div class="max-w-screen-2xl mx-auto px-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Veículos em Destaque</h2>
            <a href="<?= e($searchPageUrl); ?>" class="text-amber-600 hover:text-amber-700 font-medium flex items-center justify-end">
                Ver todos <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>

        <div id="featuredAdsContainer" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" data-verified-icon="<?= asset_path('assets/img/verificado.png'); ?>" data-placeholder-image="<?= asset_path('assets/uploads/images/sample-car-1.jpg'); ?>">
            <?php if (!$featuredAds): ?>
                <div class="col-span-full">
                    <div class="bg-white border border-dashed border-green-300 rounded-2xl p-10 text-center text-green-800">
                            <p class="font-semibold">Nenhum anúncio premium disponível no momento.</p>
                            <p class="text-sm text-green-700 mt-2">Anúncios premium aparecem aqui — torne seu anúncio premium para obter mais destaque.</p>
                        </div>
                </div>
            <?php else: ?>
                <?php foreach ($featuredAds as $index => $ad): ?>
                    <?php
                        $images = array_map('asset_path', $ad['images']);
                        $imagesJson = e(json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                        $mainImage = $images[0] ?? asset_path('assets/uploads/images/sample-car-1.jpg');
                        $mileage = number_format((int)($ad['mileage'] ?? 0), 0, ',', '.');
                        $storeOwnerId = (int)($ad['user_id'] ?? 0);
                        $storeOwnerName = (string)($ad['owner_name'] ?? '');
                        $trackPosition = $index + 1;
                    ?>
                    <article class="vehicle-card bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col relative" data-ad-id="<?= (int)$ad['id']; ?>" data-images="<?= $imagesJson; ?>" data-store-owner="<?= $storeOwnerId; ?>" data-store-owner-name="<?= e($storeOwnerName); ?>" data-track-origin="homepage_featured" data-track-position="<?= $trackPosition; ?>">
                        <div class="relative">
                            <img src="<?= e($mainImage); ?>" alt="<?= e($ad['title']); ?>" class="w-full h-56 object-cover" data-primary-image data-ad-image>
                            <span class="price-badge"><?= e(format_currency((float)$ad['price'])); ?></span>
                            <?php if (!empty($ad['is_verified'])): ?>
                                <span class="verified-badge">
                                    <img src="<?= asset_path('assets/img/verificado.png'); ?>" alt="Anunciante verificado" class="w-4 h-4">
                                    Verificado
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-5 flex flex-col flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3"><?= e($ad['title']); ?></h3>
                            <div class="text-sm text-gray-600 space-y-1 mb-4">
                                <p class="flex items-center gap-2">
                                    <i class="fa-regular fa-calendar text-amber-500"></i>
                                    <span><?= e((string)$ad['model_year']); ?> | <?= e(ucfirst(strtolower($ad['color'] ?? ''))); ?> | <?= $mileage; ?> km</span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <i class="fa-solid fa-gear text-amber-500"></i>
                                    <span><?= e($ad['transmission']); ?> | <?= e($ad['fuel_type']); ?></span>
                                </p>
                            </div>
                            <p class="text-sm text-gray-600 flex items-center mb-5">
                                <i class="fa-solid fa-location-dot text-amber-500 mr-2"></i>
                                <?= e($ad['city']); ?>, <?= e($ad['state']); ?>
                            </p>
                            <a href="<?= e(ad_public_url($ad)); ?>" class="inline-flex items-center justify-center w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg transition-colors" data-track-ad-click>
                                <i class="fa-regular fa-eye mr-2"></i> Ver detalhes
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-12 bg-gray-50">
    <div class="max-w-screen-2xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-8 text-center">Marcas Populares</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php
                $popularBrands = [
                    'Honda', 'Toyota', 'Volkswagen', 'Chevrolet', 'Ford', 'Fiat'
                ];
            ?>
            <?php foreach ($popularBrands as $brand): ?>
                <div class="brand-card bg-white rounded-lg shadow p-4 flex flex-col items-center justify-center hover:bg-gray-50">
                    <img src="	https://images.icon-icons.com/577/PNG/256/ExecutiveCar_Black_icon-icons.com_54904.png" alt="<?= e($brand); ?>" class="h-16 mb-2 floating">
                    <span class="font-semibold text-gray-800"><?= e($brand); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-12 bg-green-700">
    <div class="max-w-screen-2xl mx-auto px-4 text-center">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-4">Quer vender seu veículo?</h2>
            <p class="text-lg text-green-100 mb-8">Anuncie agora e alcance milhares de compradores interessados em todo o Brasil.</p>
            <a href="<?= e($advertisePageUrl); ?>" class="inline-block bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition transform hover:scale-105">
                <i class="fas fa-plus-circle mr-2"></i> Anunciar Veículo
            </a>
        </div>
    </div>
</section>

<script src="/assets/js/autocomplete.js"></script>
<script src="/assets/js/homepage.js?v=<?= urlencode($homeJsVersion); ?>"></script>

<script>
// Pré-carrega cidades APÓS todos os scripts serem carregados
async function loadCitiesForHomepage() {
    if (typeof window.preloadCidades === 'function') {
        try {
            const cities = await window.preloadCidades();
            window.cidadesProximas = cities;
        } catch (error) {
            console.error('[HOMEPAGE] Erro ao carregar cidades:', error);
        }
    }
}

// Inicia o carregamento de cidades após o DOMContentLoaded
document.addEventListener('DOMContentLoaded', loadCitiesForHomepage);
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php require __DIR__ . '/propagandas/load_web.php'; ?>
<?php require __DIR__ . '/propagandas/load_local.php'; ?>
<?php require __DIR__ . '/propagandas/load_embed.php'; ?>
