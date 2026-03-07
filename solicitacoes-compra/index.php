<?php
// solicitacoes-compra/index.php - Vitrine publica de solicitacoes de compra.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../propagandas/analytics/tracker.php';
require_once __DIR__ . '/helpers.php';

AnalyticsTracker::trackPageLoad('solicitacoes_compra');

$user = current_user();
$pdo = db();
$pageTitle = 'Solicitacoes de Compra | Classi Livre Autos';
$states = purchase_request_states();
$vehicleTypes = purchase_request_vehicle_types();

$searchTerm = trim((string) input_get('q', ''));
$cityFilter = trim((string) input_get('city', ''));
$stateFilter = strtoupper(trim((string) input_get('state', '')));

if ($stateFilter !== '' && !array_key_exists($stateFilter, $states)) {
    $stateFilter = '';
}

$page = max(1, (int) input_get('page', '1'));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$tableExists = purchase_request_table_exists($pdo);
$requests = [];
$totalRequests = 0;
$totalPages = 1;
$queryParams = $_GET;
$activeFilters = [];

if ($searchTerm !== '') {
    $activeFilters[] = [
        'label' => 'Busca: ' . $searchTerm,
        'url' => purchase_request_remove_filter_url($queryParams, ['q']),
    ];
}
if ($cityFilter !== '') {
    $activeFilters[] = [
        'label' => 'Cidade: ' . $cityFilter,
        'url' => purchase_request_remove_filter_url($queryParams, ['city']),
    ];
}
if ($stateFilter !== '') {
    $activeFilters[] = [
        'label' => 'Estado: ' . $stateFilter,
        'url' => purchase_request_remove_filter_url($queryParams, ['state']),
    ];
}

