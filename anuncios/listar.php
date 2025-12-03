<?php

declare(strict_types=1);


require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../propagandas/analytics/tracker.php';

// Rastrear acesso à página
AnalyticsTracker::trackPageLoad('listar');
require_once __DIR__ . '/../propagandas/analytics/tracker.php';

// Rastrear acesso à página
AnalyticsTracker::trackPageLoad('listar');

$user = current_user();
$pageTitle = 'Buscar Veículos | Classi Livre Autos';

$homeUrl = '/';
$searchPageUrl = '/anuncios/listar.php';
$servicesPageUrl = file_exists(__DIR__ . '/../servicos/index.php') ? '/servicos/index.php' : '#';
$advertisePageUrl = $user ? '/anuncios/criar.php' : '/login_system/login.php';
$accountPageUrl = '/anuncios/meus.php';
$searchJsPath = __DIR__ . '/../assets/js/search-page.js';
$searchJsVersion = file_exists($searchJsPath) ? (string)filemtime($searchJsPath) : (string)time();

function asset_path(string $path): string
{
    return '/' . ltrim($path, '/');
}

function build_remove_url(array $query, array $keys): string
{
    foreach ($keys as $key) {
        unset($query[$key]);
    }
    unset($query['page']);

    return $query ? '/anuncios/listar.php?' . http_build_query($query) : '/anuncios/listar.php';
}

function parse_location_filter(?string $input): array
{
    $city = null;
    $state = null;
    $display = trim((string)$input);

    if ($display === '') {
        return [$city, $state, ''];
    }

    $uppercase = strtoupper($display);

    if (in_array($uppercase, ['MG', 'MINAS GERAIS'], true)) {
        $state = 'MG';
        return [$city, $state, $display];
    }

    if (strlen($uppercase) === 2 && ctype_alpha($uppercase)) {
        $state = $uppercase;
        return [$city, $state, $display];
    }

    if (preg_match('/^(.*?)[,\s-]+([A-Za-z]{2})$/u', $display, $matches)) {
        $cityCandidate = trim($matches[1]);
        $stateCandidate = strtoupper($matches[2]);

        if ($cityCandidate !== '' && ctype_alpha($stateCandidate)) {
            $city = $cityCandidate;
            $state = $stateCandidate;
            return [$city, $state, $display];
        }
    }

    if (stripos($display, 'minas gerais') !== false) {
        $state = 'MG';
        $cityCandidate = trim(str_ireplace(['minas gerais', 'mg'], '', $display), ', -');
        $city = $cityCandidate !== '' ? $cityCandidate : null;
        return [$city, $state, $display];
    }

    return [$display, null, $display];
}

