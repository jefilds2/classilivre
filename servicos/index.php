<?php
// servicos/index.php — Catálogo público de serviços automotivos.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../propagandas/analytics/tracker.php';

// Rastrear acesso à página
AnalyticsTracker::trackPageLoad('servicos');

$user = current_user();
$pageTitle = 'Serviços Automotivos | Classi Livre Autos';

$pdo = db();

$categoryOptions = [
    'mechanic' => ['Oficina mecânica', 'Oficina mecanica'],
    'bodyshop' => ['Funilaria e pintura'],
    'detailing' => ['Estética automotiva', 'Estetica automotiva'],
    'tire' => ['Pneus e suspensão', 'Pneus e suspensao'],
    'alignment' => ['Alinhamento e balanceamento'],
    'electric' => ['Elétrica automotiva', 'Eletrica automotiva'],
    'air_conditioning' => ['Ar-condicionado automotivo', 'Ar condicionado automotivo'],
    'battery' => ['Baterias e elétrica leve', 'Baterias e eletrica leve'],
    'paintless_dent' => ['Martelinho de ouro'],
    'glass' => ['Vidros e para-brisas', 'Vidros e para brisas'],
    'parts' => ['Autopeças', 'Autopecas'],
    'tow' => ['Guincho e reboque'],
    'inspection' => ['Vistoria veicular'],
    'wash' => ['Lavagem e polimento'],
    'car_audio' => ['Som e multimídia', 'Som e multimedia'],
    'insurance' => ['Corretora de seguros'],
    'tracker' => ['Rastreamento veicular'],
    'custom' => ['Outro serviço', 'Outros serviços', 'Outro servico'],
];

$categories = array_combine(
    array_keys($categoryOptions),
    array_map(static fn(array $values): string => $values[0], $categoryOptions)
);

$rawCategory = input_get('category');
$category = array_key_exists((string)$rawCategory, $categories) ? (string)$rawCategory : '';

$searchTerm = trim((string)(input_get('q') ?? ''));

$hasWhatsapp = input_get('has_whatsapp') === '1';

$cityInput = input_get('city');
$cityFilter = $cityInput ? trim((string)$cityInput) : '';
$parsedLocation = parse_location_filter($cityFilter);
$city = $parsedLocation['city'];
$cityDisplay = $parsedLocation['display'];

$state = strtoupper((string)(input_get('state') ?? ''));
if ($state !== '' && !array_key_exists($state, brazilian_states())) {
    $state = '';
}

if ($city === '' && $state === '' && $category === '' && !$hasWhatsapp) {
    $city = $user['city'] ?? '';
    $state = $user['state'] ?? '';
}

$whereParts = [
    "sl.status = 'active'",
    "sl.payment_status = 'paid'"
];
$params = [];

if ($category !== '') {
    if (isset($categoryOptions[$category])) {
        $categoryVariants = $categoryOptions[$category];
        $placeholders = [];
        foreach ($categoryVariants as $i => $variant) {
            $placeholderKey = ':category_variant_' . $i;
            $placeholders[] = $placeholderKey;
            $params[$placeholderKey] = $variant;
        }
        $whereParts[] = 'sl.category IN (' . implode(', ', $placeholders) . ')';
    } else {
        $whereParts[] = 'sl.category = :category_generic';
        $params[':category_generic'] = $category;
    }
}

if ($city !== '') {
    $whereParts[] = 'sl.address_city LIKE :city';
    $params[':city'] = '%' . $city . '%';
}

if ($state !== '') {
    $whereParts[] = 'sl.address_state = :state';
    $params[':state'] = $state;
}

if ($hasWhatsapp) {
    $whereParts[] = "sl.whatsapp IS NOT NULL AND sl.whatsapp <> ''";
}

if ($searchTerm !== '') {
    $whereParts[] = '(sl.name LIKE :search OR sl.category LIKE :search OR sl.custom_category LIKE :search)';
    $params[':search'] = '%' . $searchTerm . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $whereParts);

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_service_listings sl $whereSql");
    $countStmt->execute($params);
    $totalServices = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    error_log('[servicos/index.php] Erro ao contar serviços: ' . $e->getMessage());
    $totalServices = 0;
}

