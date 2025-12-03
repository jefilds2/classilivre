<?php
declare(strict_types=1);

// anuncios/meus.php - Portal "Minha Conta" para gestao de anuncios, perfil e servicos.

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../servicos/service_renewal_helpers.php';

require_login();
ensure_user_not_blocked();

// Funções de validação
function validarCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/\D/', '', $cnpj);
    if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }
    $b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0, $n = 0; $i < 12; $n += $cnpj[$i] * $b[++$i]);
    if ($cnpj[12] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
        return false;
    }
    for ($i = 0, $n = 0; $i <= 12; $n += $cnpj[$i] * $b[$i++]);
    if ($cnpj[13] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
        return false;
    }
    // Verificar na ReceitaWS
    return verificarCNPJReceita($cnpj);
}

function verificarCNPJReceita($cnpj) {
    $url = "https://www.receitaws.com.br/v1/cnpj/" . $cnpj;
    
    // ⚠️ TIMEOUT REDUZIDO para 2 segundos (era 10) para evitar travamentos
    // Se a API externo falhar, aceitamos o CNPJ como válido (não bloqueamos o usuário)
    $context = stream_context_create([
        "http" => [
            "timeout" => 2,  // ← REDUZIDO de 10 para 2 segundos
            "user_agent" => "Mozilla/5.0 (compatible; PHP script)"
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        // Se a API falhar (timeout/erro), aceitar como válido para não bloquear criação
        // Logs serão analisados depois pelo admin
        error_log("[CNPJ_VALIDATION] Falha ao consultar ReceitaWS para CNPJ: $cnpj (pode ser timeout ou API indisponível)");
        return true;  // Permitir criação mesmo com falha da API
    }
    
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] === 'ERROR') {
        error_log("[CNPJ_VALIDATION] CNPJ inválido na ReceitaWS: $cnpj");
        return false;
    }
    
    return isset($data['cnpj']) && $data['cnpj'] !== '';
}

function ensureStoreCustomizationSchema(PDO $pdo): void {
    static $schemaChecked = false;
    if ($schemaChecked) {
        return;
    }

    $schemaChecked = true;

    try {
        $columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'store_theme_config'");
        if (!$columnCheck || $columnCheck->rowCount() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN store_theme_config LONGTEXT NULL AFTER about");
            error_log('[meus.php] Coluna store_theme_config criada automaticamente.');
        }
    } catch (Throwable $schemaError) {
        error_log('[meus.php] Nao foi possivel garantir coluna store_theme_config: ' . $schemaError->getMessage());
    }

    try {
        $instagramCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'instagram_url'");
        if (!$instagramCheck || $instagramCheck->rowCount() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN instagram_url VARCHAR(500) NULL DEFAULT NULL AFTER store_theme_config");
            error_log('[meus.php] Coluna instagram_url criada automaticamente.');
        }
    } catch (Throwable $instagramError) {
        error_log('[meus.php] Nao foi possivel garantir coluna instagram_url: ' . $instagramError->getMessage());
    }

    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'store_metrics'");
        if (!$tableCheck || $tableCheck->rowCount() === 0) {
            $createTableSql = <<<SQL
CREATE TABLE IF NOT EXISTS store_metrics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id INT UNSIGNED NOT NULL,
    ad_id INT UNSIGNED NULL,
    event_type VARCHAR(48) NOT NULL,
    viewer_id INT UNSIGNED NULL,
    metadata LONGTEXT NULL,
    user_agent VARCHAR(255) NULL,
    ip_address VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_store_created (store_id, created_at),
    INDEX idx_store_event (store_id, event_type),
    INDEX idx_ad_event (ad_id, event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
            $pdo->exec($createTableSql);
            error_log('[meus.php] Tabela store_metrics criada automaticamente.');
        }
    } catch (Throwable $schemaError) {
        error_log('[meus.php] Nao foi possivel garantir tabela store_metrics: ' . $schemaError->getMessage());
    }
}

/**
 * ===== FUNÇÃO REMOVIDA: ensureServiceSuggestionSchema =====
 * Tabela descontinuada - service_custom_suggestions foi removida
 * Data: 29/10/2025
 */
/*
function ensureServiceSuggestionSchema(PDO $pdo): void
{
    static $suggestionSchemaChecked = false;
    if ($suggestionSchemaChecked) {
        return;
    }

    $suggestionSchemaChecked = true;

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS service_custom_suggestions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(150) NOT NULL,
                slug VARCHAR(180) NOT NULL UNIQUE,
                usage_count INT UNSIGNED NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_usage (usage_count, last_used_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $schemaError) {
        error_log('[meus.php] Nao foi possivel garantir tabela service_custom_suggestions: ' . $schemaError->getMessage());
    }
}
*/

function slugify_service_label(string $label): string
{
    $normalized = trim($label);
    if ($normalized === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $normalized = mb_strtolower($normalized, 'UTF-8');
    } else {
        $normalized = strtolower($normalized);
    }

    if (function_exists('transliterator_transliterate')) {
        $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII', $normalized);
        if ($transliterated !== false) {
            $normalized = $transliterated;
        }
    } else {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $normalized);
        if ($converted !== false) {
            $normalized = $converted;
        }
    }

    $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized);
    $normalized = trim((string)$normalized, '-');

    if ($normalized === '') {
        return substr(md5($label), 0, 12);
    }

    return $normalized;
}

function process_service_creation(
    PDO $pdo,
    array &$serviceFormData,
    array $serviceCategories,
    array $stateOptions,
    array &$serviceErrors,
    array $user
): ?string {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $serviceErrors[] = 'Token invalido. Recarregue a pagina.';
        return null;
    }

    $serviceFormData['name'] = trim((string)($_POST['service_name'] ?? ''));
    $serviceFormData['category'] = (string)($_POST['service_category'] ?? '');
    $serviceFormData['custom_category'] = trim((string)($_POST['service_custom_category'] ?? ''));
    $serviceFormData['description'] = trim((string)($_POST['service_description'] ?? ''));
    $serviceFormData['phone'] = preg_replace('/\D+/', '', (string)($_POST['service_phone'] ?? ''));
    $serviceFormData['whatsapp'] = preg_replace('/\D+/', '', (string)($_POST['service_whatsapp'] ?? ''));
    $serviceFormData['email'] = trim((string)($_POST['service_email'] ?? ''));
    $serviceDocType = strtolower((string)($_POST['service_document_type'] ?? $serviceFormData['document_type']));
    $serviceFormData['document_type'] = in_array($serviceDocType, ['cpf', 'cnpj'], true) ? $serviceDocType : 'cpf';
    $serviceFormData['document_number'] = preg_replace('/\D+/', '', (string)($_POST['service_document_number'] ?? ''));
    $serviceFormData['show_document'] = isset($_POST['service_show_document']);
    $serviceFormData['address_street'] = trim((string)($_POST['service_address_street'] ?? ''));
    $serviceFormData['address_number'] = trim((string)($_POST['service_address_number'] ?? ''));
    $serviceFormData['address_complement'] = trim((string)($_POST['service_address_complement'] ?? ''));
    $serviceFormData['address_neighborhood'] = trim((string)($_POST['service_address_neighborhood'] ?? ''));
    $serviceFormData['address_city'] = trim((string)($_POST['service_address_city'] ?? ''));
    $serviceFormData['address_state'] = strtoupper(substr(trim((string)($_POST['service_address_state'] ?? '')), 0, 2));
    $serviceFormData['address_postal_code'] = preg_replace('/\D+/', '', (string)($_POST['service_address_postal_code'] ?? ''));

    if ($serviceFormData['name'] === '' || mb_strlen($serviceFormData['name']) < 3) {
        $serviceErrors[] = 'Informe o nome do servi&ccedil;o.';
    }
    if (!array_key_exists($serviceFormData['category'], $serviceCategories)) {
        $serviceErrors[] = 'Selecione uma categoria valida.';
    }
    if ($serviceFormData['category'] === 'custom' && $serviceFormData['custom_category'] === '') {
        $serviceErrors[] = 'Descreva a categoria personalizada.';
    }
    if ($serviceFormData['phone'] === '' && $serviceFormData['whatsapp'] === '') {
        $serviceErrors[] = 'Informe ao menos um telefone ou WhatsApp.';
    }
    if ($serviceFormData['email'] !== '' && !filter_var($serviceFormData['email'], FILTER_VALIDATE_EMAIL)) {
        $serviceErrors[] = 'Informe um email valido.';
    }
    if ($serviceFormData['document_number'] !== '') {
        if ($serviceFormData['document_type'] === 'cpf') {
            if (!validarCPF($serviceFormData['document_number'])) {
                $serviceErrors[] = 'Informe um CPF v&aacute;lido.';
            }
        } else {
            if (!validarCNPJ($serviceFormData['document_number'])) {
                $serviceErrors[] = 'Informe um CNPJ v&aacute;lido e existente na Receita Federal.';
            }
        }
    }
    if ($serviceFormData['show_document'] && $serviceFormData['document_number'] === '') {
        $serviceErrors[] = 'Informe o documento antes de marcar para exibir.';
    }
    if ($serviceFormData['address_state'] !== '' && !array_key_exists($serviceFormData['address_state'], $stateOptions)) {
        $serviceErrors[] = 'Selecione um estado valido.';
    }

    if ($serviceErrors) {
        return null;
    }

    $servicesFreeForAll = setting_value_enabled(get_setting('services_free_for_all', '0'));
    $serviceDurationDays = (int)get_setting('services_free_duration_days', '30');
    if ($serviceDurationDays <= 0) {
        $serviceDurationDays = 30;
    }

    $now = now();
    $paymentReference = 'SRV-' . strtoupper(bin2hex(random_bytes(4)));
    $status = $servicesFreeForAll ? 'active' : 'pending_payment';
    $paymentStatus = $servicesFreeForAll ? 'paid' : 'pending';
    $publishedAt = $servicesFreeForAll ? $now : null;
    $expiresAt = $servicesFreeForAll ? date('Y-m-d H:i:s', strtotime($now . " +{$serviceDurationDays} days")) : null;
    $lastRenewedAt = $servicesFreeForAll ? $now : null;

    $insert = $pdo->prepare('INSERT INTO user_service_listings (
        user_id,
        name,
        category,
        custom_category,
        description,
        phone,
        whatsapp,
        email,
        document_type,
        document_number,
        show_document,
        address_street,
        address_number,
        address_complement,
        address_neighborhood,
        address_city,
        address_state,
        address_postal_code,
        status,
        payment_status,
        payment_reference,
        auto_renewal_enabled,
        last_renewed_at,
        published_at,
        expires_at,
        created_at,
        updated_at
    ) VALUES (
        :user_id,
        :name,
        :category,
        :custom_category,
        :description,
        :phone,
        :whatsapp,
        :email,
        :document_type,
        :document_number,
        :show_document,
        :address_street,
        :address_number,
        :address_complement,
        :address_neighborhood,
        :address_city,
        :address_state,
        :address_postal_code,
        :status,
        :payment_status,
        :payment_reference,
        0,
        :last_renewed_at,
        :published_at,
        :expires_at,
        :created_at,
        :updated_at
    )');

    $insert->execute([
        ':user_id' => (int)$user['id'],
        ':name' => $serviceFormData['name'],
        ':category' => $serviceCategories[$serviceFormData['category']],
        ':custom_category' => $serviceFormData['category'] === 'custom' ? $serviceFormData['custom_category'] : null,
        ':description' => $serviceFormData['description'] !== '' ? $serviceFormData['description'] : null,
        ':phone' => $serviceFormData['phone'] !== '' ? $serviceFormData['phone'] : null,
        ':whatsapp' => $serviceFormData['whatsapp'] !== '' ? $serviceFormData['whatsapp'] : null,
        ':email' => $serviceFormData['email'] !== '' ? $serviceFormData['email'] : null,
        ':document_type' => $serviceFormData['document_type'],
        ':document_number' => $serviceFormData['document_number'] !== '' ? $serviceFormData['document_number'] : null,
        ':show_document' => $serviceFormData['show_document'] ? 1 : 0,
        ':address_street' => $serviceFormData['address_street'] !== '' ? $serviceFormData['address_street'] : null,
        ':address_number' => $serviceFormData['address_number'] !== '' ? $serviceFormData['address_number'] : null,
        ':address_complement' => $serviceFormData['address_complement'] !== '' ? $serviceFormData['address_complement'] : null,
        ':address_neighborhood' => $serviceFormData['address_neighborhood'] !== '' ? $serviceFormData['address_neighborhood'] : null,
        ':address_city' => $serviceFormData['address_city'] !== '' ? $serviceFormData['address_city'] : null,
        ':address_state' => $serviceFormData['address_state'] !== '' ? $serviceFormData['address_state'] : null,
        ':address_postal_code' => $serviceFormData['address_postal_code'] !== '' ? $serviceFormData['address_postal_code'] : null,
        ':status' => $status,
        ':payment_status' => $paymentStatus,
        ':payment_reference' => $paymentReference,
        ':last_renewed_at' => $lastRenewedAt,
        ':published_at' => $publishedAt,
        ':expires_at' => $expiresAt,
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);

    $serviceId = (int)$pdo->lastInsertId();

    if ($servicesFreeForAll) {
        $now = now();
        $newExpiresAt = date('Y-m-d H:i:s', strtotime($now . " +{$serviceDurationDays} days"));

        try {
            apply_service_renewal_update($pdo, (int)$user['id'], $serviceId, $newExpiresAt, $now);
        } catch (Throwable $renewalError) {
            $serviceErrors[] = 'Nao foi possivel concluir a publicacao gratuita. Tente novamente.';
            error_log('[process_service_creation][free] ' . $renewalError->getMessage());
            return null;
        }

        record_audit((int)$user['id'], 'service_free_published', 'user_service_listings', $serviceId, [
            'payment_reference' => $paymentReference,
            'expires_at' => $newExpiresAt,
            'mode' => 'free',
        ]);

        flash_add('success', 'Servico publicado gratuitamente por mais 30 dias.');
        return '/anuncios/meus.php?tab=services';
    }

    record_audit((int)$user['id'], 'service_created', 'user_service_listings', $serviceId, [
        'payment_reference' => $paymentReference,
        'mode' => 'paid',
    ]);

    return '/servicos/pagamento.php?serviceId=' . $serviceId . '&ref=' . urlencode($paymentReference);
}

function process_service_deletion(PDO $pdo, array $user, array &$serviceErrors): ?string
{
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $serviceErrors[] = 'Token invalido. Recarregue a pagina.';
        return null;
    }

    $serviceId = (int)($_POST['service_id'] ?? 0);
    if ($serviceId <= 0) {
        $serviceErrors[] = 'serviço invalido.';
        return null;
    }

    $serviceStmt = $pdo->prepare('SELECT id FROM user_service_listings WHERE id = :id AND user_id = :user LIMIT 1');
    $serviceStmt->execute([
        ':id' => $serviceId,
        ':user' => (int)$user['id'],
    ]);
    if (!$serviceStmt->fetch()) {
        $serviceErrors[] = 'serviço nao encontrado.';
        return null;
    }

    $delete = $pdo->prepare('DELETE FROM user_service_listings WHERE id = :id AND user_id = :user');
    $delete->execute([
        ':id' => $serviceId,
        ':user' => (int)$user['id'],
    ]);

    if ($delete->rowCount() < 1) {
        $serviceErrors[] = 'Nao foi possivel remover o serviço. Tente novamente.';
        return null;
    }

    record_audit((int)$user['id'], 'service_deleted', 'user_service_listings', $serviceId);
    flash_add('success', 'serviço removido com sucesso.');

    return '/anuncios/meus.php?tab=services';
}

function process_service_renewal(PDO $pdo, array $user, array &$serviceErrors): ?string
{
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $serviceErrors[] = 'Token invalido. Recarregue a pagina.';
        return null;
    }

    $serviceId = (int)($_POST['service_id'] ?? 0);
    if ($serviceId <= 0) {
        $serviceErrors[] = 'Servico invalido.';
        return null;
    }

    try {
        $serviceStmt = $pdo->prepare('SELECT id, user_id, name, status, payment_status, expires_at, payment_reference, external_reference FROM user_service_listings WHERE id = :id AND user_id = :user LIMIT 1');
        $serviceStmt->execute([
            ':id' => $serviceId,
            ':user' => (int)$user['id'],
        ]);
        $service = $serviceStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $lookupError) {
        $serviceErrors[] = 'Nao foi possivel carregar os dados do servico.';
        error_log('[process_service_renewal] lookup failure: ' . $lookupError->getMessage());
        return null;
    }

    if (!$service) {
        $serviceErrors[] = 'Servico nao encontrado.';
        return null;
    }

    $status = (string)($service['status'] ?? '');
    $paymentStatus = (string)($service['payment_status'] ?? '');
    $allowedStatuses = ['active', 'expired', 'pending_payment'];
    if (!in_array($status, $allowedStatuses, true)) {
        $serviceErrors[] = 'Servico nao esta elegivel para renovacao manual.';
        return null;
    }

    if (in_array($status, ['active', 'expired'], true) && function_exists('should_show_service_renewal_button')) {
        if (!should_show_service_renewal_button($service)) {
            $serviceErrors[] = 'Servico nao esta elegivel para renovacao manual.';
            return null;
        }
    }

    $servicesFreeForAll = setting_value_enabled(get_setting('services_free_for_all', '0'));
    $serviceDurationDays = (int)get_setting('services_free_duration_days', '30');
    if ($serviceDurationDays <= 0) {
        $serviceDurationDays = 30;
    }

    if ($servicesFreeForAll) {
        $now = now();
        $newExpiresAt = date('Y-m-d H:i:s', strtotime($now . " +{$serviceDurationDays} days"));

        try {
            apply_service_renewal_update($pdo, (int)$user['id'], $serviceId, $newExpiresAt, $now);
        } catch (Throwable $freeError) {
            $serviceErrors[] = 'Nao foi possivel renovar gratuitamente neste momento. Tente novamente.';
            error_log('[process_service_renewal][free] ' . $freeError->getMessage());
            return null;
        }

        record_audit((int)$user['id'], 'service_free_renewed', 'user_service_listings', $serviceId, [
            'expires_at' => $newExpiresAt,
            'mode' => 'free_manual',
        ]);

        flash_add('success', 'Servico renovado por mais ' . $serviceDurationDays . ' dias.');
        return '/anuncios/meus.php?tab=services';
    }

    if ($status === 'pending_payment') {
        if (in_array($paymentStatus, ['pending', 'pending_gateway'], true)) {
            flash_add('info', 'Existe uma renovacao pendente aguardando pagamento.');
            return '/servicos/pagamento.php?serviceId=' . $serviceId . '&type=renewal';
        }

        if ($paymentStatus === 'paid') {
            flash_add('info', 'Pagamento ja registrado. Conclua a publicacao para finalizar a renovacao.');
            return '/servicos/pagamento_confirmando.php?serviceId=' . $serviceId . '&type=renewal';
        }
    }

    $paymentReference = 'SRV-R-' . strtoupper(bin2hex(random_bytes(4)));
    $now = now();

    try {
        $update = $pdo->prepare('UPDATE user_service_listings SET 
                status = "pending_payment",
                payment_status = "pending",
                payment_reference = :payment_reference,
                external_reference = NULL,
                updated_at = :now
            WHERE id = :id AND user_id = :user');

        $update->execute([
            ':payment_reference' => $paymentReference,
            ':now' => $now,
            ':id' => $serviceId,
            ':user' => (int)$user['id'],
        ]);
    } catch (Throwable $paidError) {
        $serviceErrors[] = 'Nao foi possivel preparar a renovacao paga. Tente novamente.';
        error_log('[process_service_renewal][paid] ' . $paidError->getMessage());
        return null;
    }

    record_audit((int)$user['id'], 'service_renewal_requested', 'user_service_listings', $serviceId, [
        'payment_reference' => $paymentReference,
        'mode' => 'paid_manual',
    ]);

    flash_add('info', 'Finalize o pagamento para concluir a renovacao do servico.');
    return '/servicos/pagamento.php?serviceId=' . $serviceId . '&type=renewal';
}

