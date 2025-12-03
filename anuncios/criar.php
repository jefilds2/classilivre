<?php
// anuncios/criar.php - Fluxo guiado para criação de novos anúncios com etapas, resumo e autosave.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/upload_handler.php';

require_login();
ensure_user_not_blocked();
ensure_verified_email();

$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

function create_ad_log(string $message): void
{
    global $logDir;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($logDir . '/create-ad.log', $line, FILE_APPEND);
}

$user = current_user();
$isPremium = user_is_premium($user);
$pageTitle = 'Criar anúncio - Site Carros';
$errors = [];

// ✅ Obter saldo de moedas do usuário (com fallback seguro)
$userCoinBalance = 0;
if (isset($user['id']) && $user['id']) {
    $userCoinBalance = (int)get_user_coin_balance((int)$user['id']);
}

// ✅ Configuração de Anúncios Veiculares Gratuitos (não confundir com serviços automotivos)
$adsFreeForAll = (int)get_setting('ads_free_for_all', '0');

$vehicleTypes = [
    'carro' => 'Carro',
    'moto' => 'Moto',
    'caminhao' => 'Caminhão',
    'outros' => 'Outros',
];

$colorOptions = [
    'Preto', 'Branco', 'Prata', 'Cinza', 'Vermelho', 'Azul',
    'Verde', 'Marrom', 'Amarelo', 'Laranja', 'Bege', 'Roxo', 'Outra',
];

$fuelOptions = ['Gasolina', 'Etanol', 'Flex', 'Diesel', 'Elétrico', 'Híbrido', 'GNV'];
$doorOptions = ['2', '3', '4', '5'];
$plateDigits = range(0, 9);
$minPhotosRequired = 3;

$motoStyles = ['Street', 'Custom', 'Esportiva', 'Trail', 'Big Trail', 'Naked', 'Scooter', 'Off-road', 'Speed', 'Touring', 'Crossover'];
$motoCilindradas = ['50cc', '125cc', '150cc', '160cc', '200cc', '250cc', '300cc', '400cc', '500cc', '600cc', '750cc', '900cc', '1000cc', '1200cc', '1300cc', '1800cc'];

$truckTracao = ['4x2 (Toco)', '6x2 (Truck)', '6x4 (Cavalo)', '4x4 (Fora de estrada)', '6x6 (Fora de estrada)', '8x2', '8x4', 'Bitrem', 'Rodotrem', 'Treminhão'];
$truckCarroceria = ['Baú', 'Baú Frigorífico', 'Sider', 'Grade Baixa', 'Carga Seca', 'Tanque', 'Graneleiro', 'Prancha', 'Munk', 'Cegonha', 'Guincho', 'Caçamba', 'Basculante', 'Porta Container'];

$otherTypes = ['Carroceria', 'Carretinha', 'Reboque', 'Buggy', 'Quadriciclo', 'Trator', 'Máquina Agrícola', 'Ônibus', 'Micro-ônibus', 'Van', 'Utilitário', 'Ambulância', 'Caminhão pipa', 'Betoneira'];

$defaultData = [
    'vehicle_type' => 'carro',
    'brand' => '',
    'model' => '',
    'manufacture_year' => '',
    'model_year' => '',
    'mileage' => '',
    'color' => '',
    'transmission' => '',
    'fuel_type' => '',
    'doors' => '',
    'plate_last_digit' => '',
    'city' => '',
    'state' => 'MG',
    'price' => '',
    'title' => '',
    'description' => '',
    'moto_style' => '',
    'moto_cilindradas' => '',
    'truck_tracao' => '',
    'truck_carroceria' => '',
    'other_type' => '',
    'other_details' => '',
    'video_source' => 'upload',
    'video_instagram_link' => '',
];