if ($tableExists) {
    $whereParts = ['pr.status = "active"'];
    $params = [];

    if ($searchTerm !== '') {
        $whereParts[] = '(
            pr.title LIKE :search_title
            OR pr.brand LIKE :search_brand
            OR pr.model LIKE :search_model
            OR pr.details LIKE :search_details
            OR pr.city LIKE :search_city
        )';
        $searchValue = '%' . $searchTerm . '%';
        $params[':search_title'] = $searchValue;
        $params[':search_brand'] = $searchValue;
        $params[':search_model'] = $searchValue;
        $params[':search_details'] = $searchValue;
        $params[':search_city'] = $searchValue;
    }

    if ($cityFilter !== '') {
        $whereParts[] = 'pr.city LIKE :city';
        $params[':city'] = '%' . $cityFilter . '%';
    }

    if ($stateFilter !== '') {
        $whereParts[] = 'pr.state = :state';
        $params[':state'] = $stateFilter;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_requests pr $whereSql");
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRequests = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalRequests / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $listSql = "
        SELECT
            pr.*,
            u.name AS owner_name,
            u.phone AS owner_phone,
            u.is_premium AS owner_is_premium
        FROM purchase_requests pr
        JOIN users u ON u.id = pr.user_id
        $whereSql
        ORDER BY pr.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $listStmt = $pdo->prepare($listSql);
    foreach ($params as $key => $value) {
        $listStmt->bindValue($key, $value);
    }
    $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStmt->execute();
    $rows = $listStmt->fetchAll();

    foreach ($rows as $row) {
        $vehicleTypeKey = (string) ($row['vehicle_type'] ?? 'outros');
        $vehicleLabel = $vehicleTypes[$vehicleTypeKey] ?? ucfirst($vehicleTypeKey);

        $yearRange = null;
        $minYear = (int) ($row['min_year'] ?? 0);
        $maxYear = (int) ($row['max_year'] ?? 0);
        if ($minYear > 0 && $maxYear > 0) {
            $yearRange = $minYear === $maxYear ? (string) $minYear : ($minYear . ' a ' . $maxYear);
        } elseif ($minYear > 0) {
            $yearRange = 'A partir de ' . $minYear;
        } elseif ($maxYear > 0) {
            $yearRange = 'Ate ' . $maxYear;
        }

        $contactPhoneRaw = purchase_request_trim_or_null((string) ($row['contact_phone'] ?? ''))
            ?? purchase_request_trim_or_null((string) ($row['owner_phone'] ?? ''));
        $contactWhatsappRaw = purchase_request_trim_or_null((string) ($row['contact_whatsapp'] ?? ''))
            ?? $contactPhoneRaw;

        $contactMethod = (string) ($row['contact_method'] ?? 'whatsapp');
        $allowWhatsapp = in_array($contactMethod, ['whatsapp', 'both'], true);
        $allowPhone = in_array($contactMethod, ['phone', 'both'], true);

        $whatsUrl = $allowWhatsapp
            ? purchase_request_whatsapp_link(
                $contactWhatsappRaw,
                'Ola! Vi sua solicitacao de compra no Classi Livre e tenho um veiculo para oferecer.'
            )
            : null;
        $phoneDial = $allowPhone ? purchase_request_phone_dial_link($contactPhoneRaw) : null;

        $details = trim((string) ($row['details'] ?? ''));
        if (strlen($details) > 220) {
            $details = substr($details, 0, 220) . '...';
        }

        $requests[] = [
            'title' => (string) ($row['title'] ?? 'Solicitacao de compra'),
            'vehicle_type' => $vehicleLabel,
            'brand' => purchase_request_trim_or_null((string) ($row['brand'] ?? '')),
            'model' => purchase_request_trim_or_null((string) ($row['model'] ?? '')),
            'year_range' => $yearRange,
            'transmission' => purchase_request_trim_or_null((string) ($row['transmission_preference'] ?? '')),
            'fuel' => purchase_request_trim_or_null((string) ($row['fuel_preference'] ?? '')),
            'color' => purchase_request_trim_or_null((string) ($row['color_preference'] ?? '')),
            'max_mileage' => !empty($row['max_mileage']) ? number_format((int) $row['max_mileage'], 0, ',', '.') . ' km' : null,
            'budget_max' => isset($row['budget_max']) && $row['budget_max'] !== null
                ? format_currency((float) $row['budget_max'])
                : null,
            'city' => (string) ($row['city'] ?? ''),
            'state' => (string) ($row['state'] ?? ''),
            'details' => $details,
            'owner_name' => (string) ($row['owner_name'] ?? ''),
            'is_verified' => (int) ($row['owner_is_premium'] ?? 0) === 1,
            'phone_label' => purchase_request_format_phone($contactPhoneRaw),
            'whatsapp_label' => purchase_request_format_phone($contactWhatsappRaw),
            'phone_dial' => $phoneDial,
            'whatsapp_url' => $whatsUrl,
            'created_at' => format_date((string) ($row['created_at'] ?? '')),
        ];
    }
}

$resultsLabel = $totalRequests === 1 ? '1 solicitacao encontrada' : ($totalRequests . ' solicitacoes encontradas');
$createRequestUrl = $user
    ? '/solicitacoes-compra/criar.php'
    : '/login_system/login.php?redirect=' . rawurlencode('/solicitacoes-compra/criar.php');