/**
 * ===== FUNÇÃO REMOVIDA: store_service_custom_suggestion =====
 * Tabela descontinuada - service_custom_suggestions foi removida
 * Data: 29/10/2025
 */
/*
function store_service_custom_suggestion(PDO $pdo, string $label): void
{
    $label = trim(preg_replace('/\s+/', ' ', $label));
    if ($label === '') {
        return;
    }

    if (function_exists('mb_substr')) {
        $label = mb_substr($label, 0, 150, 'UTF-8');
    } else {
        $label = substr($label, 0, 150);
    }

    $slug = slugify_service_label($label);
    if ($slug === '') {
        return;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO service_custom_suggestions (label, slug, usage_count, last_used_at)
            VALUES (:label, :slug, 1, NOW())
            ON DUPLICATE KEY UPDATE label = VALUES(label), usage_count = usage_count + 1, last_used_at = NOW()');
        $stmt->execute([
            ':label' => $label,
            ':slug' => $slug,
        ]);
    } catch (Throwable $storeError) {
        error_log('[meus.php] Nao foi possivel armazenar sugestao de serviço personalizado: ' . $storeError->getMessage());
    }
}
*/

$user = current_user();
$pdo = db();

ensureStoreCustomizationSchema($pdo);
// ensureServiceSuggestionSchema($pdo); // ===== REMOVIDO: Tabela descontinuada

$customServiceSuggestions = [];
// TABELA REMOVIDA: service_custom_suggestions - Feature não implementada - Data: 30/10/2025
/*
try {
    $suggestStmt = $pdo->query('SELECT label FROM service_custom_suggestions ORDER BY usage_count DESC, last_used_at DESC LIMIT 30');
    if ($suggestStmt) {
        $customServiceSuggestions = array_values(array_filter(array_map(static function ($label) {
            if ($label === null) {
                return null;
            }
            $trimmed = trim((string)$label);
            return $trimmed !== '' ? $trimmed : null;
        }, $suggestStmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
    }
} catch (Throwable $suggestionError) {
    error_log('[meus.php] Nao foi possivel carregar sugestoes de serviços personalizados: ' . $suggestionError->getMessage());
}
*/

if (!$customServiceSuggestions) {
    try {
    $legacyStmt = $pdo->query("SELECT DISTINCT custom_category FROM user_service_listings WHERE custom_category IS NOT NULL AND custom_category <> '' ORDER BY custom_category ASC LIMIT 30");
        if ($legacyStmt) {
            $legacySuggestions = array_values(array_filter(array_map(static function ($label) {
                if ($label === null) {
                    return null;
                }
                $trimmed = trim((string)$label);
                return $trimmed !== '' ? $trimmed : null;
            }, $legacyStmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));

            // foreach ($legacySuggestions as $legacyLabel) {
            //     store_service_custom_suggestion($pdo, $legacyLabel); // ===== REMOVIDO: Tabela descontinuada
            // }

            $customServiceSuggestions = $legacySuggestions;
        }
    } catch (Throwable $legacyError) {
        error_log('[meus.php] Nao foi possivel carregar sugestoes legadas de serviços personalizados: ' . $legacyError->getMessage());
    }
}

$allowedTabs = ['ads', 'profile', 'security', 'services', 'transactions'];
$activeTab = input_get('tab', 'ads');

$isPremium = user_is_premium($user);
if ($isPremium) {
    $allowedTabs[] = 'store';
}

if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'ads';
}

if ($activeTab === 'store' && !$isPremium) {
    flash_add('error', 'Disponivel apenas para assinantes premium.');
    $activeTab = 'ads';
}
$limit = free_ads_limit();
$createdThisMonth = count_user_ads_in_month((int)$user['id']);
$freeDurationDays = (int)get_setting('free_ads_duration_days', '30');

$passwordErrors = [];
$serviceErrors = [];

$userCoinBalance = 0;
try {
    $userCoinBalance = function_exists('get_user_coin_balance') ? get_user_coin_balance((int)$user['id']) : 0;
} catch (Throwable $e) {
    error_log('[meus.php] Erro ao carregar saldo de moedas: ' . $e->getMessage());
    $userCoinBalance = 0;
}

// ============================================
// Carregar histórico de transações
// ============================================

$coin_purchase_transactions = [];
$coin_other_transactions = [];
$coin_history = [];
$coinUnitPrice = 0.0;
$premium_transactions = [];
$service_transactions = [];

// Carregar histórico de moedas (compras agregadas + outras movimentações)
try {
    $coinUnitPrice = (float)get_setting('clivre_medal_price', '15.00');

    $coinPurchaseQuery = "
        SELECT 
            MAX(created_at) AS created_at,
            MAX(CASE WHEN type = 'buy' THEN description END) AS buy_description,
            MAX(CASE WHEN type = 'bonus' THEN description END) AS bonus_description,
            SUM(CASE WHEN type = 'buy' THEN amount ELSE 0 END) AS base_amount,
            SUM(CASE WHEN type = 'bonus' THEN amount ELSE 0 END) AS bonus_amount,
            SUM(amount) AS total_amount,
            external_reference
        FROM coin_transactions
        WHERE user_id = :user_id
          AND reference_type IN ('mercado_pago','coin_purchase')
          AND type IN ('buy','bonus')
        GROUP BY external_reference
        ORDER BY created_at DESC
        LIMIT 50";
    $coinPurchaseStmt = db()->prepare($coinPurchaseQuery);
    $coinPurchaseStmt->execute([':user_id' => (int)$user['id']]);
    $coin_purchase_transactions = $coinPurchaseStmt->fetchAll(PDO::FETCH_ASSOC);

    $coinOtherQuery = "SELECT * FROM coin_transactions 
               WHERE user_id = :user_id 
                 AND NOT (reference_type IN ('mercado_pago','coin_purchase') AND type IN ('buy','bonus'))
               ORDER BY created_at DESC 
               LIMIT 50";
    $coinOtherStmt = db()->prepare($coinOtherQuery);
    $coinOtherStmt->execute([':user_id' => (int)$user['id']]);
    $coin_other_transactions = $coinOtherStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($coin_purchase_transactions as $purchase) {
        $packageName = null;
        if (!empty($purchase['buy_description']) && preg_match('/Pacote\s+(.+?)\s+\(/u', $purchase['buy_description'], $matches)) {
            $packageName = $matches[1];
        }
        $coin_history[] = [
            'type' => 'coin_purchase',
            'created_at' => $purchase['created_at'],
            'package_name' => $packageName ?: 'Pacote de moedas',
            'base_amount' => (int)($purchase['base_amount'] ?? 0),
            'bonus_amount' => (int)($purchase['bonus_amount'] ?? 0),
            'total_amount' => (int)($purchase['total_amount'] ?? 0),
            'payment_reference' => $purchase['external_reference'] ?? '',
            'payment_method' => 'Mercado Pago',
            'price_amount' => $coinUnitPrice * (float)($purchase['base_amount'] ?? 0),
        ];
    }

    foreach ($coin_other_transactions as $txRow) {
        $coin_history[] = [
            'type' => 'legacy',
            'created_at' => $txRow['created_at'],
            'raw' => $txRow,
        ];
    }

    usort($coin_history, static function (array $a, array $b): int {
        $timeA = strtotime((string)$a['created_at']);
        $timeB = strtotime((string)$b['created_at']);
        return $timeB <=> $timeA;
    });

} catch (Exception $e) {
    error_log('[meus.php] Erro ao carregar histórico de transações: ' . $e->getMessage());
    $coin_history = [];
}

// Carregar transações premium (associações com subscriptions)
try {
    $premiumTxQuery = "
        SELECT 
            ps.id,
            ps.plan_id,
            ps.started_at AS subscription_started_at,
            ps.expires_at AS subscription_expires_at,
            ps.created_at AS subscription_created_at,
            ps.payment_status AS subscription_payment_status,
            ps.payment_id,
            ps.amount_paid,
            ps.payment_currency,
            pp.name AS plan_name,
            pp.price_decimal,
            pp.duration_days,
            ct.created_at AS transaction_created_at,
            ct.payment_status AS transaction_payment_status,
            ct.payment_method,
            ct.external_reference,
            ct.description
        FROM premium_subscriptions ps
        JOIN premium_plans pp ON pp.id = ps.plan_id
        LEFT JOIN coin_transactions ct ON ct.id = (
            SELECT ct2.id FROM coin_transactions ct2
            WHERE ct2.reference_type = 'premium_subscription'
              AND ct2.reference_id = ps.id
            ORDER BY ct2.created_at DESC
            LIMIT 1
        )
        WHERE ps.user_id = :user_id
        ORDER BY COALESCE(ct.created_at, ps.updated_at, ps.created_at) DESC
        LIMIT 100
    ";
    $premiumTxStmt = db()->prepare($premiumTxQuery);
    $premiumTxStmt->execute([':user_id' => (int)$user['id']]);
    $premiumRows = $premiumTxStmt->fetchAll(PDO::FETCH_ASSOC);

    $seenPremium = [];
    foreach ($premiumRows as $row) {
        $key = $row['payment_id'] ?: ('sub_' . $row['id']);
        if (isset($seenPremium[$key])) {
            continue;
        }
        $seenPremium[$key] = true;
        $premium_transactions[] = $row;
        if (count($premium_transactions) >= 20) {
            break;
        }
    }
} catch (Exception $e) {
    error_log('[meus.php] Erro ao carregar histórico premium: ' . $e->getMessage());
    $premium_transactions = [];
}

// Carregar transações de Catálogo de Serviços  (pagamentos)
try {
    $serviceQuery = "SELECT * FROM user_service_listings 
                    WHERE user_id = :user_id 
                    ORDER BY created_at DESC 
                    LIMIT 20";
    $serviceStmt = db()->prepare($serviceQuery);
    $serviceStmt->execute([':user_id' => (int)$user['id']]);
    $service_transactions = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('[meus.php] Erro ao carregar histórico de serviços: ' . $e->getMessage());
    $service_transactions = [];
}

$serviceFormData = [
    'name' => '',
    'category' => '',
    'custom_category' => '',
    'description' => '',
    'phone' => '',
    'whatsapp' => '',
    'email' => '',
    'document_type' => 'cpf',
    'document_number' => '',
    'show_document' => false,
    'address_street' => '',
    'address_number' => '',
    'address_complement' => '',
    'address_neighborhood' => '',
    'address_city' => '',
    'address_state' => '',
    'address_postal_code' => '',
];

$storeThemeDefaults = [
    'hero_background' => '#155e2b',
    'hero_text_color' => '#ffffff',
    'hero_accent_color' => '#facc15',
    'highlight_message' => '',
    'cover_image' => '',
    'layout_style' => 'default',
    'instagram_url' => '',
];

$storeThemeFormData = $storeThemeDefaults;
if (!empty($user['store_theme_config'])) {
    $decodedTheme = json_decode((string)$user['store_theme_config'], true);
    if (is_array($decodedTheme)) {
        $storeThemeFormData = array_merge($storeThemeFormData, array_intersect_key($decodedTheme, $storeThemeDefaults));
    }
}

// Adicionar instagram_url do campo direto da tabela users
if (!empty($user['instagram_url'])) {
    $storeThemeFormData['instagram_url'] = (string)$user['instagram_url'];
}

$storeThemeFormData = array_map(static function ($value) {
    return $value === null ? '' : $value;
}, $storeThemeFormData);

$storeThemeErrors = [];

$storeMetricsRangeDays = 30;
$storeMetricsSummary = [
    'total' => 0,
    'byType' => [],
    'highlights' => [],
    'daily' => [],
    'topAds' => [],
    'rangeDays' => $storeMetricsRangeDays,
];

$storeMetricLabels = [
    'store_visit' => 'Visitas a vitrine',
    'ad_click' => 'Cliques em anuncios',
    'favorite_added' => 'Favoritos adicionados',
    'favorite_removed' => 'Favoritos removidos',
    'share_whatsapp' => 'Compartilhamentos (WhatsApp)',
    'share_instagram' => 'Compartilhamentos (Instagram)',
    'share_facebook' => 'Compartilhamentos (Facebook)',
    'share_email' => 'Compartilhamentos (E-mail)',
    'share_copy_link' => 'Link copiado para compartilhamento',
    'share_copy_message' => 'Mensagem copiada para compartilhamento',
    'whatsapp_click' => 'Cliques no WhatsApp',
    'phone_click' => 'Cliques no telefone',
    'route_google' => 'Rotas no Google Maps',
    'route_waze' => 'Rotas no Waze',
    'filter_interaction' => 'Interacoes de filtro',
    'sort_interaction' => 'Ordenacoes alteradas',
];

$serviceCategories = [
    'mechanic' => 'Oficina mecânica',
    'bodyshop' => 'Funilaria e pintura',
    'detailing' => 'Estética automotiva',
    'tire' => 'Pneus e suspensão',
    'alignment' => 'Alinhamento e balanceamento',
    'electric' => 'Elétrica automotiva',
    'air_conditioning' => 'Ar-condicionado automotivo',
    'battery' => 'Baterias e elétrica leve',
    'paintless_dent' => 'Martelinho de ouro',
    'glass' => 'Vidros e para-brisas',
    'parts' => 'Autopeças',
    'tow' => 'Guincho e reboque',
    'inspection' => 'Vistoria veicular',
    'wash' => 'Lavagem e polimento',
    'car_audio' => 'Som e multimídia',
    'insurance' => 'Corretora de seguros',
    'tracker' => 'Rastreamento veicular',
    'custom' => 'Outro serviço',
];

$stateOptions = [
    'AC' => 'Acre',
    'AL' => 'Alagoas',
    'AP' => 'Amapa',
    'AM' => 'Amazonas',
    'BA' => 'Bahia',
    'CE' => 'Ceara',
    'DF' => 'Distrito Federal',
    'ES' => 'Espirito Santo',
    'GO' => 'Goias',
    'MA' => 'Maranhao',
    'MT' => 'Mato Grosso',
    'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais',
    'PA' => 'Para',
    'PB' => 'Paraiba',
    'PR' => 'Parana',
    'PE' => 'Pernambuco',
    'PI' => 'Piaui',
    'RJ' => 'Rio de Janeiro',
    'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondonia',
    'RR' => 'Roraima',
    'SC' => 'Santa Catarina',
    'SP' => 'Sao Paulo',
    'SE' => 'Sergipe',
    'TO' => 'Tocantins',
    'EX' => 'Exterior',
];

$action = '';
$actionHandled = false;
$pendingRedirect = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedTab = $_POST['tab'] ?? '';
    if (in_array($postedTab, $allowedTabs, true)) {
        $activeTab = $postedTab;
    }

    $action = (string)($_POST['action'] ?? '');
    
    // DEBUG: Log da action recebida
    error_log('[meus.php] POST action recebida: "' . $action . '"');
    error_log('[meus.php] POST data: ' . json_encode($_POST));

    if ($action === 'create_service') {
        $actionHandled = true;
        $pendingRedirect = process_service_creation($pdo, $serviceFormData, $serviceCategories, $stateOptions, $serviceErrors, $user);
    } elseif ($action === 'delete_service') {
        $actionHandled = true;
        $pendingRedirect = process_service_deletion($pdo, $user, $serviceErrors);
    } elseif ($action === 'renew_service') {
        $actionHandled = true;
        $pendingRedirect = process_service_renewal($pdo, $user, $serviceErrors);
    }
}

if ($pendingRedirect) {
    redirect($pendingRedirect);
}