$perPage = 9;
$page = max(1, (int)(input_get('page') ?? 1));
$totalPages = max(1, (int)ceil($totalServices / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$querySql = "SELECT
        sl.*,
        u.name AS owner_name,
        CASE
            WHEN u.is_premium = 1 OR EXISTS (
                SELECT 1 FROM premium_subscriptions ps
                WHERE ps.user_id = u.id AND (ps.expires_at IS NULL OR ps.expires_at > NOW())
            ) THEN 1 ELSE 0 END AS is_verified
    FROM user_service_listings sl
    JOIN users u ON u.id = sl.user_id
    $whereSql
    ORDER BY sl.published_at DESC, sl.updated_at DESC
    LIMIT :limit OFFSET :offset";

$listStmt = $pdo->prepare($querySql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);

try {
    $listStmt->execute();
    $rawServices = $listStmt->fetchAll();
} catch (Exception $e) {
    error_log('[servicos/index.php] Erro ao executar query: ' . $e->getMessage());
    error_log('[servicos/index.php] SQL: ' . $querySql);
    error_log('[servicos/index.php] Params: ' . json_encode($params));
    http_response_code(500);
    exit('Erro ao buscar serviços. Tente novamente em instantes.');
}

function build_page_url(array $query, int $page): string
{
    $query['page'] = $page;
    return '/servicos/index.php' . ($query ? '?' . http_build_query($query) : '');
}

function parse_location_filter(?string $value): array
{
    $value = trim((string)$value);
    if ($value === '') {
        return ['city' => '', 'display' => ''];
    }

    if (preg_match('/^(.*?)[\s,\/\-]+([A-Za-z]{2})$/u', $value, $matches)) {
        $city = trim((string)$matches[1]);
        $stateCandidate = strtoupper((string)$matches[2]);
        if ($city !== '' && array_key_exists($stateCandidate, brazilian_states())) {
            return ['city' => $city, 'display' => $value];
        }
    }

    return ['city' => $value, 'display' => $value];
}

function brazilian_states(): array
{
    return [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins',
    ];
}

function format_service_phone(?string $value): ?string
{
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') {
        return null;
    }

    if (strlen($digits) === 10) {
        return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
    }

    if (strlen($digits) === 11) {
        return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
    }

    return $value;
}

function build_phone_dial_string(?string $value): ?string
{
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') {
        return null;
    }

    if (strlen($digits) >= 12 && substr($digits, 0, 2) === '55') {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) < 10) {
        return $digits;
    }

    $ddd = substr($digits, 0, 2);
    $localNumber = substr($digits, 2);
    return '0' . $ddd . $localNumber;
}