require __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-gradient-to-b from-red-700 via-orange-600 to-amber-500 text-white">
    <div class="max-w-screen-2xl mx-auto px-4">
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 text-sm font-semibold">
            <i class="fas fa-bullseye mr-2"></i> Mural de compradores
        </span>
        <h1 class="text-3xl md:text-4xl font-bold mt-4">Solicitacoes de compra de veiculos</h1>
        <p class="mt-3 text-orange-50 max-w-3xl">
            Veja o que os compradores estao procurando e entre em contato direto por WhatsApp ou ligacao.
        </p>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="<?= e($createRequestUrl); ?>"
                class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-red-700 hover:bg-red-800 text-white font-semibold shadow-lg transition-colors">
                <i class="fas fa-plus-circle mr-2"></i> Cadastrar solicitacao de compra
            </a>
            <?php if ($user): ?>
                <a href="/solicitacoes-compra/minhas.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-orange-100 text-orange-800 font-semibold shadow transition-colors hover:bg-orange-200">
                    <i class="fas fa-pen-to-square mr-2"></i> Minhas solicitacoes
                </a>
            <?php endif; ?>
            <?php if (!empty($user['is_admin'])): ?>
                <a href="/admin/solicitacoes_compra.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-gray-900 text-white font-semibold shadow transition-colors hover:bg-black">
                    <i class="fas fa-chart-column mr-2"></i> Dashboard solicitacoes
                </a>
            <?php endif; ?>
            <a href="/anuncios/listar.php"
                class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-white text-red-700 font-semibold shadow transition-colors hover:bg-red-50">
                <i class="fas fa-car mr-2"></i> Ver anuncios disponiveis
            </a>
        </div>
    </div>
</section>

