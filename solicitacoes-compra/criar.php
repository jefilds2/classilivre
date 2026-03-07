<?php
// solicitacoes-compra/criar.php - Cadastro de nova solicitacao de compra.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../propagandas/analytics/tracker.php';
require_once __DIR__ . '/helpers.php';

require_login();
ensure_user_not_blocked();
ensure_verified_email();

AnalyticsTracker::trackPageLoad('solicitacoes_compra_criar');

$pdo = db();
if (!purchase_request_table_exists($pdo)) {
    flash_add(
        'error',
        'A tabela de solicitacoes de compra ainda nao foi criada. Rode a migration 011 antes de cadastrar.'
    );
    redirect('/solicitacoes-compra/');
}

$user = current_user();
$states = purchase_request_states();
$vehicleTypes = purchase_request_vehicle_types();
$transmissionOptions = purchase_request_transmission_options();
$fuelOptions = purchase_request_fuel_options();
$colorOptions = purchase_request_color_options();
$contactMethods = purchase_request_contact_methods();
$editingRequestId = max(0, (int) input_get('id', '0'));
$isEditMode = $editingRequestId > 0;
$pageTitle = $isEditMode
    ? 'Editar Solicitacao de Compra | Classi Livre Autos'
    : 'Cadastrar Solicitacao de Compra | Classi Livre Autos';

$currentYear = (int) date('Y');
$errors = [];

$defaultContact = purchase_request_format_phone((string) ($user['phone'] ?? '')) ?? '';
$formData = [
    'title' => '',
    'vehicle_type' => 'carro',
    'brand' => '',
    'model' => '',
    'min_year' => '',
    'max_year' => '',
    'transmission_preference' => '',
    'fuel_preference' => '',
    'color_preference' => '',
    'max_mileage' => '',
    'budget_max' => '',
    'city' => '',
    'state' => 'MG',
    'details' => '',
    'contact_phone' => $defaultContact,
    'contact_whatsapp' => $defaultContact,
    'contact_method' => 'whatsapp',
];