function build_whatsapp_url(?string $value, ?string $message = null): ?string
{
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') {
        return null;
    }

    if (strlen($digits) >= 12 && substr($digits, 0, 2) === '55') {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) < 10) {
        return null;
    }

    $target = '55' . $digits;
    $url = 'https://wa.me/' . $target;
    if ($message) {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

function build_service_route_links(array $row): array
{
    $segments = [];

    $street = trim((string)($row['address_street'] ?? ''));
    $number = trim((string)($row['address_number'] ?? ''));
    if ($street !== '') {
        $streetLine = $street;
        if ($number !== '') {
            $streetLine .= ', ' . $number;
        }
        $segments[] = $streetLine;
    }

    $neighborhood = trim((string)($row['address_neighborhood'] ?? ''));
    if ($neighborhood !== '') {
        $segments[] = $neighborhood;
    }

    $city = trim((string)($row['address_city'] ?? ''));
    $state = trim((string)($row['address_state'] ?? ''));
    if ($city !== '') {
        $cityLine = $city;
        if ($state !== '') {
            $cityLine .= ' - ' . $state;
        }
        $segments[] = $cityLine;
    } elseif ($state !== '') {
        $segments[] = $state;
    }

    $postalCode = preg_replace('/\D+/', '', (string)($row['address_postal_code'] ?? ''));
    if ($postalCode !== '') {
        $segments[] = $postalCode;
    }

    if (!$segments) {
        return ['query' => null, 'google' => null, 'waze' => null];
    }

    $query = implode(', ', $segments);
    $encoded = rawurlencode($query);

    return [
        'query' => $query,
        'google' => 'https://www.google.com/maps/dir/?api=1&destination=' . $encoded,
        'waze' => 'https://waze.com/ul?ll=&q=' . $encoded . '&navigate=yes',
    ];
}

function format_document_label(?string $documentType, ?string $documentNumber): ?string
{
    $number = preg_replace('/\D+/', '', (string)$documentNumber);
    if ($number === '') {
        return null;
    }

    if ($documentType === 'cnpj' && strlen($number) === 14) {
        return sprintf('CNPJ %s.%s.%s/%s-%s',
            substr($number, 0, 2),
            substr($number, 2, 3),
            substr($number, 5, 3),
            substr($number, 8, 4),
            substr($number, 12, 2)
        );
    }

    if (strlen($number) === 11) {
        return sprintf('CPF %s.%s.%s-%s',
            substr($number, 0, 3),
            substr($number, 3, 3),
            substr($number, 6, 3),
            substr($number, 9, 2)
        );
    }

    return null;
}

function build_remove_filter(array $query, array $keys): string
{
    foreach ($keys as $key) {
        unset($query[$key]);
    }
    unset($query['page']);
    return '/servicos/index.php' . ($query ? '?' . http_build_query($query) : '');
}

$searchSuggestionSet = [];
$citySuggestionSet = ['__all' => []];
$whatsappContactMessage = 'Oi! Ví seus serviços no site Classi Livre Autos e, gostaria de algumas informações.';

$services = [];
foreach ($rawServices as $row) {
    try {
        $routeLinks = build_service_route_links($row);
        $locationSegments = [];
        if (!empty($row['address_city'])) {
            $locationSegments[] = $row['address_city'];
        }
        if (!empty($row['address_state'])) {
            $locationSegments[] = $row['address_state'];
        }
        $categoryFromDb = (string)($row['category'] ?? '');
        $categoryKeyForCard = '';
        foreach ($categoryOptions as $key => $variants) {
            if (in_array($categoryFromDb, $variants, true)) {
                $categoryKeyForCard = $key;
                break;
            }
        }
        $displayCategoryLabel = $categoryKeyForCard !== ''
            ? $categoryOptions[$categoryKeyForCard][0]
            : ($categoryFromDb !== '' ? $categoryFromDb : 'Serviço automotivo');

        $serviceName = trim((string)($row['name'] ?? ''));
        if ($serviceName !== '') {
            $searchSuggestionSet[$serviceName] = true;
        }
        if ($displayCategoryLabel !== '') {
            $searchSuggestionSet[$displayCategoryLabel] = true;
        }
        $customCategoryLabel = trim((string)($row['custom_category'] ?? ''));
        if ($customCategoryLabel !== '') {
            $searchSuggestionSet[$customCategoryLabel] = true;
        }

        $cityName = trim((string)($row['address_city'] ?? ''));
        $stateCode = strtoupper((string)($row['address_state'] ?? ''));
        if ($cityName !== '') {
            $citySuggestionSet['__all'][$cityName] = true;
            if ($stateCode !== '') {
                if (!isset($citySuggestionSet[$stateCode])) {
                    $citySuggestionSet[$stateCode] = [];
                }
                $citySuggestionSet[$stateCode][$cityName] = true;
            }
        }

        $services[] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'category_key' => $categoryKeyForCard,
            'category_label' => $displayCategoryLabel,
            'custom_category' => $row['custom_category'] ?: null,
            'description' => $row['description'] ?: null,
            'location' => $locationSegments ? implode(' / ', $locationSegments) : null,
            'city' => (string)($row['address_city'] ?? ''),
            'state' => (string)($row['address_state'] ?? ''),
            'phone' => format_service_phone($row['phone'] ?? ''),
            'phone_raw' => preg_replace('/\D+/', '', (string)($row['phone'] ?? '')),
            'phone_dial' => build_phone_dial_string($row['phone'] ?? ''),
            'whatsapp' => format_service_phone($row['whatsapp'] ?? ''),
            'whatsapp_url' => build_whatsapp_url($row['whatsapp'] ?? '', $whatsappContactMessage),
            'email' => $row['email'] ?: null,
            'has_whatsapp' => !empty($row['whatsapp']),
            'document_visible' => !empty($row['show_document']) && !empty($row['document_number']),
            'document_label' => format_document_label($row['document_type'] ?? 'cpf', $row['document_number'] ?? ''),
            'premium_badge' => (int)$row['is_verified'] === 1,
            'route_query' => $routeLinks['query'],
            'google_maps_url' => $routeLinks['google'],
            'waze_url' => $routeLinks['waze'],
            'published_at' => $row['published_at'] ? format_date($row['published_at']) : format_date($row['created_at']),
        ];
    } catch (Exception $e) {
        error_log('[servicos/index.php] Erro ao processar serviço (ID: ' . ($row['id'] ?? 'unknown') . '): ' . $e->getMessage());
        continue;
    }
}

