<?php
// loja.php - Página pública para exibir a vitrine de um vendedor premium.

declare(strict_types=1);

require_once __DIR__ . '/login_system/middleware.php';

function store_format_address(array $user): ?string
{
    $segments = [];
    if (!empty($user['address_street'])) {
        $street = $user['address_street'];
        if (!empty($user['address_number'])) {
            $street .= ', ' . $user['address_number'];
        }
        if (!empty($user['address_complement'])) {
            $street .= ' - ' . $user['address_complement'];
        }
        $segments[] = $street;
    }

    if (!empty($user['address_neighborhood'])) {
        $segments[] = $user['address_neighborhood'];
    }

    $cityState = [];
    if (!empty($user['address_city'])) {
        $cityState[] = $user['address_city'];
    }
    if (!empty($user['address_state'])) {
        $cityState[] = $user['address_state'];
    }
    if ($cityState) {
        $segments[] = implode(' / ', $cityState);
    }

    if (!empty($user['address_postal_code'])) {
        $segments[] = 'CEP ' . $user['address_postal_code'];
    }

    return $segments ? implode(', ', $segments) : null;
}

function store_build_route_query(array $user): ?string
{
    $segments = [];

    if (!empty($user['address_street'])) {
        $street = $user['address_street'];
        if (!empty($user['address_number'])) {
            $street .= ', ' . $user['address_number'];
        }
        $segments[] = $street;
    }

    if (!empty($user['address_neighborhood'])) {
        $segments[] = $user['address_neighborhood'];
    }

    if (!empty($user['address_city'])) {
        $segments[] = $user['address_city'];
    }

    if (!empty($user['address_state'])) {
        $segments[] = $user['address_state'];
    }

    if (!empty($user['address_postal_code'])) {
        $segments[] = $user['address_postal_code'];
    }

    return $segments ? implode(', ', $segments) : null;
}

$rawVendorId = input_get('id');
$vendorId = (int)($rawVendorId ?? 0);
$providedSlug = strtolower((string)(input_get('slug') ?? ''));
$pdo = db();

$verificationCase = 'CASE WHEN users.is_premium = 1 OR EXISTS (SELECT 1 FROM premium_subscriptions ps WHERE ps.user_id = users.id AND (ps.expires_at IS NULL OR ps.expires_at > NOW())) THEN 1 ELSE 0 END';
$vendor = null;