$existingRequest = null;
if ($isEditMode) {
    $existingStmt = $pdo->prepare('
        SELECT *
        FROM purchase_requests
        WHERE id = :id
          AND user_id = :user_id
        LIMIT 1
    ');
    $existingStmt->execute([
        ':id' => $editingRequestId,
        ':user_id' => (int) $user['id'],
    ]);
    $existingRequest = $existingStmt->fetch();

    if (!$existingRequest) {
        flash_add('error', 'Solicitacao de compra nao encontrada para edicao.');
        redirect('/solicitacoes-compra/minhas.php');
    }

    if ((string) ($existingRequest['status'] ?? '') !== 'active') {
        flash_add('error', 'Apenas solicitacoes ativas podem ser editadas.');
        redirect('/solicitacoes-compra/minhas.php');
    }

    $formData = [
        'title' => (string) ($existingRequest['title'] ?? ''),
        'vehicle_type' => (string) ($existingRequest['vehicle_type'] ?? 'carro'),
        'brand' => (string) ($existingRequest['brand'] ?? ''),
        'model' => (string) ($existingRequest['model'] ?? ''),
        'min_year' => !empty($existingRequest['min_year']) ? (string) $existingRequest['min_year'] : '',
        'max_year' => !empty($existingRequest['max_year']) ? (string) $existingRequest['max_year'] : '',
        'transmission_preference' => (string) ($existingRequest['transmission_preference'] ?? ''),
        'fuel_preference' => (string) ($existingRequest['fuel_preference'] ?? ''),
        'color_preference' => (string) ($existingRequest['color_preference'] ?? ''),
        'max_mileage' => !empty($existingRequest['max_mileage']) ? (string) $existingRequest['max_mileage'] : '',
        'budget_max' => isset($existingRequest['budget_max']) && $existingRequest['budget_max'] !== null
            ? number_format((float) $existingRequest['budget_max'], 2, ',', '.')
            : '',
        'city' => (string) ($existingRequest['city'] ?? ''),
        'state' => (string) ($existingRequest['state'] ?? 'MG'),
        'details' => (string) ($existingRequest['details'] ?? ''),
        'contact_phone' => purchase_request_format_phone((string) ($existingRequest['contact_phone'] ?? '')) ?? '',
        'contact_whatsapp' => purchase_request_format_phone((string) ($existingRequest['contact_whatsapp'] ?? '')) ?? '',
        'contact_method' => (string) ($existingRequest['contact_method'] ?? 'whatsapp'),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalido. Atualize a pagina e tente novamente.';
    }

    if ($isEditMode) {
        $postedRequestId = (int) ($_POST['request_id'] ?? 0);
        if ($postedRequestId !== $editingRequestId) {
            $errors[] = 'Solicitacao invalida para edicao.';
        }
    }

    $sanitize = static function (string $key): string {
        return trim((string) ($_POST[$key] ?? ''));
    };

    foreach ($formData as $field => $_value) {
        $formData[$field] = $sanitize($field);
    }

    if (!array_key_exists($formData['vehicle_type'], $vehicleTypes)) {
        $formData['vehicle_type'] = 'carro';
    }

    if (!array_key_exists(strtoupper($formData['state']), $states)) {
        $errors[] = 'Selecione um estado valido.';
    } else {
        $formData['state'] = strtoupper($formData['state']);
    }

    if (strlen($formData['title']) < 8) {
        $errors[] = 'O titulo precisa ter pelo menos 8 caracteres.';
    }

    if (strlen($formData['city']) < 2) {
        $errors[] = 'Informe a cidade onde deseja comprar o veiculo.';
    }

    if (strlen($formData['details']) < 15) {
        $errors[] = 'Descreva com mais detalhes o veiculo desejado (minimo de 15 caracteres).';
    }

    $minYearValue = null;
    if ($formData['min_year'] !== '') {
        $minYearValue = (int) preg_replace('/\D+/', '', $formData['min_year']);
        if ($minYearValue < 1950 || $minYearValue > $currentYear + 1) {
            $errors[] = 'Ano minimo invalido.';
        }
    }

    $maxYearValue = null;
    if ($formData['max_year'] !== '') {
        $maxYearValue = (int) preg_replace('/\D+/', '', $formData['max_year']);
        if ($maxYearValue < 1950 || $maxYearValue > $currentYear + 1) {
            $errors[] = 'Ano maximo invalido.';
        }
    }

    if ($minYearValue !== null && $maxYearValue !== null && $minYearValue > $maxYearValue) {
        $errors[] = 'Ano minimo nao pode ser maior que o ano maximo.';
    }

    if ($formData['transmission_preference'] !== '' && !in_array($formData['transmission_preference'], $transmissionOptions, true)) {
        $errors[] = 'Opcao de cambio invalida.';
    }

    if ($formData['fuel_preference'] !== '' && !in_array($formData['fuel_preference'], $fuelOptions, true)) {
        $errors[] = 'Opcao de combustivel invalida.';
    }

    if ($formData['color_preference'] !== '' && !in_array($formData['color_preference'], $colorOptions, true)) {
        $errors[] = 'Opcao de cor invalida.';
    }

    $maxMileageValue = null;
    if ($formData['max_mileage'] !== '') {
        $maxMileageValue = (int) preg_replace('/\D+/', '', $formData['max_mileage']);
        if ($maxMileageValue <= 0) {
            $errors[] = 'Quilometragem maxima precisa ser maior que zero.';
        }
    }

    $budgetMaxValue = purchase_request_parse_decimal($formData['budget_max']);
    if ($formData['budget_max'] !== '' && $budgetMaxValue === null) {
        $errors[] = 'Informe um valor maximo valido.';
    }

    if (!array_key_exists($formData['contact_method'], $contactMethods)) {
        $formData['contact_method'] = 'whatsapp';
    }

    $phoneDigits = purchase_request_sanitize_phone_digits($formData['contact_phone']);
    $whatsappDigits = purchase_request_sanitize_phone_digits($formData['contact_whatsapp']);

    if (in_array($formData['contact_method'], ['phone', 'both'], true) && strlen($phoneDigits) < 10) {
        $errors[] = 'Informe um telefone valido para ligacao.';
    }
    if (in_array($formData['contact_method'], ['whatsapp', 'both'], true) && strlen($whatsappDigits) < 10) {
        $errors[] = 'Informe um telefone valido para WhatsApp.';
    }

    if (!$errors) {
        $now = now();
        $params = [
            ':title' => $formData['title'],
            ':vehicle_type' => $formData['vehicle_type'],
            ':brand' => purchase_request_trim_or_null($formData['brand']),
            ':model' => purchase_request_trim_or_null($formData['model']),
            ':min_year' => $minYearValue,
            ':max_year' => $maxYearValue,
            ':transmission_preference' => purchase_request_trim_or_null($formData['transmission_preference']),
            ':fuel_preference' => purchase_request_trim_or_null($formData['fuel_preference']),
            ':color_preference' => purchase_request_trim_or_null($formData['color_preference']),
            ':max_mileage' => $maxMileageValue,
            ':budget_max' => $budgetMaxValue,
            ':city' => $formData['city'],
            ':state' => $formData['state'],
            ':details' => $formData['details'],
            ':contact_phone' => $phoneDigits !== '' ? $phoneDigits : null,
            ':contact_whatsapp' => $whatsappDigits !== '' ? $whatsappDigits : null,
            ':contact_method' => $formData['contact_method'],
            ':updated_at' => $now,
        ];

        if ($isEditMode) {
            $update = $pdo->prepare('
                UPDATE purchase_requests
                SET
                    title = :title,
                    vehicle_type = :vehicle_type,
                    brand = :brand,
                    model = :model,
                    min_year = :min_year,
                    max_year = :max_year,
                    transmission_preference = :transmission_preference,
                    fuel_preference = :fuel_preference,
                    color_preference = :color_preference,
                    max_mileage = :max_mileage,
                    budget_max = :budget_max,
                    city = :city,
                    state = :state,
                    details = :details,
                    contact_phone = :contact_phone,
                    contact_whatsapp = :contact_whatsapp,
                    contact_method = :contact_method,
                    updated_at = :updated_at
                WHERE id = :id
                  AND user_id = :user_id
                  AND status = "active"
                LIMIT 1
            ');
            $params[':id'] = $editingRequestId;
            $params[':user_id'] = (int) $user['id'];
            $update->execute($params);

            if ($update->rowCount() === 0) {
                $errors[] = 'Nao foi possivel atualizar a solicitacao. Tente novamente.';
            } else {
                record_audit((int) $user['id'], 'purchase_request_updated', 'purchase_requests', $editingRequestId, [
                    'title' => $formData['title'],
                    'city' => $formData['city'],
                    'state' => $formData['state'],
                ]);
                flash_add('success', 'Solicitacao atualizada com sucesso.');
                redirect('/solicitacoes-compra/minhas.php');
            }
        } else {
            $insert = $pdo->prepare('
                INSERT INTO purchase_requests (
                    user_id,
                    title,
                    vehicle_type,
                    brand,
                    model,
                    min_year,
                    max_year,
                    transmission_preference,
                    fuel_preference,
                    color_preference,
                    max_mileage,
                    budget_max,
                    city,
                    state,
                    details,
                    contact_phone,
                    contact_whatsapp,
                    contact_method,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :title,
                    :vehicle_type,
                    :brand,
                    :model,
                    :min_year,
                    :max_year,
                    :transmission_preference,
                    :fuel_preference,
                    :color_preference,
                    :max_mileage,
                    :budget_max,
                    :city,
                    :state,
                    :details,
                    :contact_phone,
                    :contact_whatsapp,
                    :contact_method,
                    "active",
                    :created_at,
                    :updated_at
                )
            ');
            $insertParams = $params;
            $insertParams[':user_id'] = (int) $user['id'];
            $insertParams[':created_at'] = $now;
            $insert->execute($insertParams);

            $requestId = (int) $pdo->lastInsertId();
            record_audit((int) $user['id'], 'purchase_request_created', 'purchase_requests', $requestId, [
                'title' => $formData['title'],
                'city' => $formData['city'],
                'state' => $formData['state'],
            ]);

            flash_add('success', 'Solicitacao de compra publicada com sucesso.');
            redirect('/solicitacoes-compra/');
        }
    }
}

require __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-gradient-to-b from-amber-50 to-orange-100">
    <div class="max-w-screen-2xl mx-auto px-4">
        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg border border-orange-100 overflow-hidden">
            <div class="px-5 md:px-8 py-6 bg-red-600 text-white">
                <h1 class="text-2xl md:text-3xl font-bold">
                    <?= $isEditMode ? 'Editar solicitacao de compra' : 'Cadastrar solicitacao de compra'; ?>
                </h1>
                <p class="mt-2 text-red-50">
                    <?= $isEditMode
                        ? 'Atualize as informacoes da sua solicitacao para continuar recebendo contatos qualificados.'
                        : 'Preencha os dados para receber contatos de vendedores com o veiculo que voce procura.'; ?>
                </p>
            </div>

            <div class="px-5 md:px-8 py-6">
                <?php if ($errors): ?>
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                        <ul class="list-disc pl-5 space-y-1 text-sm">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <?php if ($isEditMode): ?>
                        <input type="hidden" name="request_id" value="<?= (int) $editingRequestId; ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="title">Titulo da solicitacao *</label>
                        <input id="title" name="title" required value="<?= e($formData['title']); ?>"
                            placeholder="Ex: Compro Ford Ka 2020 automatico"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="vehicle_type">Tipo de veiculo</label>
                            <select id="vehicle_type" name="vehicle_type"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                <?php foreach ($vehicleTypes as $typeKey => $typeLabel): ?>
                                    <option value="<?= e($typeKey); ?>" <?= $formData['vehicle_type'] === $typeKey ? 'selected' : ''; ?>>
                                        <?= e($typeLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="brand">Marca</label>
                            <input id="brand" name="brand" value="<?= e($formData['brand']); ?>"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="model">Modelo</label>
                            <input id="model" name="model" value="<?= e($formData['model']); ?>"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="min_year">Ano minimo</label>
                            <input id="min_year" name="min_year" inputmode="numeric" value="<?= e($formData['min_year']); ?>"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="max_year">Ano maximo</label>
                            <input id="max_year" name="max_year" inputmode="numeric" value="<?= e($formData['max_year']); ?>"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="max_mileage">Km maxima</label>
                            <input id="max_mileage" name="max_mileage" inputmode="numeric"
                                value="<?= e($formData['max_mileage']); ?>" placeholder="Ex: 70000"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="budget_max">Valor maximo</label>
                            <input id="budget_max" name="budget_max" value="<?= e($formData['budget_max']); ?>"
                                placeholder="Ex: 55000,00"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="transmission_preference">Cambio</label>
                            <select id="transmission_preference" name="transmission_preference"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                <option value="">Indiferente</option>
                                <?php foreach ($transmissionOptions as $option): ?>
                                    <option value="<?= e($option); ?>" <?= $formData['transmission_preference'] === $option ? 'selected' : ''; ?>>
                                        <?= e($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="fuel_preference">Combustivel</label>
                            <select id="fuel_preference" name="fuel_preference"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                <option value="">Indiferente</option>
                                <?php foreach ($fuelOptions as $option): ?>
                                    <option value="<?= e($option); ?>" <?= $formData['fuel_preference'] === $option ? 'selected' : ''; ?>>
                                        <?= e($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="color_preference">Cor preferida</label>
                            <select id="color_preference" name="color_preference"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                <option value="">Indiferente</option>
                                <?php foreach ($colorOptions as $option): ?>
                                    <option value="<?= e($option); ?>" <?= $formData['color_preference'] === $option ? 'selected' : ''; ?>>
                                        <?= e($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="city">Cidade *</label>
                            <input id="city" name="city" required value="<?= e($formData['city']); ?>"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1" for="state">Estado *</label>
                            <select id="state" name="state" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                <?php foreach ($states as $uf => $stateName): ?>
                                    <option value="<?= e($uf); ?>" <?= $formData['state'] === $uf ? 'selected' : ''; ?>>
                                        <?= e($uf); ?> - <?= e($stateName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="details">Detalhes do que voce busca *</label>
                        <textarea id="details" name="details" required rows="5"
                            placeholder="Ex: carro sem passagem por leilao, revisoes em dia, preferencia por unico dono, etc."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400"><?= e($formData['details']); ?></textarea>
                    </div>

                    <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 space-y-4">
                        <h2 class="text-base font-semibold text-orange-800">Como os vendedores vao falar com voce?</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1" for="contact_whatsapp">WhatsApp</label>
                                <input id="contact_whatsapp" name="contact_whatsapp" data-mask="phone"
                                    value="<?= e($formData['contact_whatsapp']); ?>"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1" for="contact_phone">Telefone para ligacao</label>
                                <input id="contact_phone" name="contact_phone" data-mask="phone"
                                    value="<?= e($formData['contact_phone']); ?>"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
                            </div>
                        </div>

                        <fieldset>
                            <legend class="text-sm font-semibold text-gray-700 mb-2">Canal principal de contato</legend>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <?php foreach ($contactMethods as $methodKey => $methodLabel): ?>
                                    <label
                                        class="flex items-center gap-2 rounded-lg border border-orange-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <input type="radio" name="contact_method" value="<?= e($methodKey); ?>"
                                            <?= $formData['contact_method'] === $methodKey ? 'checked' : ''; ?>>
                                        <span><?= e($methodLabel); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 sm:justify-between sm:items-center pt-2">
                        <a href="<?= $isEditMode ? '/solicitacoes-compra/minhas.php' : '/solicitacoes-compra/'; ?>"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                            <?= $isEditMode ? 'Voltar para minhas solicitacoes' : 'Voltar para solicitacoes'; ?>
                        </a>
                        <button type="submit"
                            class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold">
                            <i class="fas fa-paper-plane mr-2"></i>
                            <?= $isEditMode ? 'Salvar alteracoes' : 'Publicar solicitacao'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../propagandas/load_web.php'; ?>
<?php require __DIR__ . '/../propagandas/load_local.php'; ?>
<?php require __DIR__ . '/../propagandas/load_embed.php'; ?>