$servicesFreeForAllMode = setting_value_enabled(get_setting('services_free_for_all', '0'));
$servicePriceBRL = (float)get_setting('service_price', '15.00');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$actionHandled) {
    if ($action === 'mark_sold' || $action === 'clear_sold' || $action === 'confirm_delete_sold') {
        error_log('[meus.php] Entrando em mark_sold/clear_sold/confirm_delete_sold - action: ' . $action);
        if (!validate_csrf($_POST['csrf_token'] ?? null)) {
            flash_add('error', 'Token invalido. Recarregue a pagina.');
            redirect('/anuncios/meus.php?tab=ads');
        }
        $adId = (int)($_POST['ad_id'] ?? 0);
        if ($adId <= 0) {
            flash_add('error', 'Anuncio invalido.');
            redirect('/anuncios/meus.php?tab=ads');
        }
        $adStmt = $pdo->prepare('SELECT id, user_id FROM ads WHERE id = :id LIMIT 1');
        $adStmt->execute([':id' => $adId]);
        $ad = $adStmt->fetch();
        if (!$ad || (int)$ad['user_id'] !== (int)$user['id']) {
            flash_add('error', 'Anuncio nao encontrado.');
            redirect('/anuncios/meus.php?tab=ads');
        }
        
        if ($action === 'mark_sold') {
            $soldSource = $_POST['sold_source'] ?? '';
            if (!in_array($soldSource, ['platform', 'external'], true)) {
                flash_add('error', 'Informe a origem da venda.');
                redirect('/anuncios/meus.php?tab=ads');
            }
            // Salvar a origem da venda em sessão para a confirmação
            $_SESSION['pending_sold_ad'] = [
                'ad_id' => $adId,
                'sold_source' => $soldSource,
            ];
            redirect('/anuncios/meus.php?tab=ads');
        } elseif ($action === 'confirm_delete_sold') {
            error_log('[meus.php:confirm_delete_sold] START - adId: ' . $adId);
            
            // Segunda confirmação do usuário - usar o valor da sessão
            $pendingSold = $_SESSION['pending_sold_ad'] ?? null;
            error_log('[meus.php] pending_sold_ad na sessão: ' . json_encode($pendingSold));
            
            if (!$pendingSold || $pendingSold['ad_id'] !== $adId) {
                error_log('[meus.php] ERRO: Sessão inválida ou não bate com adId');
                flash_add('error', 'Sessão expirada. Tente novamente.');
                redirect('/anuncios/meus.php?tab=ads');
            }
            
            $soldSource = $pendingSold['sold_source'] ?? '';
            error_log('[meus.php] soldSource: ' . $soldSource);
            
            if (!in_array($soldSource, ['platform', 'external'], true)) {
                error_log('[meus.php] ERRO: soldSource inválido');
                flash_add('error', 'Origem de venda inválida.');
                redirect('/anuncios/meus.php?tab=ads');
            }
            
            try {
                error_log('[meus.php] Iniciando transação...');
                $pdo->beginTransaction();
                
                // Deletar mídias vinculadas ao anúncio (arquivos E pastas)
                $mediaStmt = $pdo->prepare('SELECT filepath FROM ad_media WHERE ad_id = :ad_id');
                $mediaStmt->execute([':ad_id' => $adId]);
                $mediaFiles = $mediaStmt->fetchAll();
                error_log('[meus.php] Encontrados ' . count($mediaFiles) . ' arquivos de mídia');
                
                // Deletar arquivos individuais
                foreach ($mediaFiles as $media) {
                    $filePath = __DIR__ . '/../' . ltrim($media['filepath'], '/');
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                        error_log('[meus.php] Deletado arquivo: ' . $filePath);
                    }
                }
                
                // Deletar pasta do anúncio (imagens e vídeos)
                $imageDir = __DIR__ . '/../assets/uploads/images/' . $adId;
                if (is_dir($imageDir)) {
                    foreach ((array)glob($imageDir . '/*') as $file) {
                        @unlink($file);
                    }
                    @rmdir($imageDir);
                    error_log('[meus.php] Deletada pasta de imagens: ' . $imageDir);
                }
                
                $videoDir = __DIR__ . '/../assets/uploads/videos/' . $adId;
                if (is_dir($videoDir)) {
                    foreach ((array)glob($videoDir . '/*') as $file) {
                        @unlink($file);
                    }
                    @rmdir($videoDir);
                    error_log('[meus.php] Deletada pasta de vídeos: ' . $videoDir);
                }
                
                // Deletar registros de mídia do BD
                error_log('[meus.php] Deletando registros de ad_media...');
                $deleteMediaStmt = $pdo->prepare('DELETE FROM ad_media WHERE ad_id = :ad_id');
                $deleteMediaStmt->execute([':ad_id' => $adId]);
                error_log('[meus.php] ad_media deletado');
                
                // Atualizar anúncio com dados de venda (para registro histórico)
                error_log('[meus.php] Atualizando ads com sold_at e sold_source...');
                $updateSoldAd = $pdo->prepare('UPDATE ads SET sold_at = NOW(), sold_source = :source, updated_at = NOW() WHERE id = :id');
                $updateSoldAd->execute([
                    ':source' => $soldSource,
                    ':id' => $adId
                ]);
                error_log('[meus.php] ads atualizado');
                
                // Registrar a venda como contabilizada na tabela sales
                error_log('[meus.php] Inserindo em sales...');
                try {
                    $saleStmt = $pdo->prepare('
                        INSERT INTO sales (user_id, ad_id, source, created_at) 
                        VALUES (:user_id, :ad_id, :source, NOW())
                    ');
                    $saleStmt->execute([
                        ':user_id' => (int)$user['id'],
                        ':ad_id' => $adId,
                        ':source' => $soldSource,
                    ]);
                    error_log('[meus.php] Venda inserida com sucesso em sales');
                } catch (Throwable $saleError) {
                    error_log('[meus.php] ERRO ao inserir venda: ' . $saleError->getMessage());
                    throw $saleError;
                }
                
                // Incrementar contador de vendas no usuário
                error_log('[meus.php] Incrementando contador de vendas... (source: ' . $soldSource . ')');
                if ($soldSource === 'platform') {
                    $pdo->prepare('UPDATE users SET platform_sales_count = platform_sales_count + 1 WHERE id = :id')
                        ->execute([':id' => (int)$user['id']]);
                    error_log('[meus.php] platform_sales_count incrementado');
                } else {
                    $pdo->prepare('UPDATE users SET external_sales_count = external_sales_count + 1 WHERE id = :id')
                        ->execute([':id' => (int)$user['id']]);
                    error_log('[meus.php] external_sales_count incrementado');
                }
                
                // Deletar o anúncio
                error_log('[meus.php] Deletando anúncio...');
                $deleteAdStmt = $pdo->prepare('DELETE FROM ads WHERE id = :id');
                $deleteAdStmt->execute([':id' => $adId]);
                error_log('[meus.php] Anúncio deletado');
                
                error_log('[meus.php] Fazendo commit...');
                $pdo->commit();
                error_log('[meus.php] SUCESSO! Transação concluída');
                
                record_audit((int)$user['id'], 'ad_sold_and_deleted', 'ads', $adId, ['source' => $soldSource]);
                
                $message = $soldSource === 'platform'
                    ? '✅ Venda contabilizada na plataforma! O anúncio foi removido do sistema.'
                    : '✅ Venda registrada! O anúncio foi removido do sistema.';
                flash_add('success', $message);
            } catch (Throwable $e) {
                error_log('[meus.php] ERRO NA TRANSAÇÃO: ' . $e->getMessage());
                $pdo->rollBack();
                error_log('[meus.php] Erro ao deletar anúncio vendido: ' . $e->getMessage());
                flash_add('error', 'Erro ao processar a venda. Tente novamente.');
            }
            
            // Limpar dados da sessão
            unset($_SESSION['pending_sold_ad']);
            redirect('/anuncios/meus.php?tab=ads');
        }
    } elseif ($action === 'change_password') {
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $currentPassword = (string)($_POST['current_password'] ?? '');

        if (!validate_csrf($_POST['csrf_token'] ?? null)) {
            $passwordErrors[] = 'Token invalido. Recarregue a pagina.';
        }

        if ($currentPassword === '' || !password_verify($currentPassword, (string)$user['password_hash'])) {
            $passwordErrors[] = 'Senha atual incorreta.';
        }
        if (mb_strlen($newPassword) < 8) {
            $passwordErrors[] = 'Nova senha deve ter pelo menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
            $passwordErrors[] = 'Nova senha precisa ter letras maiusculas, minusculas e numeros.';
        }
        if ($newPassword !== $confirmPassword) {
            $passwordErrors[] = 'Confirmacao da nova senha nao confere.';
        }

        if (!$passwordErrors) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
            $update->execute([
                ':hash' => $hash,
                ':id' => (int)$user['id'],
            ]);
            record_audit((int)$user['id'], 'user_password_changed', 'users', (int)$user['id']);
            flash_add('success', 'Senha alterada com sucesso.');
            redirect('/anuncios/meus.php?tab=security');
        }
    } elseif ($action === 'update_store_theme') {
        $activeTab = 'store';
        if (!$isPremium) {
            flash_add('error', 'Funcao disponivel apenas para assinantes premium.');
        } elseif (!validate_csrf($_POST['csrf_token'] ?? null)) {
            $storeThemeErrors[] = 'Token invalido. Recarregue a pagina.';
        } else {
            $input = [
                'highlight_message' => trim((string)($_POST['highlight_message'] ?? $storeThemeFormData['highlight_message'])),
                'cover_image' => trim((string)($_POST['cover_image'] ?? $storeThemeFormData['cover_image'])),
                'instagram_url' => trim((string)($_POST['instagram_url'] ?? $storeThemeFormData['instagram_url'] ?? '')),
            ];

            if ($input['highlight_message'] !== '' && mb_strlen($input['highlight_message']) > 120) {
                $storeThemeErrors[] = 'Mensagem em destaque deve ter no maximo 120 caracteres.';
                $input['highlight_message'] = mb_substr($input['highlight_message'], 0, 120);
            }

            if ($input['cover_image'] !== '') {
                if (mb_strlen($input['cover_image']) > 255) {
                    $storeThemeErrors[] = 'URL de capa deve ter ate 255 caracteres.';
                    $input['cover_image'] = mb_substr($input['cover_image'], 0, 255);
                }
                if (!preg_match('/^(https?:\/\/|\/)/i', $input['cover_image'])) {
                    $storeThemeErrors[] = 'Use uma URL valida iniciando com http(s) ou caminho relativo.';
                }
            }

            if ($input['instagram_url'] !== '') {
                if (mb_strlen($input['instagram_url']) > 500) {
                    $storeThemeErrors[] = 'URL do Instagram deve ter ate 500 caracteres.';
                    $input['instagram_url'] = mb_substr($input['instagram_url'], 0, 500);
                }
                if (!preg_match('/^https?:\/\/.+/i', $input['instagram_url'])) {
                    $storeThemeErrors[] = 'Use uma URL valida para o Instagram (comece com http:// ou https://).';
                }
            }

            $storeThemeFormData = array_merge($storeThemeFormData, $input);

            if (!$storeThemeErrors) {
                $payload = [
                    'hero_background' => $storeThemeFormData['hero_background'],
                    'hero_text_color' => $storeThemeFormData['hero_text_color'],
                    'hero_accent_color' => $storeThemeFormData['hero_accent_color'],
                    'highlight_message' => $input['highlight_message'],
                    'cover_image' => $input['cover_image'],
                    'layout_style' => $storeThemeFormData['layout_style'],
                ];

                $encodedTheme = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($encodedTheme === false) {
                    error_log('[meus.php] Falha ao codificar tema da loja: ' . json_last_error_msg());
                    $storeThemeErrors[] = 'Nao foi possivel salvar as configuracoes agora. Tente novamente.';
                } else {
                    $persistTheme = static function () use ($pdo, $user, $encodedTheme, $input): void {
                        $updateTheme = $pdo->prepare('UPDATE users SET store_theme_config = :config, instagram_url = :instagram_url WHERE id = :id');
                        $updateTheme->execute([
                            ':config' => $encodedTheme,
                            ':instagram_url' => $input['instagram_url'] ?: null,
                            ':id' => (int)$user['id'],
                        ]);
                    };

                    $storeThemeSaved = false;

                    try {
                        $persistTheme();
                        $storeThemeSaved = true;
                    } catch (PDOException $storeSaveError) {
                        $errorCode = (string)$storeSaveError->getCode();
                        $errorMessage = (string)$storeSaveError->getMessage();
                        $isDataTooLong = $errorCode === '22001' || stripos($errorMessage, 'Data too long') !== false;
                        $isUnknownColumn = $errorCode === '42S22' || stripos($errorMessage, 'Unknown column') !== false;

                        if ($isDataTooLong) {
                            try {
                                $pdo->exec('ALTER TABLE users MODIFY COLUMN store_theme_config LONGTEXT NULL');
                                $persistTheme();
                                $storeThemeSaved = true;
                                error_log('[meus.php] Coluna store_theme_config ajustada para LONGTEXT apos erro de tamanho.');
                            } catch (Throwable $alterError) {
                                error_log('[meus.php] Falha ao ajustar coluna store_theme_config: ' . $alterError->getMessage() . ' | original: ' . $errorMessage);
                            }
                        } elseif ($isUnknownColumn) {
                            try {
                                ensureStoreCustomizationSchema($pdo);
                                $persistTheme();
                                $storeThemeSaved = true;
                                error_log('[meus.php] Coluna store_theme_config criada antes de salvar tema.');
                            } catch (Throwable $ensureError) {
                                error_log('[meus.php] Falha apos criar coluna store_theme_config: ' . $ensureError->getMessage() . ' | original: ' . $errorMessage);
                            }
                        } else {
                            error_log('[meus.php] Falha ao salvar tema da loja: ' . $errorMessage);
                        }
                    } catch (Throwable $storeSaveError) {
                        error_log('[meus.php] Falha inesperada ao salvar tema da loja: ' . $storeSaveError->getMessage());
                    }

                    if ($storeThemeSaved) {
                        $user['store_theme_config'] = $encodedTheme;
                        flash_add('success', 'Personalizacoes da vitrine salvas com sucesso.');
                    } else {
                        $storeThemeErrors[] = 'Nao foi possivel salvar as configuracoes agora. Tente novamente.';
                    }
                }
            }
        }
    }
}

