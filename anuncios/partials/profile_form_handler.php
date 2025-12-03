<?php
/**
 * anuncios/partials/profile_form_handler.php
 * 
 * Processador exclusivo da tab "Dados Pessoais"
 * Contém: validação, processamento POST, e inicialização de dados para o formulário
 * 
 * Variáveis esperadas (do meus.php):
 * - $user (array) - usuário logado
 * - $pdo (PDO) - conexão com banco de dados
 * - $userCoinBalance (int) - saldo de moedas do usuário
 * 
 * Define (para uso em profile_form_v2.php):
 * - $profileFormData (array)
 * - $profileErrors (array)
 * - $phoneCountries (array)
 * - $stateOptions (array)
 */

if (!isset($user, $pdo, $userCoinBalance)) {
    die('Erro: Variáveis necessárias não disponíveis em profile_form_handler.php');
}

// ============================================
// ARRAYS DE DADOS
// ============================================

/**
 * Mapa de países com código ISO e código de discagem
 */
$phoneCountries = [
    'BR' => ['label' => 'Brasil', 'dial_code' => '+55'],
    'US' => ['label' => 'Estados Unidos', 'dial_code' => '+1'],
    'PT' => ['label' => 'Portugal', 'dial_code' => '+351'],
    'AR' => ['label' => 'Argentina', 'dial_code' => '+54'],
    'PY' => ['label' => 'Paraguai', 'dial_code' => '+595'],
    'UY' => ['label' => 'Uruguai', 'dial_code' => '+598'],
    'ES' => ['label' => 'Espanha', 'dial_code' => '+34'],
    'FR' => ['label' => 'Franca', 'dial_code' => '+33'],
];

/**
 * Mapa de estados brasileiros
 */
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

// ============================================
// INICIALIZAÇÃO DE VARIÁVEIS
// ============================================

/**
 * Erros de validação específicos do formulário de perfil
 */
$profileErrors = [];

/**
 * Array de dados do formulário preenchido com dados atuais do usuário
 */
$profileFormData = [
    'name' => (string)($user['name'] ?? ''),
    'phone' => (string)($user['phone'] ?? ''),
    'phone_country_code' => (string)($user['phone_country_code'] ?? '+55'),
    'phone_country_iso' => (string)($user['phone_country_iso'] ?? 'BR'),
    'phone_is_whatsapp' => !empty($user['phone_is_whatsapp']),
    'show_phone' => !empty($user['show_phone']),
    'show_whatsapp' => !empty($user['show_whatsapp']),
    'show_email' => !empty($user['show_email']),
    'show_address' => !empty($user['show_address']),
    'document_type' => strtolower((string)($user['document_type'] ?? 'cpf')) === 'cnpj' ? 'cnpj' : 'cpf',
    'document_number' => preg_replace('/\D+/', '', (string)($user['document_number'] ?? '')),
    'show_document' => !empty($user['show_document']),
    'address_street' => (string)($user['address_street'] ?? ''),
    'address_number' => (string)($user['address_number'] ?? ''),
    'address_complement' => (string)($user['address_complement'] ?? ''),
    'address_neighborhood' => (string)($user['address_neighborhood'] ?? ''),
    'address_city' => (string)($user['address_city'] ?? ''),
    'address_state' => strtoupper((string)($user['address_state'] ?? '')),
    'address_postal_code' => (string)($user['address_postal_code'] ?? ''),
    'about' => (string)($user['about'] ?? ''),
    'coin_balance' => $userCoinBalance,
];