// F11: Carregar filtros de busca salva se search_id for passado
$searchIdParam = input_get('search_id');
if ($searchIdParam && is_numeric($searchIdParam)) {
    $searchId = (int)$searchIdParam;
    try {
        $searchStmt = db()->prepare('SELECT search_params FROM saved_searches WHERE id = ? AND user_id = ?');
        $currentUser = current_user();
        if ($currentUser) {
            $searchStmt->execute([$searchId, (int)$currentUser['id']]);
            $savedSearch = $searchStmt->fetch(PDO::FETCH_ASSOC);
            if ($savedSearch && is_string($savedSearch['search_params'])) {
                $params = json_decode($savedSearch['search_params'], true);
                if (is_array($params)) {
                    // Mesclar os filtros salvos com os parâmetros GET (GET tem prioridade)
                    foreach ($params as $key => $value) {
                        if (!isset($_GET[$key]) || $_GET[$key] === '') {
                            $_GET[$key] = $value;
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[F11][search_id] Erro ao carregar busca salva: '.$e->getMessage());
    }
}

$vehicleType = strtolower(trim((string)(input_get('tipo') ?? '')));
$brand = input_get('brand');
$model = input_get('model');

[$cityFilter, $stateFilter, $locationDisplayValue] = parse_location_filter(input_get('city'));
$legacyStateInput = input_get('state');
if ($legacyStateInput && !$stateFilter) {
    $stateFilter = strtoupper((string)$legacyStateInput);
}
if ($legacyStateInput && $locationDisplayValue === '') {
    $locationDisplayValue = (string)$legacyStateInput;
}

$minYearInput = input_get('year_min');
$maxYearInput = input_get('year_max');
$minMileageInput = input_get('mileage_min');
$maxMileageInput = input_get('mileage_max');
$minPriceInput = input_get('price_min');
$maxPriceInput = input_get('price_max');
$transmission = input_get('transmission');
$fuelType = input_get('fuel_type');
$color = input_get('color');
$order = input_get('sort', 'destaque');

$allowedOrders = ['destaque', 'maior_valor', 'menor_valor', 'mais_antigo', 'mais_novo', 'mais_recente', 'mais_antigo_pub', 'menor_km'];
if (!in_array($order, $allowedOrders, true)) {
    $order = 'destaque';
}

$sanitizeNumber = static function (?string $value): int {
    if ($value === null) {
        return 0;
    }

    $digits = preg_replace('/\D+/', '', $value);
    return $digits !== '' ? (int)$digits : 0;
};

$minYear = $sanitizeNumber($minYearInput);
$maxYear = $sanitizeNumber($maxYearInput);
$minMileage = $sanitizeNumber($minMileageInput);
$maxMileage = $sanitizeNumber($maxMileageInput);
$minPrice = $sanitizeNumber($minPriceInput);
$maxPrice = $sanitizeNumber($maxPriceInput);

// F08: Infinite Scroll
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';
$page = max(1, (int)(input_get('page') ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$conditions = ['ads.status = "approved"', '(ads.expires_at IS NULL OR ads.expires_at > NOW())'];
$params = [];

if ($brand) {
    $conditions[] = 'ads.brand LIKE :brand';
    $params[':brand'] = '%' . $brand . '%';
}

if ($model) {
    $conditions[] = 'ads.model LIKE :model';
    $params[':model'] = '%' . $model . '%';
}

if ($stateFilter) {
    $conditions[] = 'ads.state = :state';
    $params[':state'] = strtoupper($stateFilter);
}

if ($cityFilter) {
    $conditions[] = 'ads.city LIKE :city';
    $params[':city'] = '%' . $cityFilter . '%';
}

if ($minYear > 0) {
    $conditions[] = 'ads.manufacture_year >= :min_year';
    $params[':min_year'] = $minYear;
}

if ($maxYear > 0) {
    $conditions[] = 'ads.model_year <= :max_year';
    $params[':max_year'] = $maxYear;
}

if ($minPrice > 0) {
    $conditions[] = 'ads.price >= :min_price';
    $params[':min_price'] = $minPrice;
}

if ($maxPrice > 0) {
    $conditions[] = 'ads.price <= :max_price';
    $params[':max_price'] = $maxPrice;
}

if ($minMileage > 0) {
    $conditions[] = 'ads.mileage >= :min_mileage';
    $params[':min_mileage'] = $minMileage;
}

if ($maxMileage > 0) {
    $conditions[] = 'ads.mileage <= :max_mileage';
    $params[':max_mileage'] = $maxMileage;
}

if ($transmission) {
    $conditions[] = 'ads.transmission LIKE :transmission';
    $params[':transmission'] = '%' . $transmission . '%';
}

if ($fuelType) {
    $conditions[] = 'ads.fuel_type LIKE :fuel_type';
    $params[':fuel_type'] = '%' . $fuelType . '%';
}

if ($color) {
    $conditions[] = 'ads.color LIKE :color';
    $params[':color'] = '%' . $color . '%';
}

$vehicleTypeColumnExists = false;

try {
    $columnStmt = db()->query("SHOW COLUMNS FROM ads LIKE 'vehicle_type'");
    $vehicleTypeColumnExists = (bool)$columnStmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    $vehicleTypeColumnExists = false;
}

if ($vehicleTypeColumnExists && $vehicleType && $vehicleType !== 'todos') {
    $conditions[] = 'ads.vehicle_type = :vehicle_type';
    $params[':vehicle_type'] = $vehicleType;
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$premiumCaseSql = 'CASE WHEN users.is_premium = 1 OR EXISTS (SELECT 1 FROM premium_subscriptions ps WHERE ps.user_id = users.id AND (ps.expires_at IS NULL OR ps.expires_at > NOW())) THEN 1 ELSE 0 END';
$baseRecencyOrder = 'COALESCE(ads.approved_at, ads.updated_at, ads.created_at) DESC';

switch ($order) {
    case 'maior_valor':
        $orderClauses = ['ads.price DESC', $baseRecencyOrder];
        break;
    case 'menor_valor':
        $orderClauses = ['ads.price ASC', $baseRecencyOrder];
        break;
    case 'mais_antigo':
        $orderClauses = ['ads.manufacture_year ASC', 'ads.model_year ASC', $baseRecencyOrder];
        break;
    case 'mais_novo':
        $orderClauses = ['ads.model_year DESC', 'ads.manufacture_year DESC', $baseRecencyOrder];
        break;
    case 'mais_recente':
        $orderClauses = ['COALESCE(ads.approved_at, ads.updated_at, ads.created_at) DESC'];
        break;
    case 'mais_antigo_pub':
        $orderClauses = ['COALESCE(ads.approved_at, ads.updated_at, ads.created_at) ASC'];
        break;
    case 'menor_km':
        $orderClauses = ['ads.mileage ASC', $baseRecencyOrder];
        break;
    default:
        $orderClauses = [$baseRecencyOrder];
        break;
}

$orderClauses = array_unique($orderClauses);
$orderSql = implode(', ', $orderClauses);

$countStmt = db()->prepare("SELECT COUNT(*) FROM ads $whereSql");
$countStmt->execute($params);
$totalAds = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalAds / $perPage));

$querySql = "SELECT ads.*, users.name AS owner_name, ads.verified_badge AS is_verified,
    COALESCE(ads.approved_at, ads.updated_at, ads.created_at) AS sort_reference
    FROM ads
    JOIN users ON users.id = ads.user_id
    $whereSql
    ORDER BY is_verified DESC, $orderSql
    LIMIT :limit OFFSET :offset";

$listStmt = db()->prepare($querySql);

foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}

$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$ads = $listStmt->fetchAll();

$mediaMap = [];

if ($ads) {
    $adIds = array_map(static fn(array $ad): int => (int)$ad['id'], $ads);
    $placeholders = implode(',', array_fill(0, count($adIds), '?'));

    $mediaStmt = db()->prepare("SELECT ad_id, filepath FROM ad_media WHERE ad_id IN ($placeholders) AND type = 'image' ORDER BY id ASC");
    $mediaStmt->execute($adIds);

    while ($row = $mediaStmt->fetch(PDO::FETCH_ASSOC)) {
        $mediaMap[(int)$row['ad_id']][] = $row['filepath'];
    }

    foreach ($ads as &$ad) {
        $id = (int)$ad['id'];
        $ad['images'] = $mediaMap[$id] ?? ['assets/uploads/images/sample-car-1.jpg'];
        $ad['is_verified'] = (int)$ad['is_verified'] === 1;
        
        // Calculate if ad is new (created within last 7 days)
        $createdAt = new DateTime($ad['created_at']);
        $now = new DateTime();
        $daysOld = $now->diff($createdAt)->days;
        $ad['is_new'] = $daysOld <= 7;
    }

    unset($ad);
}

$queryParams = $_GET;
$activeFilters = [];

if (!empty($vehicleType) && $vehicleType !== 'todos') {
    $activeFilters[] = [
        'label' => 'Tipo: ' . ucfirst($vehicleType),
        'url' => build_remove_url($queryParams, ['tipo']),
    ];
}

if ($cityFilter || $stateFilter || $locationDisplayValue !== '') {
    $parts = [];

    if ($cityFilter) {
        $parts[] = $cityFilter;
    }

    if ($stateFilter) {
        $parts[] = strtoupper($stateFilter);
    }

    if (!$parts && $locationDisplayValue !== '') {
        $parts[] = $locationDisplayValue;
    }

    $activeFilters[] = [
        'label' => 'Localização: ' . implode(' / ', $parts),
        'url' => build_remove_url($queryParams, ['city', 'state']),
    ];
}

if ($brand) {
    $activeFilters[] = [
        'label' => 'Marca: ' . $brand,
        'url' => build_remove_url($queryParams, ['brand']),
    ];
}

if ($model) {
    $activeFilters[] = [
        'label' => 'Modelo: ' . $model,
        'url' => build_remove_url($queryParams, ['model']),
    ];
}

if ($minYear > 0) {
    $activeFilters[] = [
        'label' => 'Ano mínimo: ' . $minYear,
        'url' => build_remove_url($queryParams, ['year_min']),
    ];
}

if ($maxYear > 0) {
    $activeFilters[] = [
        'label' => 'Ano máximo: ' . $maxYear,
        'url' => build_remove_url($queryParams, ['year_max']),
    ];
}

if ($minPrice > 0) {
    $activeFilters[] = [
        'label' => 'Preço mínimo: ' . format_currency((float)$minPrice),
        'url' => build_remove_url($queryParams, ['price_min']),
    ];
}

if ($maxPrice > 0) {
    $activeFilters[] = [
        'label' => 'Preço máximo: ' . format_currency((float)$maxPrice),
        'url' => build_remove_url($queryParams, ['price_max']),
    ];
}

if ($transmission) {
    $activeFilters[] = [
        'label' => 'Câmbio: ' . $transmission,
        'url' => build_remove_url($queryParams, ['transmission']),
    ];
}

if ($fuelType) {
    $activeFilters[] = [
        'label' => 'Combustível: ' . $fuelType,
        'url' => build_remove_url($queryParams, ['fuel_type']),
    ];
}

$resultsLabel = $totalAds === 1 ? '1 veículo encontrado' : $totalAds . ' veículos encontrados';

// F08: Se for AJAX request, retornar apenas os anúncios em JSON
if ($isAjaxRequest) {
    header('Content-Type: application/json; charset=utf-8');
    
    $adsHtml = '';
    if ($ads) {
    foreach ($ads as $index => $ad) {
            $images = array_map(fn($img) => '/' . ltrim($img, '/'), $ad['images'] ?? []);
            $mainImage = $images[0] ?? '/assets/img/no-image.png';
            $imagesJson = htmlspecialchars(json_encode($images, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
            $mileage = $ad['mileage'] > 0 ? number_format((int)$ad['mileage'], 0, ',', '.') : '0';
            
            // Montar detalhes primários (ano | cor | km)
            $primaryDetails = [];
            if (!empty($ad['model_year'])) $primaryDetails[] = (string)$ad['model_year'];
            if (!empty($ad['color'])) $primaryDetails[] = ucfirst(strtolower((string)$ad['color']));
            if ($mileage !== '0') $primaryDetails[] = $mileage . ' km';
            $primaryText = implode(' | ', $primaryDetails);
            
            // Montar detalhes secundários (câmbio | combustível)
            $secondaryDetails = [];
            if (!empty($ad['transmission'])) $secondaryDetails[] = (string)$ad['transmission'];
            if (!empty($ad['fuel_type'])) $secondaryDetails[] = (string)$ad['fuel_type'];
            $secondaryText = implode(' | ', $secondaryDetails);
            
            $location = trim((string)(($ad['city'] ?? '') . ' / ' . ($ad['state'] ?? '')), ' /');
            $adUrl = ad_public_url($ad);
            $priceFormatted = format_currency((float)$ad['price']);
            $storeOwnerId = (int)($ad['user_id'] ?? 0);
            $storeOwnerName = (string)($ad['owner_name'] ?? '');
            $trackPosition = $offset + $index + 1;
            
            // Payload para favoritar
            $favoritePayload = [
                'id' => (int)$ad['id'],
                'title' => $ad['title'],
                'price' => (float)$ad['price'],
                'price_formatted' => $priceFormatted,
                'manufacture_year' => (int)($ad['manufacture_year'] ?? 0),
                'model_year' => (int)($ad['model_year'] ?? 0),
                'mileage' => (int)($ad['mileage'] ?? 0),
                'transmission' => $ad['transmission'] ?? null,
                'fuel_type' => $ad['fuel_type'] ?? null,
                'image' => $mainImage,
                'url' => $adUrl,
                'city' => $ad['city'] ?? '',
                'state' => $ad['state'] ?? '',
                'is_verified' => !empty($ad['is_verified']),
                'store_id' => $storeOwnerId,
            ];
            $favoritePayloadJson = htmlspecialchars(json_encode($favoritePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
            $verifiedBadge = !empty($ad['is_verified']) ? '
                <span style="
                    position: absolute;
                    top: 12px;
                    right: 12px;
                    background: white;
                    border: none;
                    color: #166534;
                    padding: 0.35rem 0.75rem;
                    border-radius: 2rem;
                    font-size: 0.75rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 0.35rem;
                ">
                    <img src="/assets/img/verificado.png" alt="Verificado" style="width: 14px; height: 14px;">
                    <span>Verificado</span>
                </span>
            ' : '';
            
            ob_start();
            ?>
<article style="
    background: white;
    border-radius: 1rem;
    box-shadow: 0 10px 15px rgba(15, 23, 42, 0.12);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
" class="vehicle-card" data-ad-id="<?= (int)$ad['id']; ?>" data-images="<?= $imagesJson; ?>" data-is-verified="<?= !empty($ad['is_verified']) ? 'true' : 'false'; ?>"<?= $storeOwnerId > 0 ? ' data-store-owner="' . $storeOwnerId . '"' : ''; ?><?= $storeOwnerName !== '' ? ' data-store-owner-name="' . e($storeOwnerName) . '"' : ''; ?> data-track-origin="search_results" data-track-position="<?= $trackPosition; ?>">
    <div style="position: relative;">
        <a href="<?= e($adUrl); ?>" style="display: block;" data-track-ad-click>
            <img src="<?= e($mainImage); ?>" alt="<?= e($ad['title']); ?>" style="width: 100%; height: 192px; object-fit: cover; display: block;" data-primary-image data-ad-image>
        </a>
        <span style="
            position: absolute;
            top: 12px;
            left: 12px;
            background: #166534;
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 0.85rem;
        "><?= e($priceFormatted); ?></span>
        <?= $verifiedBadge; ?>
    </div>
    <div style="padding: 1.25rem; display: flex; flex-direction: column; flex: 1;">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem;">
            <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1f2937; line-height: 1.35; flex: 1;">
                <?= e($ad['title']); ?>
            </h3>
            <button type="button"
                class="vehicle-card__favorite vehicle-card__favorite--inline"
                data-favorite-toggle
                data-favorite-payload="<?= $favoritePayloadJson; ?>"
                aria-pressed="false"
                title="Marcar como preferido">
                <i class="fas fa-heart"></i>
            </button>
        </div>
        <div style="display: grid; gap: 0.5rem; font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem; flex: 1;">
            <?php if ($primaryText): ?>
                <p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-regular fa-calendar" style="color: #f59e0b;"></i>
                    <span><?= e($primaryText); ?></span>
                </p>
            <?php endif; ?>
            <?php if ($secondaryText): ?>
                <p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-gear" style="color: #f59e0b;"></i>
                    <span><?= e($secondaryText); ?></span>
                </p>
            <?php endif; ?>
        </div>
        <?php if ($location): ?>
            <p style="margin: 0 0 1.25rem; font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-location-dot" style="color: #f59e0b;"></i>
                <span><?= e($location); ?></span>
            </p>
        <?php endif; ?>
        <a href="<?= e($adUrl); ?>"
            style="
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                background: #166534;
                color: white;
                padding: 0.625rem 1rem;
                border-radius: 0.5rem;
                text-decoration: none;
                font-weight: 600;
                transition: background 0.2s;
                gap: 0.5rem;
                font-size: 0.9rem;
            "
            onmouseover="this.style.background='#15803d'"
            onmouseout="this.style.background='#166534'"
            data-track-ad-click
        >
            <i class="fa-regular fa-eye"></i> Ver detalhes
        </a>
    </div>
</article>
            <?php
            $adsHtml .= ob_get_clean();
        }
    }
    
    echo json_encode([
        'success' => true,
        'page' => $page,
        'totalPages' => $totalPages,
        'totalAds' => $totalAds,
        'ads' => $adsHtml,
        'hasMore' => $page < $totalPages
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

require __DIR__ . '/../partials/header.php';
?>

<style>
    * {
        font-family: 'Inter', sans-serif;
    }

    .search-hero {
        background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
        color: white;
        padding: 3rem 1.5rem;
        margin-bottom: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .search-hero h1 {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .search-hero p {
        font-size: 1.1rem;
        opacity: 0.95;
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
        z-index: 10000;
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
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .autocomplete-list div:hover {
        background-color: #f3f4f6;
    }

    @media (max-width: 480px) {
        .autocomplete-list {
            max-height: 150px;
        }

        .autocomplete-list div {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
    }

    @media (max-width: 375px) {
        .autocomplete-list {
            max-height: 120px;
        }

        .autocomplete-list div {
            padding: 0.4rem 0.6rem;
            font-size: 0.7rem;
        }
    }

    .price-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: #2e7d32;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-weight: 600;
        font-size: 0.875rem;
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

    .new-badge {
        position: absolute;
        bottom: 0.5rem;
        right: 0.5rem;
        background: rgba(46, 125, 50, 0.95);
        color: #fbbf24;
        padding: 0.35rem 0.7rem;
        border-radius: 9999px;
        font-weight: 700;
        font-size: 0.7rem;
        letter-spacing: 0.08em;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        box-shadow: 0 2px 8px rgba(46, 125, 50, 0.4);
        animation: pulse-new 2s infinite;
        z-index: 10;
    }

    .new-badge i {
        color: #fbbf24;
        font-size: 0.85rem;
    }

    @keyframes pulse-new {
        0%, 100% {
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.4);
            transform: scale(1);
        }
        50% {
            box-shadow: 0 2px 12px rgba(46, 125, 50, 0.6);
            transform: scale(1.05);
        }
    }

    .vehicle-card {
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .vehicle-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .filter-card {
        transition: all 0.3s ease;
    }

    .filter-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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

    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        border-radius: 9999px;
        padding: 0.4rem 0.85rem;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .filter-chip i {
        cursor: pointer;
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

    /* Sticky Header com Busca Rápida */
    .sticky-search-header {
        position: sticky;
        top: 95px;
        z-index: 15;
        background: linear-gradient(135deg, #fffbf0 0%, #fef8f3 100%);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        padding: 1rem;
        margin-bottom: 1rem;
        max-width: 100%;
        border-bottom: 2px solid rgba(251, 191, 36, 0.15);
    }

    .quick-search-container {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        gap: 0.75rem;
        align-items: center;
        padding: 0 1rem;
    }

    .quick-search-field {
        flex: 1;
        min-width: 0;
    }

    .quick-search-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 1rem;
        transition: all 0.2s ease;
    }

    .quick-search-input:focus {
        outline: none;
        border-color: #2e7d32;
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
    }

    .quick-search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-top: none;
        border-radius: 0 0 0.5rem 0.5rem;
        max-height: 300px;
        overflow-y: auto;
        z-index: 50;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .quick-search-suggestions.hidden {
        display: none;
    }

    .quick-search-suggestion {
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s;
    }

    .quick-search-suggestion:hover {
        background-color: #f9fafb;
    }

    .quick-search-suggestion:last-child {
        border-bottom: none;
    }

    .quick-search-suggestion strong {
        color: #2e7d32;
    }

    @media (max-width: 640px) {
        .sticky-search-header {
            padding: 0.75rem;
        }

        .quick-search-input {
            font-size: 16px; /* Previne zoom em iOS */
        }
    }

    /* Filtros Colapsáveis (Mobile Drawer) */
    .filters-toggle-btn {
        width: 100%;
        padding: 0.75rem 1.5rem;
        background: #2e7d32;
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .filters-toggle-btn:hover {
        background: #1b5e20;
        transform: translateY(-2px);
    }

    .filters-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 30;
    }

    .filters-overlay.open {
        display: block;
    }

    .filters-drawer {
        position: fixed;
        left: -100%;
        top: 0;
        width: 80%;
        max-width: 350px;
        height: 100vh;
        background: white;
        z-index: 50;
        transition: left 0.3s ease;
        overflow-y: auto;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
    }

    .filters-drawer.open {
        left: 0;
    }

    .filters-drawer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
        position: sticky;
        top: 0;
        z-index: 51;
    }

    .filters-drawer-header h3 {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
    }

    .filters-drawer-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
        transition: color 0.2s;
        padding: 0;
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .filters-drawer-close:hover {
        color: #1f2937;
    }

    .filters-drawer-content {
        padding: 0.5rem 0.5rem 3rem 0.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filters-drawer-content form {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        font-size: 0.85rem;
    }

    .filters-drawer-content form h2 {
        font-size: 0.9rem;
        margin: 0.25rem 0;
        line-height: 1.2;
    }

    .filters-drawer-content form input,
    .filters-drawer-content form select {
        font-size: 0.8rem;
        padding: 0.3rem 0.4rem;
        line-height: 1.4;
    }

    .filters-drawer-content form button,
    .filters-drawer-content form a {
        font-size: 0.8rem;
        padding: 0.35rem 0.75rem;
        margin: 0.1rem 0;
        line-height: 1.3;
    }

    .filters-drawer-content form > div {
        margin-bottom: 0.2rem;
    }

    .filters-drawer-content form .grid {
        gap: 0.25rem !important;
    }

    @media (max-width: 1024px) {
        .filters-drawer {
            width: 90%;
            max-width: 100%;
        }

        main.max-w-screen-2xl > div.flex {
            flex-direction: column;
        }

        aside.w-full.lg\:w-80 {
            width: 100% !important;
            display: none;
        }

        aside.w-full.lg\:w-80.open-mobile {
            display: block;
        }
    }

    @media (max-width: 768px) {
        .filters-drawer {
            width: 80%;
            max-width: 80vw;
        }

        .filters-drawer-content {
            padding: 0.4rem 0.4rem 2.5rem 0.4rem;
        }
    }

    @media (max-width: 480px) {
        .filters-drawer {
            width: 88%;
            max-width: 88vw;
            height: 100vh;
            max-height: 100vh;
        }

        .filters-drawer-content {
            padding: 0.3rem 0.3rem 2.5rem 0.3rem;
            gap: 0.3rem;
        }

        .filters-drawer-header {
            padding: 0.4rem;
            min-height: 2.5rem;
        }

        .filters-drawer-header h3 {
            font-size: 1rem;
            margin: 0;
        }

        .filters-drawer-content form {
            gap: 0.3rem;
        }

        .filters-drawer-content form h2 {
            font-size: 0.8rem;
            margin: 0.15rem 0;
        }

        .filters-drawer-content form input,
        .filters-drawer-content form select {
            font-size: 0.75rem;
            padding: 0.25rem 0.3rem;
        }

        .filters-drawer-content form button,
        .filters-drawer-content form a {
            font-size: 0.75rem;
            padding: 0.3rem 0.5rem;
            margin: 0.05rem 0;
        }

        .filters-drawer-content form > div {
            margin-bottom: 0.15rem;
        }

        .filters-drawer-content form .grid {
            gap: 0.2rem !important;
        }

        .filters-drawer-content form button:not(.color-filter-btn) {
            padding: 0.3rem 0.5rem !important;
        }

        /* Color buttons - mais compactos */
        .color-filter-btn {
            width: 2rem !important;
            height: 2rem !important;
            font-size: 0.6rem !important;
        }
    }

    @media (max-width: 375px) {
        .filters-drawer {
            width: 95%;
            max-width: 95vw;
        }

        .filters-drawer-content {
            padding: 0.25rem 0.25rem 2rem 0.25rem;
        }

        .filters-drawer-content form h2 {
            font-size: 0.75rem;
        }

        .filters-drawer-content form input,
        .filters-drawer-content form select {
            font-size: 0.7rem;
            padding: 0.2rem 0.3rem;
        }

        .filters-drawer-content form button,
        .filters-drawer-content form a {
            font-size: 0.7rem;
            padding: 0.25rem 0.4rem;
        }

        .color-filter-btn {
            width: 1.75rem !important;
            height: 1.75rem !important;
        }
    }

    /* Price Slider Customization - F03 Native Range Sliders ✅ */
    .price-slider {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 100%;
        height: 0.5rem;
        background: linear-gradient(to right, #2e7d32 0%, #2e7d32 100%);
        background-size: 100% 100%;
        background-repeat: no-repeat;
        border-radius: 0.375rem;
        outline: none;
        -webkit-slider-thumb-appearance: none;
        -moz-range-thumb-appearance: none;
    }

    /* Thumb do slider (Chrome, Safari, Edge) */
    .price-slider::-webkit-slider-thumb {
        appearance: none;
        -webkit-appearance: none;
        width: 1.25rem;
        height: 1.25rem;
        background: white;
        border: 2px solid #2e7d32;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        transition: all 0.2s ease;
    }

    .price-slider::-webkit-slider-thumb:hover {
        box-shadow: 0 4px 8px rgba(46, 125, 50, 0.3);
        transform: scale(1.1);
    }

    .price-slider::-webkit-slider-thumb:active {
        cursor: grabbing;
        box-shadow: 0 4px 12px rgba(46, 125, 50, 0.4);
    }

    /* Thumb do slider (Firefox) */
    .price-slider::-moz-range-thumb {
        width: 1.25rem;
        height: 1.25rem;
        background: white;
        border: 2px solid #2e7d32;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        transition: all 0.2s ease;
    }

    .price-slider::-moz-range-thumb:hover {
        box-shadow: 0 4px 8px rgba(46, 125, 50, 0.3);
        transform: scale(1.1);
    }

    /* Track do slider (Firefox) */
    .price-slider::-moz-range-track {
        background: transparent;
        border: none;
    }

    /* Range progress (Firefox) */
    .price-slider::-moz-range-progress {
        background: #2e7d32;
        height: 0.5rem;
        border-radius: 0.375rem;
    }

    /* Estilo geral da faixa de preço */
    .price-slider {
        display: block;
        margin: 0;
    }

    /* F05: Botões de Quilometragem */
    .mileage-filter-btn {
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .mileage-filter-btn:hover {
        background-color: #f0fdf4;
        box-shadow: 0 2px 4px rgba(46, 125, 50, 0.15);
        transform: translateY(-2px);
    }

    .mileage-filter-btn.ativo {
        background-color: #2e7d32;
        color: white;
        border-color: #1b5e20;
        box-shadow: 0 4px 8px rgba(46, 125, 50, 0.3);
    }

    .mileage-filter-btn.ativo:hover {
        background-color: #1b5e20;
    }

    /* F06: Botões de Cor */
    .color-filter-btn {
        transition: all 0.2s ease;
        cursor: pointer;
        height: 2.5rem;
        width: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0;
        border-radius: 0.5rem;
        position: relative;
    }

    .color-filter-btn:hover {
        box-shadow: 0 2px 4px rgba(46, 125, 50, 0.15);
        transform: translateY(-2px);
    }

    .color-filter-btn.ativo {
        background-color: #f0fdf4;
        box-shadow: 0 4px 8px rgba(46, 125, 50, 0.3);
    }

    .color-filter-btn.all-colors:hover {
        background-color: #f9fafb;
    }

    /* Color circle classes for F06 */
    .color-circle-branco { background-color: #FFFFFF !important; border: 2px solid #cccccc !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-preto { background-color: #000000 !important; border: 2px solid #333333 !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-cinza { background-color: #808080 !important; border: 2px solid #4a4a4a !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-prata { background-color: #C0C0C0 !important; border: 2px solid #808080 !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-azul { background-color: #0066CC !important; border: 2px solid #003f99 !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-vermelho { background-color: #CC0000 !important; border: 2px solid #800000 !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-verde { background-color: #009900 !important; border: 2px solid #006600 !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-laranja { background-color: #FF8800 !important; border: 2px solid #cc6600 !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-amarelo { background-color: #FFFF00 !important; border: 2px solid #cccc00 !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-bege { background-color: #D4AF37 !important; border: 2px solid #a68627 !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-marrom { background-color: #8B4513 !important; border: 2px solid #5c2e0f !important; width: 1.25rem !important; height: 1.25rem !important; }
    .color-circle-roxo { background-color: #800080 !important; border: 2px solid #550055 !important; width: 1.25rem !important; height: 1.25rem !important; }

    /* Placeholder de Price Slider (Old noUiSlider styles - deprecated) */
        background: linear-gradient(90deg, #2e7d32 0%, #1b5e20 100%);
    }

    .noUi-handle {
        background: white;
        border: 2px solid #2e7d32;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        cursor: grab;
        transition: all 0.2s ease;
    }

    .noUi-handle:hover {
        box-shadow: 0 4px 8px rgba(46, 125, 50, 0.3);
        transform: scale(1.1);
    }

    .noUi-handle:active {
        cursor: grabbing;
    }

    .noUi-tooltip {
        background: #2e7d32;
        color: white;
        border: none;
        border-radius: 0.375rem;
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .noUi-marker-horizontal.noUi-marker-large {
        height: 5px;
    }

    #priceSlider {
        padding: 0 5px;
    }

    /* Modal de Salvar Busca */
    #saveSearchModal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: flex-start;
        justify-content: center;
        z-index: 9999 !important;
        padding-top: 130px;
        overflow-y: auto;
    }

    #saveSearchModal.hidden {
        display: none !important;
    }

    #saveSearchModal:not(.hidden) {
        display: flex !important;
    }

    #saveSearchModal .bg-white {
        position: static;
        margin-top: 0;
    }

</style>

<section class="search-hero">
    <h1>Buscar Veículos</h1>
    <p>Encontre o veículo perfeito entre nossos anúncios. Use os filtros para refinar sua busca e descobrir as melhores opções.</p>
</section>

<!-- Sticky Header com Busca Rápida (abaixo do hero) -->
<div class="sticky-search-header">
    <div class="quick-search-container">
        <div class="quick-search-field" style="position: relative;">
            <input 
                type="text" 
                id="quickSearch" 
                class="quick-search-input" 
                placeholder="🔍 Buscar marca/modelo (ex: Honda Civic)"
                autocomplete="off"
            >
            <div id="quickSearchSuggestions" class="quick-search-suggestions hidden"></div>
        </div>
        <a href="<?= $searchPageUrl; ?>" class="search-btn" style="white-space: nowrap; padding: 0.75rem 1.5rem;">
            <i class="fas fa-sliders-h"></i> Filtros
        </a>
    </div>
</div>

<!-- Botão de toggle para mobile (fica abaixo do sticky search) -->
<div class="max-w-screen-2xl mx-auto px-4 py-4 lg:hidden">
    <button class="filters-toggle-btn" id="filtersToggleBtn" title="Abrir filtros">
        <i class="fas fa-sliders-h"></i> Filtros
    </button>
</div>

<!-- Overlay para drawer de filtros -->
<div class="filters-overlay" id="filtersOverlay"></div>

<!-- Drawer de filtros (mobile) -->
<div class="filters-drawer" id="filtersDrawer">
    <div class="filters-drawer-header">
        <h3>Filtros</h3>
        <button type="button" class="filters-drawer-close" id="filtersDrawerClose" title="Fechar filtros">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="filters-drawer-content">
    </div>
</div>

<main class="max-w-screen-2xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <aside class="w-full lg:w-80">
            <form method="get" class="filter-card bg-white rounded-xl shadow-md p-6" data-filter-form>
                <h2 class="text-xl font-bold text-gray-800 mb-6">Filtrar Veículos</h2>
                <input type="hidden" name="tipo" value="<?= e($vehicleType ?: 'todos'); ?>" data-type-input>

                <div class="mb-6">
                    <span class="block text-sm font-medium text-gray-700 mb-2">Tipo de Veículo</span>
                    <div class="grid grid-cols-2 gap-2" data-type-selector data-selected="<?= e($vehicleType ?: 'todos'); ?>">
                        <?php
                            $typeOptions = [
                                'todos' => ['label' => 'Todos', 'icon' => 'fa-layer-group'],
                                'carro' => ['label' => 'Carro', 'icon' => 'fa-car'],
                                'moto' => ['label' => 'Moto', 'icon' => 'fa-motorcycle'],
                                'caminhao' => ['label' => 'Caminhão', 'icon' => 'fa-truck'],
                                'outros' => ['label' => 'Outros', 'icon' => 'fa-shuttle-van'],
                            ];
                            foreach ($typeOptions as $key => $meta):
                                $isActive = ($vehicleType ?: 'todos') === $key;
                        ?>
                            <button type="button" data-type="<?= e($key); ?>" class="tipo-btn <?= $isActive ? 'ativo' : ''; ?> bg-white text-green-800 px-3 py-2 rounded-lg font-semibold border-2 border-green-800 text-sm flex items-center justify-center">
                                <i class="fas <?= e($meta['icon']); ?> mr-1"></i> <?= e($meta['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">Localização</label>
                    <div class="relative">
                        <input id="city" name="city" type="text" value="<?= e($locationDisplayValue); ?>" placeholder="Digite a cidade ou estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" autocomplete="off" data-autocomplete-field="city">
                        <div id="cidadeSuggestions" class="autocomplete-list hidden"></div>
                    </div>
                </div>

                <input type="hidden" id="vehicle_type" value="<?= e($vehicleType ?: 'carro'); ?>">

                <div class="mb-6">
                    <label for="brand" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                    <div class="relative">
                        <input id="brand" name="brand" type="text" value="<?= e($brand ?? ''); ?>" placeholder="Ex: Honda" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" data-autocomplete-field="brand">
                        <div id="marcaSuggestions" class="autocomplete-list hidden"></div>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="model" class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                    <div class="relative">
                        <input id="model" name="model" type="text" value="<?= e($model ?? ''); ?>" placeholder="Ex: Civic" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" data-autocomplete-field="model">
                        <div id="modeloSuggestions" class="autocomplete-list hidden"></div>
                    </div>
                </div>

                <div class="mb-6">
                    <span class="block text-sm font-medium text-gray-700 mb-2">Ano</span>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="year_min" class="block text-xs text-gray-500 mb-1">Mínimo</label>
                            <input id="year_min" name="year_min" type="number" value="<?= $minYear > 0 ? e((string)$minYear) : ''; ?>" placeholder="Ex: 2010" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label for="year_max" class="block text-xs text-gray-500 mb-1">Máximo</label>
                            <input id="year_max" name="year_max" type="number" value="<?= $maxYear > 0 ? e((string)$maxYear) : ''; ?>" placeholder="Ex: 2024" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <span class="block text-sm font-medium text-gray-700 mb-3">Quilometragem (km)</span>
                    <!-- F05: Quilometragem com opções pré-definidas -->
                    <div class="grid grid-cols-2 gap-2" data-mileage-selector>
                        <?php
                        $currentMileageMin = isset($_GET['mileage_min']) && is_numeric($_GET['mileage_min']) ? (int)$_GET['mileage_min'] : 0;
                        $currentMileageMax = isset($_GET['mileage_max']) && is_numeric($_GET['mileage_max']) ? (int)$_GET['mileage_max'] : 0;
                        
                        $mileageOptions = array(
                            array('label' => 'Até 50k', 'min' => 0, 'max' => 50000, 'icon' => 'fa-gauge-high'),
                            array('label' => '50k - 100k', 'min' => 50000, 'max' => 100000, 'icon' => 'fa-gauge'),
                            array('label' => '100k - 200k', 'min' => 100000, 'max' => 200000, 'icon' => 'fa-gauge-low'),
                            array('label' => '200k+', 'min' => 200000, 'max' => 999999, 'icon' => 'fa-odometer')
                        );
                        
                        foreach ($mileageOptions as $option):
                            $isSelected = ($currentMileageMin == $option['min'] && $currentMileageMax == $option['max']);
                        ?>
                            <button type="button" class="mileage-filter-btn <?= $isSelected ? 'ativo' : ''; ?> bg-white text-green-800 px-3 py-2 rounded-lg font-semibold border-2 border-green-800 text-sm flex items-center justify-center gap-1 transition" 
                                    data-mileage-min="<?= $option['min']; ?>" data-mileage-max="<?= $option['max']; ?>">
                                <i class="fas <?= $option['icon']; ?>"></i> <?= $option['label']; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">
                        <span id="mileageDisplay">
                            <?php 
                            if ($currentMileageMin > 0 || $currentMileageMax > 0) {
                                $minDisplay = $currentMileageMin > 0 ? number_format($currentMileageMin, 0, ',', '.') : '0';
                                $maxDisplay = $currentMileageMax > 0 ? number_format($currentMileageMax, 0, ',', '.') : '∞';
                                echo 'Selecionado: ' . $minDisplay . ' - ' . $maxDisplay . ' km';
                            }
                            ?>
                        </span>
                    </div>
                    <!-- Hidden inputs para quilometragem APENAS -->
                    <input type="hidden" id="mileage_min" name="mileage_min" value="<?= $currentMileageMin > 0 ? $currentMileageMin : ''; ?>">
                    <input type="hidden" id="mileage_max" name="mileage_max" value="<?= $currentMileageMax > 0 ? $currentMileageMax : ''; ?>">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Preço</label>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <!-- F03: Price Range Slider com input range nativo -->
                        <div class="mb-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs text-gray-600">Mínimo:</span>
                                <span class="text-sm font-bold text-green-700" id="priceMinDisplay">R$ 0</span>
                            </div>
                            <input type="range" id="priceMinSlider" name="price_min_slider" min="0" max="500000" step="5000" 
                                   value="<?= $minPrice > 0 ? (int)$minPrice : 0; ?>" 
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer price-slider">
                        </div>
                        
                        <div class="mb-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs text-gray-600">Máximo:</span>
                                <span class="text-sm font-bold text-green-700" id="priceMaxDisplay">R$ 500.000</span>
                            </div>
                            <input type="range" id="priceMaxSlider" name="price_max_slider" min="0" max="500000" step="5000" 
                                   value="<?= $maxPrice > 0 ? (int)$maxPrice : 500000; ?>" 
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer price-slider">
                        </div>
                        
                        <div class="text-center text-sm text-gray-600 mt-3 p-2 bg-white rounded border border-gray-200">
                            <span id="priceRangeDisplay">R$ 0 até R$ 500.000</span>
                        </div>
                    </div>
                    <!-- Hidden inputs para envio do formulário -->
                    <input type="hidden" id="price_min" name="price_min" value="<?= $minPrice > 0 ? e((string)$minPrice) : ''; ?>">
                    <input type="hidden" id="price_max" name="price_max" value="<?= $maxPrice > 0 ? e((string)$maxPrice) : ''; ?>">
                </div>

                <div class="mb-6">
                    <label for="transmission" class="block text-sm font-medium text-gray-700 mb-2">Câmbio</label>
                    <select id="transmission" name="transmission" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" data-autocomplete-field="transmission">
                        <option value="">Todos</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label for="fuel_type" class="block text-sm font-medium text-gray-700 mb-2">Combustível</label>
                    <select id="fuel_type" name="fuel_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">Todos</option>
                        <?php
                            $fuels = ['Gasolina', 'Etanol', 'Flex', 'Diesel', 'Elétrico', 'Híbrido', 'GNV'];
                            foreach ($fuels as $fuel):
                        ?>
                            <option value="<?= e($fuel); ?>" <?= $fuelType === $fuel ? 'selected' : ''; ?>><?= e($fuel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Cor</label>
                    <!-- F06: Filtro de Cor com bolinhas coloridas -->
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3" data-color-selector>
                        <button type="button" class="color-filter-btn all-colors bg-white border-2 border-gray-300 select-none" 
                                data-color="">
                            <span class="w-5 h-5 rounded-full bg-gradient-to-br from-gray-200 to-gray-400 border border-gray-300 flex-shrink-0"></span>
                        </button>
                        <?php
                        $colorMap = array(
                            'Branco' => 'color-circle-branco',
                            'Preto' => 'color-circle-preto',
                            'Cinza' => 'color-circle-cinza',
                            'Prata' => 'color-circle-prata',
                            'Azul' => 'color-circle-azul',
                            'Vermelho' => 'color-circle-vermelho',
                            'Verde' => 'color-circle-verde',
                            'Laranja' => 'color-circle-laranja',
                            'Amarelo' => 'color-circle-amarelo',
                            'Bege' => 'color-circle-bege',
                            'Marrom' => 'color-circle-marrom',
                            'Roxo' => 'color-circle-roxo'
                        );
                        
                        foreach ($colorMap as $colorName => $colorClass):
                            $isSelected = ($color === $colorName);
                        ?>
                            <button type="button" class="color-filter-btn <?= $isSelected ? 'ativo' : ''; ?> bg-white border-2 select-none" 
                                    data-color="<?= e($colorName); ?>"
                                    style="border-color: <?= $isSelected ? '#2e7d32' : '#d1d5db'; ?>">
                                <span class="w-5 h-5 rounded-full flex-shrink-0 pointer-events-none <?= $colorClass; ?>"></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">
                        <span id="colorDisplay">
                            <?php 
                            if ($color) {
                                echo 'Cor selecionada: ' . e($color);
                            }
                            ?>
                        </span>
                    </div>
                    <!-- Hidden input para cor -->
                    <input type="hidden" id="color" name="color" value="<?= e($color ?? ''); ?>">
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="search-btn w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition">
                        <i class="fas fa-search mr-2"></i> Aplicar filtros
                    </button>
                    <a href="<?= $searchPageUrl; ?>" class="w-full text-center border border-gray-300 text-gray-600 font-semibold py-3 px-4 rounded-lg hover:bg-gray-50">Limpar tudo</a>
                </div>
            </form>
        </aside>

        <section class="flex-1" data-results-area>
            <header class="bg-white rounded-xl shadow-md p-6 mb-6" style="position: relative; z-index: 10;">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Resultados da Busca</h2>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-list-ul mr-2 text-amber-600"></i>
                            <span class="font-semibold text-amber-700"><?= e($resultsLabel); ?></span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center">
                            <label for="ordenar" class="text-sm font-medium text-gray-700 mr-2">Ordenar por:</label>
                            <select id="ordenar" name="sort" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm" data-sort-select>
                                <option value="destaque" <?= $order === 'destaque' ? 'selected' : ''; ?>>Destaque</option>
                                <option value="menor_valor" <?= $order === 'menor_valor' ? 'selected' : ''; ?>>Menor Valor</option>
                                <option value="maior_valor" <?= $order === 'maior_valor' ? 'selected' : ''; ?>>Maior Valor</option>
                                <option value="mais_novo" <?= $order === 'mais_novo' ? 'selected' : ''; ?>>Veículo Mais Novo</option>
                                <option value="mais_antigo" <?= $order === 'mais_antigo' ? 'selected' : ''; ?>>Veículo Mais Antigo</option>
                                <option value="mais_recente" <?= $order === 'mais_recente' ? 'selected' : ''; ?>>Anúncio Mais Recente</option>
                                <option value="mais_antigo_pub" <?= $order === 'mais_antigo_pub' ? 'selected' : ''; ?>>Anúncio Mais Antigo</option>
                                <option value="menor_km" <?= $order === 'menor_km' ? 'selected' : ''; ?>>Menor Quilometragem</option>
                            </select>
                        </div>
                        
                        <?php if (current_user()): ?>
                            <?php $isPremium = (bool)current_user()['is_premium']; ?>
                            <?php if ($isPremium): ?>
                                <button id="saveSearchBtn" class="px-3 py-2 bg-amber-50 border border-amber-300 text-amber-700 font-semibold rounded-lg hover:bg-amber-100 transition text-sm whitespace-nowrap">
                                    <i class="fas fa-bookmark mr-1"></i>Salvar Busca
                                </button>
                            <?php else: ?>
                                <button id="savSearchBtnLocked" class="px-3 py-2 bg-gray-100 border border-gray-300 text-gray-500 font-semibold rounded-lg cursor-not-allowed text-sm whitespace-nowrap" disabled title="Recurso Premium">
                                    <i class="fas fa-lock mr-1"></i>Salvar Busca
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($activeFilters): ?>
                    <div class="mt-4 flex flex-wrap items-center gap-2" data-active-filters>
                        <?php foreach ($activeFilters as $filter): ?>
                            <a href="<?= e($filter['url']); ?>" class="filter-chip">
                                <?= e($filter['label']); ?>
                                <i class="fas fa-times text-xs"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </header>

            <div class="grid grid-cols-1" id="resultsWrapper">
                <div id="cardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-verified-icon="<?= asset_path('assets/img/verificado.png'); ?>" data-placeholder-image="<?= asset_path('assets/uploads/images/sample-car-1.jpg'); ?>">
                    <?php if (!$ads): ?>
                        <div class="col-span-full">
                            <div class="bg-white border border-dashed border-gray-300 rounded-2xl p-10 text-center text-gray-700 space-y-3">
                                <i class="fas fa-car-crash text-4xl text-amber-500"></i>
                                <h2 class="text-xl font-semibold">Nenhum anúncio encontrado.</h2>
                                <p class="text-sm">Tente remover alguns filtros, alterar a faixa de preço ou buscar por outra cidade.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ads as $index => $ad): ?>
                            <?php
                                $images = array_map(fn($img) => '/' . ltrim($img, '/'), $ad['images'] ?? []);
                                $mainImage = $images[0] ?? '/assets/img/no-image.png';
                                $imagesJson = htmlspecialchars(json_encode($images, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                                $mileage = $ad['mileage'] > 0 ? number_format((int)$ad['mileage'], 0, ',', '.') : '0';
                                
                                // Montar detalhes primários (ano | cor | km)
                                $primaryDetails = [];
                                if (!empty($ad['model_year'])) $primaryDetails[] = (string)$ad['model_year'];
                                if (!empty($ad['color'])) $primaryDetails[] = ucfirst(strtolower((string)$ad['color']));
                                if ($mileage !== '0') $primaryDetails[] = $mileage . ' km';
                                $primaryText = implode(' | ', $primaryDetails);
                                
                                // Montar detalhes secundários (câmbio | combustível)
                                $secondaryDetails = [];
                                if (!empty($ad['transmission'])) $secondaryDetails[] = (string)$ad['transmission'];
                                if (!empty($ad['fuel_type'])) $secondaryDetails[] = (string)$ad['fuel_type'];
                                $secondaryText = implode(' | ', $secondaryDetails);
                                
                                $location = trim((string)(($ad['city'] ?? '') . ' / ' . ($ad['state'] ?? '')), ' /');
                                $adUrl = ad_public_url($ad);
                                $priceFormatted = format_currency((float)$ad['price']);
                                $storeOwnerId = (int)($ad['user_id'] ?? 0);
                                $storeOwnerName = (string)($ad['owner_name'] ?? '');
                                $trackPosition = $offset + $index + 1;
                                
                                // Payload para favoritar
                                $favoritePayload = [
                                    'id' => (int)$ad['id'],
                                    'title' => $ad['title'],
                                    'price' => (float)$ad['price'],
                                    'price_formatted' => $priceFormatted,
                                    'manufacture_year' => (int)($ad['manufacture_year'] ?? 0),
                                    'model_year' => (int)($ad['model_year'] ?? 0),
                                    'mileage' => (int)($ad['mileage'] ?? 0),
                                    'transmission' => $ad['transmission'] ?? null,
                                    'fuel_type' => $ad['fuel_type'] ?? null,
                                    'image' => $mainImage,
                                    'url' => $adUrl,
                                    'city' => $ad['city'] ?? '',
                                    'state' => $ad['state'] ?? '',
                                    'is_verified' => !empty($ad['is_verified']),
                                    'store_id' => $storeOwnerId,
                                ];
                                $favoritePayloadJson = htmlspecialchars(json_encode($favoritePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                                $verifiedBadge = !empty($ad['is_verified']) ? '
                                    <span style="
                                        position: absolute;
                                        top: 12px;
                                        right: 12px;
                                        background: white;
                                        border: none;
                                        color: #166534;
                                        padding: 0.35rem 0.75rem;
                                        border-radius: 2rem;
                                        font-size: 0.75rem;
                                        font-weight: 600;
                                        display: flex;
                                        align-items: center;
                                        gap: 0.35rem;
                                    ">
                                        <img src="/assets/img/verificado.png" alt="Verificado" style="width: 14px; height: 14px;">
                                        <span>Verificado</span>
                                    </span>
                                ' : '';
                            ?>
                            <article style="
                                background: white;
                                border-radius: 1rem;
                                box-shadow: 0 10px 15px rgba(15, 23, 42, 0.12);
                                overflow: hidden;
                                display: flex;
                                flex-direction: column;
                                position: relative;
                            " class="vehicle-card" data-ad-id="<?= (int)$ad['id']; ?>" data-images="<?= $imagesJson; ?>" data-is-verified="<?= !empty($ad['is_verified']) ? 'true' : 'false'; ?>"<?= $storeOwnerId > 0 ? ' data-store-owner="' . $storeOwnerId . '"' : ''; ?><?= $storeOwnerName !== '' ? ' data-store-owner-name="' . e($storeOwnerName) . '"' : ''; ?> data-track-origin="search_results" data-track-position="<?= $trackPosition; ?>">
                                <div style="position: relative;">
                                    <a href="<?= e($adUrl); ?>" style="display: block;" data-track-ad-click>
                                        <img src="<?= e($mainImage); ?>" alt="<?= e($ad['title']); ?>" style="width: 100%; height: 192px; object-fit: cover; display: block;" data-primary-image data-ad-image>
                                    </a>
                                    <span style="
                                        position: absolute;
                                        top: 12px;
                                        left: 12px;
                                        background: #166534;
                                        color: white;
                                        padding: 0.35rem 0.75rem;
                                        border-radius: 2rem;
                                        font-weight: 700;
                                        font-size: 0.85rem;
                                    "><?= e($priceFormatted); ?></span>
                                    <?= $verifiedBadge; ?>
                                </div>
                                <div style="padding: 1.25rem; display: flex; flex-direction: column; flex: 1;">
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem;">
                                        <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1f2937; line-height: 1.35; flex: 1;">
                                            <?= e($ad['title']); ?>
                                        </h3>
                                        <button type="button"
                                            class="vehicle-card__favorite vehicle-card__favorite--inline"
                                            data-favorite-toggle
                                            data-favorite-payload="<?= $favoritePayloadJson; ?>"
                                            aria-pressed="false"
                                            title="Marcar como preferido">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        </button>
                                    </div>
                                    <div style="display: grid; gap: 0.5rem; font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem; flex: 1;">
                                        <?php if ($primaryText): ?>
                                            <p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fa-regular fa-calendar" style="color: #f59e0b;"></i>
                                                <span><?= e($primaryText); ?></span>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($secondaryText): ?>
                                            <p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fa-solid fa-gear" style="color: #f59e0b;"></i>
                                                <span><?= e($secondaryText); ?></span>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($location): ?>
                                        <p style="margin: 0 0 1.25rem; font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="fa-solid fa-location-dot" style="color: #f59e0b;"></i>
                                            <span><?= e($location); ?></span>
                                        </p>
                                    <?php endif; ?>
                                    <a href="<?= e($adUrl); ?>"
                                        style="
                                            display: inline-flex;
                                            align-items: center;
                                            justify-content: center;
                                            width: 100%;
                                            background: #166534;
                                            color: white;
                                            padding: 0.625rem 1rem;
                                            border-radius: 0.5rem;
                                            text-decoration: none;
                                            font-weight: 600;
                                            transition: background 0.2s;
                                            gap: 0.5rem;
                                            font-size: 0.9rem;
                                        "
                                        onmouseover="this.style.background='#15803d'"
                                        onmouseout="this.style.background='#166534'"
                                        data-track-ad-click
                                    >
                                        <i class="fa-regular fa-eye"></i> Ver detalhes
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- F08: Infinite Scroll Container -->
            <div id="infiniteScrollContainer" data-current-page="<?= $page; ?>" data-total-pages="<?= $totalPages; ?>" data-total-ads="<?= $totalAds; ?>" class="mt-12"></div>
            
            <!-- Fallback pagination for non-JS users -->
            <noscript>
                <?php if ($ads && $totalPages > 1): ?>
                    <nav class="flex justify-center mt-12" aria-label="Paginação">
                        <ul class="inline-flex items-center space-x-2">
                            <?php
                                $baseQuery = $queryParams;
                                $prevPage = max(1, $page - 1);
                                $nextPage = min($totalPages, $page + 1);
                                $baseQuery['page'] = $prevPage;
                                $prevUrl = $searchPageUrl . '?' . http_build_query($baseQuery);
                                $baseQuery['page'] = $nextPage;
                                $nextUrl = $searchPageUrl . '?' . http_build_query($baseQuery);
                            ?>
                            <li>
                                <a href="<?= e($prevUrl); ?>" class="px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 <?= $page <= 1 ? 'pointer-events-none opacity-50' : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php $baseQuery['page'] = $i; ?>
                                <li>
                                    <a href="<?= e($searchPageUrl . '?' . http_build_query($baseQuery)); ?>" class="px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 <?= $i === $page ? 'font-semibold bg-amber-100 border-amber-300 text-amber-800' : ''; ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li>
                                <a href="<?= e($nextUrl); ?>" class="px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </noscript>
        </section>
    </div>
</main>

<?php
$autocompleteVersion = file_exists(__DIR__ . '/../assets/js/autocomplete.js') ? (string)filemtime(__DIR__ . '/../assets/js/autocomplete.js') : (string)time();
?>
<script defer src="/assets/js/autocomplete.js?v=<?= urlencode($autocompleteVersion); ?>"></script>

<script>
// Pré-carrega todas as cidades para melhor performance no listar.php
async function preloadCidadesParaListar() {
    // Aguarda até que preloadCidades esteja disponível (máx 5 segundos)
    let retries = 50;
    while (retries > 0 && typeof window.preloadCidades !== 'function') {
        await new Promise(resolve => setTimeout(resolve, 100));
        retries--;
    }
    
    if (typeof window.preloadCidades === 'function') {
        try {
            const cities = await window.preloadCidades();
            window.cidadesProximas = cities;
        } catch (error) {
            console.error('[LISTAR] Erro ao pré-carregar cidades:', error);
        }
    } else {
        console.warn('[LISTAR] preloadCidades ainda não disponível após timeout');
    }
}

// Inicia o pré-carregamento de cidades imediatamente
preloadCidadesParaListar();
</script>

<script>
// Sincronizar tipo de veículo com filtros de marca/modelo/câmbio
(function() {
    const typeSelector = document.querySelector('[data-type-selector]');
    if (!typeSelector) return;
    
    // Gerenciar clique nos botões de tipo
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-type]');
        if (!btn) return;
        
        const tipo = btn.getAttribute('data-type');
        const typeInput = document.getElementById('vehicle_type');
        if (typeInput) {
            typeInput.value = tipo;
            // Trigger autocomplete update via autocomplete.js
            setTimeout(() => {
                if (window.atualizarAutocompletePorTipo) {
                    window.atualizarAutocompletePorTipo(false);
                }
            }, 50);
        }
        
        // Atualizar visual dos botões
        document.querySelectorAll('[data-type]').forEach(function(b) {
            b.classList.remove('ativo');
        });
        btn.classList.add('ativo');
    });
})();

// Busca Rápida - Quick Search no Sticky Header
(function() {
    const quickSearchInput = document.getElementById('quickSearch');
    const suggestionsList = document.getElementById('quickSearchSuggestions');
    
    if (!quickSearchInput) return;

    // Dados de autocompletar (marca + modelo)
    const quickSearchData = [
        // Honda
        'Honda Civic', 'Honda Fit', 'Honda CR-V', 'Honda City', 'Honda Accord',
        // Toyota
        'Toyota Corolla', 'Toyota Etios', 'Toyota Hilux', 'Toyota SW4', 'Toyota Yaris',
        // Volkswagen
        'Volkswagen Gol', 'Volkswagen Polo', 'Volkswagen Virtus', 'Volkswagen T-Cross', 'Volkswagen Nivus',
        // Fiat
        'Fiat Uno', 'Fiat Mobi', 'Fiat Argo', 'Fiat Cronos', 'Fiat Toro',
        // Chevrolet
        'Chevrolet Onix', 'Chevrolet Prisma', 'Chevrolet Cruze', 'Chevrolet Tracker', 'Chevrolet S10',
        // Hyundai
        'Hyundai HB20', 'Hyundai Creta', 'Hyundai HB20S', 'Hyundai Tucson', 'Hyundai ix35',
        // Ford
        'Ford Ka', 'Ford Fiesta', 'Ford Focus', 'Ford EcoSport', 'Ford Ranger',
        // Renault
        'Renault Kwid', 'Renault Sandero', 'Renault Logan', 'Renault Duster', 'Renault Captur',
        // Jeep
        'Jeep Renegade', 'Jeep Compass', 'Jeep Cherokee', 'Jeep Grand Cherokee',
        // Nissan
        'Nissan March', 'Nissan Versa', 'Nissan Kicks', 'Nissan Sentra', 'Nissan Frontier',
        // Peugeot
        'Peugeot 208', 'Peugeot 2008', 'Peugeot Partner', 'Peugeot Rifter', 'Peugeot 308',
        // Citroën
        'Citroën C3', 'Citroën C4 Cactus', 'Citroën Jumpy', 'Citroën Berlingo', 'Citroën C4'
    ];

    quickSearchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        suggestionsList.innerHTML = '';

        if (query.length < 1) {
            suggestionsList.classList.add('hidden');
            return;
        }

        const filtered = quickSearchData.filter(item => 
            item.toLowerCase().includes(query)
        );

        if (filtered.length === 0) {
            suggestionsList.classList.add('hidden');
            return;
        }

        filtered.slice(0, 8).forEach(item => {
            const div = document.createElement('div');
            div.className = 'quick-search-suggestion';
            
            // Highlight da parte que matchou
            const regex = new RegExp(`(${query})`, 'gi');
            const highlighted = item.replace(regex, '<strong>$1</strong>');
            
            div.innerHTML = highlighted;
            div.onclick = () => {
                // Extrair marca e modelo
                const parts = item.split(' ');
                const brand = parts[0];
                const model = parts.slice(1).join(' ');
                
                // Redirecionar para search com filtros pré-preenchidos
                window.location.href = `/anuncios/listar.php?brand=${encodeURIComponent(brand)}&model=${encodeURIComponent(model)}`;
            };
            suggestionsList.appendChild(div);
        });

        suggestionsList.classList.remove('hidden');
    });

    // Fechar sugestões ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target !== quickSearchInput && !suggestionsList.contains(e.target)) {
            suggestionsList.classList.add('hidden');
        }
    });

    // Fechar com ESC
    quickSearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            suggestionsList.classList.add('hidden');
        }
    });
})();

// F02 & F04: Filtros Colapsáveis (Mobile Drawer) com Responsive
(function() {
    const toggleBtn = document.getElementById('filtersToggleBtn');
    const drawer = document.getElementById('filtersDrawer');
    const drawerContent = drawer ? drawer.querySelector('.filters-drawer-content') : null;
    const overlay = document.getElementById('filtersOverlay');
    const closeBtn = document.getElementById('filtersDrawerClose');
    const filterForm = document.querySelector('[data-filter-form]');

    if (!toggleBtn || !drawer || !overlay || !closeBtn || !filterForm || !drawerContent) return;

    // Clonar o formulário de filtros para o drawer (mobile)
    function populateDrawer() {
        console.log('populateDrawer chamada', {
            drawerContentHTML: drawerContent.innerHTML.trim().substring(0, 100),
            isEmpty: drawerContent.innerHTML.trim() === ''
        });
        
        if (drawerContent.innerHTML.trim() === '') {
            try {
                // Clonar o conteúdo do formulário
                const clonedForm = filterForm.cloneNode(true);
                clonedForm.removeAttribute('data-filter-form');
                clonedForm.id = 'mobileFilterForm';
                
                // Adicionar ao drawer
                drawerContent.appendChild(clonedForm);
                
                console.log('Formulário clonado com sucesso!', {
                    newHTML: drawerContent.innerHTML.substring(0, 100),
                    formExists: drawerContent.querySelector('form') ? 'SIM' : 'NÃO'
                });
                
                // Sincronizar valores do formulário principal para o drawer
                syncFormToDrawer();
                
                // Reinicializar o autocomplete para os novos inputs no drawer
                if (typeof setupAutocompleteForDrawer === 'function') {
                    console.log('[F04] Reinicializando autocomplete para drawer...');
                    setupAutocompleteForDrawer();
                }
            } catch (err) {
                console.error('Erro ao clonar formulário:', err);
            }
        } else {
            console.log('Drawer já populado');
        }
    }
    
    function syncFormToDrawer() {
        const drawerForm = drawerContent.querySelector('form');
        if (!drawerForm) return;
        
        // Copiar valores do formulário principal para o drawer
        const mainInputs = filterForm.querySelectorAll('input, select, button[data-type], button[data-mileage-min], button[data-color]');
        mainInputs.forEach(mainInput => {
            const drawerInput = drawerForm.querySelector(`[name="${mainInput.name}"]`);
            if (drawerInput) {
                if (mainInput.type === 'checkbox') {
                    drawerInput.checked = mainInput.checked;
                } else if (mainInput.type === 'radio') {
                    if (mainInput.checked) drawerInput.checked = true;
                } else if (mainInput.classList && (mainInput.classList.contains('ativo'))) {
                    drawerInput.classList.add('ativo');
                } else {
                    drawerInput.value = mainInput.value;
                }
            }
        });
    }
    
    function syncDrawerToForm() {
        const drawerForm = drawerContent.querySelector('form');
        if (!drawerForm) return;
        
        // Copiar valores do drawer para o formulário principal
        const drawerInputs = drawerForm.querySelectorAll('input, select');
        drawerInputs.forEach(drawerInput => {
            const mainInput = filterForm.querySelector(`[name="${drawerInput.name}"]`);
            if (mainInput) {
                if (drawerInput.type === 'checkbox') {
                    mainInput.checked = drawerInput.checked;
                } else if (drawerInput.type === 'radio') {
                    if (drawerInput.checked) mainInput.checked = true;
                } else {
                    mainInput.value = drawerInput.value;
                }
            }
        });
    }

    // Abrir drawer
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        populateDrawer();
        drawer.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden'; // Prevenir scroll
    });

    // Fechar drawer
    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = ''; // Restaurar scroll
    }

    // Fechar ao clicar no overlay
    overlay.addEventListener('click', closeDrawer);

    // Fechar ao clicar no botão close
    closeBtn.addEventListener('click', closeDrawer);
    
    // Capturar eventos de mudança no drawer
    drawerContent.addEventListener('change', function(e) {
        if (e.target && e.target.name) {
            syncDrawerToForm();
        }
    });
    
    // Ao enviar formulário no drawer, fechar e redirecionar
    drawerContent.addEventListener('submit', function(e) {
        syncDrawerToForm();
        // Deixar o formulário enviar naturalmente após sincronizar
        setTimeout(closeDrawer, 50);
    });

    // Fechar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && drawer.classList.contains('open')) {
            closeDrawer();
        }
    });
    
    console.log('F04: Mobile Responsiveness dos Filtros ativado com sucesso! ✅');
})();

// F08: Infinite Scroll Implementation
(function() {
    const container = document.getElementById('cardsContainer');
    const scrollContainer = document.getElementById('infiniteScrollContainer');
    
    if (!container || !scrollContainer) return;
    
    let currentPage = parseInt(scrollContainer.dataset.currentPage, 10);
    let totalPages = parseInt(scrollContainer.dataset.totalPages, 10);
    let totalAds = parseInt(scrollContainer.dataset.totalAds, 10);
    let isLoading = false;
    let hasMore = currentPage < totalPages;
    
    // Coletar parâmetros de filtro da URL atual
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = new URLSearchParams(urlParams);
    queryString.set('ajax', '1');
    
    async function loadMoreAds() {
        if (isLoading || !hasMore) return;
        
        isLoading = true;
        const nextPage = currentPage + 1;
        const baseUrl = window.location.pathname;
        
        try {
            // Clonar parâmetros e adicionar página + ajax flag
            const params = new URLSearchParams(urlParams);
            params.set('page', nextPage);
            params.set('ajax', '1');
            
            const response = await fetch(`${baseUrl}?${params.toString()}`);
            
            if (!response.ok) {
                console.error('Failed to load more ads:', response.statusText);
                isLoading = false;
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.ads) {
                // Adicionar novo HTML ao container
                container.insertAdjacentHTML('beforeend', data.ads);
                
                // Inicializar carrossel dos novos cards
                if (typeof window.initCarousels === 'function') {
                    window.initCarousels();
                }
                
                // Atualizar estado
                currentPage = data.page;
                hasMore = data.hasMore;
                totalPages = data.totalPages;
                
                // Se chegou ao fim, remover observer
                if (!hasMore) {
                    observer.disconnect();
                }
            }
        } catch (error) {
            console.error('Error loading more ads:', error);
        } finally {
            isLoading = false;
        }
    }
    
    // Intersection Observer para detectar quando scroll chega perto do fim
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && hasMore && !isLoading) {
                    loadMoreAds();
                }
            });
        },
        {
            root: null,
            rootMargin: '200px', // Começar carregamento 200px antes de chegar ao fim
            threshold: 0.1
        }
    );
    
    // Observar o container de scroll
    observer.observe(scrollContainer);
    
    // Expor função global para debugging
    window.loadMoreAds = loadMoreAds;
})();

// F03: Price Slider com input range nativo ✅ ATIVADO!
(function() {
    const minSlider = document.getElementById('priceMinSlider');
    const maxSlider = document.getElementById('priceMaxSlider');
    const minDisplay = document.getElementById('priceMinDisplay');
    const maxDisplay = document.getElementById('priceMaxDisplay');
    const rangeDisplay = document.getElementById('priceRangeDisplay');
    const minHidden = document.getElementById('price_min');
    const maxHidden = document.getElementById('price_max');

    if (!minSlider || !maxSlider) return;

    // Função para formatar valor em moeda
    const formatCurrency = (value) => {
        return 'R$ ' + parseInt(value).toLocaleString('pt-BR');
    };

    // Função para atualizar displays
    const updatePriceDisplay = () => {
        let minVal = parseInt(minSlider.value);
        let maxVal = parseInt(maxSlider.value);

        // Garantir que min não seja maior que max
        if (minVal > maxVal) {
            [minVal, maxVal] = [maxVal, minVal];
            minSlider.value = minVal;
            maxSlider.value = maxVal;
        }

        // Atualizar displays
        minDisplay.textContent = formatCurrency(minVal);
        maxDisplay.textContent = formatCurrency(maxVal);
        rangeDisplay.textContent = `${formatCurrency(minVal)} até ${formatCurrency(maxVal)}`;

        // Atualizar hidden inputs
        minHidden.value = minVal;
        maxHidden.value = maxVal;
    };

    // Event listeners
    minSlider.addEventListener('input', updatePriceDisplay);
    maxSlider.addEventListener('input', updatePriceDisplay);

    // Inicializar display
    updatePriceDisplay();

    console.log('F03: Price Slider ativado com sucesso! ✅');
})();

// F05: Filtro de Quilometragem com opções pré-definidas ✅ ATIVADO!
(function() {
    const mileageBtns = document.querySelectorAll('.mileage-filter-btn');
    const mileageMinInput = document.getElementById('mileage_min');
    const mileageMaxInput = document.getElementById('mileage_max');
    const mileageDisplay = document.getElementById('mileageDisplay');

    if (mileageBtns.length === 0) return;

    mileageBtns.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const minVal = parseInt(btn.dataset.mileageMin);
            const maxVal = parseInt(btn.dataset.mileageMax);

            // Remover classe 'ativo' de todos os botões
            mileageBtns.forEach((b) => b.classList.remove('ativo'));

            // Adicionar classe 'ativo' ao botão clicado
            btn.classList.add('ativo');

            // Atualizar hidden inputs (APENAS quilometragem, NÃO tocar em price_min/price_max)
            mileageMinInput.value = minVal;
            mileageMaxInput.value = maxVal;

            // Atualizar display de quilometragem
            const minDisplay = minVal > 0 ? minVal.toLocaleString('pt-BR') : '0';
            const maxDisplay = maxVal > 0 ? maxVal.toLocaleString('pt-BR') : '∞';
            mileageDisplay.textContent = `Selecionado: ${minDisplay} - ${maxDisplay} km`;

            console.log(`F05: Quilometragem alterada para ${minVal} - ${maxVal} km`);
        });
    });

    console.log('F05: Filtro de Quilometragem ativado com sucesso! ✅');
})();