$adsStmt = $pdo->prepare('SELECT ads.*,
    (SELECT filepath FROM ad_media WHERE ad_media.ad_id = ads.id AND ad_media.type = "image" ORDER BY id ASC LIMIT 1) AS thumb
    FROM ads
    WHERE user_id = :user_id
    ORDER BY ads.created_at DESC');
$adsStmt->execute([':user_id' => (int)$user['id']]);
$ads = $adsStmt->fetchAll();
// Ocultar anúncios em draft (não pagos)
$ads = array_filter($ads, fn($ad) => $ad['status'] !== 'draft');

$platformSalesStmt = $pdo->prepare('SELECT platform_sales_count FROM users WHERE id = :uid LIMIT 1');
$platformSalesStmt->execute([':uid' => (int)$user['id']]);
$platformSales = (int)($platformSalesStmt->fetchColumn() ?? 0);

$totalSalesStmt = $pdo->prepare('SELECT (platform_sales_count + external_sales_count) as total FROM users WHERE id = :uid LIMIT 1');
$totalSalesStmt->execute([':uid' => (int)$user['id']]);
$totalSales = (int)($totalSalesStmt->fetchColumn() ?? 0);

$servicesStmt = $pdo->prepare('SELECT * FROM user_service_listings WHERE user_id = :user ORDER BY created_at DESC');
$servicesStmt->execute([':user' => (int)$user['id']]);
$services = $servicesStmt->fetchAll();

if ($isPremium) {
    try {
        $metricsStart = (new DateTimeImmutable('-' . $storeMetricsRangeDays . ' days'))->format('Y-m-d H:i:s');

        $byTypeStmt = $pdo->prepare('SELECT event_type, COUNT(*) AS total FROM store_metrics WHERE store_id = :store AND created_at >= :start GROUP BY event_type ORDER BY total DESC');
        $byTypeStmt->execute([
            ':store' => (int)$user['id'],
            ':start' => $metricsStart,
        ]);

        $storeMetricCounts = [];
        $byTypeRows = $byTypeStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($byTypeRows as $row) {
            $eventType = (string)$row['event_type'];
            $count = (int)$row['total'];
            if (!isset($storeMetricLabels[$eventType])) {
                $storeMetricLabels[$eventType] = ucwords(str_replace('_', ' ', $eventType));
            }
            $storeMetricCounts[$eventType] = $count;
            $storeMetricsSummary['total'] += $count;
        }

        foreach ($storeMetricLabels as $eventType => $_label) {
            if (!array_key_exists($eventType, $storeMetricCounts)) {
                $storeMetricCounts[$eventType] = 0;
            }
        }

        $shareEvents = [
            'share_whatsapp',
            'share_instagram',
            'share_facebook',
            'share_email',
            'share_copy_link',
            'share_copy_message',
        ];

        $shareTotal = 0;
        foreach ($shareEvents as $shareEvent) {
            $shareTotal += $storeMetricCounts[$shareEvent] ?? 0;
        }

        $highlightConfig = [
            ['event' => 'store_visit', 'label' => 'Visitas a vitrine'],
            ['event' => 'ad_click', 'label' => 'Cliques em anuncios'],
            ['event' => 'favorite_added', 'label' => 'Anuncios favoritados'],
            ['event' => 'share_total', 'label' => 'Compartilhamentos'],
            ['event' => 'whatsapp_click', 'label' => 'Cliques no WhatsApp'],
            ['event' => 'phone_click', 'label' => 'Cliques para ligar'],
        ];

        foreach ($highlightConfig as $item) {
            $eventKey = $item['event'];
            $value = $eventKey === 'share_total'
                ? $shareTotal
                : ($storeMetricCounts[$eventKey] ?? 0);

            $storeMetricsSummary['highlights'][] = [
                'type' => $eventKey,
                'label' => $item['label'],
                'total' => $value,
            ];
        }

        $orderedEvents = [
            'store_visit',
            'ad_click',
            'favorite_added',
            'favorite_removed',
            'share_whatsapp',
            'share_instagram',
            'share_facebook',
            'share_email',
            'share_copy_link',
            'share_copy_message',
            'whatsapp_click',
            'phone_click',
            'route_google',
            'route_waze',
            'filter_interaction',
            'sort_interaction',
        ];

        $orderedSet = [];
        foreach ($orderedEvents as $eventKey) {
            if (!in_array($eventKey, $orderedSet, true)) {
                $orderedSet[] = $eventKey;
            }
        }

        foreach (array_keys($storeMetricLabels) as $eventKey) {
            if (!in_array($eventKey, $orderedSet, true)) {
                $orderedSet[] = $eventKey;
            }
        }

        foreach ($orderedSet as $eventKey) {
            if (!isset($storeMetricLabels[$eventKey])) {
                continue;
            }
            $storeMetricsSummary['byType'][] = [
                'type' => $eventKey,
                'label' => $storeMetricLabels[$eventKey],
                'total' => (int)($storeMetricCounts[$eventKey] ?? 0),
            ];
        }

        $dailyStmt = $pdo->prepare('SELECT DATE(created_at) AS day_ref, COUNT(*) AS total FROM store_metrics WHERE store_id = :store AND created_at >= :start GROUP BY day_ref ORDER BY day_ref DESC LIMIT 14');
        $dailyStmt->execute([
            ':store' => (int)$user['id'],
            ':start' => $metricsStart,
        ]);
        $dailyRows = array_reverse($dailyStmt->fetchAll(PDO::FETCH_ASSOC));
        foreach ($dailyRows as $row) {
            $day = (string)$row['day_ref'];
            $storeMetricsSummary['daily'][] = [
                'date' => $day,
                'label' => date('d/m', strtotime($day)),
                'total' => (int)$row['total'],
            ];
        }

        $topAdsStmt = $pdo->prepare('SELECT m.ad_id, COUNT(*) AS total, MAX(a.title) AS title FROM store_metrics m LEFT JOIN ads a ON a.id = m.ad_id WHERE m.store_id = :store AND m.created_at >= :start AND m.ad_id IS NOT NULL AND m.event_type = :event GROUP BY m.ad_id ORDER BY total DESC LIMIT 5');
        $topAdsStmt->execute([
            ':store' => (int)$user['id'],
            ':start' => $metricsStart,
            ':event' => 'ad_click',
        ]);

        foreach ($topAdsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $adId = (int)$row['ad_id'];
            $title = trim((string)($row['title'] ?? ''));
            if ($title === '') {
                $title = 'Anuncio #' . $adId;
            }
            $storeMetricsSummary['topAds'][] = [
                'ad_id' => $adId,
                'title' => $title,
                'total' => (int)$row['total'],
            ];
        }
    } catch (Throwable $metricsError) {
        error_log('[meus.php] Falha ao carregar metricas da vitrine: ' . $metricsError->getMessage());
    }
}

$serviceStatusLabels = [
    'active' => 'Ativo',
    'suspended' => 'Suspenso',
    'archived' => 'Arquivado',
    'pending_payment' => 'Aguardando Pagamento',
    'expired' => 'Expirado',
];

$csrfToken = csrf_token();
$pageTitle = 'Minha conta - Site Carros';

include __DIR__ . '/../partials/header.php';

?>
<div class="max-w-screen-2xl mx-auto px-4">
<style>
.account-header {
    margin: 2rem 0;
    padding: 1.75rem;
}
.account-header__top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.account-header__title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.account-header__subtitle {
    color: #6b7280;
    margin-top: 0.35rem;
}
.account-header__plan {
    margin-top: 0.75rem;
    font-weight: 600;
}
.account-header__hint {
    color: #6b7280;
    margin-top: 0.25rem;
}
.account-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin: 1.5rem 0;
}
.account-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border-radius: 9999px;
    border: 1px solid transparent;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.2s ease;
    color: #fff;
}
.account-tab--ads {
    background: linear-gradient(120deg, #22c55e 0%, #16a34a 100%);
    border-color: rgba(34, 197, 94, 0.6);
    box-shadow: 0 8px 18px rgba(34, 197, 94, 0.25);
}
.account-tab--profile {
    background: linear-gradient(120deg, #3b82f6 0%, #2563eb 100%);
    border-color: rgba(59, 130, 246, 0.6);
    box-shadow: 0 8px 18px rgba(59, 130, 246, 0.25);
}
.account-tab--security {
    background: linear-gradient(120deg, #9333ea 0%, #7c3aed 100%);
    border-color: rgba(147, 51, 234, 0.6);
    box-shadow: 0 8px 18px rgba(147, 51, 234, 0.25);
}
.account-tab--services {
    background: linear-gradient(120deg, #f59e0b 0%, #f97316 100%);
    border-color: rgba(245, 158, 11, 0.6);
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.28);
}
.account-tab--transactions {
    background: linear-gradient(120deg, #06b6d4 0%, #0891b2 100%);
    border-color: rgba(6, 182, 212, 0.6);
    box-shadow: 0 8px 18px rgba(6, 182, 212, 0.28);
}
.account-tab--store {
    background: linear-gradient(120deg, #dc2626 0%, #b91c1c 100%);
    border-color: rgba(220, 38, 38, 0.6);
    box-shadow: 0 8px 18px rgba(220, 38, 38, 0.28);
}
.account-tab--store .fas {
    color: #ffffff;
}
.account-tab--store:hover,
.account-tab--store.account-tab--active {
    box-shadow: 0 14px 28px rgba(185, 28, 28, 0.32);
}
.account-tab--store:hover .fas,
.account-tab--store.account-tab--active .fas {
    color: #ffffff;
}
.account-tab:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
}
.account-tab--active {
    transform: translateY(-2px);
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.18);
    border-color: rgba(255, 255, 255, 0.45);
}
.document-readonly {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-radius: 0.75rem;
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, 0.35);
}
.document-readonly__type {
    font-weight: 700;
    color: #dc2626;
    text-transform: uppercase;
}
.document-readonly__value {
    font-weight: 600;
    color: #111827;
}
.document-readonly__placeholder {
    font-weight: 500;
    color: #6b7280;
}
.document-support-hint {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #6b7280;
}
.account-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-top: 1.25rem;
}
.account-summary__card {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1.25rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(226, 232, 240, 0.8);
}
.account-summary__label {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
    margin-bottom: 0.35rem;
    font-weight: 600;
}
.account-summary__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
}
.account-summary__hint {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 0.35rem;
}
.account-section {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(226, 232, 240, 0.8);
}
.account-section h2 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.75rem;
}
.account-section p.lead {
    color: #6b7280;
    margin-bottom: 1.5rem;
}
.sold-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    background: rgba(22, 163, 74, 0.12);
    color: #166534;
    font-size: 0.75rem;
    font-weight: 600;
}
.service-table {
    width: 100%;
    border-collapse: collapse;
}
.service-table th,
.service-table td {
    text-align: left;
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}
.service-table th {
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.08em;
    color: #6b7280;
}
.service-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.service-actions__button {
    width: 100%;
    justify-content: center;
}
.service-actions__form {
    width: 100%;
}
.service-actions__note {
    color: #6b7280;
    font-size: 0.85rem;
}
.document-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.document-radio-group label {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.9rem;
}
.button-warning {
    background: linear-gradient(120deg, #facc15 0%, #f59e0b 100%);
    color: #1f2937;
    box-shadow: 0 12px 24px rgba(245, 158, 11, 0.3);
}
.button-warning:hover {
    box-shadow: 0 18px 34px rgba(245, 158, 11, 0.35);
    transform: translateY(-2px);
}
.button-success {
    background: linear-gradient(120deg, #22c55e 0%, #16a34a 100%);
    box-shadow: 0 12px 24px rgba(34, 197, 94, 0.25);
}
.button-success:hover {
    box-shadow: 0 18px 34px rgba(34, 197, 94, 0.32);
    transform: translateY(-2px);
}
.button-danger {
    background: linear-gradient(120deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 12px 24px rgba(220, 38, 38, 0.25);
}
.button-danger:hover {
    box-shadow: 0 18px 34px rgba(220, 38, 38, 0.32);
    transform: translateY(-2px);
}
.sold-modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1.5rem;
}
.sold-modal.hidden {
    display: none;
}
.sold-modal:not(.hidden) {
    display: flex;
}
.sold-modal__dialog {
    background: #fff;
    border-radius: 1rem;
    padding: 2rem;
    max-width: 420px;
    width: 100%;
    box-shadow: 0 18px 48px rgba(15, 23, 42, 0.25);
    text-align: center;
}
.sold-modal__title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.75rem;
}
.sold-modal__description {
    color: #6b7280;
    font-size: 0.95rem;
    margin-bottom: 1.25rem;
}
.sold-modal__actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.sold-modal__close {
    background: transparent;
    border: none;
    color: #1f2937;
    font-weight: 600;
    cursor: pointer;
    padding: 0.5rem 1rem;
}
.sold-modal__close:hover {
    color: #111827;
}
.store-settings-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    margin-bottom: 1.25rem;
}
.store-settings-card {
    background: #f8fafc;
    border-radius: 0.85rem;
    padding: 1.1rem;
    border: 1px solid rgba(191, 219, 254, 0.6);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.7);
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.store-settings-card h3 {
    margin: 0;
    font-size: 1.05rem;
    color: #1f2937;
}
.store-settings-card p {
    margin: 0;
    color: #64748b;
    font-size: 0.9rem;
}
.store-settings-field {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}
.store-settings-field span {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1f2937;
}
.store-settings-field input[type="color"] {
    width: 60px;
    height: 36px;
    padding: 0;
    border: 1px solid rgba(148, 163, 184, 0.55);
    border-radius: 0.5rem;
    background: #fff;
    cursor: pointer;
}
.store-settings-field input[type="text"],
.store-settings-field textarea,
.store-settings-field select {
    border: 1px solid rgba(148, 163, 184, 0.55);
    border-radius: 0.6rem;
    padding: 0.65rem 0.75rem;
    font-size: 0.95rem;
    background: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.store-settings-field textarea {
    min-height: 96px;
    resize: vertical;
}
.store-settings-field input:focus,
.store-settings-field textarea:focus,
.store-settings-field select:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.7);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
}
.store-settings-card--instagram {
    background: linear-gradient(135deg, rgba(240, 148, 51, 0.05) 0%, rgba(220, 39, 67, 0.05) 100%);
    border: 2px solid #f09433;
    position: relative;
    overflow: hidden;
}
.store-settings-card--instagram::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
}
.store-settings-card--instagram h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #c2185b;
}
.store-settings-card--instagram h3::before {
    content: '📱';
    font-size: 1.3rem;
}
.store-settings-card--instagram p {
    color: #a91d63;
}
.store-settings-field input[type="url"] {
    font-size: 1rem;
    padding: 0.8rem;
}
.store-settings-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 2rem;
}
.store-analytics {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.store-analytics__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}
.store-analytics__card {
    background: linear-gradient(120deg, rgba(59, 130, 246, 0.12) 0%, rgba(14, 165, 233, 0.12) 100%);
    border-radius: 0.85rem;
    padding: 1rem;
    border: 1px solid rgba(191, 219, 254, 0.6);
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.store-analytics__card span {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #475569;
    font-weight: 600;
}
.store-analytics__card strong {
    font-size: 1.65rem;
    font-weight: 700;
    color: #0f172a;
}
.store-analytics__card small {
    color: #64748b;
}
.store-analytics__split {
    display: grid;
    gap: 1.25rem;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}
.store-analytics__section {
    background: #fff;
    border-radius: 0.85rem;
    padding: 1.1rem;
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
}
.store-analytics__section h3 {
    margin: 0 0 0.75rem;
    font-size: 1.05rem;
    color: #1f2937;
}
.store-analytics__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.store-analytics__list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border-radius: 0.7rem;
    padding: 0.6rem 0.9rem;
    border: 1px solid rgba(226, 232, 240, 0.9);
    font-size: 0.9rem;
}
.store-analytics__list-item strong {
    font-size: 1rem;
    color: #0f172a;
}
.store-analytics__empty {
    color: #64748b;
    font-size: 0.9rem;
}
.store-analytics__daily {
    display: grid;
    gap: 0.5rem;
}
.store-analytics__daily-item {
    display: flex;
    justify-content: space-between;
    background: #f1f5f9;
    padding: 0.6rem 0.85rem;
    border-radius: 0.75rem;
    font-size: 0.9rem;
    color: #1f2937;
}
.store-analytics__daily-item span:last-child {
    font-weight: 600;
}
@media (max-width: 768px) {
    .store-settings-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<section class="form-card account-header">
    <div class="account-header__top">
        <div>
            <h1 class="account-header__title">Minha conta</h1>
            <p class="account-header__subtitle">👤 Logado como: <strong><?= e($user['name']); ?></strong> (<?= e($user['email']); ?>)</p>
        </div>
        <a class="button" href="/anuncios/criar.php">Novo anuncio</a>
    </div>
    <p class="account-header__plan">Status da conta: <strong><?= $isPremium ? 'Premium ativo' : 'Plano gratuito'; ?></strong></p>
    <?php if (!$isPremium): ?>
        <p class="account-header__hint">Voce utilizou <strong><?= $createdThisMonth; ?></strong> de <strong><?= $limit; ?></strong> anuncios gratuitos neste mes (<?= $freeDurationDays; ?> dias de exibicao cada).</p>
    <?php else: ?>
        <p class="account-header__hint">Assinaturas premium garantem anuncios ilimitados e prioridade de exibicao.</p>
    <?php endif; ?>

    <div class="account-summary">
        <div class="account-summary__card" style="background:linear-gradient(135deg, #15803d 0%, #22c55e 100%); color:white; border:none; display:flex; flex-direction:column; justify-content:space-between; position:relative; overflow:hidden;">
            <div style="position:relative; z-index:2;">
                <span class="account-summary__label" style="color:rgba(255,255,255,0.9); text-transform:uppercase; font-size:0.8rem;">💰 Saldo de Moedas CLivre</span>
                <span class="account-summary__value" style="color:white; font-size:2rem; display:flex; align-items:center; gap:0.5rem;">
                    <img src="/assets/img/classilivre-logo.png" alt="CLivre Medal" style="height:1.8rem;" onerror="this.style.display='none'">
                    <span><?= (int)$userCoinBalance; ?></span>
                </span>
                <span class="account-summary__hint" style="color:rgba(255,255,255,0.85); font-size:0.85rem;">1 moeda = 1 anúncio por 30 dias</span>
            </div>
            <a href="/premium/comprar_moedas.php" class="mt-3" style="align-self:stretch; background:linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color:#000; font-weight:700; padding:0.75rem 1rem; border-radius:0.5rem; text-align:center; font-size:0.95rem; transition:all 0.3s ease; box-shadow:0 4px 12px rgba(251, 191, 36, 0.4); text-decoration:none; display:flex; align-items:center; justify-content:center; gap:0.5rem; z-index:3; position:relative;">
                <span style="font-size:1.2rem;">🛒</span> + Adquirir Moedas
            </a>
            <style>
                a[href="/premium/comprar_moedas.php"]:hover {
                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
                    box-shadow: 0 6px 16px rgba(251, 191, 36, 0.6) !important;
                    transform: translateY(-2px) !important;
                }
            </style>
        </div>
        <div class="account-summary__card">
            <span class="account-summary__label">Anuncios publicados</span>
            <span class="account-summary__value"><?= count($ads); ?></span>
            <span class="account-summary__hint">Inclui pendentes, aprovados e rejeitados.</span>
        </div>
        <div class="account-summary__card">
            <span class="account-summary__label">Vendas na plataforma</span>
            <span class="account-summary__value"><?= $platformSales; ?></span>
            <span class="account-summary__hint">Contabilizamos somente quando voce informa venda via Classi Livre.</span>
        </div>
        <div class="account-summary__card">
            <span class="account-summary__label">Total de vendas registradas</span>
            <span class="account-summary__value"><?= $totalSales; ?></span>
            <span class="account-summary__hint">Inclui vendas pela plataforma e externas.</span>
        </div>
    </div>
</section>

<?php
$tabLabels = [
    'ads' => ['icon' => 'fa-car-rear', 'label' => 'Anuncios'],
];

if ($isPremium) {
    $tabLabels['store'] = ['icon' => 'fa-store', 'label' => 'Minha loja'];
}

$tabLabels['profile'] = ['icon' => 'fa-id-card', 'label' => 'Dados pessoais'];
$tabLabels['security'] = ['icon' => 'fa-lock', 'label' => 'Senha e acesso'];
$tabLabels['services'] = ['icon' => 'fa-tools', 'label' => 'Catálogo de serviços'];
$tabLabels['transactions'] = ['icon' => 'fa-history', 'label' => 'Transações'];
?>

<nav class="account-tabs">
    <?php foreach ($tabLabels as $tabKey => $tabInfo): ?>
        <?php
        $tabClasses = ['account-tab', 'account-tab--' . $tabKey];
        if ($activeTab === $tabKey) {
            $tabClasses[] = 'account-tab--active';
        }
        ?>
        <a class="<?= implode(' ', $tabClasses); ?>" href="/anuncios/meus.php?tab=<?= $tabKey; ?>">
            <i class="fas <?= $tabInfo['icon']; ?>"></i>
            <span><?= $tabInfo['label']; ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($activeTab === 'ads'): ?>
    <section class="account-section" data-tab-panel="ads">
        <h2>Meus anuncios</h2>
        <p class="lead">Monitore status, cadastre novos veiculos e marque vendas realizadas.</p>

        <?php if (!$ads): ?>
            <div class="alert alert-info">Voce ainda nao cadastrou anuncios.</div>
        <?php else: ?>
            <!-- FILTROS E CONTROLES -->
            <div style="margin-bottom:2rem; display:flex; gap:1rem; align-items:center; flex-wrap:wrap; background:#f9fafb; padding:1rem; border-radius:0.5rem; border:1px solid #e5e7eb;">
                <label style="display:flex; align-items:center; gap:0.5rem; font-weight:500; color:#374151;">
                    <i class="fas fa-filter"></i> Filtrar por:
                </label>
                <select id="ads-filter" onchange="filterAds(this.value)" style="padding:0.5rem 1rem; border:1px solid #d1d5db; border-radius:0.375rem; background:white; cursor:pointer; font-size:0.95rem;">
                    <option value="todos">📌 Todos os anúncios</option>
                    <option value="ativos">✅ Ativos (dentro do prazo)</option>
                    <option value="expirados">⚠️ Expirados (graça de 15 dias)</option>
                </select>
            </div>

            <div class="cards-grid" style="margin-top:1.5rem;">
                <?php foreach ($ads as $ad): ?>
                    <article class="card" data-ad-card>
                        <img src="/<?= e($ad['thumb'] ?? 'assets/uploads/images/sample-car-1.jpg'); ?>" alt="<?= e($ad['title']); ?>">
                        <div class="card-content">
                            <div class="flex-between">
                                <h3><?= e($ad['title']); ?></h3>
                                <div style="display:flex;gap:0.35rem;align-items:center;">
                                    <span class="status-pill status-<?= e($ad['status']); ?>">
                                        <?php
                                        $statusLabels = [
                                            'draft' => 'RASCUNHO',
                                            'pending' => 'EM MODERAÇÃO',
                                            'approved' => 'APROVADO',
                                            'rejected' => 'REJEITADO',
                                            'blocked' => 'BLOQUEADO',
                                            'expired' => 'EXPIRADO'
                                        ];
                                        echo e($statusLabels[$ad['status']] ?? strtoupper($ad['status']));
                                        ?>
                                    </span>
                                    <?php if (!empty($ad['sold_at'])): ?>
                                        <span class="sold-badge">
                                            <i class="fas fa-circle-check"></i> Vendido
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="price-tag"><?= format_currency((float)$ad['price']); ?></span>
                            <small>Criado em <?= format_date($ad['created_at']); ?></small>
                            <?php if (!empty($ad['expires_at'])): ?>
                                <small style="display:block;color:#6b7280;">Expira em <?= format_date($ad['expires_at']); ?></small>
                            <?php endif; ?>
                            
                            <!-- BADGE DE EXPIRAÇÃO DURANTE GRAÇA -->
                            <?php if ($ad['status'] === 'expired' && !empty($ad['updated_at'])): ?>
                                <?php
                                    $expirationDate = new DateTime($ad['updated_at']);
                                    $gracePeriodEnd = (clone $expirationDate)->add(new DateInterval('P15D'));
                                    $now = new DateTime();
                                    $daysRemaining = $gracePeriodEnd->diff($now)->days;
                                    if ($now < $gracePeriodEnd) {
                                        $daysRemaining = $gracePeriodEnd->diff($now)->days;
                                        if ($daysRemaining === 0) {
                                            // Verificar se ainda há horas
                                            $daysRemaining = $gracePeriodEnd->diff($now)->h > 0 ? 0 : -1;
                                        }
                                    } else {
                                        $daysRemaining = -1;
                                    }
                                ?>
                                <?php if ($daysRemaining >= 0): ?>
                                    <div style="margin-top:0.75rem; padding:0.75rem; background:#fef3c7; border:1px solid #fcd34d; border-radius:0.375rem; color:#92400e;">
                                        <strong style="display:flex; align-items:center; gap:0.35rem; margin-bottom:0.35rem;">
                                            <i class="fas fa-exclamation-triangle"></i> EXPIRADO
                                        </strong>
                                        <small>Será deletado em <strong><?= $daysRemaining; ?></strong> dia<?= $daysRemaining !== 1 ? 's' : ''; ?></small>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top:0.75rem; padding:0.75rem; background:#fee2e2; border:1px solid #fca5a5; border-radius:0.375rem; color:#7f1d1d;">
                                        <strong style="display:flex; align-items:center; gap:0.35rem;">
                                            <i class="fas fa-times-circle"></i> SERÁ EXCLUÍDO
                                        </strong>
                                        <small>A graça de 15 dias expirou</small>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($ad['status'] === 'rejected' && $ad['reason_rejected']): ?>
                                <div class="alert alert-error" style="margin-top:0.75rem;">Motivo: <?= e($ad['reason_rejected']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($ad['sold_at'])): ?>
                                <div class="alert alert-success" style="margin-top:0.75rem;">
                                    <strong>Vendido em <?= format_date($ad['sold_at']); ?></strong><br>
                                    Origem: <?= $ad['sold_source'] === 'platform' ? 'Classi Livre' : 'Fora da plataforma'; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- OPÇÕES DE AUTO-RENOVAÇÃO -->
                            <?php if ($ad['status'] === 'approved' && empty($ad['sold_at'])): ?>
                                <label style="display:flex; align-items:center; gap:0.5rem; margin-top:0.75rem; padding:0.5rem; background:#f0f9ff; border:1px solid #bfdbfe; border-radius:0.375rem; cursor:pointer; font-size:0.9rem;">
                                    <input type="checkbox" name="auto_renewal_<?= (int)$ad['id']; ?>" value="1" 
                                           <?= (!empty($ad['auto_renewal_enabled']) && (int)$ad['auto_renewal_enabled'] === 1) ? 'checked' : ''; ?>
                                           onchange="toggleAutoRenewal(<?= (int)$ad['id']; ?>, this.checked)"
                                           style="cursor:pointer;">
                                    <span><i class="fas fa-redo"></i> Auto-renovar em 30 dias</span>
                                </label>
                            <?php endif; ?>
                            
                            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:1rem;">
                            <?php if ($ad['status'] === 'draft'): ?>
                                <a class="button" href="/anuncios/pagamento.php?id=<?= (int)$ad['id']; ?>">Finalizar Pagamento</a>
                                     <a class="button secondary" href="/anuncios/editar.php?id=<?= (int)$ad['id']; ?>">Editar Rascunho</a>
                                 <?php else: ?>
                                     <a class="button secondary" href="/anuncios/editar.php?id=<?= (int)$ad['id']; ?>">Editar</a>
                                     <a class="button secondary" href="/anuncios/ver.php?id=<?= (int)$ad['id']; ?>" target="_blank">Visualizar</a>
                                 <?php endif; ?>
                                <form method="post" action="/anuncios/apagar.php" onsubmit="return confirm('Tem certeza que deseja excluir este anuncio?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                    <input type="hidden" name="id" value="<?= (int)$ad['id']; ?>">
                                    <button type="submit" class="button secondary" style="background:#fff;color:#e63946;border:1px solid rgba(230,57,70,0.3);">Excluir</button>
                                </form>
                                
                                <!-- BOTÃO RENOVAR (visible para approved e expired com graça) -->
                                <?php if (($ad['status'] === 'approved' || $ad['status'] === 'expired') && empty($ad['sold_at'])): ?>
                                    <button type="button" class="button" onclick="renewAd(<?= (int)$ad['id']; ?>)" style="background:linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); border:none;">
                                        <i class="fas fa-sync-alt"></i> Renovar por 1 moeda
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($ad['status'] === 'approved' && empty($ad['sold_at'])): ?>
                                    <button type="button" class="button secondary" data-sold-trigger>Marcar como vendido</button>
                                    <form method="post" style="display:none;" data-sold-form data-ad-id="<?= (int)$ad['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                        <input type="hidden" name="action" value="mark_sold">
                                        <input type="hidden" name="tab" value="ads">
                                        <input type="hidden" name="ad_id" value="<?= (int)$ad['id']; ?>">
                                        <input type="hidden" name="sold_source" value="">
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php elseif ($activeTab === 'store'): ?>
    <section class="account-section" data-tab-panel="store">
        <h2>Minha loja premium</h2>
        <p class="lead">Personalize o visual da vitrine e acompanhe o desempenho dos ultimos <?= (int)$storeMetricsSummary['rangeDays']; ?> dias.</p>

        <?php if ($storeThemeErrors): ?>
            <div class="alert alert-error" style="margin-bottom:1rem;">
                <ul style="list-style:disc;padding-left:1.25rem;">
                    <?php foreach ($storeThemeErrors as $error): ?>
                        <li><?= e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
            <input type="hidden" name="action" value="update_store_theme">
            <input type="hidden" name="tab" value="store">

            <div class="store-settings-grid">
                <div class="store-settings-card">
                    <h3>Conteudo em destaque</h3>
                    <p>Mostre uma mensagem curta e opcional para visitantes.</p>
                    <label class="store-settings-field">
                        <span>Mensagem (ate 120 caracteres)</span>
                        <textarea name="highlight_message" maxlength="120" placeholder="Ex: Atendimento personalizado e entrega rapida"><?= e($storeThemeFormData['highlight_message']); ?></textarea>
                    </label>
                    <label class="store-settings-field">
                        <span>Imagem de capa (URL ou caminho)</span>
                        <input type="text" name="cover_image" value="<?= e($storeThemeFormData['cover_image']); ?>" placeholder="https://exemplo.com/imagem.jpg">
                        <small style="color: #666; margin-top: 0.5rem; display: block; font-size: 0.85rem; line-height: 1.4;">
                            💡 <strong>Dica:</strong> Não tem um host de imagens? Use <a href="https://postimages.org/" target="_blank" rel="noopener" style="color: #007bff; text-decoration: underline;">PostImages.org</a> gratuitamente:
                            <br>
                            1️⃣ Envie a imagem no site
                            <br>
                            2️⃣ Clique em <strong>"Link Direto"</strong> (Direct Link)
                            <br>
                            3️⃣ Cole o link aqui
                        </small>
                    </label>
                </div>
                <div class="store-settings-card store-settings-card--instagram">
                    <h3>Conecte seu Instagram</h3>
                    <p>Adicione o link do seu perfil para que visitantes possam acompanhar suas novidades.</p>
                    <label class="store-settings-field">
                        <span>Perfil do Instagram</span>
                        <input type="url" name="instagram_url" value="<?= e($storeThemeFormData['instagram_url'] ?? ''); ?>" placeholder="https://instagram.com/seu_usuario" style="font-size: 1.05rem; padding: 0.9rem; border: 2px solid rgba(240, 148, 51, 0.3); background: rgba(255, 255, 255, 0.9);">
                    </label>
                </div>
            </div>

            <div class="store-settings-actions">
                <button type="submit" class="button button-success">Salvar personalizacao</button>
                <a class="button secondary" href="/loja.php?id=<?= (int)$user['id']; ?>" target="_blank" rel="noopener">Ver minha vitrine</a>
            </div>
        </form>

        <div class="store-analytics">
            <div class="store-analytics__grid">
                <div class="store-analytics__card">
                    <span>Total de eventos</span>
                    <strong><?= (int)$storeMetricsSummary['total']; ?></strong>
                    <small>Ultimos <?= (int)$storeMetricsSummary['rangeDays']; ?> dias</small>
                </div>
                <?php foreach ($storeMetricsSummary['highlights'] as $highlightEvent): ?>
                    <div class="store-analytics__card">
                        <span><?= e($highlightEvent['label']); ?></span>
                        <strong><?= (int)$highlightEvent['total']; ?></strong>
                        <small>Ultimos <?= (int)$storeMetricsSummary['rangeDays']; ?> dias</small>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="store-analytics__split">
                <div class="store-analytics__section">
                    <h3>Eventos por tipo</h3>
                    <?php $eventRows = array_filter($storeMetricsSummary['byType'], static fn($row) => (int)($row['total'] ?? 0) > 0); ?>
                    <?php if (!$eventRows): ?>
                        <p class="store-analytics__empty">Nenhum evento registrado ainda.</p>
                    <?php else: ?>
                        <ul class="store-analytics__list">
                            <?php foreach ($eventRows as $row): ?>
                                <li class="store-analytics__list-item">
                                    <span><?= e($row['label']); ?></span>
                                    <strong><?= (int)$row['total']; ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="store-analytics__section">
                    <h3>Anuncios mais clicados</h3>
                    <?php if (!$storeMetricsSummary['topAds']): ?>
                        <p class="store-analytics__empty">Ainda sem cliques registrados.</p>
                    <?php else: ?>
                        <ul class="store-analytics__list">
                            <?php foreach ($storeMetricsSummary['topAds'] as $row): ?>
                                <li class="store-analytics__list-item">
                                    <span><?= e($row['title']); ?></span>
                                    <strong><?= (int)$row['total']; ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="store-analytics__section">
                <h3>Evolucao diaria</h3>
                <?php if (!$storeMetricsSummary['daily']): ?>
                    <p class="store-analytics__empty">Aguardando interacoes para exibir tendencia.</p>
                <?php else: ?>
                    <div class="store-analytics__daily">
                        <?php foreach ($storeMetricsSummary['daily'] as $row): ?>
                            <div class="store-analytics__daily-item">
                                <span><?= e($row['label']); ?></span>
                                <span><?= (int)$row['total']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php elseif ($activeTab === 'profile'): ?>
    <?php include __DIR__ . '/../anuncios/partials/profile_form_handler.php'; ?>
    <?php include __DIR__ . '/../anuncios/partials/profile_form_v2.php'; ?>
<?php elseif ($activeTab === 'security'): ?>
    <section class="account-section" data-tab-panel="security">
        <h2>Seguranca e acesso</h2>
        <p class="lead">Troque sua senha periodicamente para manter sua conta protegida.</p>

        <?php if ($passwordErrors): ?>
            <div class="alert alert-error" style="margin-bottom:1rem;">
                <ul style="list-style:disc;padding-left:1.25rem;">
                    <?php foreach ($passwordErrors as $error): ?>
                        <li><?= e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" style="display:grid;gap:1rem;max-width:420px;">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="tab" value="security">

            <div class="input-group">
                <label for="current_password">Senha atual</label>
                <div style="position:relative; display:flex; align-items:center;">
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" style="flex:1; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem; padding-right:2.5rem;">
                    <button type="button" class="toggle-password" data-target="current_password" style="position:absolute; right:0.75rem; background:none; border:none; cursor:pointer; color:#6b7280; font-size:1.1rem; padding:0.25rem; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-eye" style="width:1.2rem; height:1.2rem;"></i>
                    </button>
                </div>
            </div>
            <div class="input-group">
                <label for="new_password">Nova senha</label>
                <div style="position:relative; display:flex; align-items:center;">
                    <input type="password" id="new_password" name="new_password" required autocomplete="new-password" placeholder="Minimo 8 caracteres" style="flex:1; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem; padding-right:2.5rem;">
                    <button type="button" class="toggle-password" data-target="new_password" style="position:absolute; right:0.75rem; background:none; border:none; cursor:pointer; color:#6b7280; font-size:1.1rem; padding:0.25rem; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-eye" style="width:1.2rem; height:1.2rem;"></i>
                    </button>
                </div>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirmar nova senha</label>
                <div style="position:relative; display:flex; align-items:center;">
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" style="flex:1; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem; padding-right:2.5rem;">
                    <button type="button" class="toggle-password" data-target="confirm_password" style="position:absolute; right:0.75rem; background:none; border:none; cursor:pointer; color:#6b7280; font-size:1.1rem; padding:0.25rem; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-eye" style="width:1.2rem; height:1.2rem;"></i>
                    </button>
                </div>
            </div>
            <p style="color:#6b7280;font-size:0.85rem;">Dica: combine letras maiusculas, minusculas e numeros para aumentar a seguranca.</p>
            <button type="submit" class="button">Atualizar senha</button>
        </form>

        <script>
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                const field = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    field.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        </script>
    </section>
<?php elseif ($activeTab === 'services'): ?>
    <section class="account-section" data-tab-panel="services">
        <h2>Catálogo de Serviços</h2>
        <p class="lead">Divulgue serviços especializados e destaque sua loja na vitrine de parceiros Classi Livre.</p>

        <!-- Aviso de Responsabilidade -->
        <div style="background:#fef3c7; border:2px solid #fcd34d; border-radius:0.75rem; padding:1.25rem; margin-bottom:1.5rem;">
            <div style="display:flex; gap:1rem; align-items:flex-start;">
                <i class="fas fa-exclamation-triangle" style="color:#b45309; font-size:1.2rem; flex-shrink:0; margin-top:0.2rem;"></i>
                <div>
                    <h3 style="margin:0 0 0.5rem 0; color:#92400e; font-size:1rem; font-weight:700;">Responsabilidade Legal</h3>
                    <p style="margin:0; color:#78350f; font-size:0.95rem; line-height:1.6;">
                        Você é <strong>responsável pelas informações publicadas</strong> nesta plataforma. Mesmo que esteja publicando anúncio para outra pessoa, os dados fornecidos devem ser precisos e verdadeiros. Informações falsas, enganosas ou que violem as regras da plataforma podem resultar em:
                        <strong style="display:block; margin-top:0.5rem;">• Bloqueio da conta • Remoção de anúncios • Ações legais • Penalidades financeiras</strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Aviso Importante: Informações do Formulário -->
        <div style="background:#fef2f2; border:3px solid #dc2626; border-radius:0.75rem; padding:1.5rem; margin-bottom:1.5rem;">
            <div style="display:flex; gap:1rem; align-items:flex-start;">
                <i class="fas fa-circle-exclamation" style="color:#dc2626; font-size:1.3rem; flex-shrink:0; margin-top:0.15rem;"></i>
                <div>
                    <h3 style="margin:0 0 0.75rem 0; color:#991b1b; font-size:1.1rem; font-weight:700;">⚠️ INFORMAÇÕES IMPORTANTES DO FORMULÁRIO</h3>
                    <ul style="margin:0; padding:0 0 0 1.5rem; color:#991b1b; font-size:0.95rem; line-height:1.8;">
                        <li><strong>Dados públicos:</strong> As informações que você preencher aqui serão <strong>exibidas para o público</strong> em nosso site e no Google.</li>
                        <li><strong>CPF/CNPJ opcional:</strong> A exibição do CPF/CNPJ é <strong>opcional</strong>. Você escolhe se quer mostrar ou não.</li>
                        <li><strong>Endereço Google Maps:</strong> Se informar um endereço, <strong>preencha exatamente como aparece no Google Maps</strong> (rua, número, complemento, bairro, cidade). Isto garante que o Google Maps e Waze encontrem sua localização corretamente.</li>
                        <li style="color:#dc2626; font-weight:700;">⛔ <strong>SEM POSSIBILIDADE DE EDIÇÃO:</strong> Após publicar e pagar, <strong>NÃO será possível editar essas informações</strong>. Confirme TUDO antes de publicar!</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if ($serviceErrors): ?>
            <div class="alert alert-error" style="margin-bottom:1rem;">
                <ul style="list-style:disc;padding-left:1.25rem;">
                    <?php foreach ($serviceErrors as $error): ?>
                        <li><?= e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="create-service-form" method="post" style="display:grid;gap:1rem;margin-bottom:2rem;">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
            <input type="hidden" name="action" value="create_service">
            <input type="hidden" name="tab" value="services">

            <div class="input-group">
                <label for="service_name">Nome do servi&ccedil;o / empresa <span style="color:#dc2626;">*</span></label>
                <input type="text" id="service_name" name="service_name" value="<?= e($serviceFormData['name']); ?>" required>
            </div>

            <div class="input-group">
                <label for="service_category">Categoria principal <span style="color:#dc2626;">*</span></label>
                <select id="service_category" name="service_category" required data-service-category>
                    <option value="">Selecione</option>
                    <?php foreach ($serviceCategories as $key => $label): ?>
                        <option value="<?= $key; ?>" <?= $serviceFormData['category'] === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group <?= $serviceFormData['category'] === 'custom' ? '' : 'hidden'; ?>" data-service-custom>
                <label for="service_custom_category">Descreva o serviço</label>
                <input type="text" id="service_custom_category" name="service_custom_category" value="<?= e($serviceFormData['custom_category']); ?>" placeholder="Ex: Elétrica automotiva, blindagem..." list="service_custom_suggestions" autocomplete="off">
                <datalist id="service_custom_suggestions">
                    <?php foreach ($customServiceSuggestions as $suggestion): ?>
                        <option value="<?= e($suggestion); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="input-group">
                <label for="service_description">Descrição detalhada do serviço</label>
                <textarea id="service_description" name="service_description" rows="4" placeholder="Descreva brevemente seu serviço: expertise, tipos de trabalho, diferenciais, etc. (máximo 150 caracteres)" data-word-counter maxlength="150"><?= e($serviceFormData['description']); ?></textarea>
                <small style="color:#6b7280; display:flex; justify-content:space-between; margin-top:0.25rem;">
                    <span><i class="fas fa-info-circle"></i> Seja breve e claro</span>
                    <span id="word-count" style="font-weight:600; color:#136f33;">0 / 150 caracteres</span>
                </small>
            </div>

            <div class="input-grid" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
                <div class="input-group">
                <label for="service_phone">Telefone <span style="color:#dc2626;">*</span></label>
                <input type="text" id="service_phone" name="service_phone" value="<?= e($serviceFormData['phone']); ?>" placeholder="Com DDD" data-mask="phone">
                </div>
                <div class="input-group">
                <label for="service_whatsapp">WhatsApp</label>
                <input type="text" id="service_whatsapp" name="service_whatsapp" value="<?= e($serviceFormData['whatsapp']); ?>" placeholder="Opcional" data-mask="phone">
                </div>
                <div class="input-group">
                    <label for="service_email">Email</label>
                    <input type="email" id="service_email" name="service_email" value="<?= e($serviceFormData['email']); ?>" placeholder="Opcional">
                </div>
            </div>
            <small style="color:#6b7280; margin-bottom:1rem; display:block;"><span style="color:#dc2626;">*</span> Informe ao menos telefone ou WhatsApp</small>
            <div class="input-group">
                <label>Documento do prestador (CPF/CNPJ) <span style="color:#dc2626;">*</span></label>
                <div class="document-radio-group">
                    <label>
                        <input type="radio" name="service_document_type" value="cpf" <?= $serviceFormData['document_type'] === 'cpf' ? 'checked' : ''; ?>>
                        CPF
                    </label>
                    <label>
                        <input type="radio" name="service_document_type" value="cnpj" <?= $serviceFormData['document_type'] === 'cnpj' ? 'checked' : ''; ?>>
                        CNPJ
                    </label>
                </div>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" id="service_document_number" name="service_document_number" value="<?= e($serviceFormData['document_number'] !== '' ? format_document($serviceFormData['document_number'], $serviceFormData['document_type']) : ''); ?>" maxlength="<?= $serviceFormData['document_type'] === 'cnpj' ? '18' : '14'; ?>" placeholder="<?= $serviceFormData['document_type'] === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00'; ?>" data-mask="document" data-document-type="<?= e($serviceFormData['document_type']); ?>" data-document-input data-document-radio="service_document_type" style="flex: 1;">
                    <span id="service_document_validation" style="position: absolute; right: 12px; font-size: 1.3rem; font-weight: bold; cursor: default;"></span>
                </div>
            </div>
            <label style="display:flex;gap:0.5rem;align-items:center;">
                <input type="checkbox" name="service_show_document" value="1" <?= $serviceFormData['show_document'] ? 'checked' : ''; ?>>
                Exibir CPF/CNPJ no anuncio do serviço
            </label>

            <h3 style="font-size:1.1rem;font-weight:600;color:#1f2937;margin-top:1rem;">Endereço do serviço <span style="color:#6b7280; font-size:0.9rem;">(Opcional)</span></h3>
            <p style="color:#6b7280; font-size:0.9rem; margin-bottom:1rem;">Deixe em branco se prestar serviços em domicílio ou em vários locais</p>
            <div class="input-grid" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
                <div class="input-group" style="grid-column:1/-1;">
                    <label for="service_address_postal_code">CEP</label>
                    <input type="text" id="service_address_postal_code" name="service_address_postal_code" value="<?= e($serviceFormData['address_postal_code']); ?>" maxlength="9" placeholder="00000-000" inputmode="numeric" data-mask="postal" data-service-cep>
                </div>
                <div class="input-group">
                    <label for="service_address_street">Rua</label>
                    <input type="text" id="service_address_street" name="service_address_street" value="<?= e($serviceFormData['address_street']); ?>" data-service-logradouro>
                </div>
                <div class="input-group">
                    <label for="service_address_number">Número</label>
                    <input type="text" id="service_address_number" name="service_address_number" value="<?= e($serviceFormData['address_number']); ?>">
                </div>
                <div class="input-group">
                    <label for="service_address_complement">Complemento</label>
                    <input type="text" id="service_address_complement" name="service_address_complement" value="<?= e($serviceFormData['address_complement']); ?>">
                </div>
                <div class="input-group">
                    <label for="service_address_neighborhood">Bairro</label>
                    <input type="text" id="service_address_neighborhood" name="service_address_neighborhood" value="<?= e($serviceFormData['address_neighborhood']); ?>">
                </div>
                <div class="input-group">
                    <label for="service_address_city">Cidade</label>
                    <input type="text" id="service_address_city" name="service_address_city" value="<?= e($serviceFormData['address_city']); ?>" autocomplete="off" data-service-cidade>
                </div>
                <div class="input-group">
                    <label for="service_address_state">UF</label>
                    <select id="service_address_state" name="service_address_state" data-service-uf>
                        <option value="">Selecione um estado</option>
                        <?php foreach ($stateOptions as $code => $label): ?>
                            <option value="<?= e($code); ?>" <?= strtoupper((string)$serviceFormData['address_state']) === $code ? 'selected' : ''; ?>>
                                <?= e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <p style="color:#6b7280;font-size:0.85rem;">Ao enviar, voce sera direcionado para concluir o pagamento do anuncio de servi&ccedil;os.</p>
            <button type="submit" class="button">Cadastrar servi&ccedil;o</button>
        </form>

        <h3 style="font-size:1.2rem;font-weight:600;color:#1f2937;margin-bottom:1rem;">Hist&oacute;rico de servi&ccedil;os</h3>
        <?php if (!$services): ?>
            <div class="alert alert-info">Nenhum servi&ccedil;o cadastrado at&eacute; o momento.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="service-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Servi&ccedil;o</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th>A&ccedil;&otilde;es</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>#<?= (int)$service['id']; ?></td>
                                <td>
                                    <strong><?= e($service['name']); ?></strong><br>
                                    <small><?= e($service['category']); ?><?= $service['custom_category'] ? ' - ' . e($service['custom_category']) : ''; ?></small><br>
                                    <?php if (!empty($service['address_city'])): ?>
                                        <small><?= e($service['address_city']); ?><?= $service['address_state'] ? ' / ' . e($service['address_state']) : ''; ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($service['document_number'])): ?>
                                        <br><small><i class="fa-solid fa-id-card text-amber-500 mr-1"></i><?= e(format_document($service['document_number'], $service['document_type'] ?? 'cpf')); ?><?= !empty($service['show_document']) ? ' (visivel)' : ''; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($serviceStatusLabels[$service['status']] ?? strtoupper((string)$service['status'])); ?></td>
                                <td><?= format_date($service['created_at']); ?></td>
                                <td>
                                    <div class="service-actions">
                                        <div class="service-actions__auto-renew" style="margin-bottom:1rem;">
                                            <label style="display:flex; gap:0.6rem; align-items:flex-start; padding:0.65rem 0.75rem; background:#ecfeff; border:1px solid #67e8f9; border-radius:0.5rem; cursor:pointer; font-size:0.9rem;">
                                                <input type="checkbox"
                                                       name="service_auto_renewal_<?= (int)$service['id']; ?>"
                                                       value="1"
                                                       <?= (int)($service['auto_renewal_enabled'] ?? 0) === 1 ? 'checked' : ''; ?>
                                                       onchange="toggleServiceAutoRenewal(<?= (int)$service['id']; ?>, this.checked)"
                                                       style="margin-top:0.15rem;">
                                                <span>
                                                    <strong style="display:block; color:#0f766e;">Renovacao automatica</strong>
                                                    <small style="display:block; color:#0f172a;">Ao ativar, cada renovacao consome 1 Clivre Medal e adiciona +30 dias de validade. Se o servi&ccedil;o estiver expirado, a cobranca e a reativa&ccedil;&atilde;o ocorrem imediatamente.</small>
                                                </span>
                                            </label>
                                        </div>

                                        <?php if ($service['status'] === 'active'): ?>
                                            <span class="service-actions__note" style="color:#16a34a;font-weight:600;">Servico ativo na vitrine.</span>
                                            <?php if (!empty($service['expires_at'])): ?>
                                                <small class="service-actions__note">Expira em <?= format_date($service['expires_at']); ?></small>
                                            <?php endif; ?>
                                            <?php if (should_show_service_renewal_button($service)): ?>
                                                <div style="margin-top:1rem; padding:1rem; background:#fee2e2; border:2px solid #dc2626; border-radius:0.5rem;">
                                                    <p style="margin:0 0 0.75rem 0; color:#991b1b; font-weight:700; font-size:1.05rem;">
                                                        <i class="fas fa-exclamation-circle" style="margin-right:0.5rem;"></i>
                                                        Seu servico vence em breve!
                                                    </p>
                                                    <?php $daysLeft = get_service_days_remaining($service['expires_at']); ?>
                                                    <small style="color:#7f1d1d; display:block;">
                                                        Renove automaticamente acima ou aguarde o vencimento para habilitar a renovacao manual (+30 dias). Restam <?= abs($daysLeft); ?> dia<?= abs($daysLeft) !== 1 ? 's' : ''; ?>.
                                                    </small>
                                                </div>
                                            <?php endif; ?>

                                        <?php elseif ($service['status'] === 'pending_payment'): ?>
                                            <?php if ($service['payment_status'] === 'paid'): ?>
                                                <span class="service-actions__note" style="color:#0369a1;font-weight:600;"><i class="fas fa-check-circle"></i> Pagamento confirmado!</span>
                                                <small class="service-actions__note">Clique em "Publicar" para ativar na vitrine.</small>
                                            <?php else: ?>
                                                <span class="service-actions__note" style="color:#dc2626;font-weight:600;">Pagamento pendente.</span>
                                                <small class="service-actions__note">Conclua o pagamento para publicar o servico.</small>
                                                <a href="/servicos/pagamento.php?serviceId=<?= (int)$service['id']; ?>"
                                                   class="button"
                                                   style="background:#ea580c;color:#fff;padding:0.5rem 1rem;font-size:0.95rem;text-decoration:none;display:inline-block;border-radius:0.5rem;margin-top:0.75rem;">
                                                    Finalizar Pagamento
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($service['status'] === 'expired'): ?>
                                            <span class="service-actions__note" style="color:#a16207;">Servico expirou. Renove para manter ativo.</span>
                                            <?php if (!empty($service['expires_at'])): ?>
                                                <small class="service-actions__note">Expirou em <?= format_date($service['expires_at']); ?></small>
                                            <?php endif; ?>
                        <?php if (should_show_service_renewal_button($service)): ?>
                                                <div style="margin-top:1rem; padding:1rem; background:#fee2e2; border:2px solid #dc2626; border-radius:0.5rem;">
                                                    <p style="margin:0 0 0.75rem 0; color:#991b1b; font-weight:700; font-size:1.05rem;">
                                                        <i class="fas fa-exclamation-circle" style="margin-right:0.5rem;"></i>
                                                        Renovar antes que seja deletado!
                                                    </p>
                                                    <small style="color:#7f1d1d; display:block; margin-bottom:0.75rem;">
                                                        Se nao renovar, o servico sera excluido automaticamente em 15 dias.
                                                    </small>
                                                    <form method="post" class="service-actions__form" onsubmit="return confirm('Deseja renovar este servico e adicionar mais 30 dias de validade?');">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="renew_service">
                                                        <input type="hidden" name="tab" value="services">
                                                        <input type="hidden" name="service_id" value="<?= (int)$service['id']; ?>">
                                                        <button type="submit"
                                                                class="button"
                                                                style="background:#dc2626; color:#fff; padding:0.75rem 1.5rem; font-size:1rem; font-weight:700; text-decoration:none; display:inline-block; border-radius:0.5rem; margin-top:0.5rem;">
                                                            <i class="fas fa-redo" style="margin-right:0.5rem;"></i>
                                                            Renovar Agora
                                                        </button>
                                                    </form>
                                                    <?php if ($servicesFreeForAllMode): ?>
                                                        <small style="display:block; margin-top:0.5rem; color:#6b7280;">Renovacao gratuita: nao ha cobranca e o servico ganha mais 30 dias.</small>
                                                    <?php else: ?>
                                                        <small style="display:block; margin-top:0.5rem; color:#6b7280;">Ao renovar manualmente, voce sera direcionado ao pagamento via Mercado Pago para adicionar +30 dias.</small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <span class="service-actions__note">Status atual: <?= e($serviceStatusLabels[$service['status']] ?? strtoupper((string)$service['status'])); ?>.</span>
                                        <?php endif; ?>

                                        <form method="post" class="service-actions__form" onsubmit="return confirm('Excluir permanentemente este servico?');" style="margin-top:1rem;">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete_service">
                                            <input type="hidden" name="tab" value="services">
                                            <input type="hidden" name="service_id" value="<?= (int)$service['id']; ?>">
                                            <button type="submit" class="button button-danger service-actions__button">Excluir servico</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php elseif ($activeTab === 'transactions'): ?>
    <section class="account-section" data-tab-panel="transactions">
        <h2 style="margin-bottom:0.5rem;">
            <i class="fas fa-receipt" style="margin-right:0.5rem;"></i>
            Histórico de Transações
        </h2>
        <p class="lead">Acompanhe todas as suas movimentações de moedas e pagamentos do Catálogo de Serviços.</p>

        <!-- ===== TRANSAÇÕES DE MOEDAS ===== -->
        <div style="margin-top:2rem;">
            <h3 style="margin-bottom:1rem; color:#667eea; font-size:1.2rem; border-bottom:2px solid #667eea; padding-bottom:0.5rem;">
                💰 Histórico de Transações (Moedas)
            </h3>
            
            <?php if ($coin_history): ?>
                <div style="overflow-x:auto; margin-top:1rem;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <thead>
                            <tr style="background:#f5f5f5; border-bottom:2px solid #ddd;">
                                <th style="padding:0.75rem; text-align:left;">Data/Hora</th>
                                <th style="padding:0.75rem; text-align:left;">Detalhes</th>
                                <th style="padding:0.75rem; text-align:center;">Moedas</th>
                                <th style="padding:0.75rem; text-align:right;">Valor</th>
                                <th style="padding:0.75rem; text-align:left;">Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coin_history as $tx): ?>
                                <?php if (($tx['type'] ?? '') === 'coin_purchase'): ?>
                                    <tr style="border-bottom:1px solid #eee;">
                                        <td style="padding:0.75rem; white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($tx['created_at'])); ?></td>
                                        <td style="padding:0.75rem;">
                                            <strong>Compra de moedas</strong><br>
                                            <small><?= e($tx['package_name']); ?></small>
                                        </td>
                                        <td style="padding:0.75rem; text-align:center; font-weight:600;">
                                            <?= (int)$tx['total_amount']; ?> moedas<br>
                                            <small style="color:#6b7280;">Base: <?= (int)$tx['base_amount']; ?> | Bônus: <?= (int)$tx['bonus_amount']; ?></small>
                                        </td>
                                        <td style="padding:0.75rem; text-align:right; font-weight:600; color:#166534;">
                                            R$ <?= number_format((float)$tx['price_amount'], 2, ',', '.'); ?>
                                        </td>
                                        <td style="padding:0.75rem; color:#374151;">
                                            <div><strong>Pagamento:</strong> <?= e($tx['payment_method']); ?></div>
                                            <?php if (!empty($tx['payment_reference'])): ?>
                                                <div><strong>Referência:</strong> <?= e($tx['payment_reference']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $raw = $tx['raw']; ?>
                                    <tr style="border-bottom:1px solid #eee;">
                                        <td style="padding:0.75rem; white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($raw['created_at'])); ?></td>
                                        <td style="padding:0.75rem;">
                                            <?php 
                                            $tx_colors = [
                                                'buy' => '#e8f5e9',
                                                'bonus' => '#fff3e0',
                                                'spend' => '#ffebee',
                                                'debit' => '#ffebee',
                                                'ad_publish' => '#e3f2fd',
                                                'ad_feature' => '#f3e5f5',
                                                'admin_add' => '#e0f2f1',
                                                'admin_remove' => '#fce4ec',
                                                'refund' => '#f1f8e9'
                                            ];
                                            $tx_text_colors = [
                                                'buy' => '#2e7d32',
                                                'bonus' => '#f57f17',
                                                'spend' => '#c62828',
                                                'debit' => '#c62828',
                                                'ad_publish' => '#1565c0',
                                                'ad_feature' => '#6a1b9a',
                                                'admin_add' => '#00695c',
                                                'admin_remove' => '#ad1457',
                                                'refund' => '#558b2f'
                                            ];
                                            $type_labels = [
                                                'buy' => '🛒 Compra',
                                                'bonus' => '🎁 Bônus',
                                                'spend' => '💸 Gasto',
                                                'debit' => '🚗 Serviço Automotivo',
                                                'ad_publish' => '📢 Anúncio',
                                                'ad_feature' => '⭐ Destaque',
                                                'admin_add' => '➕ Adicionado',
                                                'admin_remove' => '➖ Removido',
                                                'refund' => '↩ Reembolso'
                                            ];
                                            ?>
                                            <span style="display:inline-block; padding:0.35rem 0.65rem; border-radius:6px; font-size:0.8rem; font-weight:600; background:<?= $tx_colors[$raw['type']] ?? '#f5f5f5'; ?>; color:<?= $tx_text_colors[$raw['type']] ?? '#333'; ?>;">
                                                <?= $type_labels[$raw['type']] ?? ucfirst(str_replace('_', ' ', $raw['type'])); ?>
                                            </span>
                                        </td>
                                        <td style="padding:0.75rem; text-align:center; font-weight:bold; color:<?= ($raw['amount'] > 0 ? '#2e7d32' : '#c62828'); ?>;">
                                            <?php 
                                            // Se é transação de serviço (em centavos), converter para reais
                                            if ($raw['reference_type'] === 'user_service_listings') {
                                                $displayAmount = $raw['amount'] / 100;
                                                echo 'R$ ' . number_format($displayAmount, 2, ',', '.');
                                            } else {
                                                echo ($raw['amount'] > 0 ? '+' : '') . (int)$raw['amount'];
                                            }
                                            ?>
                                        </td>
                                        <td style="padding:0.75rem; text-align:right; font-weight:600; color:#d32f2f;">
                                            -
                                        </td>
                                        <td style="padding:0.75rem; color:#666;">
                                            <?= e($raw['description']); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background:#f5f5f5; padding:2rem; border-radius:8px; text-align:center; color:#999;">
                    <i class="fas fa-inbox" style="font-size:2rem; margin-bottom:0.5rem; display:block;"></i>
                    <p>Nenhuma transação de moedas encontrada.</p>
                </div>
            <?php endif; ?>
        </div>
        <!-- ===== TRANSAÇÕES DE Catálogo de Serviços ===== -->
        <div style="margin-top:2.5rem;">
            <h3 style="margin-bottom:1rem; color:#f97316; font-size:1.2rem; border-bottom:2px solid #f97316; padding-bottom:0.5rem;">
                🚗 Histórico de Catálogo de Serviços 
            </h3>
            
            <?php if ($service_transactions): ?>
                <div style="overflow-x:auto; margin-top:1rem;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <thead>
                            <tr style="background:#f5f5f5; border-bottom:2px solid #ddd;">
                                <th style="padding:0.75rem; text-align:left;">Data</th>
                                <th style="padding:0.75rem; text-align:left;">Serviço</th>
                                <th style="padding:0.75rem; text-align:center;">Status</th>
                                <th style="padding:0.75rem; text-align:center;">Pagamento</th>
                                <th style="padding:0.75rem; text-align:right;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($service_transactions as $svc): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:0.75rem; white-space:nowrap;">
                                    <?= date('d/m/Y', strtotime($svc['created_at'])); ?>
                                </td>
                                <td style="padding:0.75rem;">
                                    <strong><?= e($svc['name'] ?? 'Serviço'); ?></strong>
                                </td>
                                <td style="padding:0.75rem; text-align:center;">
                                    <span style="display:inline-block; padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; font-weight:600;
                                                 background:<?php 
                                                 $status_colors = [
                                                     'pending' => '#fff3cd',
                                                     'published' => '#d4edda',
                                                     'paid' => '#d4edda',
                                                     'failed' => '#f8d7da',
                                                     'refunded' => '#e2e3e5',
                                                     'expired' => '#fff3cd',
                                                     'suspended' => '#f8d7da',
                                                     'archived' => '#e2e3e5'
                                                 ];
                                                 echo $status_colors[$svc['status']] ?? '#e2e3e5';
                                                 ?>; color:<?php
                                                 $status_text_colors = [
                                                     'pending' => '#856404',
                                                     'published' => '#155724',
                                                     'paid' => '#155724',
                                                     'failed' => '#721c24',
                                                     'refunded' => '#383d41',
                                                     'expired' => '#856404',
                                                     'suspended' => '#721c24',
                                                     'archived' => '#383d41'
                                                 ];
                                                 echo $status_text_colors[$svc['status']] ?? '#383d41';
                                                 ?>;">
                                        <?php 
                                        $status_emoji = [
                                            'pending' => '⏳',
                                            'published' => '✅',
                                            'paid' => '✅',
                                            'failed' => '❌',
                                            'refunded' => '🔄',
                                            'expired' => '⏰',
                                            'suspended' => '🚫',
                                            'archived' => '📦'
                                        ];
                                        echo ($status_emoji[$svc['status']] ?? '•') . ' ' . ucfirst(str_replace('_', ' ', $svc['status']));
                                        ?>
                                    </span>
                                </td>
                                <td style="padding:0.75rem; text-align:center;">
                                    <span style="display:inline-block; padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; font-weight:600;
                                                 background:<?php 
                                                 $payment_colors = [
                                                     'pending' => '#fff3cd',
                                                     'paid' => '#d4edda',
                                                     'failed' => '#f8d7da',
                                                     'refunded' => '#e2e3e5'
                                                 ];
                                                 echo $payment_colors[$svc['payment_status']] ?? '#e2e3e5';
                                                 ?>; color:<?php
                                                 $payment_text_colors = [
                                                     'pending' => '#856404',
                                                     'paid' => '#155724',
                                                     'failed' => '#721c24',
                                                     'refunded' => '#383d41'
                                                 ];
                                                 echo $payment_text_colors[$svc['payment_status']] ?? '#383d41';
                                                 ?>;">
                                        <?php 
                                        $payment_emoji = [
                                            'pending' => '⏳',
                                            'paid' => '✅',
                                            'failed' => '❌',
                                            'refunded' => '🔄'
                                        ];
                                        echo ($payment_emoji[$svc['payment_status']] ?? '•') . ' ' . ucfirst(str_replace('_', ' ', $svc['payment_status']));
                                        ?>
                                    </span>
                                </td>
                                <td style="padding:0.75rem; text-align:right; font-weight:bold;">
                                    R$ <?= number_format($svc['price'] ?? 0, 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background:#f5f5f5; padding:2rem; border-radius:8px; text-align:center; color:#999;">
                    <i class="fas fa-inbox" style="font-size:2rem; margin-bottom:0.5rem; display:block;"></i>
                    <p>Nenhuma transação de serviço encontrada.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== TRANSAÇÕES PREMIUM ===== -->
        <div style="margin-top:2.5rem;">
            <h3 style="margin-bottom:1rem; color:#dc2626; font-size:1.2rem; border-bottom:2px solid #dc2626; padding-bottom:0.5rem;">
                👑 Histórico Premium
            </h3>

            <?php if ($premium_transactions): ?>
                <div style="overflow-x:auto; margin-top:1rem;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <thead>
                            <tr style="background:#fef2f2; border-bottom:2px solid #dc2626;">
                                <th style="padding:0.75rem; text-align:left;">Data/Hora</th>
                                <th style="padding:0.75rem; text-align:left;">Plano</th>
                                <th style="padding:0.75rem; text-align:right;">Valor</th>
                                <th style="padding:0.75rem; text-align:left;">Vigência</th>
                                <th style="padding:0.75rem; text-align:left;">Pagamento</th>
                                <th style="padding:0.75rem; text-align:left;">Referências</th>
                                <th style="padding:0.75rem; text-align:left;">Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($premium_transactions as $ptx): ?>
                                <?php
                                    $planName = (string)($ptx['plan_name'] ?? 'Plano Premium');
                                    $amountPaid = null;
                                    if (isset($ptx['amount_paid']) && $ptx['amount_paid'] !== null) {
                                        $amountPaid = (float)$ptx['amount_paid'];
                                    }
                                    if ($amountPaid === null || $amountPaid <= 0) {
                                        $amountPaid = (float)($ptx['price_decimal'] ?? 0);
                                    }
                                    $currencyCode = strtoupper((string)($ptx['payment_currency'] ?? 'BRL'));
                                    if ($currencyCode === '') {
                                        $currencyCode = 'BRL';
                                    }
                                    $currencyPrefix = $currencyCode === 'BRL' ? 'R$ ' : ($currencyCode . ' ');
                                    $planValue = number_format($amountPaid, 2, ',', '.');
                                    $durationDays = (int)($ptx['duration_days'] ?? 0);
                                    $startAt = !empty($ptx['subscription_started_at']) ? date('d/m/Y H:i', strtotime($ptx['subscription_started_at'])) : '-';
                                    $endAt = !empty($ptx['subscription_expires_at']) ? date('d/m/Y H:i', strtotime($ptx['subscription_expires_at'])) : 'Sem data definida';
                                    $recordedAt = $ptx['transaction_created_at'] ?? $ptx['subscription_created_at'] ?? $ptx['subscription_started_at'] ?? null;
                                    $paymentStatus = strtolower((string)($ptx['transaction_payment_status'] ?? $ptx['subscription_payment_status'] ?? ''));
                                    $statusMap = [
                                        'pending' => ['label' => 'Pendente', 'bg' => '#fff7ed', 'color' => '#c2410c', 'icon' => '⏳'],
                                        'approved' => ['label' => 'Aprovado', 'bg' => '#dcfce7', 'color' => '#166534', 'icon' => '✅'],
                                        'paid' => ['label' => 'Pago', 'bg' => '#dcfce7', 'color' => '#166534', 'icon' => '✅'],
                                        'rejected' => ['label' => 'Recusado', 'bg' => '#fee2e2', 'color' => '#b91c1c', 'icon' => '❌'],
                                        'cancelled' => ['label' => 'Cancelado', 'bg' => '#fee2e2', 'color' => '#b91c1c', 'icon' => '❌'],
                                        'refunded' => ['label' => 'Estornado', 'bg' => '#e0f2fe', 'color' => '#075985', 'icon' => '↩'],
                                    ];
                                    $statusInfo = $statusMap[$paymentStatus] ?? ['label' => ucfirst($paymentStatus ?: 'Desconhecido'), 'bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'ℹ'];
                                    $paymentMethod = (string)($ptx['payment_method'] ?? 'Mercado Pago');
                                    $paymentId = $ptx['payment_id'] ?? '';
                                    $externalReference = $ptx['external_reference'] ?? '';
                                    $observation = trim((string)($ptx['description'] ?? ''));
                                ?>
                                <tr style="border-bottom:1px solid #f5d0c5;">
                                    <td style="padding:0.75rem; white-space:nowrap;"><?= $recordedAt ? date('d/m/Y H:i', strtotime($recordedAt)) : '-'; ?></td>
                                    <td style="padding:0.75rem;">
                                        <strong><?= e($planName); ?></strong><br>
                                        <small style="color:#6b7280;">Duração: <?= $durationDays; ?> dia(s)</small>
                                    </td>
                                    <td style="padding:0.75rem; text-align:right; font-weight:600; color:#991b1b;">
                                        <?= $currencyPrefix; ?><?= $planValue; ?>
                                    </td>
                                    <td style="padding:0.75rem;">
                                        <span style="display:block; font-weight:600; color:#1f2937;"><?= $startAt; ?></span>
                                        <span style="display:block; color:#6b7280; font-size:0.85rem;">até <?= $endAt; ?></span>
                                    </td>
                                    <td style="padding:0.75rem;">
                                        <span style="display:inline-block; padding:0.35rem 0.65rem; border-radius:6px; background:<?= $statusInfo['bg']; ?>; color:<?= $statusInfo['color']; ?>; font-weight:600; font-size:0.8rem;">
                                            <?= $statusInfo['icon']; ?> <?= e($statusInfo['label']); ?>
                                        </span>
                                        <div style="margin-top:0.35rem; color:#6b7280; font-size:0.85rem;">Forma: <?= e(strtoupper($paymentMethod)); ?></div>
                                    </td>
                                    <td style="padding:0.75rem; font-size:0.85rem; color:#374151;">
                                        <?php if ($paymentId): ?>
                                            <div><strong>ID pagamento:</strong> <?= e((string)$paymentId); ?></div>
                                        <?php endif; ?>
                                        <?php if ($externalReference): ?>
                                            <div><strong>Referência:</strong> <?= e($externalReference); ?></div>
                                        <?php endif; ?>
                                        <?php if ($recordedAt): ?>
                                            <div><strong>Registro:</strong> <?= date('d/m/Y H:i', strtotime($recordedAt)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:0.75rem; color:#6b7280;">
                                        <?php if ($observation !== ''): ?>
                                            <?= e($observation); ?>
                                        <?php else: ?>
                                            <span style="color:#9ca3af;">Nenhum detalhe adicional.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background:#fef2f2; padding:2rem; border-radius:8px; text-align:center; color:#b91c1c; border:1px dashed #fecaca;">
                    <i class="fas fa-crown" style="font-size:2rem; margin-bottom:0.5rem; display:block;"></i>
                    <p>Nenhuma transação premium registrada até o momento.</p>
                </div>
            <?php endif; ?>
        </div>
</section>
<?php endif; ?>

<div class="sold-modal hidden" data-sold-modal aria-hidden="true" role="dialog" aria-modal="true">
    <div class="sold-modal__dialog">
        <h3 class="sold-modal__title">Onde a venda aconteceu?</h3>
        <p class="sold-modal__description">Selecione a origem da venda para atualizarmos suas estatisticas.</p>
        <div class="sold-modal__actions">
            <button type="button" class="button button-success" data-sold-choice="platform">Venda pelo Classi Livre</button>
            <button type="button" class="button button-warning" data-sold-choice="external">Venda fora da plataforma</button>
        </div>
        <button type="button" class="sold-modal__close" data-sold-close>Cancelar</button>
    </div>
</div>

<!-- Modal de Confirmação de Deleção -->
<div class="sold-modal hidden" data-confirm-sold-modal aria-hidden="true" role="dialog" aria-modal="true">
    <div class="sold-modal__dialog">
        <h3 class="sold-modal__title">⚠️ Confirmar Exclusão do Anúncio</h3>
        <p class="sold-modal__description" style="color: #d32f2f; font-weight: 600; margin: 1rem 0;">
            O anúncio será permanentemente deletado do sistema após confirmar a venda!
        </p>
        <p class="sold-modal__description">
            Após marcar este anúncio como vendido, ele será removido do sistema e a venda será contabilizada em seu perfil.
            <br><br>
            <strong>Esta ação é irreversível.</strong>
        </p>
        <div class="sold-modal__actions">
            <button type="button" class="button button-danger" data-confirm-sold="yes" onclick="handleConfirmSoldYes()">Sim, deletar e registrar venda</button>
            <button type="button" class="button button-secondary" data-confirm-sold="no" onclick="handleConfirmSoldNo()">Não, voltar</button>
        </div>
    </div>
</div>

<?php
$autocompleteScriptPath = __DIR__ . '/../assets/js/autocomplete.js';
$autocompleteScriptVersion = file_exists($autocompleteScriptPath) ? (string)filemtime($autocompleteScriptPath) : (string)time();
$hasPendingSoldAd = !empty($_SESSION['pending_sold_ad']);
$pendingSoldAdId = $hasPendingSoldAd ? (int)($_SESSION['pending_sold_ad']['ad_id'] ?? 0) : 0;
?>
<script src="/assets/js/autocomplete.js?v=<?= urlencode($autocompleteScriptVersion); ?>"></script>

<!-- Funções globais para Modal de Venda -->
<script>
var soldTargetFormGlobal = null;
var confirmSoldModalGlobal = null;
var soldModalGlobal = null;

function handleConfirmSoldYes() {
    console.log('handleConfirmSoldYes() called');
    console.log('soldTargetFormGlobal:', soldTargetFormGlobal);
    
    if (!soldTargetFormGlobal) {
        console.error('ERROR: soldTargetFormGlobal is null!');
        alert('Erro: formulário não encontrado. Tente novamente.');
        return;
    }
    
    var actionInput = soldTargetFormGlobal.querySelector('[name="action"]');
    console.log('Action input found:', !!actionInput);
    
    if (actionInput) {
        console.log('Setting action to confirm_delete_sold');
        actionInput.value = 'confirm_delete_sold';
    }
    
    // Fechar modal
    if (confirmSoldModalGlobal) {
        confirmSoldModalGlobal.classList.add('hidden');
        confirmSoldModalGlobal.setAttribute('aria-hidden', 'true');
    }
    
    console.log('Submitting form now...');
    soldTargetFormGlobal.submit();
}

function handleConfirmSoldNo() {
    console.log('handleConfirmSoldNo() called');
    if (confirmSoldModalGlobal) {
        confirmSoldModalGlobal.classList.add('hidden');
        confirmSoldModalGlobal.setAttribute('aria-hidden', 'true');
    }
    
    // Voltar para primeiro modal
    if (soldModalGlobal) {
        soldModalGlobal.classList.remove('hidden');
        soldModalGlobal.setAttribute('aria-hidden', 'false');
    }
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var soldModal = document.querySelector('[data-sold-modal]');
    var soldChoiceButtons = soldModal ? soldModal.querySelectorAll('[data-sold-choice]') : [];
    var soldCloseButton = soldModal ? soldModal.querySelector('[data-sold-close]') : null;
    var soldTargetForm = null;
    var hasPendingSoldAd = <?= json_encode($hasPendingSoldAd); ?>;
    var pendingSoldAdId = <?= json_encode($pendingSoldAdId); ?>;
    
    // Salvar referências globais
    soldModalGlobal = soldModal;
    confirmSoldModalGlobal = document.querySelector('[data-confirm-sold-modal]');

    function closeSoldModal() {
        if (!soldModal) {
            return;
        }
        soldModal.classList.add('hidden');
        soldModal.setAttribute('aria-hidden', 'true');
        soldTargetForm = null;
    }

    document.querySelectorAll('[data-sold-trigger]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            var card = button.closest('[data-ad-card]');
            if (!card) {
                return;
            }
            var form = card.querySelector('[data-sold-form]');
            if (!form || !soldModal) {
                return;
            }
            soldTargetForm = form;
            soldModal.classList.remove('hidden');
            soldModal.setAttribute('aria-hidden', 'false');
            var firstChoice = soldModal.querySelector('[data-sold-choice]');
            if (firstChoice) {
                firstChoice.focus();
            }
        });
    });

    if (soldCloseButton) {
        soldCloseButton.addEventListener('click', closeSoldModal);
    }
    if (soldModal) {
        soldModal.addEventListener('click', function (event) {
            if (event.target === soldModal) {
                closeSoldModal();
            }
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSoldModal();
        }
    });

    // Handler para segundo modal de confirmação de deleção
    var confirmSoldModal = document.querySelector('[data-confirm-sold-modal]');
    var confirmSoldYesButton = confirmSoldModal ? confirmSoldModal.querySelector('[data-confirm-sold="yes"]') : null;
    var confirmSoldNoButton = confirmSoldModal ? confirmSoldModal.querySelector('[data-confirm-sold="no"]') : null;

    function showConfirmSoldModal() {
        if (!confirmSoldModal) {
            return;
        }
        confirmSoldModal.classList.remove('hidden');
        confirmSoldModal.setAttribute('aria-hidden', 'false');
        if (confirmSoldYesButton) {
            confirmSoldYesButton.focus();
        }
    }

    function closeConfirmSoldModal() {
        if (!confirmSoldModal) {
            return;
        }
        confirmSoldModal.classList.add('hidden');
        confirmSoldModal.setAttribute('aria-hidden', 'true');
        // NÃO resetar soldTargetForm aqui, só fechar o modal
    }

    // NOTE: confirmSoldYesButton e confirmSoldNoButton agora usam onclick no HTML
    // Os addEventListener abaixo são redundantes mas não prejudicam

    if (confirmSoldModal) {
        confirmSoldModal.addEventListener('click', function (event) {
            if (event.target === confirmSoldModal) {
                closeConfirmSoldModal();
                // Volta para o primeiro modal
                if (soldModal) {
                    soldModal.classList.remove('hidden');
                    soldModal.setAttribute('aria-hidden', 'false');
                }
            }
        });
    }

    // Handler para Escape no segundo modal
    var handleEscapeInConfirmModal = function (event) {
        if (event.key === 'Escape' && confirmSoldModal && !confirmSoldModal.classList.contains('hidden')) {
            closeConfirmSoldModal();
            // Volta para o primeiro modal
            if (soldModal) {
                soldModal.classList.remove('hidden');
                soldModal.setAttribute('aria-hidden', 'false');
            }
        }
    };
    document.addEventListener('keydown', handleEscapeInConfirmModal);

    // Modificar o comportamento do primeiro modal para fazer primeiro POST
    // Após escolher, submete com action=mark_sold para salvar em sessão
    soldChoiceButtons.forEach(function (choiceButton) {
        choiceButton.addEventListener('click', function (event) {
            event.preventDefault();
            if (!soldTargetForm) {
                console.error('No target form found');
                return;
            }
            var input = soldTargetForm.querySelector('[name="sold_source"]');
            if (!input) {
                console.error('No sold_source input found');
                return;
            }
            var choice = choiceButton.getAttribute('data-sold-choice');
            input.value = choice;
            console.log('Choice selected:', choice, 'Submitting mark_sold form...');
            // Fechar primeiro modal
            if (soldModal) {
                soldModal.classList.add('hidden');
                soldModal.setAttribute('aria-hidden', 'true');
            }
            // Submeter formulário com action=mark_sold (vai salvar em sessão e redirecionar)
            soldTargetForm.submit();
        });
    });

    // Se houver pending_sold_ad na sessão, encontre o formulário correspondente e mostre o segundo modal
    if (hasPendingSoldAd && pendingSoldAdId > 0) {
        console.log('Pending sold ad found:', pendingSoldAdId);
        var adIdInputs = document.querySelectorAll('input[name="ad_id"]');
        for (var i = 0; i < adIdInputs.length; i++) {
            var form = adIdInputs[i].closest('[data-sold-form]');
            if (form && parseInt(adIdInputs[i].value) === pendingSoldAdId) {
                soldTargetForm = form;
                // Salvar na variável global também
                soldTargetFormGlobal = form;
                console.log('Found form for ad:', pendingSoldAdId, 'Showing confirm modal');
                showConfirmSoldModal();
                break;
            }
        }
        if (!soldTargetForm) {
            console.error('Could not find form for ad_id:', pendingSoldAdId);
        }
    }

    var phoneCountrySelect = document.getElementById('phone_country_iso');
    var phoneCountryCode = document.getElementById('phone_country_code');
    var syncDialCode = function () {
        if (!phoneCountrySelect || !phoneCountryCode) {
            return;
        }
        var selected = phoneCountrySelect.selectedOptions[0];
        if (selected && selected.dataset.dial) {
            phoneCountryCode.value = selected.dataset.dial;
        }
    };
    if (phoneCountrySelect && phoneCountryCode) {
        syncDialCode();
        phoneCountrySelect.addEventListener('change', syncDialCode);
    }

    var categorySelect = document.querySelector('[data-service-category]');
    if (categorySelect) {
        var customField = document.querySelector('[data-service-custom]');
        var toggleCustomField = function () {
            if (!customField) {
                return;
            }
            if (categorySelect.value === 'custom') {
                customField.classList.remove('hidden');
            } else {
                customField.classList.add('hidden');
            }
        };
        categorySelect.addEventListener('change', toggleCustomField);
        toggleCustomField();
    }

    var cityDataset = [];
    if (typeof cidadesProximas !== 'undefined' && Array.isArray(cidadesProximas)) {
        cityDataset = cidadesProximas;
    } else if (typeof window !== 'undefined' && Array.isArray(window.cidadesProximas)) {
        cityDataset = window.cidadesProximas;
    }
    if (typeof window.setupAutocompleteElements === 'function' && cityDataset.length) {
        var profileCityInput = document.getElementById('profile_city');
        var profileCitySuggestions = document.getElementById('profile_city_suggestions');
        if (profileCityInput && profileCitySuggestions) {
            window.setupAutocompleteElements(profileCityInput, profileCitySuggestions, cityDataset, null);
        }
        var serviceCityInput = document.getElementById('service_address_city');
        var serviceCitySuggestions = document.getElementById('service_city_suggestions');
        if (serviceCityInput && serviceCitySuggestions) {
            window.setupAutocompleteElements(serviceCityInput, serviceCitySuggestions, cityDataset, null);
        }
    }
});
</script>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Autocomplete cidades por estado na aba profile
    var profileStateEl = document.getElementById('profile_state');
    if (profileStateEl) {
        profileStateEl.addEventListener('change', function() {
            const uf = this.value;
            const datalist = document.getElementById('profile_cities_list');
            if (!datalist) return;
            datalist.innerHTML = ''; // Limpar opções anteriores

            if (uf && uf !== 'EX') {
                fetch(`https://serviçodados.ibge.gov.br/api/v1/localidades/estados/${uf}/municipios`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(municipio => {
                            const option = document.createElement('option');
                            option.value = municipio.nome;
                            datalist.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Erro ao carregar cidades:', error));
            }
        });
    }

    // Busca CEP via ViaCEP na aba profile
    var addressPostalCodeEl = document.getElementById('address_postal_code');
    if (addressPostalCodeEl) {
        addressPostalCodeEl.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            var addressStreetEl = document.getElementById('address_street');
                            var addressNeighborhoodEl = document.getElementById('address_neighborhood');
                            var profileCityEl = document.getElementById('profile_city');
                            var profileStateEl2 = document.getElementById('profile_state');
                            
                            if (addressStreetEl) addressStreetEl.value = data.logradouro || '';
                            if (addressNeighborhoodEl) addressNeighborhoodEl.value = data.bairro || '';
                            if (profileCityEl) profileCityEl.value = data.localidade || '';
                            if (profileStateEl2) profileStateEl2.value = data.uf || '';
                            
                            // Habilitar campos estado e cidade
                            if (profileStateEl2) profileStateEl2.disabled = false;
                            if (profileCityEl) profileCityEl.disabled = false;
                            
                            // Trigger change para carregar cidades
                            if (profileStateEl2) profileStateEl2.dispatchEvent(new Event('change'));
                        } else {
                            alert('CEP não encontrado.');
                            // Manter campos desabilitados se CEP inválido
                            var profileStateEl3 = document.getElementById('profile_state');
                            var profileCityEl2 = document.getElementById('profile_city');
                            if (profileStateEl3) profileStateEl3.disabled = true;
                            if (profileCityEl2) profileCityEl2.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar CEP:', error);
                        alert('Erro ao buscar CEP. Tente novamente.');
                    });
            } else {
                // Se CEP incompleto, desabilitar campos
                var profileStateEl4 = document.getElementById('profile_state');
                var profileCityEl3 = document.getElementById('profile_city');
                if (profileStateEl4) profileStateEl4.disabled = true;
                if (profileCityEl3) profileCityEl3.disabled = true;
            }
        });
    }

    // Funções para máscara de documento
    console.log('Script start');
    const sanitizeDigits = (value) => (value || '').toString().replace(/\D+/g, '');

    const formatCpf = (digits) => {
        const d = digits.slice(0, 11);
        let output = '';
        if (d.length > 0) {
            output = d.slice(0, 3);
        }
        if (d.length >= 4) {
            output += '.' + d.slice(3, 6);
        }
        if (d.length >= 7) {
            output += '.' + d.slice(6, 9);
        }
        if (d.length >= 10) {
            output += '-' + d.slice(9, 11);
        }
        return output;
    };

    const formatCnpj = (digits) => {
        const d = digits.slice(0, 14);
        let output = '';
        if (d.length > 0) {
            output = d.slice(0, 2);
        }
        if (d.length >= 3) {
            output += '.' + d.slice(2, 5);
        }
        if (d.length >= 6) {
            output += '.' + d.slice(5, 8);
        }
        if (d.length >= 9) {
            output += '/' + d.slice(8, 12);
        }
        if (d.length >= 13) {
            output += '-' + d.slice(12, 14);
        }
        return output;
    };

    // Atualizar máscara de documento dinamicamente
    document.querySelectorAll('input[name="document_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const type = this.value;
            const input = document.querySelector('#document_number');
            if (input) {
                input.setAttribute('data-document-type', type);

input.value = '';
input.placeholder = type === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00';
input.maxLength = type === 'cnpj' ? 18 : 14;
}
});
});