// ============================================
// PROCESSAMENTO DE POST
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'update_profile') {
    // ========== Validação de CSRF ==========
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $profileErrors[] = 'Token invalido. Recarregue a pagina.';
    }

    // ========== Captura de dados do POST ==========
    $profileFormData['name'] = trim((string)($_POST['name'] ?? $profileFormData['name']));
    $profileFormData['phone'] = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
    
    // Validar e normalizar país
    $selectedIso = strtoupper(substr((string)($_POST['phone_country_iso'] ?? $profileFormData['phone_country_iso']), 0, 2));
    if (!array_key_exists($selectedIso, $phoneCountries)) {
        $selectedIso = 'BR';
    }
    $profileFormData['phone_country_iso'] = $selectedIso;
    $profileFormData['phone_country_code'] = (string)$phoneCountries[$selectedIso]['dial_code'];
    
    // Checkboxes
    $profileFormData['phone_is_whatsapp'] = isset($_POST['phone_is_whatsapp']);
    $profileFormData['show_phone'] = isset($_POST['show_phone']);
    $profileFormData['show_whatsapp'] = isset($_POST['show_whatsapp']);
    $profileFormData['show_email'] = isset($_POST['show_email']);
    $profileFormData['show_address'] = isset($_POST['show_address']);
    $profileFormData['show_document'] = isset($_POST['show_document']);
    
    // Endereço
    $profileFormData['address_street'] = trim((string)($_POST['address_street'] ?? ''));
    $profileFormData['address_number'] = trim((string)($_POST['address_number'] ?? ''));
    $profileFormData['address_complement'] = trim((string)($_POST['address_complement'] ?? ''));
    $profileFormData['address_neighborhood'] = trim((string)($_POST['address_neighborhood'] ?? ''));
    $profileFormData['address_city'] = trim((string)($_POST['address_city'] ?? ''));
    $profileFormData['address_state'] = strtoupper(substr(trim((string)($_POST['address_state'] ?? '')), 0, 2));
    $profileFormData['address_postal_code'] = preg_replace('/\D+/', '', (string)($_POST['address_postal_code'] ?? ''));
    
    // Descrição
    $profileFormData['about'] = trim((string)($_POST['about'] ?? ''));

    // ========== VALIDAÇÕES ==========
    
    if ($profileFormData['name'] === '' || mb_strlen($profileFormData['name']) < 3) {
        $profileErrors[] = 'Informe seu nome completo.';
    }
    
    if ($profileFormData['phone'] !== '' && mb_strlen($profileFormData['phone']) < 10) {
        $profileErrors[] = 'Telefone deve ter ao menos 10 digitos.';
    }
    
    if ($profileFormData['document_number'] !== '') {
        if ($profileFormData['document_type'] === 'cpf') {
            if (!validarCPF($profileFormData['document_number'])) {
                $profileErrors[] = 'CPF cadastrado invalido. Contate o suporte.';
            }
        } else {
            if (!validarCNPJ($profileFormData['document_number'])) {
                $profileErrors[] = 'CNPJ cadastrado invalido. Contate o suporte para revisar.';
            }
        }
    }
    
    if ($profileFormData['show_document'] && $profileFormData['document_number'] === '') {
        $profileErrors[] = 'CPF/CNPJ nao cadastrado. Contate o suporte para habilitar a exibicao.';
    }
    
    if ($profileFormData['address_state'] !== '' && !array_key_exists($profileFormData['address_state'], $stateOptions)) {
        $profileErrors[] = 'Selecione um estado valido.';
    }
    
    if (mb_strlen($profileFormData['about']) > 1000) {
        $profileErrors[] = 'Descricao "Sobre" deve ter no maximo 1000 caracteres.';
    }

    // ========== SALVAR NO BANCO DE DADOS ==========
    
    if (!$profileErrors) {
        try {
            $update = $pdo->prepare('UPDATE users SET
                name = :name,
                phone = :phone,
                phone_country_iso = :phone_country_iso,
                phone_country_code = :phone_country_code,
                phone_is_whatsapp = :phone_is_whatsapp,
                show_phone = :show_phone,
                show_whatsapp = :show_whatsapp,
                show_email = :show_email,
                document_type = :document_type,
                document_number = :document_number,
                show_document = :show_document,
                address_street = :address_street,
                address_number = :address_number,
                address_complement = :address_complement,
                address_neighborhood = :address_neighborhood,
                address_city = :address_city,
                address_state = :address_state,
                address_postal_code = :address_postal_code,
                show_address = :show_address,
                about = :about,
                updated_at = NOW()
            WHERE id = :id');
            
            $updateParams = [
                ':name' => $profileFormData['name'],
                ':phone' => $profileFormData['phone'] !== '' ? $profileFormData['phone'] : null,
                ':phone_country_iso' => $profileFormData['phone_country_iso'],
                ':phone_country_code' => $profileFormData['phone_country_code'],
                ':phone_is_whatsapp' => $profileFormData['phone_is_whatsapp'] ? 1 : 0,
                ':show_phone' => $profileFormData['show_phone'] ? 1 : 0,
                ':show_whatsapp' => $profileFormData['show_whatsapp'] ? 1 : 0,
                ':show_email' => $profileFormData['show_email'] ? 1 : 0,
                ':document_type' => $profileFormData['document_type'],
                ':document_number' => $profileFormData['document_number'] !== '' ? $profileFormData['document_number'] : null,
                ':show_document' => $profileFormData['show_document'] ? 1 : 0,
                ':address_street' => $profileFormData['address_street'] !== '' ? $profileFormData['address_street'] : null,
                ':address_number' => $profileFormData['address_number'] !== '' ? $profileFormData['address_number'] : null,
                ':address_complement' => $profileFormData['address_complement'] !== '' ? $profileFormData['address_complement'] : null,
                ':address_neighborhood' => $profileFormData['address_neighborhood'] !== '' ? $profileFormData['address_neighborhood'] : null,
                ':address_city' => $profileFormData['address_city'] !== '' ? $profileFormData['address_city'] : null,
                ':address_state' => $profileFormData['address_state'] !== '' ? $profileFormData['address_state'] : null,
                ':address_postal_code' => $profileFormData['address_postal_code'] !== '' ? $profileFormData['address_postal_code'] : null,
                ':show_address' => $profileFormData['show_address'] ? 1 : 0,
                ':about' => $profileFormData['about'] !== '' ? $profileFormData['about'] : null,
                ':id' => (int)$user['id'],
            ];
            
            $update->execute($updateParams);
            
            // Registrar auditoria
            record_audit((int)$user['id'], 'user_profile_updated', 'users', (int)$user['id']);
            
            // Recarregar dados do usuário
            current_user(true);
            $user = current_user();
            
            // Reinicializar dados do formulário com valores salvos
            $profileFormData = [
                'name' => (string)($user['name'] ?? ''),
                'phone' => (string)($user['phone'] ?? ''),
                'phone_country_code' => (string)($user['phone_country_code'] ?? '+55'),
                'phone_country_iso' => (string)($user['phone_country_iso'] ?? 'BR'),
                'phone_is_whatsapp' => !empty($user['phone_is_whatsapp']),
                'show_phone' => !empty($user['show_phone']),
                'show_whatsapp' => !empty($user['show_whatsapp']),
                'show_email' => !empty($user['show_email']),
                'show_address' => !empty($user['show_address']),
                'document_type' => strtolower((string)($user['document_type'] ?? 'cpf')) === 'cnpj' ? 'cnpj' : 'cpf',
                'document_number' => preg_replace('/\D+/', '', (string)($user['document_number'] ?? '')),
                'show_document' => !empty($user['show_document']),
                'address_street' => (string)($user['address_street'] ?? ''),
                'address_number' => (string)($user['address_number'] ?? ''),
                'address_complement' => (string)($user['address_complement'] ?? ''),
                'address_neighborhood' => (string)($user['address_neighborhood'] ?? ''),
                'address_city' => (string)($user['address_city'] ?? ''),
                'address_state' => strtoupper((string)($user['address_state'] ?? '')),
                'address_postal_code' => (string)($user['address_postal_code'] ?? ''),
                'about' => (string)($user['about'] ?? ''),
                'coin_balance' => $userCoinBalance,
            ];
            
            flash_add('success', 'Dados atualizados com sucesso.');
            redirect('/anuncios/meus.php?tab=profile');
            
        } catch (Exception $e) {
            error_log('[profile_form_handler.php] Erro ao atualizar perfil: ' . $e->getMessage());
            $profileErrors[] = 'Erro ao salvar dados: ' . $e->getMessage();
        }
    }
}
