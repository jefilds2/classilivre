<?php
// anuncios/ver.php - Página pública para visualizar detalhes de um anúncio.

declare(strict_types=1);

// Forçar limpeza de cache de stat
clearstatcache(true);

require_once __DIR__ . '/../login_system/middleware.php';

function format_display_address(array $data): ?string
{
    $parts = [];
    if (!empty($data['owner_address_street'])) {
        $street = $data['owner_address_street'];
        if (!empty($data['owner_address_number'])) {
            $street .= ', ' . $data['owner_address_number'];
        }
        $parts[] = $street;
    }
    if (!empty($data['owner_address_neighborhood'])) {
        $parts[] = $data['owner_address_neighborhood'];
    }
    $cityState = [];
    if (!empty($data['owner_address_city'])) {
        $cityState[] = $data['owner_address_city'];
    }
    if (!empty($data['owner_address_state'])) {
        $cityState[] = $data['owner_address_state'];
    }
    if ($cityState) {
        $parts[] = implode(' / ', $cityState);
    }
    if (!empty($data['owner_address_postal_code'])) {
        $parts[] = 'CEP ' . $data['owner_address_postal_code'];
    }
    return $parts ? implode(', ', $parts) : null;
}

$id = (int)(input_get('id') ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

$pdo = db();
$verificationCase = 'ads.verified_badge';

$stmt = $pdo->prepare("SELECT ads.*,
        users.status AS owner_status,
        users.name AS owner_name,
        users.email AS owner_email,
        users.phone AS owner_phone,
        users.phone_country_iso AS owner_phone_country_iso,
        users.phone_country_code AS owner_phone_country_code,
        users.phone_is_whatsapp AS owner_phone_is_whatsapp,
        users.is_premium AS owner_is_premium,
        users.address_street AS owner_address_street,
        users.address_number AS owner_address_number,
        users.address_complement AS owner_address_complement,
        users.address_neighborhood AS owner_address_neighborhood,
        users.address_city AS owner_address_city,
        users.address_state AS owner_address_state,
        users.address_postal_code AS owner_address_postal_code,
        users.google_avatar AS owner_avatar,
        users.show_phone AS owner_show_phone,
        users.show_whatsapp AS owner_show_whatsapp,
        users.show_email AS owner_show_email,
        users.show_address AS owner_show_address,
        users.document_type AS owner_document_type,
        users.document_number AS owner_document_number,
        users.show_document AS owner_show_document,
        users.about AS owner_about,
        $verificationCase AS owner_is_verified
    FROM ads
    JOIN users ON users.id = ads.user_id
    WHERE ads.id = :id
    LIMIT 1");
$stmt->execute([':id' => $id]);
$ad = $stmt->fetch();

if (!$ad) {
    http_response_code(404);
    exit('Anúncio não encontrado.');
}

$user = current_user();
$isOwner = $user && (int)$user['id'] === (int)$ad['user_id'];
$previewMode = $isOwner && (int)(input_get('preview') ?? 0) === 1;

// Validação 1: Se vendedor está BLOQUEADO, não exibe para público
if ($ad['owner_status'] === 'blocked' && !$isOwner && !($user && !empty($user['is_admin']))) {
    $pageTitle = 'Anúncio Indisponível - Classi Livre Autos';
    include __DIR__ . '/../partials/header.php';
    ?>
    <section class="unavailable-ad" style="padding: 40px 20px; text-align: center;">
        <div style="background-color: #f9c74f; border: 2px solid #f8b500; border-radius: 8px; padding: 30px; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333; margin: 0 0 15px 0; font-size: 24px;">Anúncio Indisponível</h2>
            <p style="color: #555; margin: 0; font-size: 16px;">Este anúncio se encontra indisponível no momento.</p>
        </div>
    </section>
    <?php
    include __DIR__ . '/../partials/footer.php';
    exit;
}

// Validação 2: Se anúncio não está APROVADO, não exibe para público
if ($ad['status'] !== 'approved' && !$isOwner && !($user && !empty($user['is_admin']))) {
    http_response_code(403);
    exit('Anúncio aguardando moderação.');
}

$pageTitle = $ad['title'] . ' - Classi Livre Autos';
$typeLabels = [
    'carro' => 'Carro',
    'moto' => 'Moto',
    'caminhao' => 'Caminhão',
    'outros' => 'Outros',
];
$vehicleType = strtolower((string)($ad['vehicle_type'] ?? 'carro'));
$typeLabel = $typeLabels[$vehicleType] ?? ucfirst($vehicleType);

$extraAttributes = [];
if (!empty($ad['extra_attributes'])) {
    $decoded = json_decode($ad['extra_attributes'], true);
    if (is_array($decoded)) {
        $extraAttributes = $decoded;
    }
}

$imagesStmt = $pdo->prepare('SELECT filepath, id FROM ad_media WHERE ad_id = :ad AND type = "image" ORDER BY sort_order ASC, id ASC');
$imagesStmt->execute([':ad' => $id]);
$images = $imagesStmt->fetchAll();

$primaryImages = [];
if ($images) {
    foreach ($images as $index => $image) {
        $src = '/' . ltrim((string)$image['filepath'], '/');
        $primaryImages[] = [
            'src' => $src,
            'alt' => sprintf('%s — foto %d', $ad['title'], $index + 1),
        ];
    }
}

if (!$primaryImages) {
    $primaryImages[] = [
        'src' => '/assets/uploads/images/sample-car-1.jpg',
        'alt' => 'Imagem ilustrativa do veículo',
    ];
}

// Meta tags Open Graph para compartilhamento social
// Verificar se há parâmetro 'photo' na URL para usar a foto selecionada
$photoIndex = 0;
if (isset($_GET['photo']) && is_numeric($_GET['photo'])) {
    $photoIndex = max(0, (int)$_GET['photo'] - 1); // Converter para índice (1-based para 0-based)
    $photoIndex = min($photoIndex, count($primaryImages) - 1); // Garantir que não exceda o número de fotos
}

$ogImage = $primaryImages[$photoIndex]['src'] ?? '/assets/uploads/images/sample-car-1.jpg';
if (strpos($ogImage, 'http') !== 0) {
    $ogImage = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $ogImage;
}

$ogDescription = sprintf(
    '%s - %s, %s com %s km',
    $ad['title'],
    ($ad['brand'] ?? 'Veículo'),
    ($extraAttributes['year'] ?? date('Y')),
    number_format((int)($ad['mileage'] ?? 0), 0, ',', '.')
);

$pageMetaTags = [
    'og:title' => $ad['title'],
    'og:description' => $ogDescription,
    'og:image' => $ogImage,
    'og:url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    'og:type' => 'website',
    'twitter:card' => 'summary_large_image',
    'twitter:title' => $ad['title'],
    'twitter:description' => $ogDescription,
    'twitter:image' => $ogImage,
];

$videoStmt = $pdo->prepare('SELECT filepath FROM ad_media WHERE ad_id = :ad AND type = "video" LIMIT 1');
$videoStmt->execute([':ad' => $id]);
$video = $videoStmt->fetch();

// Detectar se é link do Instagram ou vídeo local
$isInstagramVideo = false;
$videoPath = null;
if ($video) {
    $filepath = (string)$video['filepath'];
    // Links do Instagram contêm 'instagram.com' ou começam com 'instagram:' (fallback)
    if (strpos($filepath, 'instagram.com') !== false || strpos($filepath, 'instagram:') === 0) {
        $isInstagramVideo = true;
        // Se começar com 'instagram:', remover o prefixo
        if (strpos($filepath, 'instagram:') === 0) {
            $filepath = substr($filepath, 10); // Remove 'instagram:'
        }
        $videoPath = $filepath;
    } else {
        // É um arquivo de vídeo local
        $videoPath = '/' . ltrim($filepath, '/');
    }
}

// Badge "Verificado" agora é baseada no campo verified_badge do anúncio, não mais apenas em premium
$ownerIsVerified = (int)$ad['verified_badge'] === 1;
$ownerIsPremium = (int)$ad['owner_is_premium'] === 1;
$ownerName = (string)$ad['owner_name'];
$ownerSlug = slugify($ownerName ?: ('vendedor-' . $ad['user_id']));
$storeUrl = '/loja.php?id=' . (int)$ad['user_id'] . '&slug=' . $ownerSlug;

$sellerAddress = format_display_address($ad);
$sellerPhoneDigits = preg_replace('/\D+/', '', (string)($ad['owner_phone'] ?? ''));
$sellerPhoneCode = $ad['owner_phone_country_code'] ?: '';
$sellerPrefersWhatsapp = !empty($ad['owner_phone_is_whatsapp']);
$countryCalling = preg_replace('/\D+/', '', (string)$sellerPhoneCode) ?: '55';
$whatsNumber = $sellerPhoneDigits ? $countryCalling . $sellerPhoneDigits : null;
$telNumber = null;
if ($sellerPhoneDigits) {
    if (strlen($sellerPhoneDigits) >= 10) { // Brasileiro
        $ddd = substr($sellerPhoneDigits, 0, 2);
        $numero = substr($sellerPhoneDigits, 2);
        $telNumber = '0' . $ddd . $numero;
    } else {
        $telNumber = '0' . ltrim($sellerPhoneDigits, '0');
    }
}

$publicPath = ad_public_url($ad);
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'classilivreautos.com');
$publicUrl = $scheme . '://' . $host . $publicPath;

$shareText = sprintf('Olá! Vi este anúncio %s no Classi Livre Autos!', $ad['title']);
$shareMessageEncoded = rawurlencode($shareText);
$whatsappUrl = $whatsNumber ? 'https://api.whatsapp.com/send?phone=' . rawurlencode($whatsNumber) . '&text=' . $shareMessageEncoded : null;
$telephoneUrl = $telNumber ? 'tel:' . $telNumber : null;
$attributes = [];
$yearDisplay = trim((string)$ad['manufacture_year'] . '/' . (string)$ad['model_year'], '/');
if ($yearDisplay !== '') {
    $attributes[] = ['icon' => 'fa-calendar-alt', 'label' => 'Ano/Modelo', 'value' => $yearDisplay];
}
if (!empty($ad['mileage'])) {
    $attributes[] = ['icon' => 'fa-road', 'label' => 'Quilometragem', 'value' => number_format((float)$ad['mileage'], 0, ',', '.') . ' km'];
}
if (!empty($ad['transmission'])) {
    $attributes[] = ['icon' => 'fa-gear', 'label' => 'Câmbio', 'value' => $ad['transmission']];
}
if (!empty($ad['fuel_type'])) {
    $attributes[] = ['icon' => 'fa-gas-pump', 'label' => 'Combustível', 'value' => $ad['fuel_type']];
}
if (!empty($ad['color'])) {
    $attributes[] = ['icon' => 'fa-droplet', 'label' => 'Cor', 'value' => $ad['color']];
}
if (!empty($ad['doors'])) {
    $attributes[] = ['icon' => 'fa-door-open', 'label' => 'Portas', 'value' => (string)$ad['doors']];
}
if (!empty($ad['plate_last_digit'])) {
    $attributes[] = ['icon' => 'fa-id-card', 'label' => 'Final da placa', 'value' => (string)$ad['plate_last_digit']];
} elseif (!empty($ad['license_plate'])) {
    $attributes[] = ['icon' => 'fa-id-card', 'label' => 'Placa', 'value' => $ad['license_plate']];
}

switch ($vehicleType) {
    case 'moto':
        if (!empty($extraAttributes['estilo'])) {
            $attributes[] = ['icon' => 'fa-motorcycle', 'label' => 'Estilo', 'value' => $extraAttributes['estilo']];
        }
        if (!empty($extraAttributes['cilindradas'])) {
            $attributes[] = ['icon' => 'fa-gauge', 'label' => 'Cilindradas', 'value' => $extraAttributes['cilindradas']];
        }
        break;
    case 'caminhao':
        if (!empty($extraAttributes['tracao'])) {
            $attributes[] = ['icon' => 'fa-truck', 'label' => 'Tração', 'value' => $extraAttributes['tracao']];
        }
        if (!empty($extraAttributes['carroceria'])) {
            $attributes[] = ['icon' => 'fa-box-open', 'label' => 'Carroceria', 'value' => $extraAttributes['carroceria']];
        }
        break;
    case 'outros':
        if (!empty($extraAttributes['tipo'])) {
            $attributes[] = ['icon' => 'fa-tags', 'label' => 'Categoria', 'value' => $extraAttributes['tipo']];
        }
        if (!empty($extraAttributes['detalhes'])) {
            $attributes[] = ['icon' => 'fa-list', 'label' => 'Detalhes', 'value' => $extraAttributes['detalhes']];
        }
        break;
}

$attributes = array_values(array_filter($attributes, static fn(array $attr): bool => trim((string)$attr['value']) !== ''));

$suggestions = [];
$suggestionImages = [];
$conditions = ['ads.id <> :current', 'ads.status = "approved"', '(ads.expires_at IS NULL OR ads.expires_at > NOW())', 'ads.vehicle_type = :type'];
$suggestionParams = [
    ':current' => $id,
    ':type' => $vehicleType,
];
if (!empty($ad['brand'])) {
    $conditions[] = 'ads.brand = :brand';
    $suggestionParams[':brand'] = $ad['brand'];
}
if (!empty($ad['model'])) {
    $conditions[] = 'ads.model = :model';
    $suggestionParams[':model'] = $ad['model'];
}
$whereSuggestions = 'WHERE ' . implode(' AND ', $conditions);
$suggestStmt = $pdo->prepare("SELECT ads.id, ads.user_id, users.name AS owner_name, users.is_premium, ads.title, ads.slug, ads.price, ads.brand, ads.model, ads.manufacture_year, ads.model_year, ads.mileage, ads.city, ads.state, ads.transmission, ads.fuel_type, ads.color, $verificationCase AS is_verified
    FROM ads
    JOIN users ON users.id = ads.user_id
    $whereSuggestions
    ORDER BY ads.updated_at DESC
    LIMIT 8");
$suggestStmt->execute($suggestionParams);
$suggestions = $suggestStmt->fetchAll();

if ($suggestions) {
    $suggestionIds = array_map(static fn(array $row): int => (int)$row['id'], $suggestions);
    $placeholders = implode(',', array_fill(0, count($suggestionIds), '?'));
    $mediaStmt = $pdo->prepare("SELECT ad_id, filepath FROM ad_media WHERE ad_id IN ($placeholders) AND type = 'image' ORDER BY sort_order ASC, id ASC");
    $mediaStmt->execute($suggestionIds);
    while ($row = $mediaStmt->fetch(PDO::FETCH_ASSOC)) {
        $suggestionImages[(int)$row['ad_id']][] = '/' . ltrim((string)$row['filepath'], '/');
    }
}

$activeAdsStmt = $pdo->prepare('SELECT COUNT(*) FROM ads WHERE user_id = :uid AND status = "approved" AND (expires_at IS NULL OR expires_at > NOW())');
$activeAdsStmt->execute([':uid' => (int)$ad['user_id']]);
$sellerActiveAds = (int)$activeAdsStmt->fetchColumn();

$sellerPlatformSalesStmt = $pdo->prepare('SELECT platform_sales_count FROM users WHERE id = :uid LIMIT 1');
$sellerPlatformSalesStmt->execute([':uid' => (int)$ad['user_id']]);
$sellerPlatformSales = (int)($sellerPlatformSalesStmt->fetchColumn() ?? 0);

$ownerShowPhone = !empty($ad['owner_show_phone']);
$ownerShowWhatsapp = !empty($ad['owner_show_whatsapp']);
$ownerShowEmail = !empty($ad['owner_show_email']);
$ownerShowAddress = !empty($ad['owner_show_address']);
$ownerShowDocument = !empty($ad['owner_show_document']);
$ownerDocumentType = strtolower((string)($ad['owner_document_type'] ?? 'cpf')) === 'cnpj' ? 'cnpj' : 'cpf';
$ownerDocumentNumber = preg_replace('/\D+/', '', (string)($ad['owner_document_number'] ?? ''));
$ownerDocumentFormatted = ($ownerShowDocument && $ownerDocumentNumber !== '') ? format_document($ownerDocumentNumber, $ownerDocumentType) : null;

$displayWhatsappUrl = $ownerShowWhatsapp && $sellerPrefersWhatsapp && $whatsNumber ? $whatsappUrl : null;
$displayPhoneUrl = $ownerShowPhone && $telNumber ? $telephoneUrl : null;
$displayAddress = $ownerShowAddress ? $sellerAddress : null;
$displayEmail = $ownerShowEmail ? ($ad['owner_email'] ?? null) : null;
$displayPhoneMasked = ($ownerShowPhone || $ownerShowWhatsapp) ? mask_phone($ad['owner_phone'] ?? '') : null;

$sellerData = [
    'id' => (int)$ad['user_id'],
    'name' => $ownerName,
    'email' => $displayEmail,
    'isVerified' => $ownerIsVerified,
    'isPremium' => $ownerIsPremium,
    'slug' => $ownerSlug,
    'storeUrl' => $storeUrl,
    'address' => $displayAddress,
    'whatsappUrl' => $displayWhatsappUrl,
    'phoneUrl' => $displayPhoneUrl,
    'phoneMasked' => $displayPhoneMasked,
    'activeAds' => $sellerActiveAds,
    'soldCount' => $sellerPlatformSales,
    'avatar' => $ad['owner_avatar'] ? '/' . ltrim((string)$ad['owner_avatar'], '/') : null,
    'showWhatsapp' => $ownerShowWhatsapp,
    'showPhone' => $ownerShowPhone,
    'showEmail' => $ownerShowEmail,
    'showAddress' => $ownerShowAddress,
    'showDocument' => $ownerShowDocument,
    'document' => $ownerDocumentFormatted,
    'documentType' => $ownerDocumentType,
    'about' => $ad['owner_about'] ?? null,
];

$locationDisplay = trim((string)(($ad['city'] ?? '') . ' / ' . ($ad['state'] ?? '')), ' /');
$vehicleDescription = trim((string)($ad['description'] ?? ''));

$adPayload = [
    'id' => (int)$ad['id'],
    'title' => $ad['title'],
    'slug' => $ad['slug'],
    'price' => (float)$ad['price'],
    'price_formatted' => format_currency((float)$ad['price']),
    'manufacture_year' => (int)$ad['manufacture_year'],
    'model_year' => (int)$ad['model_year'],
    'mileage' => (int)$ad['mileage'],
    'vehicle_type' => $vehicleType,
    'transmission' => $ad['transmission'],
    'fuel_type' => $ad['fuel_type'],
    'color' => $ad['color'],
    'doors' => $ad['doors'],
    'plate_last_digit' => $ad['plate_last_digit'],
    'city' => $ad['city'],
    'state' => $ad['state'],
    'is_verified' => $ownerIsVerified,
    'images' => $primaryImages,
    'video' => $videoPath,
    'url' => $publicUrl,
    'share_text' => $shareText,
    'seller' => $sellerData,
];

$csrfToken = csrf_token();
$isAuthenticated = $user !== null;
$loginRedirectUrl = '/login_system/login.php?redirect=' . urlencode($publicPath . '#denunciar');
$logoPath = '/assets/img/classilivre-logo.png';

include __DIR__ . '/../partials/header.php';
?>
<?php if ($ownerIsPremium): ?>
    <div data-store-owner="<?= (int)$ad['user_id']; ?>" data-store-name="<?= e($ownerName); ?>" data-store-track-visit="false" hidden></div>
<?php endif; ?>
<section class="ad-view" data-ad-view>
    <header class="ad-view__heading">
        <div class="ad-view__heading-main">
            <h1 class="ad-view__title"><?= e($ad['title']); ?></h1>
            <?php if ($locationDisplay !== ''): ?>
                <p class="ad-view__location">
                    <?= e($locationDisplay); ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="ad-view__price">
            <span><?= e(format_currency((float)$ad['price'])); ?></span>
            <?php if ($previewMode): ?>
                <span class="ad-view__badge">Visualização</span>
            <?php endif; ?>
        </div>
    </header>

    <div class="ad-view__hero">
        <div class="ad-view__media">
            <div class="media-gallery" data-gallery data-gallery-length="<?= count($primaryImages); ?>">
                <div class="media-gallery__viewer">
                    <?php if (count($primaryImages) > 1): ?>
                        <button type="button" class="media-gallery__nav media-gallery__nav--prev" data-gallery-prev aria-label="Foto anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    <?php endif; ?>
                    <div class="media-gallery__stage">
                        <img src="<?= e($primaryImages[0]['src']); ?>" alt="<?= e($primaryImages[0]['alt']); ?>" data-gallery-main data-ad-image loading="eager">
                        <button type="button" class="media-gallery__fullscreen" data-gallery-open aria-label="Abrir galeria de fotos">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                    <?php if (count($primaryImages) > 1): ?>
                        <button type="button" class="media-gallery__nav media-gallery__nav--next" data-gallery-next aria-label="Próxima foto">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <?php if (count($primaryImages) > 1): ?>
                    <div class="media-gallery__thumbs" role="list">
                        <?php foreach ($primaryImages as $index => $image): ?>
                            <button type="button"
                                class="media-gallery__thumb<?= $index === 0 ? ' is-active' : ''; ?>"
                                data-gallery-thumb="<?= $index; ?>"
                                aria-label="Foto <?= $index + 1; ?>">
                                <img src="<?= e($image['src']); ?>" alt="<?= e($image['alt']); ?>" data-ad-image loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($videoPath): ?>
                <div class="media-gallery__video-block">
                    <header class="media-gallery__video-header">
                        <i class="fas fa-video"></i>
                        <span><?= $isInstagramVideo ? 'Vídeo Instagram' : 'Vídeo do veículo'; ?></span>
                    </header>
                    <div class="media-gallery__video-body">
                        <div class="media-gallery__video">
                            <?php if ($isInstagramVideo): ?>
                                <style>
                                    .instagram-card-container {
                                        position: relative;
                                        width: 100%;
                                        max-width: 600px;
                                        margin: 0 auto;
                                        background: linear-gradient(135deg, #E4405F 0%, #833AB4 50%, #FD1D1D 100%);
                                        border-radius: 12px;
                                        overflow: hidden;
                                        padding-bottom: 40%;
                                        cursor: pointer;
                                        transition: transform 0.2s, box-shadow 0.2s;
                                    }
                                    
                                    @media (min-width: 768px) {
                                        .instagram-card-container {
                                            padding-bottom: 30%;
                                        }
                                    }
                                    
                                    .instagram-card-content {
                                        position: absolute;
                                        top: 0;
                                        left: 0;
                                        width: 100%;
                                        height: 100%;
                                        display: flex;
                                        flex-direction: column;
                                        align-items: center;
                                        justify-content: center;
                                        color: white;
                                        text-align: center;
                                        padding: 20px 15px;
                                        background: rgba(0,0,0,0.3);
                                    }
                                    
                                    @media (min-width: 768px) {
                                        .instagram-card-content {
                                            padding: 40px 20px;
                                        }
                                    }
                                    
                                    .instagram-icon {
                                        font-size: 40px;
                                        margin-bottom: 10px;
                                        opacity: 0.9;
                                    }
                                    
                                    @media (min-width: 768px) {
                                        .instagram-icon {
                                            font-size: 64px;
                                            margin-bottom: 20px;
                                        }
                                    }
                                    
                                    .instagram-title {
                                        font-size: 14px;
                                        font-weight: 600;
                                        margin: 0 0 5px 0;
                                        white-space: normal;
                                    }
                                    
                                    @media (min-width: 768px) {
                                        .instagram-title {
                                            font-size: 18px;
                                            margin: 0 0 10px 0;
                                        }
                                    }
                                    
                                    .instagram-subtitle {
                                        font-size: 11px;
                                        margin: 0;
                                        opacity: 0.9;
                                    }
                                    
                                    @media (min-width: 768px) {
                                        .instagram-subtitle {
                                            font-size: 14px;
                                        }
                                    }
                                    
                                    .instagram-play-btn {
                                        width: 50px;
                                        height: 50px;
                                        border: 2px solid white;
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin-top: 10px;
                                        opacity: 0.8;
                                    }
                                    
                                    @media (min-width: 768px) {
                                        .instagram-play-btn {
                                            width: 80px;
                                            height: 80px;
                                            border: 3px solid white;
                                            margin-top: 20px;
                                        }
                                    }
                                    
                                    .instagram-play-icon {
                                        font-size: 20px;
                                        margin-left: 3px;
                                    }
                                    
                                    @media (min-width: 768px) {
                                        .instagram-play-icon {
                                            font-size: 32px;
                                            margin-left: 6px;
                                        }
                                    }
                                </style>
                                <!-- Instagram Video Card - Redireciona para Instagram -->
                                <a href="<?= e($videoPath); ?>" target="_blank" rel="noopener noreferrer" style="display: block; text-decoration: none; cursor: pointer;">
                                    <div class="instagram-card-container"
                                    onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.2)';"
                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                                        
                                        <!-- Conteúdo do Card -->
                                        <div class="instagram-card-content">
                                            <!-- Ícone do Instagram Grande -->
                                            <i class="fab fa-instagram instagram-icon"></i>
                                            
                                            <!-- Texto -->
                                            <p class="instagram-title">
                                                Assistir no Instagram
                                            </p>
                                            
                                            <!-- Subtexto -->
                                            <p class="instagram-subtitle">
                                                Clique para abrir o vídeo
                                            </p>
                                            
                                            <!-- Play Button Decorativo -->
                                            <div class="instagram-play-btn">
                                                <i class="fas fa-play instagram-play-icon"></i>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                
                                <!-- Informações abaixo do card -->
                                <div style="
                                    text-align: center;
                                    margin-top: 20px;
                                    padding: 20px;
                                    background: #f5f5f5;
                                    border-radius: 8px;
                                ">
                                    <p style="
                                        color: #666;
                                        margin: 0 0 12px 0;
                                        font-size: 14px;
                                    ">
                                        <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                                        Este vídeo está hospedado no Instagram
                                    </p>
                                    <a href="<?= e($videoPath); ?>" target="_blank" rel="noopener noreferrer" style="
                                        display: inline-block;
                                        background: linear-gradient(135deg, #E4405F 0%, #833AB4 50%, #FD1D1D 100%);
                                        color: white;
                                        padding: 12px 28px;
                                        border-radius: 6px;
                                        text-decoration: none;
                                        font-weight: 600;
                                        font-size: 14px;
                                        transition: transform 0.2s, box-shadow 0.2s;
                                    "
                                    onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)';"
                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                                        <i class="fab fa-instagram" style="margin-right: 8px;"></i>
                                        Abrir no Instagram
                                    </a>
                                </div>
                            <?php else: ?>
                                <!-- Local Video File -->
                                <video controls preload="metadata" controls oncontextmenu="return false;" poster="<?= e($primaryImages[0]['src']); ?>" data-ad-video controlsList="nodownload">
                                    <source src="<?= e($videoPath); ?>" type="video/mp4">
                                    Seu navegador não suporta vídeo HTML5.
                                </video>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside class="seller-card">
            <div class="seller-card__title-row">
                <h2 class="seller-card__title">Informações do Vendedor</h2>
                <?php if ($sellerData['isVerified']): ?>
                    <span class="badge-verified seller-card__status">
                        <img src="/assets/img/verificado.png" alt="" aria-hidden="true">
                        <span>Verificado</span>
                    </span>
                <?php endif; ?>
            </div>
            <div class="seller-card__identity">
                <?php if ($sellerData['avatar']): ?>
                    <div class="seller-card__avatar">
                        <img src="<?= e($sellerData['avatar']); ?>" alt="Foto de <?= e($sellerData['name']); ?>">
                    </div>
                <?php endif; ?>
                <div class="seller-card__info">
                    <p class="seller-card__name"><?= e($sellerData['name']); ?></p>
                    <?php if (!empty($sellerData['address'])): ?>
                        <p class="seller-card__meta">
                            <i class="fas fa-map-marker-alt"></i> <?= e($sellerData['address']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($sellerData['about'])): ?>
                        <p class="seller-card__bio"><?= nl2br(e($sellerData['about'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="seller-card__contact">
                <?php if ($sellerData['isPremium']): ?>
                    <a class="button seller-card__contact-btn seller-card__contact-btn--store" href="<?= e($storeUrl); ?>">
                        <i class="fas fa-store"></i> Loja do vendedor
                    </a>
                <?php endif; ?>
                <?php if (!empty($sellerData['whatsappUrl'])): ?>
                    <a class="button seller-card__contact-btn seller-card__contact-btn--whatsapp" href="<?= e($sellerData['whatsappUrl']); ?>" target="_blank" rel="noopener">
                        <i class="fab fa-whatsapp"></i> Conversar no WhatsApp
                    </a>
                <?php endif; ?>
                <?php if (!empty($sellerData['phoneUrl'])): ?>
                    <a class="button seller-card__contact-btn seller-card__contact-btn--phone" href="<?= e($sellerData['phoneUrl']); ?>">
                        <i class="fas fa-phone"></i> Ligar para o vendedor
                    </a>
                <?php endif; ?>
                <?php if (!empty($sellerData['email'])): ?>
                    <p class="seller-card__contact-note">
                        <i class="fas fa-envelope"></i> <?= e($sellerData['email']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($sellerData['document']) && $sellerData['showDocument']): ?>
                    <p class="seller-card__contact-note">
                        <i class="fas fa-id-card"></i> <?= e($sellerData['document']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($sellerData['phoneMasked'])): ?>
                    <p class="seller-card__contact-note">
                        <i class="fas fa-info-circle"></i> <?= e($sellerData['phoneMasked']); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="seller-card__actions">
                <button type="button" class="seller-card__action seller-card__action--favorite" data-action="favorite" data-favorite-current aria-pressed="false">
                    <i class="fas fa-heart"></i>
                    <span>Favoritar</span>
                </button>
                <button type="button" class="seller-card__action" data-action="compare">
                    <i class="fas fa-scale-balanced"></i>
                    <span>Comparar</span>
                </button>
                <button type="button" class="seller-card__action" data-action="share">
                    <i class="fas fa-share-alt"></i>
                    <span>Compartilhar</span>
                </button>
                <button type="button" class="seller-card__action" data-action="report">
                    <i class="fas fa-flag"></i>
                    <span>Denunciar</span>
                </button>
            </div>

            <ul class="seller-card__stats">
                <li>
                    <span class="label">Categoria</span>
                    <span class="value"><?= e($typeLabel); ?></span>
                </li>
                <li>
                    <span class="label">Anúncios ativos</span>
                    <span class="value"><?= $sellerData['activeAds']; ?></span>
                </li>
                <li>
                    <span class="label">Vendas na plataforma</span>
                    <span class="value"><?= $sellerData['soldCount'] !== null ? (int)$sellerData['soldCount'] : 'Em breve'; ?></span>
                </li>
            </ul>
        </aside>
    </div>

    <?php if ($attributes): ?>
        <section class="form-card ad-section">
            <h2 class="ad-section__title">Detalhes do Veículo</h2>
            <div class="vehicle-attributes" role="list">
                <?php foreach ($attributes as $attr): ?>
                    <div class="vehicle-attributes__item" role="listitem" title="<?= e($attr['label']); ?>">
                        <div class="vehicle-attributes__icon">
                            <i class="fas <?= e($attr['icon']); ?>"></i>
                        </div>
                        <div class="vehicle-attributes__content">
                            <span class="vehicle-attributes__label"><?= e($attr['label']); ?></span>
                            <span class="vehicle-attributes__value"><?= e((string)$attr['value']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($vehicleDescription !== ''): ?>
        <section class="form-card ad-section">
            <h2 class="ad-section__title">Descrição</h2>
            <div class="ad-description">
                <p><?= nl2br(e($vehicleDescription)); ?></p>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($suggestions): ?>
        <section class="form-card ad-suggestions">
            <header class="ad-suggestions__header">
                <i class="fas fa-car-side" aria-hidden="true"></i>
                <div class="ad-suggestions__text">
                    <h2>Você também pode gostar</h2>
                    <p>Veículos semelhantes disponíveis na plataforma</p>
                </div>
            </header>
            <div class="ad-suggestions__list">
                <?php foreach ($suggestions as $suggestIndex => $suggest): ?>
                    <?php
                    $imageList = $suggestionImages[(int)$suggest['id']] ?? [];
                    $cover = $imageList[0] ?? '/assets/uploads/images/sample-car-1.jpg';
                    if (!$imageList) {
                        $imageList[] = $cover;
                    }
                    $imagesJson = htmlspecialchars(json_encode($imageList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                    $suggestUrl = '/anuncio/' . (int)$suggest['id'] . '/' . ($suggest['slug'] ?? slugify((string)$suggest['title']));
                    $suggestLocation = trim((string)(($suggest['city'] ?? '') . ' / ' . ($suggest['state'] ?? '')), ' /');
                    $mileageValue = isset($suggest['mileage']) ? (int)$suggest['mileage'] : null;
                    $mileageLabel = ($mileageValue && $mileageValue > 0) ? number_format($mileageValue, 0, ',', '.') . ' km' : null;
                    $modelYear = !empty($suggest['model_year']) ? (string)$suggest['model_year'] : (!empty($suggest['manufacture_year']) ? (string)$suggest['manufacture_year'] : null);
                    $colorLabel = !empty($suggest['color']) ? ucfirst(strtolower((string)$suggest['color'])) : null;
                    $primaryDetails = array_values(array_filter([$modelYear, $colorLabel, $mileageLabel], static fn($value) => !empty($value)));
                    $primaryText = implode(' | ', $primaryDetails);
                    $transmissionLabel = !empty($suggest['transmission']) ? (string)$suggest['transmission'] : null;
                    $fuelLabel = !empty($suggest['fuel_type']) ? (string)$suggest['fuel_type'] : null;
                    $secondaryDetails = array_values(array_filter([$transmissionLabel, $fuelLabel], static fn($value) => !empty($value)));
                    $secondaryText = implode(' | ', $secondaryDetails);
                    $isVerifiedSuggest = !empty($suggest['is_verified']);
                    $storeOwnerId = (int)($suggest['user_id'] ?? 0);
                    $storeOwnerName = (string)($suggest['owner_name'] ?? '');
                    $trackPosition = $suggestIndex + 1;
                    $favoritePayload = [
                        'id' => (int)$suggest['id'],
                        'title' => $suggest['title'],
                        'price' => (float)$suggest['price'],
                        'price_formatted' => format_currency((float)$suggest['price']),
                        'manufacture_year' => (int)$suggest['manufacture_year'],
                        'model_year' => (int)$suggest['model_year'],
                        'mileage' => (int)$suggest['mileage'],
                        'transmission' => $suggest['transmission'] ?? null,
                        'fuel_type' => $suggest['fuel_type'] ?? null,
                        'image' => $cover,
                        'images' => $imageList,
                        'url' => $suggestUrl,
                        'city' => $suggest['city'],
                        'state' => $suggest['state'],
                        'is_verified' => $isVerifiedSuggest,
                        'is_premium' => (int)($suggest['is_premium'] ?? 0) === 1,
                        'store_id' => $storeOwnerId,
                        'owner_name' => $storeOwnerName,
                    ];
                    $favoritePayloadJson = htmlspecialchars(json_encode($favoritePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                    ?>
                    <article class="vehicle-card bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col relative" data-favorite-card data-ad-id="<?= (int)$suggest['id']; ?>" data-images="<?= $imagesJson; ?>"<?= $storeOwnerId > 0 ? ' data-store-owner="' . $storeOwnerId . '"' : ''; ?><?= $storeOwnerName !== '' ? ' data-store-owner-name="' . e($storeOwnerName) . '"' : ''; ?> data-track-origin="ad_detail_suggestions" data-track-position="<?= $trackPosition; ?>">
                        <div class="relative">
                            <a href="<?= e($suggestUrl); ?>" class="block" data-track-ad-click>
                                <img src="<?= e($cover); ?>" alt="<?= e($suggest['title']); ?>" class="w-full h-56 object-cover" data-primary-image loading="lazy">
                            </a>
                            <span class="price-badge"><?= e(format_currency((float)$suggest['price'])); ?></span>
                            <?php if ($isVerifiedSuggest): ?>
                                <span class="verified-badge">
                                    <img src="/assets/img/verificado.png" alt="Anunciante verificado" class="w-4 h-4" aria-hidden="true">
                                    <span>Verificado</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <h3 class="text-lg font-semibold text-gray-800 flex-1"><?= e($suggest['title']); ?></h3>
                                <button type="button"
                                    class="vehicle-card__favorite vehicle-card__favorite--inline"
                                    data-favorite-toggle
                                    data-favorite-payload="<?= $favoritePayloadJson; ?>"
                                    aria-pressed="false"
                                    title="Marcar como preferido">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                            <div class="text-sm text-gray-600 space-y-2 mb-4">
                                <?php if ($primaryText !== ''): ?>
                                    <p class="flex items-center gap-2">
                                        <i class="fa-regular fa-calendar text-amber-500"></i>
                                        <span><?= e($primaryText); ?></span>
                                    </p>
                                <?php endif; ?>
                                <?php if ($secondaryText !== ''): ?>
                                    <p class="flex items-center gap-2">
                                        <i class="fa-solid fa-gear text-amber-500"></i>
                                        <span><?= e($secondaryText); ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php if ($suggestLocation !== ''): ?>
                                <p class="text-sm text-gray-600 flex items-center mb-5">
                                    <i class="fa-solid fa-location-dot text-amber-500 mr-2"></i>
                                    <?= e($suggestLocation); ?>
                                </p>
                            <?php endif; ?>
                            <a class="inline-flex items-center justify-center w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg transition-colors" href="<?= e($suggestUrl); ?>" data-track-ad-click>
                                <i class="fa-regular fa-eye mr-2"></i> Ver detalhes
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>

<div class="modal-overlay" data-modal="lightbox" aria-hidden="true">
    <div class="lightbox" role="dialog" aria-modal="true" aria-label="Galeria de fotos do veículo">
        <button type="button" class="lightbox__close" data-modal-close aria-label="Fechar galeria">
            <i class="fas fa-times"></i>
        </button>
        <div class="lightbox__viewer">
            <?php if (count($primaryImages) > 1): ?>
                <button type="button" class="lightbox__nav lightbox__nav--prev" data-lightbox-prev aria-label="Foto anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
            <?php endif; ?>
            <figure class="lightbox__stage">
                <img src="<?= e($primaryImages[0]['src']); ?>" alt="<?= e($primaryImages[0]['alt']); ?>" data-lightbox-main data-ad-image>
            </figure>
            <?php if (count($primaryImages) > 1): ?>
                <button type="button" class="lightbox__nav lightbox__nav--next" data-lightbox-next aria-label="Próxima foto">
                    <i class="fas fa-chevron-right"></i>
                </button>
            <?php endif; ?>
        </div>
        <?php if (count($primaryImages) > 1): ?>
            <div class="lightbox__thumbs">
                <?php foreach ($primaryImages as $index => $image): ?>
                    <button type="button"
                        class="lightbox__thumb<?= $index === 0 ? ' is-active' : ''; ?>"
                        data-lightbox-thumb="<?= $index; ?>"
                        aria-label="Foto <?= $index + 1; ?>">
                        <img src="<?= e($image['src']); ?>" alt="<?= e($image['alt']); ?>" data-ad-image>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" data-modal="compare" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Comparar anúncios">
        <header class="modal__header">
            <h2>Comparador de anúncios</h2>
            <button type="button" class="button secondary modal__close" data-modal-close><i class="fas fa-times"></i></button>
        </header>
        <div class="modal__body">
            <button type="button" class="compare-nav-button compare-nav-button--left" data-compare-nav="left" aria-label="Scroll esquerda">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div data-compare-content></div>
            <button type="button" class="compare-nav-button compare-nav-button--right" data-compare-nav="right" aria-label="Scroll direita">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <footer class="modal__footer">
            <button type="button" class="button secondary" data-compare-clear>Limpar preferidos</button>
            <button type="button" class="button" data-modal-close>Fechar</button>
        </footer>
    </div>
</div>

    <div class="modal-overlay" data-modal="share" aria-hidden="true">
    <div class="modal modal--share" role="dialog" aria-modal="true" aria-label="Compartilhar anúncio">
        <header class="modal__header">
            <h2 style="background: linear-gradient(to right, #166534 0%, #166534 8px, transparent 8px); padding-left: 12px; display: inline-block;">Compartilhar anúncio</h2>
            <button type="button" class="button secondary modal__close" data-modal-close><i class="fas fa-times"></i></button>
        </header>
        <div class="modal__body share-modal">
            <p class="share-modal__intro">Escolha uma plataforma para enviar este veículo:</p>
            
            <!-- Seletor de Fotos para Compartilhamento Social -->
            <div class="share-modal__photo-selector" data-share-photo-selector hidden>
                <div class="share-modal__photo-preview">
                    <img class="share-modal__selected-photo" data-share-selected-photo src="" alt="Foto selecionada">
                </div>
                <div class="share-modal__photo-gallery" data-share-photo-gallery></div>
                <button type="button" class="button" data-share-photo-confirm hidden>Compartilhar com esta foto</button>
                <button type="button" class="button secondary" data-share-photo-cancel hidden>Voltar</button>
            </div>

            <div class="share-modal__grid" data-share-options-grid>
                <button type="button" class="share-modal__option share-modal__option--whatsapp" data-share-target="whatsapp">
                    <i class="fab fa-whatsapp"></i>
                    <span>WhatsApp</span>
                </button>
                <button type="button" class="share-modal__option share-modal__option--instagram" data-share-target="instagram">
                    <i class="fab fa-instagram"></i>
                    <span>Instagram<br><small>Stories</small></span>
                </button>
                <button type="button" class="share-modal__option share-modal__option--facebook" data-share-target="facebook">
                    <i class="fab fa-facebook-f"></i>
                    <span>Facebook<br><small>Feed</small></span>
                </button>
                <button type="button" class="share-modal__option share-modal__option--email" data-share-target="email">
                    <i class="fas fa-envelope"></i>
                    <span>E-mail</span>
                </button>
                <button type="button" class="share-modal__option share-modal__option--copy" data-share-copy-link>
                    <i class="fas fa-link"></i>
                    <span>Copiar link</span>
                </button>
            </div>
            <div class="share-modal__message">
                <span class="share-modal__message-label">Mensagem sugerida</span>
                <p class="share-modal__message-text" data-share-message><?= e($shareText); ?></p>
                <button type="button" class="button secondary share-modal__copy-message" data-share-copy-message>
                    <i class="fas fa-copy"></i> Copiar mensagem
                </button>
                <span class="share-modal__feedback" data-share-feedback hidden></span>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" data-modal="report" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Denunciar anúncio">
        <header class="modal__header">
            <h2>Denunciar anúncio</h2>
            <button type="button" class="button secondary modal__close" data-modal-close><i class="fas fa-times"></i></button>
        </header>
        <div class="modal__body">
            <form data-report-form>
                <fieldset class="report-reasons">
                    <legend>Qual o motivo da denúncia?</legend>
                    <?php foreach ([
                        'informations_false' => 'Informações falsas ou enganosas',
                        'photo_inadequate' => 'Foto indevida ou ofensiva',
                        'scam' => 'Golpe / veículo não existe',
                        'duplicated' => 'Anúncio duplicado',
                        'other' => 'Outro motivo',
                    ] as $value => $label): ?>
                        <label class="report-reason">
                            <input type="radio" name="reason" value="<?= e($value); ?>" required>
                            <span><?= e($label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <div class="input-group">
                    <label for="reportDetails">Detalhes adicionais (opcional)</label>
                    <textarea id="reportDetails" name="details" rows="4" placeholder="Conte o que aconteceu"></textarea>
                </div>
                <div class="input-group">
                    <label for="reportEvidence">Link com evidências (opcional)</label>
                    <input type="url" id="reportEvidence" name="evidence" placeholder="https://">
                </div>
                <div class="modal__footer">
                    <button type="submit" class="button secondary">Enviar denúncia</button>
                    <button type="button" class="button" data-modal-close>Cancelar</button>
                </div>
            </form>
            <div class="report-feedback" data-report-feedback hidden></div>
        </div>
    </div>
</div>
<?php
// Forçar limpeza de cache sempre
clearstatcache(true);

// Usar hash aleatório para garantir que sempre carrega novo script
// Isso força o browser a baixar o arquivo novamente mesmo com cache
$randomHash = bin2hex(random_bytes(8));
$viewScriptCacheBreaker = 'v=' . time() . '&h=' . $randomHash;
?>
<script>
window.AD_VIEW = <?= json_encode($adPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.AD_VIEW_CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.AD_VIEW_CONFIG = {
    authenticated: <?= $isAuthenticated ? 'true' : 'false'; ?>,
    loginUrl: <?= json_encode($loginRedirectUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    logo: <?= json_encode($logoPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
};
</script>
<script defer src="/assets/js/view-ad.js?<?= $viewScriptCacheBreaker; ?>"></script>
<?php include __DIR__ . '/../partials/footer.php'; ?>