// Adicionar listener de input para aplicar máscara no campo de profile
const profileInput = document.querySelector('#document_number');
if (profileInput) {
    console.log('Profile input found, adding mask listener');
    profileInput.addEventListener('input', function() {
        console.log('Applying mask for profile input');
        const type = this.getAttribute('data-document-type') === 'cnpj' ? 'cnpj' : 'cpf';
        const digits = sanitizeDigits(this.value);
        this.value = type === 'cnpj' ? formatCnpj(digits) : formatCpf(digits);
    });
} else {
    console.log('Profile input NOT found');
}

document.querySelectorAll('input[name="service_document_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const type = this.value;
        const input = document.querySelector('#service_document_number');
        if (input) {
            input.setAttribute('data-document-type', type);
            input.value = '';
            input.placeholder = type === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00';
            input.maxLength = type === 'cnpj' ? 18 : 14;
        }
    });
});

// Adicionar listener de input para aplicar máscara no campo de services
console.log('Before querySelector service');
const serviceInput = document.querySelector('#service_document_number');
console.log('serviceInput:', serviceInput);
if (serviceInput) {
    console.log('Service input found, adding mask listener');
    serviceInput.addEventListener('input', function() {
        console.log('Applying mask for service input');
        const type = this.getAttribute('data-document-type') === 'cnpj' ? 'cnpj' : 'cpf';
        const digits = sanitizeDigits(this.value);
        this.value = type === 'cnpj' ? formatCnpj(digits) : formatCpf(digits);
    });
} else {
    console.log('Service input NOT found');
}

});
</script>