$formData = $defaultData;
$minYear = 1950;
$maxYear = (int)date('Y') + 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido. Atualize a página e tente novamente.';
    }

    // Validar se usuário está bloqueado
    if ($user['status'] === 'blocked') {
        $errors[] = 'Sua conta está bloqueada. Você não pode publicar anúncios.';
    }

    $sanitize = static function (string $key) {
        return trim((string)($_POST[$key] ?? ''));
    };

    $vehicleType = strtolower($sanitize('vehicle_type'));
    if (!array_key_exists($vehicleType, $vehicleTypes)) {
        $vehicleType = 'carro';
    }

    $formData['vehicle_type'] = $vehicleType;
    $formData['brand'] = $sanitize('brand');
    $formData['model'] = $sanitize('model');
    $formData['manufacture_year'] = $sanitize('manufacture_year');
    $formData['model_year'] = $sanitize('model_year');
    $formData['mileage'] = $sanitize('mileage');
    $formData['color'] = $sanitize('color');
    $formData['transmission'] = $sanitize('transmission');
    $formData['fuel_type'] = $sanitize('fuel_type');
    $formData['doors'] = $sanitize('doors');
    $formData['plate_last_digit'] = $sanitize('plate_last_digit');
    $formData['city'] = $sanitize('city');
    $formData['state'] = 'MG';
    $formData['title'] = $sanitize('title');
    $formData['description'] = $sanitize('description');
    $formData['moto_style'] = $sanitize('moto_style');
    $formData['moto_cilindradas'] = $sanitize('moto_cilindradas');
    $formData['truck_tracao'] = $sanitize('truck_tracao');
    $formData['truck_carroceria'] = $sanitize('truck_carroceria');
    $formData['other_type'] = $sanitize('other_type');
    $formData['other_details'] = $sanitize('other_details');

    $priceNumeric = str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $_POST['price'] ?? '0'));
    $formData['price'] = $priceNumeric !== '' ? (float)$priceNumeric : 0.0;

    if ($formData['brand'] === '' || mb_strlen($formData['brand']) < 2) {
        $errors[] = 'Informe a marca do veículo.';
    }
    if ($formData['model'] === '' || mb_strlen($formData['model']) < 2) {
        $errors[] = 'Informe o modelo do veículo.';
    }

    $manufactureYear = (int)$formData['manufacture_year'];
    $modelYear = (int)$formData['model_year'];

    if ($manufactureYear < $minYear || $manufactureYear > $maxYear) {
        $errors[] = 'Ano de fabricação inválido.';
    }
    if ($modelYear < $minYear || $modelYear > $maxYear) {
        $errors[] = 'Ano do modelo inválido.';
    }
    if ($modelYear < $manufactureYear) {
        $errors[] = 'Ano do modelo não pode ser menor do que o de fabricação.';
    }

    $mileage = (int)preg_replace('/\D+/', '', $formData['mileage']);
    if ($mileage <= 0) {
        $errors[] = 'Informe a quilometragem do veículo.';
    }

    if (!in_array($formData['color'], $colorOptions, true)) {
        $errors[] = 'Selecione a cor nas opções disponíveis.';
    }
    if (!in_array($formData['fuel_type'], $fuelOptions, true)) {
        $errors[] = 'Selecione o combustível.';
    }

    if ($vehicleType !== 'moto' && $vehicleType !== 'outros' && !in_array($formData['doors'], $doorOptions, true)) {
        $errors[] = 'Informe a quantidade de portas.';
    }

    if (!in_array($formData['plate_last_digit'], array_map('strval', $plateDigits), true)) {
        $errors[] = 'Selecione o final da placa.';
    }

    if (mb_strlen($formData['title']) < 10) {
        $errors[] = 'Título do anúncio muito curto. Utilize ao menos 10 caracteres.';
    }

    if (mb_strlen($formData['description']) < 60) {
        $errors[] = 'Descreva o veículo com mais detalhes (mínimo de 60 caracteres).';
    }

    if ($formData['price'] <= 0) {
        $errors[] = 'Informe um valor válido para o preço.';
    }

    if ($formData['city'] === '') {
        $errors[] = 'Informe a cidade onde o veículo está localizado.';
    }

    if ($vehicleType === 'moto') {
        if ($formData['moto_style'] === '' || !in_array($formData['moto_style'], $motoStyles, true)) {
            $errors[] = 'Selecione o estilo da moto.';
        }
        if ($formData['moto_cilindradas'] === '' || !in_array($formData['moto_cilindradas'], $motoCilindradas, true)) {
            $errors[] = 'Selecione as cilindradas.';
        }
    }

    if ($vehicleType === 'caminhao') {
        if ($formData['truck_tracao'] === '' || !in_array($formData['truck_tracao'], $truckTracao, true)) {
            $errors[] = 'Informe a tração do caminhão.';
        }
        if ($formData['truck_carroceria'] === '' || !in_array($formData['truck_carroceria'], $truckCarroceria, true)) {
            $errors[] = 'Informe a carroceria do caminhão.';
        }
    }

    if ($vehicleType === 'outros') {
        if ($formData['other_type'] === '' || !in_array($formData['other_type'], $otherTypes, true)) {
            $errors[] = 'Informe o tipo do veículo.';
        }
        if (mb_strlen($formData['other_details']) < 4) {
            $errors[] = 'Detalhe o tipo de veículo informado na seção \"Outros\".';
        }
    }

    $imageFiles = normalize_files_array($_FILES['images'] ?? []);
    if (count($imageFiles) < $minPhotosRequired) {
        $errors[] = 'Adicione pelo menos ' . $minPhotosRequired . ' fotos para o anúncio.';
    }

    // Validar vídeo (se selecionado)
    if (!$errors && $isPremium && video_allowed_for_premium()) {
        $videoSource = trim((string)($_POST['video_source'] ?? 'upload'));
        
        if ($videoSource === 'instagram') {
            $instagramLink = trim((string)($_POST['video_instagram_link'] ?? ''));
            if (empty($instagramLink)) {
                $errors[] = 'Informe o link do Instagram se optar por compartilhar vídeo.';
            } elseif (!filter_var($instagramLink, FILTER_VALIDATE_URL)) {
                $errors[] = 'Link do Instagram inválido.';
            } elseif (!preg_match('#https?://(?:www\.)?instagram\.com/#i', $instagramLink)) {
                $errors[] = 'Link deve ser do Instagram (instagram.com).';
            }
        } elseif ($videoSource === 'upload') {
            $videoRequested = !empty($_FILES['video']['name'] ?? '');
            if ($videoRequested && (!$isPremium || !video_allowed_for_premium())) {
                $errors[] = 'Upload de vídeo está disponível apenas para usuários premium.';
            }
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $now = now();
        $slug = generate_unique_ad_slug($pdo, $formData['title']);

        $extraAttributes = [];
        switch ($vehicleType) {
            case 'moto':
                $extraAttributes = [
                    'estilo' => $formData['moto_style'],
                    'cilindradas' => $formData['moto_cilindradas'],
                ];
                break;
            case 'caminhao':
                $extraAttributes = [
                    'tracao' => $formData['truck_tracao'],
                    'carroceria' => $formData['truck_carroceria'],
                ];
                break;
            case 'outros':
                $extraAttributes = [
                    'tipo' => $formData['other_type'],
                    'detalhes' => $formData['other_details'],
                ];
                break;
        }

        $freeDurationDays = (int)get_setting('free_ads_duration_days', '30');
        $expiresAt = null;
        if (!$isPremium && $freeDurationDays > 0) {
            $expiresAt = date('Y-m-d H:i:s', time() + ($freeDurationDays * 86400));
        }

        try {
            $insert = $pdo->prepare('INSERT INTO ads (
                user_id, vehicle_type, title, slug, description, price, city, state, brand, model,
                manufacture_year, model_year, mileage, transmission, fuel_type, color, doors,
                license_plate, plate_last_digit, extra_attributes, status, reason_rejected,
                created_at, updated_at, approved_at, expires_at, payment_status
            ) VALUES (
                :user_id, :vehicle_type, :title, :slug, :description, :price, :city, :state, :brand, :model,
                :manufacture_year, :model_year, :mileage, :transmission, :fuel_type, :color, :doors,
                NULL, :plate_last_digit, :extra_attributes, \'pending\', NULL,
                :created_at, :updated_at, NULL, :expires_at, \'pending\'
            )');

            $insert->execute([
                ':user_id' => (int)$user['id'],
                ':vehicle_type' => $vehicleType,
                ':title' => $formData['title'],
                ':slug' => $slug,
                ':description' => $formData['description'],
                ':price' => $formData['price'],
                ':city' => $formData['city'],
                ':state' => $formData['state'],
                ':brand' => $formData['brand'],
                ':model' => $formData['model'],
                ':manufacture_year' => $manufactureYear,
                ':model_year' => $modelYear,
                ':mileage' => $mileage,
                ':transmission' => $formData['transmission'],
                ':fuel_type' => $formData['fuel_type'],
                ':color' => $formData['color'],
                ':doors' => $formData['doors'] !== '' ? (int)$formData['doors'] : null,
                ':plate_last_digit' => $formData['plate_last_digit'],
                ':extra_attributes' => $extraAttributes ? json_encode($extraAttributes, JSON_UNESCAPED_UNICODE) : null,
                ':created_at' => $now,
                ':updated_at' => $now,
                ':expires_at' => $expiresAt,
            ]);

            $adId = (int)$pdo->lastInsertId();
            $savedImages = process_image_uploads($_FILES['images'] ?? [], max_images_allowed(), $errors, $adId);

            if (count($savedImages) < $minPhotosRequired) {
                $errors[] = 'Não foi possível salvar a quantidade mínima de fotos.';
                throw new RuntimeException('Fotos insuficientes.');
            }

            $mediaStmt = $pdo->prepare('INSERT INTO ad_media (ad_id, type, sort_order, filepath, created_at) VALUES (:ad_id, :type, :sort_order, :filepath, :created_at)');
            foreach ($savedImages as $index => $path) {
                $mediaStmt->execute([
                    ':ad_id' => $adId,
                    ':type' => 'image',
                    ':sort_order' => $index,
                    ':filepath' => $path,
                    ':created_at' => $now,
                ]);
            }

            // Processar documento de verificação se premium e enviado
            $verificationDocPath = null;
            if ($isPremium && isset($_FILES['verification_document']) && $_FILES['verification_document']['error'] === UPLOAD_ERR_OK) {
                $verificationDocPath = process_verification_document_upload($_FILES['verification_document'], $errors, $adId);
                if ($verificationDocPath) {
                    // Atualizar o anúncio com o caminho do documento
                    $updateDoc = $pdo->prepare('UPDATE ads SET verification_document_path = :path WHERE id = :id');
                    $updateDoc->execute([':path' => $verificationDocPath, ':id' => $adId]);
                }
            }

            // Processar vídeo (anexado OU link Instagram)
            $videoPath = null;
            if ($isPremium && video_allowed_for_premium()) {
                $videoSource = trim((string)($_POST['video_source'] ?? 'upload'));

                if ($videoSource === 'instagram') {
                    // Processar link do Instagram
                    $instagramLink = trim((string)($_POST['video_instagram_link'] ?? ''));
                    $videoPath = process_instagram_link($instagramLink, $errors);
                } else {
                    // Processar upload de arquivo
                    $videoRequested = !empty($_FILES['video']['name'] ?? '');
                    if ($videoRequested) {
                        $videoPath = process_video_upload($_FILES['video'] ?? null, $errors, true, $adId);
                    }
                }

                // Salvar referência do vídeo
                if ($videoPath) {
                    $mediaStmt->execute([
                        ':ad_id' => $adId,
                        ':type' => 'video',
                        ':sort_order' => 0,
                        ':filepath' => $videoPath,
                        ':created_at' => $now,
                    ]);
                }
            }

            if ($errors) {
                throw new RuntimeException('Erros ao salvar mídia.');
            }

            $pdo->commit();
            record_audit((int)$user['id'], 'ad_created', 'ads', $adId, ['title' => $formData['title']]);

            // ✅ Descontar 1 moeda se ANÚNCIOS VEICULARES GRATUITOS não estiverem ativados
            create_ad_log('========================================');
            create_ad_log(sprintf(
                'INICIANDO DESCONTO: adsFreeForAll=%d, user_id=%d, ad_id=%d',
                $adsFreeForAll,
                (int)$user['id'],
                $adId
            ));
            
            if (!$adsFreeForAll) {
                create_ad_log('✓ Anúncios gratuitos DESATIVADOS - prosseguindo com desconto');
                
                $currentBalance = get_user_coin_balance((int)$user['id']);
                create_ad_log(sprintf(
                    'Saldo atual ANTES do desconto: %d moedas para usuário %d',
                    $currentBalance,
                    (int)$user['id']
                ));
                
                if ($currentBalance > 0) {
                    create_ad_log(sprintf('Saldo suficiente - chamando adjust_user_coins(-1)'));
                    create_ad_log(sprintf('Parâmetros: userId=%d, amount=-1, type=ad_publish, ref_id=%d, ref_type=ads', (int)$user['id'], $adId));
                    $adjusted = adjust_user_coins((int)$user['id'], -1, 'ad_publish', $adId, 'ads', 'Publicação de anúncio veicular');
                    create_ad_log(sprintf('Resultado de adjust_user_coins: %s', $adjusted ? 'TRUE' : 'FALSE'));
                    create_ad_log(sprintf(
                        'Desconto aplicado: resultado=%s, user_id=%d, referenceId=%d',
                        $adjusted ? 'SUCCESS' : 'FAILED',
                        (int)$user['id'],
                        $adId
                    ));
                    
                    if ($adjusted) {
                        create_ad_log('✓ Desconto bem-sucedido - invalidando cache');
                        // ✅ Invalidar cache de saldo para que a próxima leitura seja do BD
                        invalidate_coin_cache((int)$user['id']);
                        
                        $newBalance = get_user_coin_balance((int)$user['id']);
                        create_ad_log(sprintf(
                            'Saldo DEPOIS do desconto: %d moedas (era %d)',
                            $newBalance,
                            $currentBalance
                        ));
                        
                        // ✅ Marcar anúncio como PAGO (já que moeda foi debitada com sucesso)
                        $updatePayment = $pdo->prepare('UPDATE ads SET payment_status = "paid" WHERE id = :id');
                        $updatePayment->execute([':id' => $adId]);
                        create_ad_log(sprintf('✓ payment_status atualizado para "paid" - anúncio ID %d', $adId));
                        
                        record_audit((int)$user['id'], 'coin_deducted_for_ad', 'ads', $adId, [
                            'amount' => 1,
                            'reason' => 'Publicação de anúncio veicular',
                            'balance_before' => $currentBalance,
                            'balance_after' => $newBalance
                        ]);
                    } else {
                        create_ad_log('✗ ERRO: adjust_user_coins retornou FALSE');
                    }
                } else {
                    create_ad_log(sprintf(
                        '✗ Saldo insuficiente: %d moedas (necessário 1) - user_id=%d',
                        $currentBalance,
                        (int)$user['id']
                    ));
                }
            } else {
                create_ad_log('✓ Anúncios gratuitos ATIVADOS - desconto não será aplicado');
                // ✅ Marcar anúncio como PAGO (gratuito = já pago)
                $updatePayment = $pdo->prepare('UPDATE ads SET payment_status = "paid" WHERE id = :id');
                $updatePayment->execute([':id' => $adId]);
                create_ad_log(sprintf('✓ payment_status atualizado para "paid" (anúncio gratuito) - anúncio ID %d', $adId));
            }
            create_ad_log('========================================');

            create_ad_log(sprintf(
                'Anúncio %d criado para usuário %d (%s) tipo %s',
                $adId,
                (int)$user['id'],
                $formData['title'],
                $vehicleType
            ));

            // F11: Disparar verificação de buscas salvas para notificações
            // (assíncrono via cron job - ver api/check_saved_searches.php)
            // Esta chamada pode ser adicionada aqui se houver um queue de jobs

            $_SESSION['ad_preview_id'] = $adId;
            flash_add('success', 'Anúncio enviado para moderação com sucesso!');
            redirect('/anuncios/meus.php?novo=' . $adId);
        } catch (Throwable $exception) {
            $pdo->rollBack();

            if (!empty($savedImages ?? [])) {
                foreach ($savedImages as $path) {
                    delete_media_file($path);
                }
            }
            if (!empty($videoPath ?? null)) {
                delete_media_file($videoPath);
            }

            if (!$errors) {
                error_log(sprintf(
                    '[create_ad] Falha ao salvar anúncio para usuário %d: %s',
                    (int)$user['id'],
                    $exception->getMessage()
                ));
                create_ad_log(sprintf(
                    'Exception ao salvar anúncio para usuário %d: %s',
                    (int)$user['id'],
                    $exception->getMessage()
                ));
                $errors[] = 'Falha ao salvar o anúncio. Tente novamente.';
            } else {
                create_ad_log(sprintf(
                    'Exception com erros prévios para usuário %d: %s | %s',
                    (int)$user['id'],
                    implode(' | ', $errors),
                    $exception->getMessage()
                ));
            }
        }
    }
}