<section class="py-8 bg-gray-50">
    <div class="max-w-screen-2xl mx-auto px-4">
        <form method="get" action="/solicitacoes-compra/" class="bg-white rounded-2xl shadow p-4 md:p-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="q">Buscar comprador</label>
                    <input id="q" name="q" value="<?= e($searchTerm); ?>"
                        placeholder="Ex: Ford Ka automatico, SUV ate 70 mil..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="city">Cidade</label>
                    <input id="city" name="city" value="<?= e($cityFilter); ?>" placeholder="Ex: Belo Horizonte"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="state">Estado</label>
                    <select id="state" name="state"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <option value="">Todos</option>
                        <?php foreach ($states as $uf => $stateName): ?>
                            <option value="<?= e($uf); ?>" <?= $stateFilter === $uf ? 'selected' : ''; ?>>
                                <?= e($uf); ?> - <?= e($stateName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
                <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-semibold transition-colors">
                    <i class="fas fa-search mr-2"></i> Filtrar solicitacoes
                </button>
                <span class="text-sm text-gray-600"><?= e($resultsLabel); ?></span>
            </div>
        </form>

        <?php if ($activeFilters): ?>
            <div class="mt-4 flex flex-wrap gap-2">
                <?php foreach ($activeFilters as $filter): ?>
                    <a href="<?= e($filter['url']); ?>"
                        class="inline-flex items-center gap-2 bg-orange-100 text-orange-800 px-3 py-1.5 rounded-full text-xs font-semibold">
                        <?= e($filter['label']); ?>
                        <i class="fas fa-times"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="pb-12 bg-gray-50">
    <div class="max-w-screen-2xl mx-auto px-4">
        <?php if (!$tableExists): ?>
            <div class="bg-white border border-red-200 rounded-2xl p-6 text-red-700">
                <h2 class="text-lg font-semibold mb-2">Tabela de solicitacoes ainda nao existe</h2>
                <p class="text-sm">
                    Execute a migration <code>migrations/011_create_purchase_requests.php</code> (ou o SQL equivalente)
                    no Docker antes de subir para a hospedagem.
                </p>
            </div>
        <?php elseif (!$requests): ?>
            <div class="bg-white rounded-2xl shadow p-10 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mb-4">
                    <i class="fas fa-inbox text-xl"></i>
                </div>
                <h2 class="text-xl font-semibold text-gray-800">Nenhuma solicitacao ativa por enquanto</h2>
                <p class="text-gray-600 mt-2">Seja o primeiro a publicar o veiculo que esta procurando.</p>
                <a href="<?= e($createRequestUrl); ?>"
                    class="inline-flex items-center justify-center mt-6 px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                    <i class="fas fa-plus-circle mr-2"></i> Criar solicitacao
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <?php foreach ($requests as $request): ?>
                    <article class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 flex flex-col">
                        <div class="p-5 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-lg font-bold text-gray-800 leading-tight">
                                    <?= e($request['title']); ?>
                                </h2>
                                <?php if ($request['is_verified']): ?>
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-700 font-semibold">
                                        <i class="fas fa-check-circle mr-1"></i> Verificado
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="px-2.5 py-1 rounded-full bg-orange-100 text-orange-800 font-semibold">
                                    <?= e($request['vehicle_type']); ?>
                                </span>
                                <?php if ($request['brand']): ?>
                                    <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700"><?= e($request['brand']); ?></span>
                                <?php endif; ?>
                                <?php if ($request['model']): ?>
                                    <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700"><?= e($request['model']); ?></span>
                                <?php endif; ?>
                                <?php if ($request['year_range']): ?>
                                    <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700"><?= e($request['year_range']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 space-y-1 text-sm text-gray-600">
                                <?php if ($request['transmission']): ?>
                                    <p><strong>Cambio:</strong> <?= e($request['transmission']); ?></p>
                                <?php endif; ?>
                                <?php if ($request['fuel']): ?>
                                    <p><strong>Combustivel:</strong> <?= e($request['fuel']); ?></p>
                                <?php endif; ?>
                                <?php if ($request['color']): ?>
                                    <p><strong>Cor:</strong> <?= e($request['color']); ?></p>
                                <?php endif; ?>
                                <?php if ($request['max_mileage']): ?>
                                    <p><strong>Km maxima:</strong> <?= e($request['max_mileage']); ?></p>
                                <?php endif; ?>
                                <?php if ($request['budget_max']): ?>
                                    <p><strong>Faixa de valor:</strong> ate <?= e($request['budget_max']); ?></p>
                                <?php endif; ?>
                            </div>

                            <?php if ($request['details']): ?>
                                <p class="mt-4 text-sm text-gray-700 bg-gray-50 rounded-lg p-3">
                                    <?= e($request['details']); ?>
                                </p>
                            <?php endif; ?>

                            <div class="mt-4 text-sm text-gray-700 space-y-1">
                                <p class="flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-orange-500"></i>
                                    <?= e($request['city']); ?>, <?= e($request['state']); ?>
                                </p>
                                <p class="flex items-center">
                                    <i class="fas fa-user mr-2 text-orange-500"></i>
                                    <?= e($request['owner_name']); ?>
                                </p>
                                <p class="text-xs text-gray-500">Publicado em <?= e($request['created_at']); ?></p>
                            </div>
                        </div>
                        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <?php if ($request['whatsapp_url']): ?>
                                <a href="<?= e($request['whatsapp_url']); ?>" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm">
                                    <i class="fab fa-whatsapp mr-2"></i>
                                    <?= e($request['whatsapp_label'] ?: 'WhatsApp'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($request['phone_dial']): ?>
                                <a href="tel:<?= e($request['phone_dial']); ?>"
                                    class="inline-flex items-center justify-center rounded-lg px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold text-sm">
                                    <i class="fas fa-phone mr-2"></i>
                                    <?= e($request['phone_label'] ?: 'Ligar'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!$request['phone_dial'] && !$request['whatsapp_url']): ?>
                                <span class="sm:col-span-2 text-center text-xs text-gray-500 py-2">
                                    Contato indisponivel no momento.
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-8 flex items-center justify-center gap-2" aria-label="Paginacao solicitacoes">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(purchase_request_page_url($queryParams, $page - 1)); ?>"
                            class="px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= e(purchase_request_page_url($queryParams, $p)); ?>"
                            class="px-4 py-2 rounded-lg border <?= $p === $page
                                ? 'bg-orange-500 border-orange-500 text-white'
                                : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'; ?>">
                            <?= $p; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e(purchase_request_page_url($queryParams, $page + 1)); ?>"
                            class="px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../propagandas/load_web.php'; ?>
<?php require __DIR__ . '/../propagandas/load_local.php'; ?>
<?php require __DIR__ . '/../propagandas/load_embed.php'; ?>