<script>
// ========================================================================
// FUNÇÕES DE RENOVAÇÃO DE ANÚNCIOS (GLOBAIS)
// ========================================================================

/**
 * Filtra anúncios por status
 */
function filterAds(filter) {
    const cards = document.querySelectorAll('[data-ad-card]');
    const now = new Date();
    
    cards.forEach(card => {
        let shouldShow = false;
        const statusPill = card.querySelector('.status-pill');
        const status = statusPill ? statusPill.classList[1]?.replace('status-', '') : '';
        const expirationBadge = card.querySelector('[style*="fef3c7"]');
        const soldBadge = card.querySelector('.sold-badge');
        
        switch(filter) {
            case 'ativos':
                // Anúncios aprovados e não vendidos
                shouldShow = status === 'approved' && !soldBadge;
                break;
            case 'expirados':
                // Anúncios com status expirado (com graça)
                shouldShow = status === 'expired' && !!expirationBadge && !soldBadge;
                break;
            case 'todos':
            default:
                shouldShow = true;
                break;
        }
        
        card.style.display = shouldShow ? 'block' : 'none';
    });
}

/**
 * Renova um anúncio manualmente
 */
function renewAd(adId) {
    if (!confirm('Deseja renovar este anúncio? Custo: 1 moeda (válido por 30 dias)')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/anuncios/renovar.php';
    
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    
    form.innerHTML = `
        <input type="hidden" name="id" value="${adId}">
        <input type="hidden" name="csrf_token" value="${csrfToken}">
    `;
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Alterna auto-renovação do anúncio
 */
function toggleAutoRenewal(adId, enabled) {
    const formData = new FormData();
    formData.append('action', 'toggle_auto_renewal');
    formData.append('ad_id', adId);
    formData.append('enabled', enabled ? 1 : 0);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value);
    
    fetch('/api/toggle_auto_renewal.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Erro ao atualizar preferência de auto-renovação: ' + (data.error || 'Desconhecido'));
            // Reverter o checkbox
            document.querySelector(`input[name="auto_renewal_${adId}"]`).checked = !enabled;
        }
    })
    .catch(error => {
        console.error('Erro ao enviar requisição:', error);
        alert('Erro ao conectar com o servidor');
        document.querySelector(`input[name="auto_renewal_${adId}"]`).checked = !enabled;
    });
}