$searchSuggestions = array_keys($searchSuggestionSet);
sort($searchSuggestions, SORT_FLAG_CASE | SORT_NATURAL);

$citySuggestionsByState = [];
foreach ($citySuggestionSet as $stateKey => $citySet) {
    $cities = array_keys($citySet);
    sort($cities, SORT_FLAG_CASE | SORT_NATURAL);
    $citySuggestionsByState[$stateKey] = array_values($cities);
}

$queryParams = $_GET;
$activeFilters = [];

if ($category !== '') {
    $activeFilters[] = [
        'label' => 'Categoria: ' . ($categories[$category] ?? ucfirst($category)),
        'url' => build_remove_filter($queryParams, ['category']),
    ];
}

if ($cityDisplay !== '') {
    $activeFilters[] = [
        'label' => 'Cidade: ' . $cityDisplay,
        'url' => build_remove_filter($queryParams, ['city']),
    ];
}

if ($state !== '') {
    $activeFilters[] = [
        'label' => 'Estado: ' . ($state !== '' ? ($state . ' - ' . brazilian_states()[$state]) : ''),
        'url' => build_remove_filter($queryParams, ['state']),
    ];
}

if ($hasWhatsapp) {
    $activeFilters[] = [
        'label' => 'Atende no WhatsApp',
        'url' => build_remove_filter($queryParams, ['has_whatsapp']),
    ];
}

if ($searchTerm !== '') {
    $activeFilters[] = [
        'label' => 'Busca: ' . $searchTerm,
        'url' => build_remove_filter($queryParams, ['q']),
    ];
}

$resultsLabel = $totalServices === 1 ? '1 serviço encontrado' : $totalServices . ' serviços encontrados';

$resultsLabel .= $cityDisplay !== '' ? ' em ' . $cityDisplay : '';
if ($state !== '') {
    $resultsLabel .= $cityDisplay !== '' ? ' - ' . $state : ' em ' . $state;
}

