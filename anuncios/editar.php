<?php
// anuncios/editar.php — Permite atualização de anúncios pelo proprietário.
// Formulário SIMPLES (sem etapas) com campos dinâmicos por tipo de veículo
// Campos travados (desabilitados): vehicle_type, brand, model, city, state

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/upload_handler.php';

require_login();
ensure_user_not_blocked();
ensure_verified_email();

$user = current_user();
$id = (int)(input_get('id') ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

$stmt = db()->prepare('SELECT * FROM ads WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $id, ':user_id' => (int)$user['id']]);
$ad = $stmt->fetch();

if (!$ad) {
    http_response_code(404);
    exit('Anúncio não encontrado.');
}

// Remover escapes duplos nos dados
foreach ($ad as $key => &$value) {
    if (is_string($value)) {
        $value = stripslashes(stripslashes($value));
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $decoded = json_decode($value, true);
            if ($decoded !== null) {
                $value = $decoded;
            }
        }
    }
}
unset($value);

$pageTitle = 'Editar Anúncio do Veículo - Site Carros';
$errors = [];
$isPremium = user_is_premium($user);

// Opções disponíveis para cada tipo de veículo
$vehicleTypes = [
    'carro' => 'Carro',
    'moto' => 'Moto',
    'caminhao' => 'Caminhão',
    'outros' => 'Outros',
];

$colorOptions = [
    'Preto', 'Branco', 'Prata', 'Cinza', 'Vermelho', 'Azul',
    'Verde', 'Marrom', 'Amarelo', 'Laranja', 'Bege', 'Roxo', 'Outra'
];

$fuelOptions = ['Gasolina', 'Etanol', 'Flex', 'Diesel', 'Elétrico', 'Híbrido', 'GNV'];
$doorOptions = ['2', '3', '4', '5'];
$plateDigits = range(0, 9);

$motoStyles = ['Street', 'Custom', 'Esportiva', 'Trail', 'Big Trail', 'Naked', 'Scooter', 'Off-road', 'Speed', 'Touring', 'Crossover'];
$motoCilindradas = ['50cc', '125cc', '150cc', '160cc', '200cc', '250cc', '300cc', '400cc', '500cc', '600cc', '750cc', '900cc', '1000cc', '1200cc', '1300cc', '1800cc'];

$truckTracao = ['4x2 (Toco)', '6x2 (Truck)', '6x4 (Cavalo)', '4x4 (Fora de estrada)', '6x6 (Fora de estrada)', '8x2', '8x4', 'Bitrem', 'Rodotrem', 'Treminhão'];
$truckCarroceria = ['Baú', 'Baú Frigorífico', 'Sider', 'Grade Baixa', 'Carga Seca', 'Tanque', 'Graneleiro', 'Prancha', 'Munk', 'Cegonha', 'Guincho', 'Caçamba', 'Basculante', 'Porta Container'];

$otherTypes = ['Carroceria', 'Carretinha', 'Reboque', 'Buggy', 'Quadriciclo', 'Trator', 'Máquina Agrícola', 'Ônibus', 'Micro-ônibus', 'Van', 'Utilitário', 'Ambulância', 'Caminhão pipa', 'Betoneira'];

$minYear = 1950;
$maxYear = (int)date('Y') + 1;

// Descompactar extra_attributes
$extraAttributes = [];
if ($ad['extra_attributes']) {
    $decoded = json_decode($ad['extra_attributes'], true);
    if (is_array($decoded)) {
        $extraAttributes = $decoded;
    }
}

// Montar formData com valores do BD
$formData = [
    'vehicle_type' => $ad['vehicle_type'],
    'brand' => $ad['brand'],
    'model' => $ad['model'],
    'manufacture_year' => $ad['manufacture_year'],
    'model_year' => $ad['model_year'],
    'mileage' => $ad['mileage'],
    'color' => $ad['color'],
    'transmission' => $ad['transmission'],
    'fuel_type' => $ad['fuel_type'],
    'doors' => $ad['doors'] ? (string)$ad['doors'] : '',
    'plate_last_digit' => (string)($ad['plate_last_digit'] ?? ''),
    'city' => $ad['city'],
    'state' => $ad['state'],
    'price' => $ad['price'],
    'title' => $ad['title'],
    'description' => $ad['description'],
    'moto_style' => $extraAttributes['estilo'] ?? '',
    'moto_cilindradas' => $extraAttributes['cilindradas'] ?? '',
    'truck_tracao' => $extraAttributes['tracao'] ?? '',
    'truck_carroceria' => $extraAttributes['carroceria'] ?? '',
    'other_type' => $extraAttributes['tipo'] ?? '',
    'other_details' => $extraAttributes['detalhes'] ?? '',
];

$priceDisplay = $formData['price'] ? format_currency((float)$formData['price']) : '';

// Valores originais (para validação de campos travados)
$originalVehicleType = $ad['vehicle_type'];
$originalBrand = $ad['brand'];
$originalModel = $ad['model'];
$originalCity = $ad['city'];
$originalState = $ad['state'];

// Valores originais (para validação de campos travados)
$originalVehicleType = $ad['vehicle_type'];
$originalBrand = $ad['brand'];
$originalModel = $ad['model'];
$originalCity = $ad['city'];
$originalState = $ad['state'];

// Carregar imagens e vídeos atuais
$images = [];
$video = null;
try {
    $mediaStmt = db()->prepare('SELECT * FROM ad_media WHERE ad_id = :ad_id AND type = "image" ORDER BY id');
    $mediaStmt->execute([':ad_id' => $id]);
    $images = $mediaStmt->fetchAll();
} catch (Throwable $e) {
    error_log('[editar.php] Falha ao carregar imagens: ' . $e->getMessage());
}

try {
    $videoStmt = db()->prepare('SELECT * FROM ad_media WHERE ad_id = :ad_id AND type = "video" LIMIT 1');
    $videoStmt->execute([':ad_id' => $id]);
    $video = $videoStmt->fetch();
} catch (Throwable $e) {
    error_log('[editar.php] Falha ao carregar vídeo: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido. Atualize a página e tente novamente.';
    }

    $sanitize = static function (string $key) {
        return trim((string)($_POST[$key] ?? ''));
    };

    // ✅ VALIDAÇÃO DE CAMPOS TRAVADOS - verificar valores hidden
    $originalVehicleTypeFromPost = $sanitize('original_vehicle_type');
    $originalBrandFromPost = $sanitize('original_brand');
    $originalModelFromPost = $sanitize('original_model');
    $originalCityFromPost = $sanitize('original_city');
    $originalStateFromPost = $sanitize('original_state');

    // Se os valores hidden foram alterados/perdidos, rejeitar
    if ($originalVehicleTypeFromPost !== $originalVehicleType) {
        $errors[] = 'Validação de segurança falhou para tipo de veículo.';
    }
    if ($originalBrandFromPost !== $originalBrand) {
        $errors[] = 'Validação de segurança falhou para marca.';
    }
    if ($originalModelFromPost !== $originalModel) {
        $errors[] = 'Validação de segurança falhou para modelo.';
    }
    if ($originalCityFromPost !== $originalCity) {
        $errors[] = 'Validação de segurança falhou para cidade.';
    }
    if ($originalStateFromPost !== $originalState) {
        $errors[] = 'Validação de segurança falhou para estado.';
    }

    $vehicleType = strtolower($sanitize('vehicle_type'));
    if (!array_key_exists($vehicleType, $vehicleTypes)) {
        $vehicleType = $originalVehicleType; // Manter o tipo original
    }

    // Montar formData com valores editáveis
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
    $formData['state'] = $sanitize('state');
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

    // Validações de campos editáveis
    $titleTrimmed = trim((string)$formData['title']);
    if ($titleTrimmed === '' || mb_strlen($titleTrimmed) < 10) {
        $errors[] = 'Título do anúncio muito curto. Utilize ao menos 10 caracteres.';
    }

    if ($formData['description'] === '' || mb_strlen($formData['description']) < 30) {
        $errors[] = 'Descreva o veículo com mais detalhes (mínimo de 30 caracteres).';
    }

    if ($formData['price'] <= 0) {
        $errors[] = 'Informe um valor válido para o preço.';
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

    // Portas: obrigatório apenas para carros e caminhões
    if ($vehicleType !== 'moto' && $vehicleType !== 'outros' && !in_array($formData['doors'], $doorOptions, true)) {
        $errors[] = 'Informe a quantidade de portas.';
    }

    if (!in_array($formData['plate_last_digit'], array_map('strval', $plateDigits), true)) {
        $errors[] = 'Selecione o final da placa.';
    }

    // Validações específicas por tipo
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
            $errors[] = 'Detalhe o tipo de veículo informado.';
        }
    }

    if ($vehicleType === 'outros') {
        if ($formData['other_type'] === '' || !in_array($formData['other_type'], $otherTypes, true)) {
            $errors[] = 'Informe o tipo do veículo.';
        }
        if (mb_strlen($formData['other_details']) < 4) {
            $errors[] = 'Detalhe o tipo de veículo informado.';
        }
    }

    // Processar imagens
    $removeIds = array_map('intval', $_POST['remove_images'] ?? []);
    $remainingImages = array_filter($images, fn($img) => !in_array((int)$img['id'], $removeIds, true));

    $maxNewImages = max(max_images_allowed() - count($remainingImages), 0);
    $newImages = process_image_uploads($_FILES['images'] ?? [], $maxNewImages, $errors, $id);
    $totalImages = count($remainingImages) + count($newImages);
    if ($totalImages === 0) {
        $errors[] = 'Mantenha ao menos uma imagem do anúncio.';
    }
    if ($totalImages > max_images_allowed()) {
        $errors[] = 'Excedeu o limite de ' . max_images_allowed() . ' imagens.';
    }

    // ✅ VALIDAÇÃO DE CONFLITO DE VÍDEO (Camada 3 - Servidor)
    $videoSource = strtolower(trim($_POST['video_source'] ?? 'upload'));
    $removeVideoFlag = isset($_POST['remove_video']) && $_POST['remove_video'] === '1';
    $hasNewVideoFile = !empty($_FILES['video']['name']);
    $hasInstagramLink = !empty(trim($_POST['video_instagram_link'] ?? ''));
    
    // Se tem vídeo antigo e NÃO está marcado para remover
    if ($video && !$removeVideoFlag) {
        // Verifica se está tentando adicionar novo vídeo (upload OU instagram)
        if ($hasNewVideoFile || $hasInstagramLink) {
            $errors[] = '⚠️ Você tem um vídeo no anúncio. Para adicionar um novo, primeiro remova o anterior.';
        }
    }
    
    // Se está tentando adicionar ambos (upload E instagram ao mesmo tempo)
    if ($hasNewVideoFile && $hasInstagramLink) {
        $errors[] = 'Escolha apenas UM: fazer upload de vídeo OU compartilhar link do Instagram. Não ambos.';
    }
    
    // Se tem vídeo antigo, foi marcado para remover, mas não selecionou nenhuma opção
    if ($video && $removeVideoFlag && !$hasNewVideoFile && !$hasInstagramLink && $videoSource === 'upload') {
        // OK - vídeo será removido e nenhum novo será adicionado (isso é permitido)
    }

    // Processar vídeo
    $videoPath = $video['filepath'] ?? null;
    $videoInstagramLink = null;
    
    if (!empty($_FILES['video']['name'])) {
        if ($isPremium && video_allowed_for_premium()) {
            $videoPath = process_video_upload($_FILES['video'], $errors, true, $id);
            if ($video && $video['filepath'] && $videoPath) {
                delete_media_file($video['filepath']);
                $del = db()->prepare('DELETE FROM ad_media WHERE id = :id');
                $del->execute([':id' => (int)$video['id']]);
            }
        } else {
            $errors[] = 'Vídeo permitido apenas para usuários premium.';
        }
    } elseif (!empty(trim($_POST['video_instagram_link'] ?? ''))) {
        // ✅ Processar link do Instagram
        if ($isPremium && video_allowed_for_premium()) {
            $instagramUrl = trim($_POST['video_instagram_link']);
            // Validação básica de URL Instagram
            if (filter_var($instagramUrl, FILTER_VALIDATE_URL) && (
                str_contains($instagramUrl, 'instagram.com') ||
                str_contains($instagramUrl, 'instagr.am')
            )) {
                $videoInstagramLink = $instagramUrl;
                // Remover vídeo antigo se houver
                if ($video && $video['filepath']) {
                    delete_media_file($video['filepath']);
                    $del = db()->prepare('DELETE FROM ad_media WHERE id = :id');
                    $del->execute([':id' => (int)$video['id']]);
                }
                $videoPath = null;
            } else {
                $errors[] = 'Link do Instagram inválido. Use um link completo do post (ex: https://instagram.com/p/ABC123...).';
            }
        } else {
            $errors[] = 'Link de vídeo permitido apenas para usuários premium.';
        }
    } elseif (isset($_POST['remove_video']) && $video) {
        delete_media_file($video['filepath']);
        $del = db()->prepare('DELETE FROM ad_media WHERE id = :id');
        $del->execute([':id' => (int)$video['id']]);
        $videoPath = null;
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $now = now();
        $slug = generate_unique_ad_slug($pdo, $formData['title'], $id);

        try {
            // Montar extra_attributes baseado no tipo
            $extraAttributesUpdate = [];
            switch ($vehicleType) {
                case 'moto':
                    $extraAttributesUpdate = [
                        'estilo' => $formData['moto_style'],
                        'cilindradas' => $formData['moto_cilindradas'],
                    ];
                    break;
                case 'caminhao':
                    $extraAttributesUpdate = [
                        'tracao' => $formData['truck_tracao'],
                        'carroceria' => $formData['truck_carroceria'],
                    ];
                    break;
                case 'outros':
                    $extraAttributesUpdate = [
                        'tipo' => $formData['other_type'],
                        'detalhes' => $formData['other_details'],
                    ];
                    break;
            }

            $update = $pdo->prepare('UPDATE ads SET
                title = :title,
                slug = :slug,
                description = :description,
                price = :price,
                manufacture_year = :manufacture_year,
                model_year = :model_year,
                mileage = :mileage,
                transmission = :transmission,
                fuel_type = :fuel_type,
                color = :color,
                doors = :doors,
                plate_last_digit = :plate_last_digit,
                extra_attributes = :extra_attributes,
                status = "pending",
                reason_rejected = NULL,
                approved_at = NULL,
                updated_at = :updated_at
            WHERE id = :id AND user_id = :user_id');

            $update->execute([
                ':title' => $formData['title'],
                ':slug' => $slug,
                ':description' => $formData['description'],
                ':price' => $formData['price'],
                ':manufacture_year' => $manufactureYear,
                ':model_year' => $modelYear,
                ':mileage' => $mileage,
                ':transmission' => $formData['transmission'],
                ':fuel_type' => $formData['fuel_type'],
                ':color' => $formData['color'],
                ':doors' => $formData['doors'] !== '' ? (int)$formData['doors'] : null,
                ':plate_last_digit' => $formData['plate_last_digit'],
                ':extra_attributes' => $extraAttributesUpdate ? json_encode($extraAttributesUpdate, JSON_UNESCAPED_UNICODE) : null,
                ':updated_at' => $now,
                ':id' => $id,
                ':user_id' => (int)$user['id'],
            ]);

            // Deletar imagens marcadas
            if ($removeIds) {
                $getRemoved = db()->prepare('SELECT filepath FROM ad_media WHERE id = :id AND ad_id = :ad_id');
                $delete = db()->prepare('DELETE FROM ad_media WHERE id = :id');
                foreach ($removeIds as $removeId) {
                    $getRemoved->execute([':id' => $removeId, ':ad_id' => $id]);
                    $file = $getRemoved->fetchColumn();
                    if ($file) {
                        delete_media_file($file);
                        $delete->execute([':id' => $removeId]);
                    }
                }
            }

            // Inserir novas imagens
            if ($newImages) {
                $mediaInsert = $pdo->prepare('INSERT INTO ad_media (ad_id, type, sort_order, filepath, created_at) VALUES (:ad_id, :type, :sort_order, :filepath, :created_at)');
                $startingOrder = count($remainingImages);
                foreach ($newImages as $index => $path) {
                    $mediaInsert->execute([
                        ':ad_id' => $id,
                        ':type' => 'image',
                        ':sort_order' => $startingOrder + $index,
                        ':filepath' => $path,
                        ':created_at' => $now,
                    ]);
                }
            }

            // Inserir novo vídeo
            if ($videoPath && (!$video || $videoPath !== $video['filepath'])) {
                $insertVideo = $pdo->prepare('INSERT INTO ad_media (ad_id, type, sort_order, filepath, created_at) VALUES (:ad_id, "video", :sort_order, :filepath, :created_at)');
                $insertVideo->execute([
                    ':ad_id' => $id,
                    ':sort_order' => 0,
                    ':filepath' => $videoPath,
                    ':created_at' => $now,
                ]);
            }
            
            // Inserir link Instagram se fornecido
            if ($videoInstagramLink) {
                // Armazenar com prefixo 'instagram:' no filepath para compatibilidade
                // Se tiver coluna external_url, usa ela; senão usa filepath com prefixo
                try {
                    $checkColumn = $pdo->query('SHOW COLUMNS FROM ad_media WHERE Field = "external_url"')->fetch();
                    if ($checkColumn) {
                        $insertInstagram = $pdo->prepare('INSERT INTO ad_media (ad_id, type, sort_order, filepath, external_url, created_at) VALUES (:ad_id, "video", :sort_order, "", :external_url, :created_at)');
                        $insertInstagram->execute([
                            ':ad_id' => $id,
                            ':sort_order' => 0,
                            ':external_url' => $videoInstagramLink,
                            ':created_at' => $now,
                        ]);
                    } else {
                        // Fallback para compatibilidade
                        $insertInstagram = $pdo->prepare('INSERT INTO ad_media (ad_id, type, sort_order, filepath, created_at) VALUES (:ad_id, "video", :sort_order, :filepath, :created_at)');
                        $insertInstagram->execute([
                            ':ad_id' => $id,
                            ':sort_order' => 0,
                            ':filepath' => 'instagram:' . $videoInstagramLink,
                            ':created_at' => $now,
                        ]);
                    }
                } catch (Throwable $e) {
                    error_log('[editar.php] Erro ao inserir link Instagram: ' . $e->getMessage());
                }
            }

            $pdo->commit();
            record_audit((int)$user['id'], 'ad_updated', 'ads', $id, ['title' => $formData['title']]);
            flash_add('success', 'Anúncio atualizado! Ele retornará para análise da moderação.');
            redirect('/anuncios/meus.php');
        } catch (Throwable $exception) {
            $pdo->rollBack();

            if (!empty($newImages ?? [])) {
                foreach ($newImages as $path) {
                    delete_media_file($path);
                }
            }
            if (!empty($videoPath ?? null) && (!$video || $videoPath !== $video['filepath'])) {
                delete_media_file($videoPath);
            }

            if (!$errors) {
                error_log(sprintf(
                    '[edit_ad] Falha ao atualizar anúncio para usuário %d: %s | Stack: %s',
                    (int)$user['id'],
                    $exception->getMessage(),
                    $exception->getTraceAsString()
                ));
                $errors[] = 'Falha ao atualizar o anúncio. Tente novamente. Detalhes: ' . $exception->getMessage();
            }
        }
    } else {
        // Limpar arquivos se houver erros
        foreach ($newImages as $path) {
            delete_media_file($path);
        }
        if (isset($videoPath) && $videoPath && (!$video || $videoPath !== $video['filepath'])) {
            delete_media_file($videoPath);
        }
    }
}

$priceDisplay = $formData['price'] ? format_currency((float)$formData['price']) : '';

include __DIR__ . '/../partials/header.php';
?>
<section class="form-card">
    <div class="page-header-banner">
        <h2>✏️ Editar Anúncio do Veículo</h2>
        <p>Alterações retornam o anúncio para revisão. Capriche nas informações e mantenha o anúncio atrativo.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error" style="margin-top:1rem;">
            <ul style="padding-left:1.2rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate
          data-max-images="<?= max_images_allowed(); ?>"
          data-max-image-size-mb="<?= get_setting('max_image_size_mb', '2'); ?>"
          data-max-video-size-mb="<?= get_setting('max_video_size_mb', '30'); ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

        <!-- Campos hidden para validar integridade dos campos travados -->
        <input type="hidden" name="original_vehicle_type" value="<?= e($originalVehicleType); ?>">
        <input type="hidden" name="original_brand" value="<?= e($originalBrand); ?>">
        <input type="hidden" name="original_model" value="<?= e($originalModel); ?>">
        <input type="hidden" name="original_city" value="<?= e($originalCity); ?>">
        <input type="hidden" name="original_state" value="<?= e($originalState); ?>">

        <!-- SEÇÃO 1: Dados Básicos (alguns travados) -->
        <fieldset>
            <legend style="font-size:1.1rem;font-weight:600;margin-bottom:1rem;">📋 Dados Básicos</legend>
            
            <div class="grid-2" style="margin-bottom:1.5rem;">
                <div class="input-group">
                    <label for="vehicle_type">Tipo de veículo *</label>
                    <select id="vehicle_type" name="vehicle_type" disabled style="opacity:0.6;cursor:not-allowed;background-color:#f5f5f5;" data-vehicle-type="<?= e($formData['vehicle_type']); ?>">
                        <option selected><?= e($vehicleTypes[$formData['vehicle_type']] ?? 'Carro'); ?></option>
                    </select>
                    <p class="field-hint" style="color:#666;">Este campo não pode ser alterado. Entre em contato com o suporte se precisar fazer isso.</p>
                </div>
                <div class="input-group">
                    <label for="brand">Marca *</label>
                    <input type="text" id="brand" name="brand" value="<?= e($formData['brand']); ?>" disabled style="opacity:0.6;cursor:not-allowed;background-color:#f5f5f5;">
                    <p class="field-hint" style="color:#666;">Este campo não pode ser alterado. Entre em contato com o suporte se precisar fazer isso.</p>
                </div>
                <div class="input-group">
                    <label for="model">Modelo *</label>
                    <input type="text" id="model" name="model" value="<?= e($formData['model']); ?>" disabled style="opacity:0.6;cursor:not-allowed;background-color:#f5f5f5;">
                    <p class="field-hint" style="color:#666;">Este campo não pode ser alterado. Entre em contato com o suporte se precisar fazer isso.</p>
                </div>
                <div class="input-group">
                    <label for="title">Título do anúncio *</label>
                    <input type="text" id="title" name="title" value="<?= e($formData['title']); ?>" required>
                </div>
            </div>
        </fieldset>

        <!-- SEÇÃO 2: Detalhes Técnicos -->
        <fieldset>
            <legend style="font-size:1.1rem;font-weight:600;margin-bottom:1rem;margin-top:1.5rem;">⚙️ Detalhes Técnicos</legend>
            
            <div class="grid-2" style="margin-bottom:1.5rem;">
                <div class="input-group">
                    <label for="manufacture_year">Ano de fabricação *</label>
                    <input type="number" id="manufacture_year" name="manufacture_year" min="<?= $minYear; ?>" max="<?= $maxYear; ?>" value="<?= e($formData['manufacture_year']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="model_year">Ano do modelo *</label>
                    <input type="number" id="model_year" name="model_year" min="<?= $minYear; ?>" max="<?= $maxYear; ?>" value="<?= e($formData['model_year']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="mileage">Quilometragem *</label>
                    <input type="number" id="mileage" name="mileage" min="0" value="<?= e($formData['mileage']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="color">Cor *</label>
                    <select id="color" name="color" required>
                        <option value="">Selecione</option>
                        <?php foreach ($colorOptions as $color): ?>
                            <option value="<?= e($color); ?>" <?= $formData['color'] === $color ? 'selected' : ''; ?>><?= e($color); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="transmission">Câmbio *</label>
                    <select id="transmission" name="transmission" required>
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
                </div>
                <div class="input-group">
                    <label for="fuel_type">Combustível *</label>
                    <select id="fuel_type" name="fuel_type" required>
                        <option value="">Selecione</option>
                        <?php foreach ($fuelOptions as $fuel): ?>
                            <option value="<?= e($fuel); ?>" <?= $formData['fuel_type'] === $fuel ? 'selected' : ''; ?>><?= e($fuel); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="doors">Portas <?= $formData['vehicle_type'] === 'moto' ? '(opcional)' : '*'; ?></label>
                    <select id="doors" name="doors" data-required-for="carro,caminhao">
                        <option value="">Selecione</option>
                        <?php foreach ($doorOptions as $door): ?>
                            <option value="<?= e($door); ?>" <?= $formData['doors'] === $door ? 'selected' : ''; ?>><?= e($door); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="plate_last_digit">Final da placa *</label>
                    <select id="plate_last_digit" name="plate_last_digit" required>
                        <option value="">Selecione</option>
                        <?php foreach ($plateDigits as $digit): ?>
                            <option value="<?= $digit; ?>" <?= $formData['plate_last_digit'] === (string)$digit ? 'selected' : ''; ?>><?= $digit; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Campos dinâmicos por tipo de veículo -->
            <?php if ($formData['vehicle_type'] === 'moto'): ?>
                <div style="margin-bottom:1.5rem;">
                    <h4 style="font-size:0.95rem;font-weight:600;margin-bottom:0.8rem;">🏍️ Especificações da Moto</h4>
                    <div class="grid-2">
                        <div class="input-group">
                            <label for="moto_style">Estilo *</label>
                            <select id="moto_style" name="moto_style" required>
                                <option value="">Selecione</option>
                                <?php foreach ($motoStyles as $style): ?>
                                    <option value="<?= e($style); ?>" <?= $formData['moto_style'] === $style ? 'selected' : ''; ?>><?= e($style); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="moto_cilindradas">Cilindradas *</label>
                            <select id="moto_cilindradas" name="moto_cilindradas" required>
                                <option value="">Selecione</option>
                                <?php foreach ($motoCilindradas as $cc): ?>
                                    <option value="<?= e($cc); ?>" <?= $formData['moto_cilindradas'] === $cc ? 'selected' : ''; ?>><?= e($cc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php elseif ($formData['vehicle_type'] === 'caminhao'): ?>
                <div style="margin-bottom:1.5rem;">
                    <h4 style="font-size:0.95rem;font-weight:600;margin-bottom:0.8rem;">🚛 Especificações do Caminhão</h4>
                    <div class="grid-2">
                        <div class="input-group">
                            <label for="truck_tracao">Tração *</label>
                            <select id="truck_tracao" name="truck_tracao" required>
                                <option value="">Selecione</option>
                                <?php foreach ($truckTracao as $option): ?>
                                    <option value="<?= e($option); ?>" <?= $formData['truck_tracao'] === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="truck_carroceria">Carroceria *</label>
                            <select id="truck_carroceria" name="truck_carroceria" required>
                                <option value="">Selecione</option>
                                <?php foreach ($truckCarroceria as $option): ?>
                                    <option value="<?= e($option); ?>" <?= $formData['truck_carroceria'] === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php elseif ($formData['vehicle_type'] === 'outros'): ?>
                <div style="margin-bottom:1.5rem;">
                    <h4 style="font-size:0.95rem;font-weight:600;margin-bottom:0.8rem;">📦 Especificações do Veículo</h4>
                    <div class="grid-2">
                        <div class="input-group">
                            <label for="other_type">Tipo de veículo *</label>
                            <select id="other_type" name="other_type" required>
                                <option value="">Selecione</option>
                                <?php foreach ($otherTypes as $option): ?>
                                    <option value="<?= e($option); ?>" <?= $formData['other_type'] === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="other_details">Detalhes *</label>
                            <input type="text" id="other_details" name="other_details" value="<?= e($formData['other_details']); ?>" placeholder="Ex.: Reboque para transporte de motos" required>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </fieldset>

        <!-- SEÇÃO 3: Localização (travada) -->
        <fieldset>
            <legend style="font-size:1.1rem;font-weight:600;margin-bottom:1rem;margin-top:1.5rem;">📍 Localização</legend>
            
            <div class="grid-2" style="margin-bottom:1.5rem;">
                <div class="input-group">
                    <label for="state">Estado *</label>
                    <select id="state" name="state" disabled style="opacity:0.6;cursor:not-allowed;background-color:#f5f5f5;">
                        <option selected><?= e($formData['state']); ?></option>
                    </select>
                    <p class="field-hint" style="color:#666;">Este campo não pode ser alterado. Entre em contato com o suporte se precisar fazer isso.</p>
                </div>
                <div class="input-group">
                    <label for="city">Cidade *</label>
                    <input type="text" id="city" name="city" value="<?= e($formData['city']); ?>" disabled style="opacity:0.6;cursor:not-allowed;background-color:#f5f5f5;">
                    <p class="field-hint" style="color:#666;">Este campo não pode ser alterado. Entre em contato com o suporte se precisar fazer isso.</p>
                </div>
            </div>
        </fieldset>

        <!-- SEÇÃO 4: Preço e Descrição -->
        <fieldset>
            <legend style="font-size:1.1rem;font-weight:600;margin-bottom:1rem;margin-top:1.5rem;">💰 Preço e Descrição</legend>
            
            <div class="grid-2" style="margin-bottom:1.5rem;">
                <div class="input-group">
                    <label for="price_display">Preço (R$) *</label>
                    <input type="text" id="price_display" name="price_display" value="<?= e($priceDisplay); ?>" data-price-display required>
                    <input type="hidden" name="price" id="price" value="<?= e($formData['price']); ?>" data-price-hidden>
                    <p class="field-hint" data-feedback-for="price_display"></p>
                    <small style="color: #666; font-size: 0.9em;">Digite apenas números (ex: 1000 = R$ 1.000,00)</small>
                </div>
            </div>

            <div class="input-group" style="margin-bottom:1.5rem;">
                <label for="description">Descrição detalhada *</label>
                <textarea id="description" name="description" rows="6" required><?= e($formData['description']); ?></textarea>
                <p class="field-hint" data-feedback-for="description"></p>
            </div>
        </fieldset>

        <!-- SEÇÃO 5: Mídia -->
        <fieldset>
            <legend style="font-size:1.1rem;font-weight:600;margin-bottom:1rem;margin-top:1.5rem;">📷 Imagens e Vídeo</legend>
            
            <h4 style="margin-bottom:0.8rem;">Imagens atuais</h4>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem;">
                <?php foreach ($images as $img): ?>
                    <div style="position:relative;border-radius:8px;overflow:hidden;border:2px solid #e0e0e0;">
                        <img src="<?= e('/' . ltrim($img['filepath'], '/')); ?>" alt="Imagem do anúncio" style="width:100%;height:120px;object-fit:cover;">
                        <label style="position:absolute;top:10px;left:10px;background:#fff;padding:0.3rem 0.6rem;border-radius:999px;font-size:0.85rem;cursor:pointer;">
                            <input type="checkbox" name="remove_images[]" value="<?= (int)$img['id']; ?>" style="cursor:pointer;"> Remover
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="input-group" style="margin-bottom:1.5rem;">
                <label for="images">Adicionar novas imagens</label>
                <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                <p class="field-hint">Máximo <?= max_images_allowed() - count($images); ?> imagens adicionais. (Máx <?= get_setting('max_image_size_mb', '2'); ?> MB cada)</p>
            </div>

            <?php if ($isPremium && video_allowed_for_premium()): ?>
                <h4 style="margin-bottom:0.8rem;">Vídeo</h4>
                <?php if ($video): ?>
                    <div style="margin-bottom:1rem;">
                        <video src="<?= e('/' . ltrim($video['filepath'], '/')); ?>" controls style="max-width:100%;border-radius:12px;max-height:200px;"></video>
                        <div style="margin-top:0.6rem;">
                            <label><input type="checkbox" name="remove_video" value="1" style="cursor:pointer;"> Remover vídeo</label>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- TAB 1: Anexar Vídeo -->
                <div style="margin-bottom: 1.5rem; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; overflow: hidden;">
                    <label style="display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.75rem; cursor: pointer; word-break: break-word;">
                        <input type="radio" name="video_source" value="upload" <?= ($formData['video_source'] ?? 'upload') === 'upload' ? 'checked' : ''; ?> style="flex-shrink: 0; margin-top: 0.25rem;">
                        <strong style="word-break: break-word;">📹 Anexar vídeo do seu dispositivo</strong>
                    </label>
                    <div id="videoUploadSection" style="display: <?= ($formData['video_source'] ?? 'upload') === 'upload' ? 'block' : 'none'; ?>; margin-left: calc(1rem + 0.5rem + 24px); overflow: hidden;">
                        <p style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; word-break: break-word;">
                            <strong>Formatos:</strong> MP4, MOV (iPhone), AVI<br>
                            <strong>Limite:</strong> até <?= e(get_setting('max_video_size_mb', '30')); ?>MB<br>
                            <strong>Tempo:</strong> ~2-5 min (depende da conexão)
                        </p>
                        <div id="videoUploadInputWrapper" style="position: relative;">
                            <input type="file" id="videoFileInput" name="video" accept="video/mp4,video/quicktime,video/x-msvideo,video/avi" style="width: 100%; max-width: 100%; overflow-x: hidden;">
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Link do Instagram -->
                <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem; overflow: hidden;">
                    <label style="display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.75rem; cursor: pointer; word-break: break-word;">
                        <input type="radio" name="video_source" value="instagram" <?= ($formData['video_source'] ?? 'upload') === 'instagram' ? 'checked' : ''; ?> style="flex-shrink: 0; margin-top: 0.25rem;">
                        <strong style="word-break: break-word;">🎬 Compartilhar link do Instagram</strong>
                    </label>
                    <div id="instagramLinkSection" style="display: <?= ($formData['video_source'] ?? 'upload') === 'instagram' ? 'block' : 'none'; ?>; margin-left: calc(1rem + 0.5rem + 24px); overflow: hidden;">
                        <p style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; word-break: break-word;">
                            Cole o link do post do Instagram com seu vídeo (ex: https://instagram.com/p/ABC123...)<br>
                            <span style="color: #999;">O vídeo será exibido diretamente do Instagram no anúncio</span>
                        </p>
                        <input type="url" name="video_instagram_link" placeholder="https://instagram.com/p/..." value="<?= e($formData['video_instagram_link'] ?? ''); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-size: 0.9rem; box-sizing: border-box; max-width: 100%; overflow-x: auto;">
                    </div>
                </div>
            <?php else: ?>
                <h4 style="margin-bottom:0.8rem;">Vídeo</h4>
                <div class="upload-tips__card" data-upsell>
                    <strong>Quer publicar vídeo?</strong>
                    <span>Usuários premium têm prioridade na busca, selo de verificação e podem incluir vídeo e link do Instagram.</span>
                </div>
            <?php endif; ?>
        </fieldset>

        <div style="margin-top:2rem;display:flex;gap:1rem;">
            <button type="submit" class="button" style="flex:1;">💾 Salvar Alterações</button>
            <a href="/anuncios/meus.php" class="button secondary" style="flex:1;text-align:center;text-decoration:none;">← Voltar</a>
        </div>
    </form>
</section>

<!-- Configuração global para compressão de imagens e vídeos -->
<script>
window.uploadConfig = {
    maxImageSizeMB: <?= (int)get_setting('max_image_size_mb', '2'); ?>,
    maxVideoSizeMB: <?= (int)get_setting('max_video_size_mb', '30'); ?>,
    maxDocumentSizeMB: 10
};
</script>

<!-- Biblioteca de conversão HEIC e compressão -->
<?php
    $heicConverterVersion = file_exists(__DIR__ . '/../assets/js/heic-converter.js') ? 
        md5_file(__DIR__ . '/../assets/js/heic-converter.js') : time();
    $compressorVersion = file_exists(__DIR__ . '/../assets/js/image-compressor.js') ? 
        md5_file(__DIR__ . '/../assets/js/image-compressor.js') : time();
    $editAdVersion = file_exists(__DIR__ . '/../assets/js/create-ad.js') ? 
        md5_file(__DIR__ . '/../assets/js/create-ad.js') : time();
?>
<script defer src="/assets/js/heic-converter.js?v=<?= urlencode($heicConverterVersion); ?>"></script>
<script defer src="/assets/js/image-compressor.js?v=<?= urlencode($compressorVersion); ?>"></script>
<script defer src="/assets/js/create-ad.js?v=<?= urlencode($editAdVersion); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Opções de câmbio por tipo de veículo (sincronizado com assets/js/autocomplete.js)
    const tiposCambio = {
        carro: ["Manual", "Automático", "Automatizado", "CVT", "Dualogic", "Tiptronic", "DSG"],
        moto: ["Manual", "Automático", "Semiautomático", "CVT"],
        caminhao: ["Manual", "Automatizado", "Automático", "Híbrido"],
        outros: ["Manual", "Automático"]
    };

    // Função para popular opções de câmbio baseado no tipo
    function atualizarOpcoesCambio(tipo) {
        const transmissionField = document.getElementById('transmission');
        if (!transmissionField) return;

        const valorAtual = transmissionField.value;
        transmissionField.innerHTML = '<option value="">Selecione</option>';

        const opcoesCambio = tiposCambio[tipo] || tiposCambio.outros;
        opcoesCambio.forEach((opcao) => {
            const option = document.createElement('option');
            option.value = opcao;
            option.textContent = opcao;
            if (valorAtual === opcao) {
                option.selected = true;
            }
            transmissionField.appendChild(option);
        });
    }

    // Carregar opções de câmbio para o tipo de veículo atual
    // Lê do atributo data-vehicle-type porque o campo está desabilitado
    const vehicleTypeSelect = document.getElementById('vehicle_type');
    const vehicleType = vehicleTypeSelect?.dataset.vehicleType || 'carro';
    atualizarOpcoesCambio(vehicleType);

    // Formatador de preço sem loop infinito
    const priceDisplay = document.querySelector('[data-price-display]');
    const priceHidden = document.querySelector('[data-price-hidden]');
    
    if (priceDisplay && priceHidden) {
        priceDisplay.addEventListener('input', function(e) {
            // Remover tudo que não é número
            let value = e.target.value.replace(/\D/g, '');
            
            // Se vazio, deixa vazio
            if (!value) {
                e.target.value = '';
                priceHidden.value = '';
                return;
            }
            
            // Converter para número (centavos para reais)
            let numValue = parseInt(value, 10);
            let realValue = (numValue / 100).toFixed(2);
            
            // Formatar para exibição: 1000000 -> 10.000,00
            let [intPart, decPart] = realValue.split('.');
            intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            
            // Atualizar ambos os campos
            e.target.value = intPart + ',' + decPart;
            priceHidden.value = realValue;
        });
        
        // Inicializar com valor atual se houver
        if (priceDisplay.value) {
            priceDisplay.dispatchEvent(new Event('input'));
        }
    }

    // ✅ Controle de vídeo antigo (checkbox "Remover vídeo")
    const removeVideoCheckbox = document.querySelector('input[name="remove_video"]');
    const videoSourceRadios = document.querySelectorAll('input[name="video_source"]');
    const videoUploadSection = document.getElementById('videoUploadSection');
    const instagramLinkSection = document.getElementById('instagramLinkSection');
    const videoFileInput = document.getElementById('videoFileInput');
    const videoUploadInputWrapper = document.getElementById('videoUploadInputWrapper');
    const instagramLinkInput = document.querySelector('input[name="video_instagram_link"]');
    
    // Função para atualizar estado dos radio buttons baseado no checkbox "Remover"
    function updateVideoFieldsState() {
        const videoRemovalChecked = removeVideoCheckbox?.checked ?? false;
        const hasOldVideo = removeVideoCheckbox !== null; // Se elemento existe, tem vídeo antigo
        
        if (hasOldVideo && !videoRemovalChecked) {
            // Vídeo antigo EXISTS e NÃO está marcado para remover
            // ➜ Desabilita os radio buttons
            videoSourceRadios.forEach(radio => {
                radio.disabled = true;
                radio.parentElement.style.opacity = '0.5';
                radio.parentElement.style.pointerEvents = 'none';
            });
            
            // Desabilita os inputs com overlay visual
            if (videoFileInput) {
                videoFileInput.disabled = true;
                videoFileInput.style.opacity = '0.5';
                videoFileInput.style.cursor = 'not-allowed';
            }
            if (videoUploadInputWrapper) {
                videoUploadInputWrapper.style.opacity = '0.5';
                videoUploadInputWrapper.style.pointerEvents = 'none';
            }
            if (instagramLinkInput) {
                instagramLinkInput.disabled = true;
                instagramLinkInput.style.opacity = '0.5';
            }
            
            // Mostra aviso em tempo real
            let warningDiv = document.getElementById('video-conflict-warning');
            if (!warningDiv) {
                warningDiv = document.createElement('div');
                warningDiv.id = 'video-conflict-warning';
                warningDiv.style.cssText = 'background: #ffebee; border: 2px solid #f44336; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;';
                warningDiv.innerHTML = '⚠️ <strong>Para adicionar um novo vídeo, primeiro remova o anterior</strong>';
                removeVideoCheckbox.parentElement.parentElement.insertAdjacentElement('afterend', warningDiv);
            }
            warningDiv.style.display = 'block';
        } else {
            // Vídeo antigo foi removido OU não existe
            // ➜ Habilita os radio buttons
            videoSourceRadios.forEach(radio => {
                radio.disabled = false;
                radio.parentElement.style.opacity = '1';
                radio.parentElement.style.pointerEvents = 'auto';
            });
            
            // Habilita os inputs
            if (videoFileInput) {
                videoFileInput.disabled = false;
                videoFileInput.style.opacity = '1';
                videoFileInput.style.cursor = 'pointer';
            }
            if (videoUploadInputWrapper) {
                videoUploadInputWrapper.style.opacity = '1';
                videoUploadInputWrapper.style.pointerEvents = 'auto';
            }
            if (instagramLinkInput) {
                instagramLinkInput.disabled = false;
                instagramLinkInput.style.opacity = '1';
            }
            
            // Esconde aviso
            const warningDiv = document.getElementById('video-conflict-warning');
            if (warningDiv) warningDiv.style.display = 'none';
        }
    }
    
    // Listener no checkbox "Remover vídeo"
    if (removeVideoCheckbox) {
        removeVideoCheckbox.addEventListener('change', function() {
            updateVideoFieldsState();
        });
        
        // Inicializa estado na carga
        updateVideoFieldsState();
    }
    
    // ✅ Toggle video_source (upload vs instagram) + validação
    if (videoSourceRadios.length > 0) {
        videoSourceRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'upload') {
                    if (videoUploadSection) videoUploadSection.style.display = 'block';
                    if (instagramLinkSection) instagramLinkSection.style.display = 'none';
                    // Desabilita input Instagram
                    if (instagramLinkInput) {
                        instagramLinkInput.disabled = true;
                        instagramLinkInput.value = '';
                    }
                    if (videoFileInput) videoFileInput.disabled = false;
                } else if (this.value === 'instagram') {
                    if (videoUploadSection) videoUploadSection.style.display = 'none';
                    if (instagramLinkSection) instagramLinkSection.style.display = 'block';
                    // Desabilita input Vídeo
                    if (videoFileInput) {
                        videoFileInput.disabled = true;
                        videoFileInput.value = '';
                    }
                    if (instagramLinkInput) instagramLinkInput.disabled = false;
                }
            });
        });
        
        // Simula mudança inicial para aplicar estado correto
        const checkedRadio = document.querySelector('input[name="video_source"]:checked');
        if (checkedRadio) {
            checkedRadio.dispatchEvent(new Event('change'));
        }
    }

    // ✅ Compressão de vídeo (mesmo que em criar.php)
    if (videoFileInput && typeof window.compressVideoForUpload === 'function') {
        videoFileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            // Chamar função de compressão global
            window.compressVideoForUpload(file, this, (progress) => {
                console.log(`Vídeo validado: ${progress}%`);
            });
        });
    }

    // ✅ Compressão de imagens (com global function)
    const imagesInput = document.querySelector('input[name="images[]"]');
    if (imagesInput && typeof window.compressImages === 'function') {
        imagesInput.addEventListener('change', function() {
            const files = Array.from(this.files);
            if (files.length === 0) return;
            
            // Chamar função de compressão global
            window.compressImages(files, 
                (progress) => {
                    console.log(`Compressão: ${progress}%`);
                },
                (compressedFiles) => {
                    console.log(`✅ ${compressedFiles.length} imagem(s) comprimida(s)`);
                }
            );
        });
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php';