if ($vendorId > 0) {
    $vendorStmt = $pdo->prepare("
        SELECT
            users.*,
            {$verificationCase} AS is_verified
        FROM users
        WHERE users.id = :id
        LIMIT 1
    ");
    $vendorStmt->execute([':id' => $vendorId]);
    $vendor = $vendorStmt->fetch();
} elseif ($providedSlug !== '') {
    $allPremiumStmt = $pdo->prepare("
        SELECT DISTINCT
            users.*,
            {$verificationCase} AS is_verified
        FROM users
        LEFT JOIN premium_subscriptions ps
            ON ps.user_id = users.id
            AND (ps.expires_at IS NULL OR ps.expires_at > NOW())
        WHERE users.is_premium = 1
           OR ps.id IS NOT NULL
    ");
    $allPremiumStmt->execute();
    while ($candidate = $allPremiumStmt->fetch()) {
        $candidateSlug = slugify((string)($candidate['name'] ?: ('vendedor-' . $candidate['id'])));
        if ($candidateSlug === $providedSlug) {
            $vendor = $candidate;
            $vendorId = (int)$candidate['id'];
            break;
        }
    }
}

if (!$vendor || !user_is_premium($vendor)) {
    http_response_code(404);
    exit('Loja indisponível ou não encontrada.');
}

// Validar se o vendedor está bloqueado
if ($vendor['status'] === 'blocked') {
    $pageTitle = 'Loja Indisponível - Classi Livre Autos';
    include __DIR__ . '/partials/header.php';
    ?>
    <section class="unavailable-store" style="padding: 40px 20px; text-align: center;">
        <div style="background-color: #f9c74f; border: 2px solid #f8b500; border-radius: 8px; padding: 30px; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333; margin: 0 0 15px 0; font-size: 24px;">Loja Indisponível</h2>
            <p style="color: #555; margin: 0; font-size: 16px;">Esta loja se encontra indisponível no momento.</p>
        </div>
    </section>
    <?php
    include __DIR__ . '/partials/footer.php';
    exit;
}

$vendorId = (int)$vendor['id'];
$storeSlug = slugify((string)($vendor['name'] ?: ('vendedor-' . $vendorId)));
$canonicalUrl = '/loja/' . $vendorId . '/' . $storeSlug;

$user = current_user();
$isStoreOwner = $user && (int)$user['id'] === $vendorId;
$vendorVerified = (int)$vendor['is_verified'] === 1;

$vendorShowPhone = !empty($vendor['show_phone']);
$vendorShowWhatsapp = !empty($vendor['show_whatsapp']);
$vendorShowEmail = !empty($vendor['show_email']);
$vendorShowAddress = !empty($vendor['show_address']);
$vendorInstagramUrl = !empty($vendor['instagram_url']) ? trim((string)$vendor['instagram_url']) : null;

$phoneDigits = preg_replace('/\D+/', '', (string)($vendor['phone'] ?? ''));
$countryCodeDigits = preg_replace('/\D+/', '', (string)($vendor['phone_country_code'] ?? ''));
$countryCodeDigits = $countryCodeDigits !== '' ? $countryCodeDigits : '55';
$whatsappUrl = null;
$telephoneUrl = null;

if ($phoneDigits !== '') {
    if (!empty($vendor['phone_is_whatsapp']) && $vendorShowWhatsapp) {
        $whatsappMessage = 'Oi! Vi sua loja no site Classi Livre Autos';
        $whatsappUrl = 'https://api.whatsapp.com/send?phone=' . rawurlencode($countryCodeDigits . $phoneDigits) . '&text=' . rawurlencode($whatsappMessage);
    }
    if ($vendorShowPhone) {
        if (strlen($phoneDigits) >= 10) { // Brasileiro
            $ddd = substr($phoneDigits, 0, 2);
            $numero = substr($phoneDigits, 2);
            $telephoneUrl = 'tel:0' . $ddd . $numero;
        } else {
            $telephoneUrl = 'tel:0' . ltrim($phoneDigits, '0');
        }
    }
}
$vendorPhoneMasked = ($vendorShowPhone || $vendorShowWhatsapp) ? mask_phone($vendor['phone'] ?? '') : null;

$defaultTheme = [
    'hero_background' => '#155e2b',
    'hero_text_color' => '#ffffff',
    'hero_accent_color' => '#facc15',
    'highlight_message' => null,
    'cover_image' => null,
    'layout_style' => 'default'
];

$themeConfig = $defaultTheme;
if (!empty($vendor['store_theme_config'])) {
    $decodedTheme = json_decode((string)$vendor['store_theme_config'], true);
    if (is_array($decodedTheme)) {
        $themeConfig = array_merge($themeConfig, array_intersect_key($decodedTheme, $defaultTheme));
    }
}

$heroBackground = $themeConfig['hero_background'] ?: $defaultTheme['hero_background'];
$heroTextColor = $themeConfig['hero_text_color'] ?: $defaultTheme['hero_text_color'];
$heroAccentColor = $themeConfig['hero_accent_color'] ?: $defaultTheme['hero_accent_color'];

$rawHighlight = $themeConfig['highlight_message'] ?? null;
if (is_string($rawHighlight)) {
    $trimmedHighlight = trim($rawHighlight);
    $heroHighlightMessage = $trimmedHighlight !== '' ? $trimmedHighlight : null;
} else {
    $heroHighlightMessage = null;
}

$rawCoverImage = $themeConfig['cover_image'] ?? null;
if (is_string($rawCoverImage)) {
    $trimmedCover = trim($rawCoverImage);
    $heroCoverImage = $trimmedCover !== '' ? $trimmedCover : null;
} else {
    $heroCoverImage = null;
}
$heroStyles = sprintf('--store-hero-bg:%s; --store-hero-text:%s; --store-hero-accent:%s;', $heroBackground, $heroTextColor, $heroAccentColor);

$adsStmt = $pdo->prepare("
    SELECT
        ads.id,
        ads.slug,
        ads.title,
        ads.price,
        ads.brand,
        ads.model,
        ads.manufacture_year,
        ads.model_year,
        ads.mileage,
        ads.transmission,
        ads.fuel_type,
        ads.city,
        ads.state,
        ads.vehicle_type,
        ads.updated_at,
        ads.created_at,
        ads.verified_badge
    FROM ads
    WHERE ads.user_id = :user_id
      AND ads.status = 'approved'
      AND (ads.expires_at IS NULL OR ads.expires_at > NOW())
    ORDER BY ads.updated_at DESC
");
$adsStmt->execute([':user_id' => $vendorId]);
$ads = $adsStmt->fetchAll();

$adImages = [];
if ($ads) {
    $ids = array_map(static fn(array $row): int => (int)$row['id'], $ads);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $mediaStmt = $pdo->prepare("
        SELECT ad_id, filepath
        FROM ad_media
        WHERE ad_id IN ($placeholders) AND type = 'image'
        ORDER BY sort_order ASC, id ASC
    ");
    $mediaStmt->execute($ids);
    while ($row = $mediaStmt->fetch(PDO::FETCH_ASSOC)) {
        $adId = (int)$row['ad_id'];
        $path = '/' . ltrim((string)$row['filepath'], '/');
        if (!isset($adImages[$adId])) {
            $adImages[$adId] = [];
        }
        if (!in_array($path, $adImages[$adId], true)) {
            $adImages[$adId][] = $path;
        }
    }
}

$storeAds = [];
$suggestionPool = [];

foreach ($ads as $ad) {
    $adId = (int)$ad['id'];
    $images = $adImages[$adId] ?? [];
    if (!$images) {
        $images[] = '/assets/uploads/images/sample-car-1.jpg';
    }
    $mainImage = $images[0];
    $price = (float)$ad['price'];
    $priceFormatted = format_currency($price);
    $brand = (string)$ad['brand'];
    $model = (string)$ad['model'];
    $yearParts = array_filter([
        $ad['manufacture_year'] ? (string)$ad['manufacture_year'] : null,
        $ad['model_year'] ? (string)$ad['model_year'] : null,
    ]);
    $yearText = $yearParts ? implode('/', array_unique($yearParts)) : null;
    $location = trim((string)(($ad['city'] ?? '') . ' / ' . ($ad['state'] ?? '')), ' /');
    $mileageText = null;
    if (!empty($ad['mileage'])) {
        $mileageText = number_format((float)$ad['mileage'], 0, ',', '.') . ' km';
    }

    $updatedAt = strtotime((string)($ad['updated_at'] ?? '')) ?: 0;
    $createdAt = strtotime((string)($ad['created_at'] ?? '')) ?: 0;
    $isNew = $updatedAt > 0 ? (time() - $updatedAt) <= (7 * 24 * 60 * 60) : false;

    $suggestionPool[] = $brand;
    $suggestionPool[] = $model;
    $suggestionPool[] = trim($brand . ' ' . $model);

    $storeAds[] = [
        'id' => $adId,
        'title' => $ad['title'],
        'url' => ad_public_url($ad),
        'image' => $mainImage,
        'images' => $images,
        'price' => $price,
        'price_formatted' => $priceFormatted,
        'brand' => $brand,
        'model' => $model,
        'year' => $yearText,
        'mileage' => $mileageText,
        'mileage_value' => (int)($ad['mileage'] ?? 0),
        'manufacture_year' => (int)($ad['manufacture_year'] ?? 0),
        'model_year' => (int)($ad['model_year'] ?? 0),
        'transmission' => $ad['transmission'] ?? null,
        'fuel_type' => $ad['fuel_type'] ?? null,
        'city' => $ad['city'] ?? '',
        'state' => $ad['state'] ?? '',
        'location' => $location,
        'is_verified' => (bool)$ad['verified_badge'],
        'updated_ts' => $updatedAt,
        'created_ts' => $createdAt,
        'is_new' => $isNew,
    ];
}

$platformSalesStmt = $pdo->prepare('SELECT platform_sales_count FROM users WHERE id = :uid LIMIT 1');
$platformSalesStmt->execute([':uid' => $vendorId]);
$platformSales = (int)($platformSalesStmt->fetchColumn() ?? 0);

$storeSuggestions = array_values(array_filter(array_unique(array_map(static fn(string $item): string => trim($item), $suggestionPool))));

$vendorName = $vendor['name'] ?: 'vendedor';
$pageTitle = 'Loja de Veículos de ' . $vendorName . ' - Classi Livre Autos';
$pageDescription = 'Loja de veículos de ' . $vendorName . '. ' . $totalAds . ' veículos disponíveis. Vendedor premium com ' . $platformSales . ' vendas na plataforma.';
$vendorAddress = $vendorShowAddress ? store_format_address($vendor) : null;
$vendorEmail = $vendorShowEmail ? ($vendor['email'] ?? null) : null;
$vendorAbout = trim((string)($vendor['about'] ?? '')) ?: null;
$vendorShowDocument = !empty($vendor['show_document']);
$vendorDocumentType = strtolower((string)($vendor['document_type'] ?? 'cpf')) === 'cnpj' ? 'cnpj' : 'cpf';
$vendorDocumentNumber = preg_replace('/\D+/', '', (string)($vendor['document_number'] ?? ''));
$vendorDocumentFormatted = ($vendorShowDocument && $vendorDocumentNumber !== '') ? format_document($vendorDocumentNumber, $vendorDocumentType) : null;
$totalAds = count($storeAds);
$routeQuery = $vendorShowAddress ? store_build_route_query($vendor) : null;

// Meta tags para compartilhamento social
$pageMetaTags = [
    'og:title' => $pageTitle,
    'og:description' => $pageDescription,
    'og:type' => 'business.business',
    'og:url' => $canonicalUrl,
];

include __DIR__ . '/partials/header.php';
?>
<section class="store-hero" style="<?= e($heroStyles); ?><?= $heroCoverImage ? ' background-image:url(' . e($heroCoverImage) . ');' : ''; ?>">
    <div class="container mx-auto px-4">
        <div class="store-hero__card">
            <div class="store-hero__info">
                <!-- Topo: Nome + Badge -->
                <div class="store-hero__header">
                    <div class="store-hero__identity">
                        <div class="store-hero__name-block">
                            <h1 class="store-hero__title">
                                <?= e($vendor['name'] ?? 'Vendedor Premium'); ?>
                            </h1>
                            <?php if ($heroHighlightMessage): ?>
                                <span class="store-hero__highlight"><?= e($heroHighlightMessage); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="store-hero__badge">
                        <i class="fas fa-star"></i>
                        Premium
                    </div>
                </div>

                <!-- Meio: Endereço + Bio em uma linha se possível -->
                <div class="store-hero__details">
                    <?php if ($vendorAddress): ?>
                        <p class="store-hero__address">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= e($vendorAddress); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($vendorAbout): ?>
                        <p class="store-hero__bio"><?= nl2br(e($vendorAbout)); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Stats em linha -->
                <div class="store-hero__stats-inline">
                    <div class="store-hero__stat-item">
                        <span class="store-hero__stat-number"><?= $totalAds; ?></span>
                        <span class="store-hero__stat-label">Veículos</span>
                    </div>
                    <div class="store-hero__stat-item">
                        <span class="store-hero__stat-number"><?= $platformSales; ?></span>
                        <span class="store-hero__stat-label">Vendas</span>
                    </div>
                    <?php if ($vendorPhoneMasked && $vendorPhoneMasked !== 'Não informado'): ?>
                        <div class="store-hero__stat-item">
                            <i class="fas fa-phone" style="font-size:0.9rem;"></i>
                            <span class="store-hero__stat-label"><?= e($vendorPhoneMasked); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($vendorEmail): ?>
                        <div class="store-hero__stat-item">
                            <i class="fas fa-envelope" style="font-size:0.9rem;"></i>
                            <span class="store-hero__stat-label"><?= e($vendorEmail); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($vendorDocumentFormatted) && $vendorShowDocument): ?>
                        <div class="store-hero__stat-item store-hero__stat-item--highlight">
                            <i class="fas fa-certificate" style="font-size:1.1rem; color:#16a34a;"></i>
                            <span class="store-hero__stat-label" style="font-weight:600; color:#166534;">
                                <?= strtoupper($vendorDocumentType) ?>: <?= e($vendorDocumentFormatted); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ações: Botões em linha -->
                <div class="store-hero__actions-bar">
                    <?php if ($whatsappUrl): ?>
                        <a class="store-hero__action-btn store-hero__action-btn--whatsapp" href="<?= e($whatsappUrl); ?>" target="_blank" rel="noopener" data-store-contact="whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                    <?php if ($vendorInstagramUrl): ?>
                        <a class="store-hero__action-btn store-hero__action-btn--instagram" href="<?= e($vendorInstagramUrl); ?>" target="_blank" rel="noopener" data-store-contact="instagram">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                    <?php endif; ?>
                    <?php if ($telephoneUrl): ?>
                        <a class="store-hero__action-btn store-hero__action-btn--phone" href="<?= e($telephoneUrl); ?>" data-store-contact="phone">
                            <i class="fas fa-phone"></i> Ligar
                        </a>
                    <?php endif; ?>
                    <?php if ($routeQuery): ?>
                        <?php
                            $encodedRoute = urlencode($routeQuery);
                            $googleMapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $encodedRoute;
                            $wazeUrl = 'https://waze.com/ul?ll=&q=' . $encodedRoute . '&navigate=yes';
                        ?>
                        <a class="store-hero__action-btn store-hero__action-btn--route" href="<?= e($googleMapsUrl); ?>" target="_blank" rel="noopener" data-store-route="google">
                            <i class="fas fa-map-marked-alt"></i> Maps
                        </a>
                        <a class="store-hero__action-btn store-hero__action-btn--route" href="<?= e($wazeUrl); ?>" target="_blank" rel="noopener" data-store-route="waze">
                            <i class="fab fa-waze"></i> Waze
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Ferramentas: Personalizar + Compartilhar -->
                <div class="store-hero__tools">
                    <?php if ($isStoreOwner): ?>
                        <a class="store-hero__owner-link" href="/anuncios/meus.php?tab=store">
                            <i class="fas fa-store"></i>
                            Personalizar vitrine
                        </a>
                    <?php endif; ?>
                    <button type="button" class="store-hero__share-button" id="storeShareBtn" title="Compartilhar loja" data-store-share="<?= e($canonicalUrl); ?>" data-store-name="<?= e($vendor['name'] ?? 'Loja'); ?>">
                        <i class="fas fa-share-alt"></i>
                        Compartilhar
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="store-section">
    <div class="container mx-auto px-4">
        <header class="store-toolbar">
            <div class="store-toolbar__row store-toolbar__row--top">
                <div class="store-toolbar__search input-group">
                    <label for="storeSearchInput" class="store-toolbar__label">Filtrar por marca ou modelo</label>
                    <div class="store-toolbar__search-field">
                        <input type="search"
                            id="storeSearchInput"
                            class="store-toolbar__input"
                            placeholder="Digite a marca ou modelo..."
                            autocomplete="off"
                            data-store-search>
                        <div class="autocomplete-list hidden" data-store-suggestions></div>
                    </div>
                </div>
                <div class="store-toolbar__resume">
                    <span class="store-toolbar__count"><strong data-store-count><?= $totalAds; ?></strong> veículos disponíveis</span>
                    <button type="button" class="store-toolbar__reset hidden" data-store-reset>
                        Limpar filtro
                    </button>
                </div>
            </div>
            <div class="store-toolbar__row store-toolbar__row--controls">
                <div class="store-toolbar__block">
                    <label for="storeSortSelect" class="store-toolbar__block-label">Ordenar por</label>
                    <div class="store-toolbar__block-body">
                        <select id="storeSortSelect" data-store-sort>
                            <option value="recent">Mais recentes</option>
                            <option value="price_asc">Menor preço</option>
                            <option value="price_desc">Maior preço</option>
                            <option value="mileage_asc">Menor quilometragem</option>
                            <option value="mileage_desc">Maior quilometragem</option>
                            <option value="year_desc">Ano mais novo</option>
                            <option value="year_asc">Ano mais antigo</option>
                        </select>
                    </div>
                </div>
                <div class="store-toolbar__block">
                    <span class="store-toolbar__block-label">Filtros rápidos</span>
                    <div class="store-toolbar__block-body store-toolbar__block-body--chips">
                        <button type="button" data-store-toggle="new" class="store-filter-chip">
                            <i class="fas fa-star"></i> Novos
                        </button>
                        <button type="button" data-store-toggle="premium" class="store-filter-chip">
                            <i class="fas fa-certificate"></i> Somente verificados
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="store-grid" data-store-grid data-store-owner="<?= (int)$vendorId; ?>" data-store-name="<?= e($vendor['name'] ?? ''); ?>" data-total-ads="<?= $totalAds; ?>">
            <?php foreach ($storeAds as $ad): ?>
                <?php
                $imagesJson = htmlspecialchars(json_encode($ad['images'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                $favoritePayload = [
                    'id' => (int)$ad['id'],
                    'title' => $ad['title'],
                    'price' => (float)$ad['price'],
                    'price_formatted' => $ad['price_formatted'],
                    'manufacture_year' => (int)$ad['manufacture_year'],
                    'model_year' => (int)$ad['model_year'],
                    'mileage' => (int)$ad['mileage_value'],
                    'transmission' => $ad['transmission'],
                    'fuel_type' => $ad['fuel_type'],
                    'image' => $ad['image'],
                    'images' => $ad['images'],
                    'url' => $ad['url'],
                    'city' => $ad['city'],
                    'state' => $ad['state'],
                    'is_verified' => (int)$ad['verified_badge'] === 1,
                    'store_id' => (int)$vendorId,
                ];
                $favoritePayloadJson = htmlspecialchars(json_encode($favoritePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                ?>
                <article class="vehicle-card bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col relative store-card" data-store-card data-brand="<?= e($ad['brand']); ?>" data-model="<?= e($ad['model']); ?>" data-title="<?= e($ad['title']); ?>" data-images="<?= $imagesJson; ?>" data-ad-id="<?= (int)$ad['id']; ?>" data-price="<?= (int)round($ad['price']); ?>" data-mileage="<?= (int)$ad['mileage_value']; ?>" data-updated="<?= (int)$ad['updated_ts']; ?>" data-year="<?= (int)$ad['model_year']; ?>" data-is-new="<?= $ad['is_new'] ? 'true' : 'false'; ?>" data-is-verified="<?= $ad['is_verified'] ? 'true' : 'false'; ?>">
                    <div class="relative">
                        <a href="<?= e($ad['url']); ?>" class="vehicle-card__image-link" data-store-ad-link data-ad-id="<?= (int)$ad['id']; ?>">
                            <img src="<?= e($ad['image']); ?>" alt="<?= e($ad['title']); ?>" class="w-full h-56 object-cover" data-ad-image>
                        </a>
                        <span class="price-badge"><?= e($ad['price_formatted']); ?></span>
                        <?php if (!empty($ad['is_verified'])): ?>
                            <span class="verified-badge">
                                <img src="/assets/img/verificado.png" alt="" aria-hidden="true" class="w-4 h-4">
                                <span>Verificado</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="p-5 flex flex-col flex-1">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <h3 class="text-lg font-semibold text-gray-800 flex-1">
                                <a href="<?= e($ad['url']); ?>" class="hover:text-green-700 transition-colors"><?= e($ad['title']); ?></a>
                            </h3>
                            <button type="button"
                                class="vehicle-card__favorite vehicle-card__favorite--inline"
                                data-favorite-toggle
                                data-favorite-payload="<?= $favoritePayloadJson; ?>"
                                data-favorite-id="<?= (int)$ad['id']; ?>"
                                aria-pressed="false"
                                title="Marcar como preferido">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <div class="text-sm text-gray-600 space-y-1 mb-4">
                            <?php if ($ad['year']): ?>
                                <p class="flex items-center gap-2">
                                    <i class="fa-regular fa-calendar text-amber-500"></i>
                                    <span><?= e($ad['year']); ?></span>
                                </p>
                            <?php endif; ?>
                            <?php if ($ad['mileage']): ?>
                                <p class="flex items-center gap-2">
                                    <i class="fa-solid fa-road text-amber-500"></i>
                                    <span><?= e($ad['mileage']); ?></span>
                                </p>
                            <?php endif; ?>
                            <?php if ($ad['transmission']): ?>
                                <p class="flex items-center gap-2">
                                    <i class="fa-solid fa-gear text-amber-500"></i>
                                    <span><?= e($ad['transmission']); ?></span>
                                </p>
                            <?php endif; ?>
                            <?php if ($ad['fuel_type']): ?>
                                <p class="flex items-center gap-2">
                                    <i class="fa-solid fa-gas-pump text-amber-500"></i>
                                    <span><?= e($ad['fuel_type']); ?></span>
                                </p>
                            <?php endif; ?>
                            <?php if ($ad['location']): ?>
                                <p class="text-sm text-gray-600 flex items-center">
                                    <i class="fa-solid fa-location-dot text-amber-500 mr-2"></i><?= e($ad['location']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <a href="<?= e($ad['url']); ?>" class="inline-flex items-center justify-center w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg transition-colors">
                            <i class="fa-regular fa-eye mr-2"></i> Ver detalhes
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="store-empty <?= $totalAds > 0 ? 'hidden' : ''; ?>" data-store-empty>
            <i class="fas fa-car-side"></i>
            <p>Nenhum anúncio encontrado para o filtro escolhido.</p>
        </div>
    </div>
</section>

<?php if ($storeSuggestions): ?>
    <script type="application/json" id="store-suggestions"><?= e(json_encode($storeSuggestions, JSON_UNESCAPED_UNICODE)); ?></script>
<?php endif; ?>

<?php
$storeScriptPath = __DIR__ . '/assets/js/store-view.js';
$storeScriptVersion = file_exists($storeScriptPath) ? (string)filemtime($storeScriptPath) : (string)time();
?>
<script src="/assets/js/store-view.js?v=<?= urlencode($storeScriptVersion); ?>" defer></script>

<?php include __DIR__ . '/partials/footer.php'; ?>