$postAttempted = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($postAttempted && $errors) {
    create_ad_log(sprintf(
        'Envio inválido para usuário %d: %s',
        (int)$user['id'],
        implode(' | ', $errors)
    ));
}

$postAttempted && !$errors && create_ad_log(sprintf(
    'Envio concluído para usuário %d sem erros adicionais.',
    (int)$user['id']
));

$priceDisplay = $formData['price'] ? format_currency((float)$formData['price']) : '';
$draftKey = 'clautos:draft:' . (int)$user['id'];

include __DIR__ . '/../partials/header.php';
?>

<!-- ✅ Tarja verde com gradiente -->
<style>
    .criar-hero {
        background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
        color: white;
        padding: 3rem 1.5rem;
        margin-bottom: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .criar-hero h1 {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    .criar-hero p {
        font-size: 1.1rem;
        opacity: 0.95;
    }
</style>

<section class="criar-hero">
    <h1>Publicar novo anúncio</h1>
    <p>Organizamos tudo em etapas para você revisar cada detalhe com tranquilidade. Complete todas as seções para publicar seu veículo.</p>
</section>

<section class="form-card">
    <?php if ($errors): ?>
        <div class="alert alert-error" style="margin-top:0;">
            <ul style="padding-left:1.2rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="create-ad-grid" style="margin-top:2rem;">
        <div>
            <div class="steps-nav">
                <button type="button" class="steps-nav__item active" data-step-trigger="1"><span>1</span>Dados básicos</button>
                <button type="button" class="steps-nav__item" data-step-trigger="2"><span>2</span>Detalhes técnicos</button>
                <button type="button" class="steps-nav__item" data-step-trigger="3"><span>3</span>Localização</button>
                <button type="button" class="steps-nav__item" data-step-trigger="4"><span>4</span>Preço & descrição</button>
                <button type="button" class="steps-nav__item" data-step-trigger="5"><span>5</span>Mídia</button>
                <button type="button" class="steps-nav__item" data-step-trigger="6"><span>6</span>Revisão</button>
                <button type="button" class="steps-nav__item" data-step-trigger="7"><span>7</span>Pagamento e Publicação</button>
            </div>
            <div class="steps-progress">
                <span class="steps-progress__bar" data-steps-progress></span>
            </div>

            <form id="createAdForm"
                  method="post"
                  enctype="multipart/form-data"
                  data-min-photos="<?= (int)$minPhotosRequired; ?>"
                  data-draft-key="<?= e($draftKey); ?>"
                  data-is-premium="<?= $isPremium ? '1' : '0'; ?>">

                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="vehicle_type" id="vehicle_type" value="<?= e($formData['vehicle_type']); ?>" data-save data-required-for="carro,moto,caminhao,outros" data-label="<?= e($vehicleTypes[$formData['vehicle_type']] ?? 'Carro'); ?>">

                <div class="step-pane active" data-step-pane="1">
                    <h2>1. Escolha o tipo e dados básicos</h2>
                    <p class="field-hint">Comece selecionando o tipo de veículo. O formulário se adapta automaticamente.</p>

                    <div class="inline-actions" style="margin-top:1.2rem;">
                        <?php foreach ($vehicleTypes as $typeKey => $label): ?>
                            <button type="button"
                                    class="button secondary<?= $formData['vehicle_type'] === $typeKey ? ' active' : ''; ?>"
                                    data-type-button="<?= e($typeKey); ?>"
                                    data-type-label="<?= e($label); ?>">
                                <?= e($label); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="grid-2" style="margin-top:1.5rem;">
                        <div class="input-group">
                            <label for="brand">Marca *</label>
                            <input type="text" id="brand" name="brand" value="<?= e($formData['brand']); ?>" data-save required>
                            <div id="marcaSuggestions" class="autocomplete-list hidden"></div>
                            <p class="field-hint" data-feedback-for="brand">Digite e selecione uma marca.</p>
                        </div>
                        <div class="input-group">
                            <label for="model">Modelo *</label>
                            <input type="text" id="model" name="model" value="<?= e($formData['model']); ?>" data-save required>
                            <div id="modeloSuggestions" class="autocomplete-list hidden"></div>
                            <p class="field-hint" data-feedback-for="model">Informe o modelo correspondente à marca.</p>
                        </div>
                    </div>

                    <div data-type-pane="moto"<?= $formData['vehicle_type'] === 'moto' ? '' : ' hidden'; ?> style="margin-top:1.2rem;">
                        <div class="grid-2">
                            <div class="input-group">
                                <label for="moto_style">Estilo *</label>
                                <select id="moto_style" name="moto_style" data-save data-required-for="moto">
                                    <option value="">Selecione</option>
                                    <?php foreach ($motoStyles as $style): ?>
                                        <option value="<?= e($style); ?>" <?= $formData['moto_style'] === $style ? 'selected' : ''; ?>><?= e($style); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="field-hint" data-feedback-for="moto_style"></p>
                            </div>
                            <div class="input-group">
                                <label for="moto_cilindradas">Cilindradas *</label>
                                <select id="moto_cilindradas" name="moto_cilindradas" data-save data-required-for="moto">
                                    <option value="">Selecione</option>
                                    <?php foreach ($motoCilindradas as $cc): ?>
                                        <option value="<?= e($cc); ?>" <?= $formData['moto_cilindradas'] === $cc ? 'selected' : ''; ?>><?= e($cc); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="field-hint" data-feedback-for="moto_cilindradas"></p>
                            </div>
                        </div>
                    </div>

                    <div data-type-pane="caminhao"<?= $formData['vehicle_type'] === 'caminhao' ? '' : ' hidden'; ?> style="margin-top:1.2rem;">
                        <div class="grid-2">
                            <div class="input-group">
                                <label for="truck_tracao">Tração *</label>
                                <select id="truck_tracao" name="truck_tracao" data-save data-required-for="caminhao">
                                    <option value="">Selecione</option>
                                    <?php foreach ($truckTracao as $option): ?>
                                        <option value="<?= e($option); ?>" <?= $formData['truck_tracao'] === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="field-hint" data-feedback-for="truck_tracao"></p>
                            </div>
                            <div class="input-group">
                                <label for="truck_carroceria">Carroceria *</label>
                                <select id="truck_carroceria" name="truck_carroceria" data-save data-required-for="caminhao">
                                    <option value="">Selecione</option>
                                    <?php foreach ($truckCarroceria as $option): ?>
                                        <option value="<?= e($option); ?>" <?= $formData['truck_carroceria'] === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="field-hint" data-feedback-for="truck_carroceria"></p>
                            </div>
                        </div>
                    </div>

                    <div data-type-pane="outros"<?= $formData['vehicle_type'] === 'outros' ? '' : ' hidden'; ?> style="margin-top:1.2rem;">
                        <div class="grid-2">
                            <div class="input-group">
                                <label for="other_type">Tipo de veículo *</label>
                                <select id="other_type" name="other_type" data-save data-required-for="outros">
                                    <option value="">Selecione</option>
                                    <?php foreach ($otherTypes as $option): ?>
                                        <option value="<?= e($option); ?>" <?= $formData['other_type'] === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="field-hint" data-feedback-for="other_type">Escolha a categoria que melhor representa o veículo.</p>
                            </div>
                            <div class="input-group">
                                <label for="other_details">Detalhes *</label>
                                <input type="text" id="other_details" name="other_details" value="<?= e($formData['other_details']); ?>" data-save data-required-for="outros" placeholder="Ex.: Reboque para transporte de motos">
                                <p class="field-hint" data-feedback-for="other_details">Descreva rapidamente o tipo de veículo ou acessório.</p>
                            </div>
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="button secondary" data-action="prev" disabled>Voltar</button>
                        <button type="button" class="button" data-action="next">Próxima etapa</button>
                    </div>
                </div>

                <div class="step-pane" data-step-pane="2">
                    <h2>2. Informe os detalhes técnicos</h2>
                    <div class="grid-2" style="margin-top:1.2rem;">
                        <div class="input-group">
                            <label for="manufacture_year">Ano de fabricação *</label>
                            <input type="number" id="manufacture_year" name="manufacture_year" min="<?= $minYear; ?>" max="<?= $maxYear; ?>" value="<?= e($formData['manufacture_year']); ?>" data-save required>
                            <p class="field-hint" data-feedback-for="manufacture_year"></p>
                        </div>
                        <div class="input-group">
                            <label for="model_year">Ano do modelo *</label>
                            <input type="number" id="model_year" name="model_year" min="<?= $minYear; ?>" max="<?= $maxYear; ?>" value="<?= e($formData['model_year']); ?>" data-save required>
                            <p class="field-hint" data-feedback-for="model_year"></p>
                        </div>
                        <div class="input-group">
                            <label for="mileage">Quilometragem *</label>
                            <input type="number" id="mileage" name="mileage" min="0" value="<?= e($formData['mileage']); ?>" data-save required>
                            <p class="field-hint" data-feedback-for="mileage"></p>
                        </div>
                        <div class="input-group">
                            <label for="color">Cor *</label>
                            <select id="color" name="color" data-save required>
                                <option value="">Selecione</option>
                                <?php foreach ($colorOptions as $color): ?>
                                    <option value="<?= e($color); ?>" <?= $formData['color'] === $color ? 'selected' : ''; ?>><?= e($color); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-hint" data-feedback-for="color"></p>
                        </div>
                        <div class="input-group">
                            <label for="transmission">Câmbio *</label>
                            <select id="transmission" name="transmission" data-save required>
                                <option value="">Selecione</option>
                                <?php 
                                    // Opções de câmbio por tipo de veículo
                                    $transmissionOptions = [
                                        'carro' => ['Manual', 'Automático', 'Automatizado', 'CVT', 'Dualogic', 'Tiptronic', 'DSG'],
                                        'moto' => ['Manual', 'Automático', 'Semiautomático', 'CVT'],
                                        'caminhao' => ['Manual', 'Automatizado', 'Automático', 'Híbrido'],
                                        'outros' => ['Manual', 'Automático'],
                                    ];
                                    $currentType = $formData['vehicle_type'] ?? 'carro';
                                    $currentOptions = $transmissionOptions[$currentType] ?? $transmissionOptions['carro'];
                                    foreach ($currentOptions as $transmission):
                                ?>
                                    <option value="<?= e($transmission); ?>" <?= $formData['transmission'] === $transmission ? 'selected' : ''; ?>><?= e($transmission); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-hint" data-feedback-for="transmission"></p>
                        </div>
                        <div class="input-group">
                            <label for="fuel_type">Combustível *</label>
                            <select id="fuel_type" name="fuel_type" data-save required>
                                <option value="">Selecione</option>
                                <?php foreach ($fuelOptions as $fuel): ?>
                                    <option value="<?= e($fuel); ?>" <?= $formData['fuel_type'] === $fuel ? 'selected' : ''; ?>><?= e($fuel); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-hint" data-feedback-for="fuel_type"></p>
                        </div>
                        <div class="input-group">
                            <label for="doors">Portas <?= $formData['vehicle_type'] === 'moto' ? '(opcional)' : '*'; ?></label>
                            <select id="doors" name="doors" data-save data-required-for="carro,caminhao">
                                <option value="">Selecione</option>
                                <?php foreach ($doorOptions as $door): ?>
                                    <option value="<?= e($door); ?>" <?= $formData['doors'] === $door ? 'selected' : ''; ?>><?= e($door); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-hint" data-feedback-for="doors"></p>
                        </div>
                        <div class="input-group">
                            <label for="plate_last_digit">Final da placa *</label>
                            <select id="plate_last_digit" name="plate_last_digit" data-save required>
                                <option value="">Selecione</option>
                                <?php foreach ($plateDigits as $digit): ?>
                                    <option value="<?= $digit; ?>" <?= $formData['plate_last_digit'] === (string)$digit ? 'selected' : ''; ?>><?= $digit; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-hint" data-feedback-for="plate_last_digit"></p>
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="button secondary" data-action="prev">Voltar</button>
                        <button type="button" class="button" data-action="next">Próxima etapa</button>
                    </div>
                </div>

                <div class="step-pane" data-step-pane="3">
                    <h2>3. Onde está seu veículo?</h2>
                    <p class="field-hint">Por enquanto trabalhamos apenas com anúncios em Minas Gerais.</p>

                    <div class="grid-2" style="margin-top:1.2rem;">
                        <div class="input-group">
                            <label for="state">Estado *</label>
                            <select id="state" name="state" data-save required>
                                <option value="MG" selected>Minas Gerais</option>
                            </select>
                            <p class="field-hint" data-feedback-for="state"></p>
                        </div>
                        <div class="input-group">
                            <label for="city">Cidade *</label>
                            <input type="text" id="city" name="city" value="<?= e($formData['city']); ?>" data-save required data-city-input autocomplete="off">
                            <div id="cidadeSuggestions" class="autocomplete-list hidden" data-suggestions></div>
                            <p class="field-hint" data-feedback-for="city"></p>
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="button secondary" data-action="prev">Voltar</button>
                        <button type="button" class="button" data-action="next">Próxima etapa</button>
                    </div>
                </div>

                <div class="step-pane" data-step-pane="4">
                    <h2>4. Preço e descrição</h2>
                    <div class="grid-2" style="margin-top:1.2rem;">
                        <div class="input-group">
                            <label for="price_display">Preço *</label>
                            <input type="text" id="price_display" name="price_display" value="<?= e($priceDisplay); ?>" data-price-display data-save required>
                            <input type="hidden" name="price" id="price" value="<?= e($formData['price']); ?>" data-price-hidden>
                            <p class="field-hint" data-feedback-for="price_display"></p>
                        </div>
                        <div class="input-group">
                            <label for="title">Título do anúncio *</label>
                            <input type="text" id="title" name="title" value="<?= e($formData['title']); ?>" data-save required>
                            <p class="field-hint" data-feedback-for="title"></p>
                            <button type="button" class="button secondary" style="margin-top:0.75rem;" data-open-modal="title">Gerar sugestões de título</button>
                        </div>
                    </div>

                    <div class="input-group" style="margin-top:1.2rem;">
                        <label for="description">Descrição detalhada *</label>
                        <textarea id="description" name="description" rows="6" data-save required><?= e($formData['description']); ?></textarea>
                        <p class="field-hint" data-feedback-for="description"></p>
                        <button type="button" class="button secondary" style="margin-top:0.75rem;" data-open-modal="description">Gerar sugestões de descrição</button>
                    </div>

                    <div class="upload-tips" style="margin-top:1.3rem;">
                        <div class="upload-tips__card">
                            <strong>Dicas rápidas para a descrição:</strong>
                            <span>• Informe revisões e histórico.</span>
                            <span>• Destaque diferenciais como multimídia e segurança.</span>
                            <span>• Seja transparente sobre pequenos detalhes.</span>
                        </div>
                        <div class="upload-tips__card">
                            <strong>Precificação inteligente:</strong>
                            <span>• Compare com modelos semelhantes na região.</span>
                            <span>• Valores competitivos aumentam contatos.</span>
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="button secondary" data-action="prev">Voltar</button>
                        <button type="button" class="button" data-action="next">Próxima etapa</button>
                    </div>
                </div>

                <div class="step-pane" data-step-pane="5">
                    <h2>5. Fotos e vídeo</h2>
                    <p class="field-hint">Arraste arquivos ou clique para selecionar. Aceitamos até <?= (int)max_images_allowed(); ?> fotos (máx. <?= (int)get_setting('max_image_size_mb', '2'); ?> MB cada).</p>

                    <div class="media-uploader" data-drop-area>
                        <div class="media-uploader__icon">📷</div>
                        <p><strong>Arraste suas fotos aqui</strong> ou clique para escolher</p>
                        <p class="field-hint">Dê preferência a ambientes iluminados e fotos na diagonal do veículo.</p>
                        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" data-photos-input hidden multiple>
                    </div>
                    <div class="field-hint" id="photoCounter" data-photos-count>0 fotos selecionadas (mínimo <?= (int)$minPhotosRequired; ?>)</div>
                    <div class="photos-grid" data-photos-grid></div>

                    <div class="upload-tips" style="margin-top:1.5rem; margin-bottom:2.5rem;">
                        <div class="upload-tips__card">
                            <strong>Ângulos indispensáveis</strong>
                            <span>Frente, traseira e laterais em 45° para valorizar o design.</span>
                        </div>
                        <div class="upload-tips__card">
                            <strong>Interior e detalhes</strong>
                            <span>Painel ligado, bancos, multimídia e possíveis acessórios.</span>
                        </div>
                        <div class="upload-tips__card">
                            <strong>Limpeza importa</strong>
                            <span>Veículo limpo e ambiente neutro aumentam a confiança do comprador.</span>
                        </div>
                    </div>

                    <div class="video-card<?= $isPremium && video_allowed_for_premium() ? '' : ' locked'; ?>" data-video-section>
                        <div>
                            <strong>Vídeo do anúncio</strong>
                            <p class="field-hint">
                                Escolha uma opção: <strong>anexar um vídeo</strong> ou <strong>compartilhar um link do Instagram</strong> (apenas um)
                            </p>
                        </div>

                        <!-- TAB 1: Anexar Vídeo -->
                        <div style="margin-bottom: 1.5rem; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; overflow: hidden;">
                            <label style="display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.75rem; cursor: pointer; word-break: break-word;">
                                <input type="radio" name="video_source" value="upload" <?= ($formData['video_source'] ?? 'upload') === 'upload' ? 'checked' : ''; ?> <?= $isPremium && video_allowed_for_premium() ? '' : 'disabled'; ?> style="flex-shrink: 0; margin-top: 0.25rem;">
                                <strong style="word-break: break-word;">📹 Anexar vídeo do seu dispositivo</strong>
                            </label>
                            <div id="videoUploadSection" style="display: <?= ($formData['video_source'] ?? 'upload') === 'upload' ? 'block' : 'none'; ?>; margin-left: calc(1rem + 0.5rem + 24px); overflow: hidden;">
                                <p style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; word-break: break-word;">
                                    <strong>Formatos:</strong> MP4, MOV (iPhone), AVI<br>
                                    <strong>Limite:</strong> até <?= e(get_setting('max_video_size_mb', '100')); ?>MB<br>
                                    <strong>Tempo:</strong> ~2-5 min (depende da conexão)
                                </p>
                                <input type="file" name="video" accept="video/mp4,video/quicktime,video/x-msvideo,video/avi" <?= $isPremium && video_allowed_for_premium() ? '' : 'disabled'; ?> style="width: 100%; max-width: 100%; overflow-x: hidden;">
                            </div>
                        </div>

                        <!-- TAB 2: Link do Instagram -->
                        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; overflow: hidden;">
                            <label style="display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.75rem; cursor: pointer; word-break: break-word;">
                                <input type="radio" name="video_source" value="instagram" <?= ($formData['video_source'] ?? 'upload') === 'instagram' ? 'checked' : ''; ?> <?= $isPremium && video_allowed_for_premium() ? '' : 'disabled'; ?> style="flex-shrink: 0; margin-top: 0.25rem;">
                                <strong style="word-break: break-word;">🎬 Compartilhar link do Instagram</strong>
                            </label>
                            <div id="instagramLinkSection" style="display: <?= ($formData['video_source'] ?? 'upload') === 'instagram' ? 'block' : 'none'; ?>; margin-left: calc(1rem + 0.5rem + 24px); overflow: hidden;">
                                <p style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; word-break: break-word;">
                                    Cole o link do post do Instagram com seu vídeo (ex: https://instagram.com/p/ABC123...)<br>
                                    <span style="color: #999;">O vídeo será exibido diretamente do Instagram no anúncio</span>
                                </p>
                                <input type="url" name="video_instagram_link" placeholder="https://instagram.com/p/..." value="<?= e($formData['video_instagram_link'] ?? ''); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-size: 0.9rem; box-sizing: border-box; max-width: 100%; overflow-x: auto;" <?= $isPremium && video_allowed_for_premium() ? '' : 'disabled'; ?>>
                            </div>
                        </div>
                    </div>

                    <?php if (!$isPremium || !video_allowed_for_premium()): ?>
                        <div class="upload-tips__card" data-upsell>
                            <strong>Quer publicar vídeo?</strong>
                            <span>Usuários premium têm prioridade na busca, selo de verificação e podem incluir vídeo.</span>
                        </div>
                    <?php endif; ?>

                    <div class="step-actions">
                        <button type="button" class="button secondary" data-action="prev">Voltar</button>
                        <button type="button" class="button" data-action="next">Próxima etapa</button>
                    </div>
                </div>

                <div class="step-pane" data-step-pane="6">
                    <h2>6. Revisão final</h2>
                    <p>Confira se todas as seções do checklist estão completas antes de enviar para moderação.</p>
                    <div class="alert alert-warning" style="margin-top:1.2rem;">
                        Assim que o anúncio for enviado, ele passará por uma avaliação rápida da nossa equipe.
                        Você poderá acompanhar o status dentro de "Meus anúncios" e visualizar como ficou mesmo antes da aprovação oficial.
                    </div>

                    <?php if ($isPremium): ?>
                    <div class="verification-section" style="margin-top:2rem; padding:1.5rem; background:#f9f9f9; border:1px solid #e0e0e0; border-radius:8px;">
                        <h3 style="margin-top:0; color:#166534; font-size:1.1rem;">✓ Solicitar Verificação (Premium)</h3>
                        <p style="margin-bottom:1.5rem; color:#666;">
                            Anexe o Laudo Cautelar/Documento do veículo para solicitar a badge de "Verificado". Isso aumenta a confiabilidade do seu anúncio.
                        </p>
                        
                        <div style="margin-bottom:1rem;">
                            <label style="display:block; margin-bottom:0.5rem; font-weight:600; color:#333;">
                                Documento do Veículo <span style="color:#d32f2f;">*</span>
                            </label>
                            <input type="file" 
                                   id="verificationDocument" 
                                   name="verification_document" 
                                   accept="image/jpeg,image/png,application/pdf"
                                   style="display:block; padding:0.75rem; border:2px solid #ddd; border-radius:4px; width:100%; cursor:pointer;">
                            <small style="display:block; margin-top:0.5rem; color:#999;">
                                Formatos aceitos: JPEG, PNG ou PDF. Tamanho máximo: 10MB.
                            </small>
                        </div>

                        <div id="verificationDocumentInfo" style="display:none; margin-top:1rem; padding:1rem; background:#e8f5e9; border-left:4px solid #4caf50; border-radius:4px;">
                            <strong style="color:#2e7d32;">✓ Documento selecionado</strong>
                            <p id="verificationDocumentName" style="margin:0.5rem 0 0 0; color:#1b5e20; font-size:0.95rem;"></p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info" style="margin-top:2rem;">
                        <strong>💡 Dica:</strong> Atualize para Premium para solicitar a badge de "Verificado" no seu anúncio!
                    </div>
                    <?php endif; ?>

                    <div class="step-actions">
                        <button type="button" class="button secondary" data-action="prev">Voltar</button>
                        <button type="button" class="button" data-action="next">Próxima etapa</button>
                    </div>
                </div>

                <div class="step-pane" data-step-pane="7">
                    <h2>7. Pagamento e Publicação</h2>
                    <p>Finalize a publicação do seu anúncio. Revise seu saldo de moedas antes de publicar.</p>
                    
                    <!-- 💰 Caixa de Saldo de Moedas -->
                    <div style="margin-top:1.5rem; padding:1.5rem; background:linear-gradient(135deg, #15803d 0%, #22c55e 100%); border-radius:12px; color:white; text-align:center;">
                        <div style="font-size:0.95rem; opacity:0.9; margin-bottom:0.5rem;">Seu Saldo de Moedas</div>
                        <div style="font-size:2.5rem; font-weight:bold; display:flex; align-items:center; justify-content:center; gap:0.75rem;">
                            <img src="/assets/img/classilivre-logo.png" alt="CLivre Medal" style="height:2.5rem; vertical-align:middle;">
                            <span id="balanceDisplay" data-balance-amount="<?= (int)$userCoinBalance; ?>">
                                <?= (int)$userCoinBalance; ?>
                            </span>
                        </div>
                        <div style="font-size:0.85rem; opacity:0.9; margin-top:0.5rem;">
                            Você precisará de 1 moeda para publicar este anúncio
                        </div>
                    </div>

                    <!-- 📊 Mensagem Condicional de Saldo (gerenciada por JavaScript) -->
                    <div id="balanceMessage" style="margin-top:1.5rem;"></div>

                    <div class="step-actions" style="margin-top:2rem;">
                        <button type="button" class="button secondary" data-action="prev">Voltar</button>
                        
                        <!-- Botão de Submit (visível apenas se tiver saldo OU anúncios veiculares grátis ativados) -->
                        <button type="submit" 
                                class="button" 
                                id="submitButton"
                                style="<?= ($userCoinBalance > 0 || $adsFreeForAll) ? '' : 'display:none;'; ?>">
                            Enviar para moderação
                        </button>
                        
                        <!-- Botão de Comprar Moedas (visível apenas se NÃO tiver saldo E anúncios veiculares grátis desativados) -->
                        <a href="/premium/comprar_moedas.php?return_to=/anuncios/criar.php"
                           class="button"
                           id="buyButton"
                           style="<?= ($userCoinBalance <= 0 && !$adsFreeForAll) ? '' : 'display:none;'; ?>; text-decoration:none; text-align:center;">
                            🛒 Comprar Moedas Premium
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <aside class="summary-panel">
            <div class="summary-panel__header">
                <h3>Resumo do anúncio</h3>
                <span class="pill">Rascunho automático</span>
            </div>
            <div class="summary-panel__list">
                <div class="summary-item">
                    <strong>Tipo</strong>
                    <span data-summary="type">—</span>
                </div>
                <div class="summary-item">
                    <strong>Marca / Modelo</strong>
                    <span data-summary="brand">—</span>
                </div>
                <div class="summary-item">
                    <strong>Preço</strong>
                    <span data-summary="price">—</span>
                </div>
                <div class="summary-item">
                    <strong>Localização</strong>
                    <span data-summary="location">—</span>
                </div>
                <div class="summary-item">
                    <strong>Fotos</strong>
                    <span data-summary="photos">0 / <?= (int)$minPhotosRequired; ?> mín.</span>
                </div>
            </div>

            <h4 style="margin-top:1.5rem;">Checklist</h4>
            <div class="checklist">
                <div class="checklist__item" data-check="basic">
                    <div>
                        <strong>Dados básicos</strong>
                        <span data-check-text>Selecione tipo, marca e modelo.</span>
                    </div>
                </div>
                <div class="checklist__item" data-check="technical">
                    <div>
                        <strong>Detalhes técnicos</strong>
                        <span data-check-text>Informar anos, km, câmbio e combustível.</span>
                    </div>
                </div>
                <div class="checklist__item" data-check="location">
                    <div>
                        <strong>Localização</strong>
                        <span data-check-text>Informe cidade em MG.</span>
                    </div>
                </div>
                <div class="checklist__item" data-check="description">
                    <div>
                        <strong>Preço & descrição</strong>
                        <span data-check-text>Defina valor, título e descrição atrativa.</span>
                    </div>
                </div>
                <div class="checklist__item" data-check="media" data-threshold="<?= (int)$minPhotosRequired; ?>">
                    <div>
                        <strong>Fotos & vídeo</strong>
                        <span data-check-text>Adicione pelo menos <?= (int)$minPhotosRequired; ?> fotos.</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="modal-backdrop" data-modal-backdrop="title">
        <div class="modal">
            <div class="modal__header">
                <h3>Sugestões de título</h3>
                <button type="button" class="button secondary" data-close-modal="title">Fechar</button>
            </div>
            <div class="modal__list"></div>
        </div>
    </div>

    <div class="modal-backdrop" data-modal-backdrop="description">
        <div class="modal">
            <div class="modal__header">
                <h3>Sugestões de descrição</h3>
                <button type="button" class="button secondary" data-close-modal="description">Fechar</button>
            </div>
            <div class="modal__list"></div>
        </div>
    </div>
</section>

<?php
$autocompleteVersion = file_exists(__DIR__ . '/../assets/js/autocomplete.js') ? (string)filemtime(__DIR__ . '/../assets/js/autocomplete.js') : (string)time();
$createAdVersion = file_exists(__DIR__ . '/../assets/js/create-ad.js') ? (string)filemtime(__DIR__ . '/../assets/js/create-ad.js') : (string)time();
$compressorVersion = file_exists(__DIR__ . '/../assets/js/image-compressor.js') ? (string)filemtime(__DIR__ . '/../assets/js/image-compressor.js') : (string)time();
$heicConverterVersion = file_exists(__DIR__ . '/../assets/js/heic-converter.js') ? (string)filemtime(__DIR__ . '/../assets/js/heic-converter.js') : (string)time();
?>
<script defer src="/assets/js/autocomplete.js?v=<?= urlencode($autocompleteVersion); ?>"></script>

<script>
// ✅ Configurações de Upload - Passadas do PHP para o JavaScript
// Essas configurações são usadas para validação ANTES da compressão
window.uploadConfig = {
    maxImageSizeMB: <?= (int)get_setting('max_image_size_mb', '2'); ?>,
    maxVideoSizeMB: <?= (int)get_setting('max_video_size_mb', '30'); ?>,
    maxDocumentSizeMB: 10
};
console.log('[CREATE-AD] Configurações de upload carregadas:', window.uploadConfig);
</script>

<!-- ✅ CONVERSOR HEIC para JPG (client-side) - Carregado ANTES da compressão -->
<script defer src="/assets/js/heic-converter.js?v=<?= urlencode($heicConverterVersion); ?>"></script>

<!-- ✅ COMPRESSOR DE IMAGENS (client-side) - Carregado APÓS uploadConfig -->
<script defer src="/assets/js/image-compressor.js?v=<?= urlencode($compressorVersion); ?>"></script>

<script defer src="/assets/js/create-ad.js?v=<?= urlencode($createAdVersion); ?>"></script>

<script>
// ✅ Atualizar saldo e visibilidade de botões na aba 7
document.addEventListener('DOMContentLoaded', async () => {
    const balanceDisplay = document.getElementById('balanceDisplay');
    const balanceMessage = document.getElementById('balanceMessage');
    const submitButton = document.getElementById('submitButton');
    const buyButton = document.getElementById('buyButton');
    const adsFreeForAll = <?= $adsFreeForAll ? 'true' : 'false'; ?>;

    if (!balanceDisplay) {
        console.error('[TAB 7] Elemento balanceDisplay não encontrado');
        return;
    }

    const updateBalanceDisplay = async () => {
        try {
            const response = await fetch('/api/coin_balance.php');
            const data = await response.json();
            
            console.log('[TAB 7] Saldo obtido da API:', data);
            
            if (data && data.success && typeof data.balance === 'number') {
                const newBalance = data.balance;
                balanceDisplay.textContent = newBalance;
                balanceDisplay.dataset.balanceAmount = newBalance;
                
                console.log('[TAB 7] Atualizando display com saldo:', newBalance);
                
                // Atualizar visibilidade de botões
                const hasBalance = newBalance > 0;
                const canPublish = hasBalance || adsFreeForAll;
                
                if (submitButton) {
                    submitButton.style.display = canPublish ? '' : 'none';
                    console.log('[TAB 7] Submit button visibilidade:', canPublish);
                }
                if (buyButton) {
                    buyButton.style.display = (!hasBalance && !adsFreeForAll) ? '' : 'none';
                    console.log('[TAB 7] Buy button visibilidade:', !hasBalance && !adsFreeForAll);
                }
                
                // Atualizar mensagem
                if (balanceMessage) {
                    if (adsFreeForAll) {
                        balanceMessage.innerHTML = '<div class="alert alert-success">✅ Anúncios veiculares gratuitos ativados! Você pode publicar sem gastar moedas.</div>';
                    } else if (hasBalance) {
                        balanceMessage.innerHTML = '<div class="alert alert-success">✅ Você tem ' + newBalance + ' moeda(s). Clique em "Enviar para moderação" para publicar.</div>';
                    } else {
                        balanceMessage.innerHTML = '<div class="alert alert-warning">⚠️ Você não tem moedas. Clique em "Comprar Moedas Premium" para continuar.</div>';
                    }
                }
            } else {
                console.warn('[TAB 7] Resposta da API inválida:', data);
            }
        } catch (error) {
            console.error('[TAB 7] Erro ao atualizar saldo:', error);
        }
    };

    // Atualizar ao carregar
    console.log('[TAB 7] Iniciando atualização de saldo...');
    await updateBalanceDisplay();
    
    // Atualizar a cada 2 segundos (para detectar quando usuário volta de compra)
    setInterval(updateBalanceDisplay, 2000);
});

// Inicializa autocomplete para o campo de cidade no formulário de criação de anúncio
document.addEventListener('DOMContentLoaded', async () => {
    const cityInput = document.querySelector('[data-city-input]');
    const suggestionsList = document.querySelector('[data-suggestions]');
    
    if (!cityInput || !suggestionsList) return;
    
    // Aguarda preloadCidades estar disponível
    let retries = 50;
    while (retries > 0 && typeof window.preloadCidades !== 'function') {
        await new Promise(resolve => setTimeout(resolve, 100));
        retries--;
    }
    
    if (typeof window.preloadCidades === 'function') {
        try {
            const cities = await window.preloadCidades();
            
            // Inicializa o autocomplete usando a função global
            if (typeof window.setupAutocompleteElements === 'function') {
                window.setupAutocompleteElements(cityInput, suggestionsList, cities, null);
            }
        } catch (error) {
            console.error('[CRIAR] Erro ao carregar cidades:', error);
        }
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>