// F06: Filtro de Cor com bolinhas coloridas ✅ ATIVADO!
(function() {
    const colorBtns = document.querySelectorAll('.color-filter-btn');
    const colorInput = document.getElementById('color');
    const colorDisplay = document.getElementById('colorDisplay');

    if (colorBtns.length === 0) return;

    colorBtns.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const colorValue = btn.dataset.color;

            // Remover classe 'ativo' de todos os botões
            colorBtns.forEach((b) => {
                b.classList.remove('ativo');
                b.style.borderColor = '#d1d5db';
            });

            // Adicionar classe 'ativo' ao botão clicado
            btn.classList.add('ativo');
            btn.style.borderColor = '#2e7d32';

            // Atualizar hidden input
            colorInput.value = colorValue;

            // Atualizar display de cor
            if (colorValue) {
                colorDisplay.textContent = `Cor selecionada: ${colorValue}`;
            } else {
                colorDisplay.textContent = '';
            }

            console.log(`F06: Cor alterada para: ${colorValue || 'Todas'}`);
        });
    });

    console.log('F06: Filtro de Cor ativado com sucesso! ✅');
})();

console.log('F08: Infinite Scroll ativado com sucesso');

// F11: Save Searches - Salvar buscas com filtros aplicados
(function() {
    const saveSearchBtn = document.getElementById('saveSearchBtn');
    if (!saveSearchBtn) return;

    // Criar modal dinamicamente
    const modalHTML = `
        <div id="saveSearchModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Salvar Busca</h3>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="saveSearchForm" class="space-y-4">
                    <div>
                        <label for="searchName" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome da Busca
                        </label>
                        <input 
                            type="text" 
                            id="searchName" 
                            name="search_name" 
                            placeholder="Ex: Carros até 50k SP" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            maxlength="255"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Máximo 255 caracteres</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            id="notifySearch" 
                            name="notify" 
                            checked
                            class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"
                        >
                        <label for="notifySearch" class="text-sm text-gray-700">
                            Notificar-me quando houver novos anúncios
                        </label>
                    </div>

                    <p class="text-xs text-gray-600 bg-blue-50 p-3 rounded">
                        <i class="fas fa-info-circle mr-1"></i>
                        Você pode salvar até 10 buscas. Esta busca salvará todos os filtros atualmente aplicados.
                    </p>

                    <div class="flex gap-2 justify-end">
                        <button type="button" id="cancelModal" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                            <i class="fas fa-bookmark mr-1"></i>Salvar Busca
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('saveSearchModal');
    const saveForm = document.getElementById('saveSearchForm');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelModal');
    const searchNameInput = document.getElementById('searchName');
    const notifyCheckbox = document.getElementById('notifySearch');

    // Abrir modal
    saveSearchBtn.addEventListener('click', () => {
        modal.classList.remove('hidden');
        searchNameInput.focus();
        document.body.style.overflow = 'hidden'; // Previne scroll de fundo
    });

    // Fechar modal
    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // Restaura scroll
    };
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Fechar com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // Enviar formulário
    saveForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const searchName = searchNameInput.value.trim();
        const notify = notifyCheckbox.checked ? 1 : 0;

        // Coletar parâmetros de busca do FORMULÁRIO (não só da URL!)
        // Isso garante que capture os valores atuais dos campos, mesmo que não estejam na URL
        const filterForm = document.querySelector('[data-filter-form]');
        const searchParams = {};
        
        if (filterForm) {
            // Nomes dos campos exatamente como em listar.php (linhas 105-120)
            const fieldNames = [
                'tipo', 'brand', 'model', 'city', 'state',
                'year_min', 'year_max', 'mileage_min', 'mileage_max',
                'price_min', 'price_max', 'transmission', 'fuel_type', 'color', 'sort'
            ];
            
            fieldNames.forEach(fieldName => {
                const field = filterForm.querySelector(`[name="${fieldName}"]`);
                if (field && field.value && field.value.trim() !== '') {
                    searchParams[fieldName] = field.value.trim();
                }
            });
        }

        try {
            const response = await fetch('/anuncios/api/save-search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    search_name: searchName,
                    search_params: searchParams,
                    notify: notify
                })
            });

            const result = await response.json();

            if (result.success) {
                // Sucesso
                searchNameInput.value = '';
                closeModal();
                
                // Mostrar notificação de sucesso
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                toast.innerHTML = '<i class="fas fa-check mr-2"></i>' + (result.message || 'Busca salva com sucesso!');
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.3s';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            } else {
                // Erro
                if (result.requires_premium) {
                    // Erro de permissão premium
                    alert('🔒 Este recurso é exclusivo para usuários Premium!\n\nAtualize sua conta para desfrutar de mais benefícios.');
                } else {
                    alert('Erro ao salvar: ' + (result.message || 'Erro desconhecido'));
                }
            }
        } catch (error) {
            console.error('Error saving search:', error);
            alert('Erro ao salvar busca. Tente novamente.');
        }
    });

    // Adicionar listener para botão bloqueado (não-premium)
    const lockedBtn = document.getElementById('savSearchBtnLocked');
    if (lockedBtn) {
        lockedBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-amber-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
            toast.innerHTML = '<i class="fas fa-crown text-lg"></i> <div><strong>Recurso Premium</strong><br><small>Atualize para salvar buscas</small></div>';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        });
    }

    console.log('F11: Save Searches ativado com sucesso');
})();
</script>

<script>
// Gerenciador de Favoritos para cards carregados via AJAX
(function() {
    const FAVORITES_KEY = 'clautos:favorites';

    // Função para obter todos os favoritos
    function getFavorites() {
        try {
            const stored = localStorage.getItem(FAVORITES_KEY);
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            return [];
        }
    }

    // Função para salvar favoritos
    function setFavorites(favorites) {
        localStorage.setItem(FAVORITES_KEY, JSON.stringify(favorites));
    }

    // Função para verificar se um anúncio é favorito
    function isFavorited(adId) {
        return getFavorites().some(fav => fav.id === adId);
    }

    // Função para adicionar favorito
    function addFavorite(payload) {
        const favorites = getFavorites();
        if (!favorites.some(fav => fav.id === payload.id)) {
            favorites.push(payload);
            setFavorites(favorites);
        }
    }

    // Função para remover favorito
    function removeFavorite(adId) {
        const favorites = getFavorites();
        const updated = favorites.filter(fav => fav.id !== adId);
        setFavorites(updated);
    }

    // Função para atualizar visual do botão
    function updateFavoriteButton(button, isFav) {
        button.setAttribute('aria-pressed', isFav ? 'true' : 'false');
        const icon = button.querySelector('i');
        if (icon) {
            if (isFav) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
            }
        }
    }

    // Função para inicializar botões de favorito
    function initializeFavoriteButtons() {
        document.querySelectorAll('[data-favorite-toggle]').forEach(button => {
            const payload = button.getAttribute('data-favorite-payload');
            if (!payload) return;

            try {
                const adData = JSON.parse(payload);
                const isFav = isFavorited(adData.id);
                updateFavoriteButton(button, isFav);

                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const currentIsFav = isFavorited(adData.id);
                    
                    if (currentIsFav) {
                        removeFavorite(adData.id);
                        updateFavoriteButton(button, false);
                    } else {
                        addFavorite(adData);
                        updateFavoriteButton(button, true);
                    }
                });
            } catch (e) {
                console.error('Erro ao processar favorito:', e);
            }
        });
    }

    // Inicializar na carga da página
    initializeFavoriteButtons();

    // Observer para inicializar novos botões adicionados dinamicamente (AJAX)
    const observer = new MutationObserver(() => {
        initializeFavoriteButtons();
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();
</script>

<script defer src="/assets/js/search-page.js?v=<?= urlencode($searchJsVersion); ?>"></script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<?php require __DIR__ . '/../propagandas/load_web.php'; ?>
<?php require __DIR__ . '/../propagandas/load_local.php'; ?>
<?php require __DIR__ . '/../propagandas/load_embed.php'; ?>