/**
 * Alterna auto-renovacao de servicos
 */
function toggleServiceAutoRenewal(serviceId, enabled) {
    const formData = new FormData();
    formData.append('service_id', serviceId);
    formData.append('enabled', enabled ? 1 : 0);
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput) {
        formData.append('csrf_token', csrfInput.value);
    }

    fetch('/api/toggle_service_auto_renewal.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Erro ao atualizar auto-renovacao do servico: ' + (data.error || 'Desconhecido'));
                const checkbox = document.querySelector(`input[name="service_auto_renewal_${serviceId}"]`);
                if (checkbox) {
                    checkbox.checked = !enabled;
                }
                return;
            }

            if (data.message) {
                alert(data.message);
            }

            if (data.renewed) {
                window.location.reload();
                return;
            }

            if (typeof data.auto_renewal_enabled !== 'undefined') {
                const checkbox = document.querySelector(`input[name="service_auto_renewal_${serviceId}"]`);
                if (checkbox) {
                    checkbox.checked = !!data.auto_renewal_enabled;
                }
            }
        })
        .catch(error => {
            console.error('Erro ao enviar requisicao:', error);
            alert('Erro ao conectar com o servidor');
            const checkbox = document.querySelector(`input[name="service_auto_renewal_${serviceId}"]`);
            if (checkbox) {
                checkbox.checked = !enabled;
            }
        });
}