include __DIR__ . '/../partials/header.php';
?>
<section class="py-12 bg-gradient-to-b from-emerald-950 via-emerald-800 to-emerald-700 text-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-500 text-emerald-950 text-sm font-semibold">
                <i class="fas fa-tools mr-1"></i> Catálogo de Serviços Classi Livre
            </span>
            <h1 class="text-3xl md:text-4xl font-bold mt-4">Encontre especialistas automotivos de confiança</h1>
            <p class="text-emerald-100 mt-3 text-lg">
                Mecânicos, funilarias, guinchos e diversos especialistas prontos para atender você e o seu veículo.
            </p>
        </div>
        <div class="mt-10 bg-white text-emerald-950 rounded-3xl shadow-2xl p-6 md:p-8">
            <form method="get" class="grid gap-4 md:grid-cols-12" data-service-filters>
                <div class="md:col-span-5">
                    <label for="service_search" class="block text-sm font-semibold text-emerald-900 mb-2">Buscar por nome ou categoria</label>
                    <div class="relative">
                        <input type="search" id="service_search" name="q" value="<?= e($searchTerm); ?>" class="w-full border border-emerald-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Digite parte do nome ou categoria" autocomplete="off" data-service-search list="service_search_suggestions">
                        <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-emerald-400"></i>
                        <datalist id="service_search_suggestions">
                            <?php foreach ($searchSuggestions as $suggestion): ?>
                                <option value="<?= e($suggestion); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                <div class="md:col-span-5">
                    <label for="category" class="block text-sm font-semibold text-emerald-900 mb-2">Categoria do serviço</label>
                    <div class="relative">
                        <select id="category" name="category" class="w-full border border-emerald-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= e($key); ?>" <?= $key === ($category ?? '') ? 'selected' : ''; ?>><?= e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-emerald-400"></i>
                    </div>
                </div>
                <div class="md:col-span-4">
                    <label for="city" class="block text-sm font-semibold text-emerald-900 mb-2">Cidade</label>
                    <div class="relative">
                        <input type="search" id="city" name="city" value="<?= e($city ?? ''); ?>" class="w-full border border-emerald-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Ex: Guanhães ou Guanhães / MG" autocomplete="off" data-city-input list="service_city_suggestions">
                        <datalist id="service_city_suggestions"></datalist>
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label for="state" class="block text-sm font-semibold text-emerald-900 mb-2">UF</label>
                    <select id="state" name="state" class="w-full border border-emerald-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">Todas</option>
                        <?php foreach (brazilian_states() as $uf => $label): ?>
                            <option value="<?= e($uf); ?>" <?= $uf === ($state ?? '') ? 'selected' : ''; ?>><?= e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-12 flex flex-wrap gap-3 items-end justify-between">
                    <label class="inline-flex items-center space-x-2 text-sm font-semibold text-emerald-900">
                        <input type="checkbox" name="has_whatsapp" value="1" <?= !empty($hasWhatsapp) ? 'checked' : ''; ?> class="rounded border-emerald-300 text-amber-500 focus:ring-amber-500">
                        <span>Mostrar apenas quem atende no WhatsApp</span>
                    </label>
                    <div class="flex gap-3 w-full md:w-auto">
                        <a href="/servicos/index.php" class="button secondary flex-1 md:flex-none justify-center">
                            <i class="fas fa-times mr-2"></i> Limpar filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="py-14 bg-gray-100">
    <div class="container mx-auto px-4">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-emerald-950">Resultados</h2>
                <p class="text-emerald-700 text-sm mt-1" data-service-results-label><?= e($resultsLabel ?? 'Serviços encontrados'); ?></p>
            </div>
            <?php if (!empty($activeFilters)): ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($activeFilters as $filter): ?>
                        <a href="<?= e($filter['url']); ?>" class="inline-flex items-center gap-2 bg-white text-emerald-900 px-3 py-2 rounded-full text-sm shadow">
                            <span><?= e($filter['label']); ?></span>
                            <i class="fas fa-times text-emerald-400"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$services): ?>
            <div class="bg-white rounded-3xl shadow-xl p-10 text-center text-emerald-900">
                <div class="w-16 h-16 mx-auto flex items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-4">
                    <i class="fas fa-info"></i>
                </div>
                <h3 class="text-xl font-semibold">Nenhum serviço encontrado</h3>
                <p class="mt-2 text-emerald-600">Ajuste os filtros ou tente novamente mais tarde.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3 service-cards-grid" data-service-list>
                <?php foreach ($services as $service): ?>
                    <article class="bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col transition-transform hover:-translate-y-1 hover:shadow-2xl" data-service-card data-service-name="<?= e($service['name']); ?>" data-service-category="<?= e($service['category_key']); ?>" data-service-category-label="<?= e($service['category_label']); ?>" data-service-custom="<?= e($service['custom_category'] ?? ''); ?>" data-service-city="<?= e($service['city']); ?>" data-service-state="<?= e($service['state']); ?>" data-service-location="<?= e($service['location'] ?? ''); ?>" data-service-has-whatsapp="<?= $service['has_whatsapp'] ? '1' : '0'; ?>">
                        <div class="p-6 flex flex-col gap-3 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                    <i class="fas fa-tag"></i>
                                    <?= e($service['category_label']); ?>
                                </span>
                                <?php if (!empty($service['whatsapp'])): ?>
                                    <span class="inline-flex items-center gap-2 text-sm text-emerald-600">
                                        <i class="fab fa-whatsapp"></i> Atendimento via WhatsApp
                                    </span>
                                <?php endif; ?>
                            </div>

                            <header>
                                <h3 class="text-xl font-semibold text-emerald-950">
                                    <?= e($service['name']); ?>
                                </h3>
                                <?php if (!empty($service['custom_category'])): ?>
                                    <p class="text-sm text-emerald-600">
                                        <?= e($service['custom_category']); ?>
                                    </p>
                                <?php endif; ?>
                            </header>

                            <?php if ($service['location']): ?>
                                <p class="text-sm text-emerald-600 flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= e($service['location']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($service['description'])): ?>
                                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border-l-4 border-emerald-400 rounded-r-lg p-3 my-2">
                                    <p class="text-sm text-emerald-900 font-medium line-clamp-3">
                                        <i class="fas fa-comment-dots text-emerald-500 mr-2"></i>
                                        <?php 
                                            $desc = trim((string)$service['description']);
                                            if (strlen($desc) > 150) {
                                                echo e(substr($desc, 0, 150)) . '...';
                                            } else {
                                                echo e($desc);
                                            }
                                        ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <?php if ($service['document_visible']): ?>
                                <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-emerald-500">
                                    <i class="fas fa-id-card"></i>
                                    <span><?= e($service['document_label']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($service['phone'] || $service['whatsapp_url'] || $service['email'] || $service['google_maps_url'] || $service['waze_url']): ?>
                                <div class="mt-4 grid gap-2 sm:grid-cols-2 service-buttons-grid">
                                    <?php if ($service['phone'] && $service['phone_dial']): ?>
                                        <a class="service-contact-button service-contact-button--call" href="tel:<?= e($service['phone_dial']); ?>">
                                            <span class="service-contact-button__icon"><i class="fas fa-phone"></i></span>
                                            <span class="service-contact-button__content">
                                                <strong>Ligar agora</strong>
                                                <span class="service-contact-button__hint"><?= e($service['phone']); ?></span>
                                            </span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($service['whatsapp_url']): ?>
                                        <a class="service-contact-button service-contact-button--whatsapp" href="<?= e($service['whatsapp_url']); ?>" target="_blank" rel="noopener">
                                            <span class="service-contact-button__icon"><i class="fab fa-whatsapp"></i></span>
                                            <span class="service-contact-button__content">
                                                <strong>Conversar no WhatsApp</strong>
                                                <?php if ($service['whatsapp']): ?>
                                                    <span class="service-contact-button__hint"><?= e($service['whatsapp']); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($service['email']): ?>
                                        <a class="service-contact-button service-contact-button--email" href="mailto:<?= e($service['email']); ?>">
                                            <span class="service-contact-button__icon"><i class="fas fa-envelope"></i></span>
                                            <span class="service-contact-button__content">
                                                <strong>Enviar e-mail</strong>
                                                <span class="service-contact-button__hint"><?= e($service['email']); ?></span>
                                            </span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($service['google_maps_url'] || $service['waze_url']): ?>
                                        <details class="service-route-selector sm:col-span-2">
                                            <summary class="service-contact-button service-contact-button--route">
                                                <span class="service-contact-button__icon"><i class="fas fa-route"></i></span>
                                                <span class="service-contact-button__content">
                                                    <strong>Traçar rota</strong>
                                                    <?php if ($service['route_query']): ?>
                                                        <span class="service-contact-button__hint"><?= e($service['route_query']); ?></span>
                                                    <?php else: ?>
                                                        <span class="service-contact-button__hint">Escolha o aplicativo</span>
                                                    <?php endif; ?>
                                                </span>
                                                <i class="fas fa-chevron-down service-route-selector__chevron"></i>
                                            </summary>
                                            <div class="service-route-options">
                                                <?php if ($service['google_maps_url']): ?>
                                                    <a href="<?= e($service['google_maps_url']); ?>" target="_blank" rel="noopener" class="service-route-option">
                                                        <i class="fab fa-google"></i> Google Maps
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($service['waze_url']): ?>
                                                    <a href="<?= e($service['waze_url']); ?>" target="_blank" rel="noopener" class="service-route-option">
                                                        <i class="fas fa-location-arrow"></i> Waze
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="px-6 py-4 bg-emerald-50 text-emerald-700 text-xs flex items-center justify-between">
                            <span>Publicado em <?= e($service['published_at']); ?></span>
                            <?php if (!empty($service['premium_badge'])): ?>
                                <span class="inline-flex items-center gap-1 text-amber-600 font-semibold">
                                    <i class="fas fa-star"></i> Anuncio Classi Livre
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="bg-white rounded-3xl shadow-xl p-10 text-center text-emerald-900 hidden" data-service-empty>
                <div class="w-16 h-16 mx-auto flex items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-4">
                    <i class="fas fa-info"></i>
                </div>
                <h3 class="text-xl font-semibold">Nenhum serviço corresponde aos filtros</h3>
                <p class="mt-2 text-emerald-600">Ajuste os filtros ou limpe a busca para ver outras opções.</p>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-12 flex items-center justify-center gap-2" aria-label="Paginação de serviços">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(build_page_url($queryParams ?? [], $page - 1)); ?>" class="px-4 py-2 rounded-full bg-white text-emerald-900 shadow hover:bg-emerald-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= e(build_page_url($queryParams ?? [], $p)); ?>" class="px-4 py-2 rounded-full <?= $p === $page ? 'bg-emerald-600 text-white shadow-lg' : 'bg-white text-emerald-900 shadow hover:bg-emerald-50'; ?>">
                            <?= $p; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e(build_page_url($queryParams ?? [], $page + 1)); ?>" class="px-4 py-2 rounded-full bg-white text-emerald-900 shadow hover:bg-emerald-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="container mx-auto px-4 grid gap-8 md:grid-cols-3">
        <div class="bg-emerald-950 text-white rounded-3xl p-6 shadow-xl">
            <i class="fas fa-shield-alt text-2xl text-amber-400"></i>
            <h3 class="text-xl font-semibold mt-4">Cuidados ao contratar</h3>
            <p class="mt-2 text-emerald-100 text-sm">Confira todas as informações diretamente com o prestador antes de fechar negócio. O Classi Livre Autos não se responsabiliza nem garante a veracidade dos anúncios publicados.</p>
        </div>
        <div class="bg-white border border-emerald-100 rounded-3xl p-6 shadow-lg">
            <i class="fas fa-clock text-2xl text-emerald-600"></i>
            <h3 class="text-xl font-semibold mt-4">Atendimento rápido</h3>
            <p class="mt-2 text-emerald-800 text-sm">Use o filtro de WhatsApp para encontrar quem responde imediatamente.</p>
        </div>
        <div class="bg-white border border-amber-100 rounded-3xl p-6 shadow-lg">
            <i class="fas fa-bullhorn text-2xl text-amber-500"></i>
            <h3 class="text-xl font-semibold mt-4">Divulgue seu negócio</h3>
            <p class="mt-2 text-emerald-800 text-sm">É vendedor? Acesse <a href="/anuncios/meus.php?tab=services" class="text-amber-600 underline hover:text-amber-700">Minha Conta</a> e cadastre seus serviços.</p>
        </div>
    </div>
</section>

<script type="application/json" id="service-city-suggestions-data">
<?= json_encode($citySuggestionsByState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<?php require __DIR__ . '/../propagandas/load_web.php'; ?>
<?php require __DIR__ . '/../propagandas/load_local.php'; ?>
<?php require __DIR__ . '/../propagandas/load_embed.php'; ?>