// ===== VALIDADOR DE CPF/CNPJ COM FEEDBACK VISUAL =====
(function() {
    function validarCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }
        for (let t = 9; t < 11; t++) {
            let d = 0;
            for (let c = 0; c < t; c++) {
                d += cpf[c] * ((t + 1) - c);
            }
            d = ((10 * d) % 11) % 10;
            if (cpf[t] != d) {
                return false;
            }
        }
        return true;
    }

    function validarCNPJ(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
            return false;
        }
        const b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        let n = 0;
        for (let i = 0; i < 12; i++) {
            n += cnpj[i] * b[i + 1];
        }
        if (cnpj[12] != ((n % 11) < 2 ? 0 : 11 - (n % 11))) {
            return false;
        }
        n = 0;
        for (let i = 0; i <= 12; i++) {
            n += cnpj[i] * b[i];
        }
        if (cnpj[13] != ((n % 11) < 2 ? 0 : 11 - (n % 11))) {
            return false;
        }
        return true;
    }

    const docInput = document.querySelector('#service_document_number');
    const docValidation = document.querySelector('#service_document_validation');
    const documentTypeRadios = document.querySelectorAll('input[name="service_document_type"]');

    function updateValidation() {
        const value = docInput.value.replace(/\D/g, '');
        const type = document.querySelector('input[name="service_document_type"]:checked').value;

        if (value.length === 0) {
            docValidation.textContent = '';
            return;
        }

        let isValid = false;
        if (type === 'cpf') {
            isValid = value.length === 11 && validarCPF(value);
        } else {
            isValid = value.length === 14 && validarCNPJ(value);
        }

        if (isValid) {
            docValidation.textContent = '✓';
            docValidation.style.color = '#16a34a';
            docValidation.title = 'Documento válido';
        } else {
            docValidation.textContent = '✗';
            docValidation.style.color = '#dc2626';
            docValidation.title = 'Documento inválido';
        }
    }

    docInput.addEventListener('input', updateValidation);
    documentTypeRadios.forEach(radio => {
        radio.addEventListener('change', updateValidation);
    });

    // Validar na primeira carga se houver valor
    updateValidation();
})();
</script>

<script>
// Mantem feedback visual sem bloquear o fluxo de submissao nativo
document.addEventListener('DOMContentLoaded', function() {
    const serviceForm = document.getElementById('create-service-form');
    if (!serviceForm) {
        return;
    }

    const submitButton = serviceForm.querySelector('button[type="submit"]');
    if (!submitButton) {
        return;
    }

    serviceForm.addEventListener('submit', function() {
        if (submitButton.disabled) {
            return;
        }

        submitButton.dataset.originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Processando...';
    });

    serviceForm.addEventListener('invalid', function() {
        if (submitButton.dataset.originalText) {
            submitButton.disabled = false;
            submitButton.textContent = submitButton.dataset.originalText;
        }
    }, true);
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>






