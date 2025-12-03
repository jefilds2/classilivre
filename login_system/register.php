<?php
// login_system/register.php - Cadastro de novos usuários com suporte a CPF/CNPJ e endereço completo.

declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/middleware.php';

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
    
    // Primeiro dígito verificador
    $b = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $n = 0;
    for ($i = 0; $i < 12; $i++) {
        $n += (int)$cnpj[$i] * $b[$i];
    }
    $firstCheck = $n % 11 < 2 ? 0 : 11 - ($n % 11);
    
    if ((int)$cnpj[12] != $firstCheck) {
        return false;
    }
    
    // Segundo dígito verificador
    $b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $n = 0;
    for ($i = 0; $i < 13; $i++) {
        $n += (int)$cnpj[$i] * $b[$i];
    }
    $secondCheck = $n % 11 < 2 ? 0 : 11 - ($n % 11);
    
    if ((int)$cnpj[13] != $secondCheck) {
        return false;
    }
    
    // MUDANÇA: Não fazer verificação síncrona na ReceitaWS
    // A verificação será feita em background depois de registrado
    // Isto evita timeout de 10 segundos bloqueando o registro
    error_log("[CNPJ] Format valid for $cnpj, will verify asynchronously");
    return true;
}

function verificarCNPJReceita($cnpj) {
    $url = "https://www.receitaws.com.br/v1/cnpj/" . $cnpj;
    $context = stream_context_create([
        "http" => [
            "timeout" => 10,
            "user_agent" => "Mozilla/5.0 (compatible; PHP script)"
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        error_log("Erro ao consultar CNPJ na ReceitaWS: $cnpj");
        return true;  // Aceita como válido se API falhar
    }
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] === 'ERROR') {
        return false;
    }
    return isset($data['cnpj']) && $data['cnpj'] !== '';
}

$pageTitle = 'Criar conta - Site Carros';

$phoneCountries = [
    'BR' => ['label' => 'Brasil', 'dial_code' => '+55'],
    'US' => ['label' => 'Estados Unidos', 'dial_code' => '+1'],
    'PT' => ['label' => 'Portugal', 'dial_code' => '+351'],
    'AR' => ['label' => 'Argentina', 'dial_code' => '+54'],
    'PY' => ['label' => 'Paraguai', 'dial_code' => '+595'],
    'UY' => ['label' => 'Uruguai', 'dial_code' => '+598'],
    'ES' => ['label' => 'Espanha', 'dial_code' => '+34'],
    'FR' => ['label' => 'França', 'dial_code' => '+33'],
];

$stateOptions = [
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
    'EX' => 'Exterior',
];

$errors = [];
$successLink = null;

$personType = $_POST['person_type'] ?? 'cpf';
if (!in_array($personType, ['cpf', 'cnpj'], true)) {
    $personType = 'cpf';
}

$selectedPhoneCountryIso = strtoupper($_POST['phone_country_iso'] ?? 'BR');
if (!array_key_exists($selectedPhoneCountryIso, $phoneCountries)) {
    $selectedPhoneCountryIso = 'BR';
}

$selectedState = strtoupper($_POST['address_state'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("[REGISTER] POST request received. personType from POST: " . ($_POST['person_type'] ?? 'NOT SET'));
    
    if (!enforce_rate_limit('register', 10, 60)) {
        $errors[] = 'Muitas tentativas. Aguarde um minuto e tente novamente.';
    }

    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido. Atualize a página e tente novamente.';
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phoneRaw = trim((string)($_POST['phone'] ?? ''));
    $phoneDigits = preg_replace('/\D+/', '', $phoneRaw);
    $phoneCountryIso = strtoupper((string)($_POST['phone_country_iso'] ?? $selectedPhoneCountryIso));
    $phoneIsWhatsapp = isset($_POST['phone_is_whatsapp']) ? 1 : 0;
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');
    $terms = isset($_POST['terms']);

    $personType = $_POST['person_type'] ?? $personType;
    if (!in_array($personType, ['cpf', 'cnpj'], true)) {
        $personType = 'cpf';
    }

    $documentRaw = preg_replace('/\D+/', '', (string)($_POST['document_number'] ?? ''));
    error_log("[REGISTER] Processing $personType. documentRaw extracted: $documentRaw (length: " . strlen($documentRaw) . ")");
    $addressStreet = trim((string)($_POST['address_street'] ?? ''));
    $addressNumber = trim((string)($_POST['address_number'] ?? ''));
    $addressComplement = trim((string)($_POST['address_complement'] ?? ''));
    $addressNeighborhood = trim((string)($_POST['address_neighborhood'] ?? ''));
    $addressCity = trim((string)($_POST['address_city'] ?? ''));
    $selectedState = strtoupper((string)($_POST['address_state'] ?? ''));
    $postalRaw = preg_replace('/\D+/', '', (string)($_POST['address_postal_code'] ?? ''));

    if ($name === '' || strlen($name) < 3) {
        $errors[] = 'Informe seu nome completo ou razão social.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }
    if (!array_key_exists($phoneCountryIso, $phoneCountries)) {
        $errors[] = 'Selecione um país válido para o telefone.';
    } else {
        $selectedPhoneCountryIso = $phoneCountryIso;
    }
    if ($phoneDigits === '' || strlen($phoneDigits) < 10) {
        $errors[] = 'Informe um telefone celular válido.';
    }

    if ($personType === 'cpf') {
        if (!validarCPF($documentRaw)) {
            $errors[] = 'Informe um CPF válido.';
        }
    } else {
        if (!validarCNPJ($documentRaw)) {
            $errors[] = 'Informe um CNPJ válido.';
        } else {
            error_log("[REGISTER] CNPJ validation passed for $documentRaw, personType=$personType");
        }
    }

    if ($addressStreet === '') {
        $errors[] = 'Informe o logradouro (rua, avenida, etc.).';
    }
    if ($addressNumber === '') {
        $errors[] = 'Informe o número do endereço.';
    }
    if ($addressNeighborhood === '') {
        $errors[] = 'Informe o bairro.';
    }
    if ($addressCity === '') {
        $errors[] = 'Informe a cidade.';
    }
    if (!array_key_exists($selectedState, $stateOptions)) {
        $errors[] = 'Selecione um estado válido.';
    }
    if ($selectedState === 'EX') {
        if ($postalRaw !== '' && strlen($postalRaw) < 4) {
            $errors[] = 'Informe um código postal válido.';
        }
    } else {
        if (strlen($postalRaw) !== 8) {
            $errors[] = 'Informe um CEP válido com 8 dígitos.';
        }
    }

    if (strlen($password) < 8) {
        $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/u', $password)) {
        $errors[] = 'A senha deve conter pelo menos uma letra maiúscula.';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'A senha deve conter pelo menos um número.';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'As senhas informadas não conferem.';
    }
    if (!$terms) {
        $errors[] = 'É necessário aceitar os Termos de Uso e Política de Privacidade.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Já existe um cadastro com este e-mail.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE document_number = :document LIMIT 1');
        $stmt->execute([':document' => $documentRaw]);
        if ($existing = $stmt->fetch()) {
            error_log("[REGISTER] Document already exists! personType=$personType, documentRaw=$documentRaw, existing_user_id=" . $existing['id']);
            $errors[] = 'Este CPF/CNPJ já está cadastrado.';
        } else {
            error_log("[REGISTER] Document is NEW! personType=$personType, documentRaw=$documentRaw");
        }
    }

    if ($errors) {
        error_log("[REGISTER] Validation errors for $personType registration: " . json_encode($errors));
    }

    if (!$errors) {
        $now = now();
        $phoneCountryIsoDb = $phoneCountryIso;
        $phoneCountryCodeDb = $phoneCountries[$phoneCountryIso]['dial_code'];

        // Definir limites gratuitos baseados nas configurações globais
        // (removido: sistema de créditos descontinuado)

        $insert = db()->prepare('INSERT INTO users (
        name, email,
        phone_country_iso, phone_country_code, phone, phone_is_whatsapp,
            document_type, document_number,
        address_street, address_number, address_complement, address_neighborhood, address_city, address_state, address_postal_code,
        terms_accepted_at,
        password_hash, is_admin, is_premium, email_verified_at, status, created_at, updated_at
        ) VALUES (
        :name, :email,
            :phone_country_iso, :phone_country_code, :phone, :phone_is_whatsapp,
            :document_type, :document_number,
            :address_street, :address_number, :address_complement, :address_neighborhood, :address_city, :address_state, :address_postal_code,
            :terms_accepted_at,
            :password_hash, 0, 0, NULL, "active", :created_at, :updated_at
        )');

        $insert->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone_country_iso' => $phoneCountryIsoDb,
            ':phone_country_code' => $phoneCountryCodeDb,
            ':phone' => $phoneDigits,
            ':phone_is_whatsapp' => $phoneIsWhatsapp,
            ':document_type' => $personType,
            ':document_number' => $documentRaw,
            ':address_street' => $addressStreet,
            ':address_number' => $addressNumber,
            ':address_complement' => $addressComplement !== '' ? $addressComplement : null,
            ':address_neighborhood' => $addressNeighborhood,
            ':address_city' => $addressCity,
            ':address_state' => $selectedState,
            ':address_postal_code' => $postalRaw,
            ':terms_accepted_at' => $terms ? $now : null,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $userId = (int)db()->lastInsertId();
        
        // ✅ Dar moedas iniciais para conta nova (se configurado)
        $newAccountCoins = (int)get_setting('new_account_coins', '0');
        if ($newAccountCoins > 0) {
            $updateCoins = db()->prepare('UPDATE users SET coin_balance = :coins WHERE id = :id');
            $updateCoins->execute([':coins' => $newAccountCoins, ':id' => $userId]);
            
            // Registrar na auditoria
            record_audit($userId, 'welcome_bonus_coins', 'users', $userId, [
                'amount' => $newAccountCoins,
                'reason' => 'Bônus inicial para novo usuário'
            ]);
        }
        
        // === [EMAIL][verify] gerar token no users e enfileirar boas-vindas ===
        $token = bin2hex(random_bytes(32));
        // grava token diretamente no users (sem tabela auxiliar)
        $upd = db()->prepare('UPDATE users SET email_verify_token = :tkn, email_verified_at = NULL, updated_at = :upd WHERE id = :uid');
        $upd->execute([':tkn'=>$token, ':upd'=>$now, ':uid'=>$userId]);

        // monta URL de verificação
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'classilivreautos.com';
        $verifyUrl = $scheme.'://'.$host.'/login_system/verify_email.php?token='.$token;
        $verifyUrl = filter_var($verifyUrl, FILTER_SANITIZE_URL);

        // include não intrusivo do helper
        if (!function_exists('mail_send_immediate')) {
            $try = [
                __DIR__.'/../api/helpers_mail.php',
                __DIR__.'/../../api/helpers_mail.php',
                __DIR__.'/helpers_mail.php',
            ];
            foreach ($try as $p) { if (file_exists($p)) { require_once $p; break; } }
        }
        try {
            if (function_exists('mail_send_immediate')) {
                $sent = mail_send_immediate(
                    $email,
                    'Confirme seu e-mail - Classi Livre Autos',
                    'welcome_verify',
                    ['nome' => $name, 'verifyUrl' => $verifyUrl],
                    $name
                );
                if (!$sent) {
                    error_log('[MAIL][register] Falha ao enviar e-mail de verificação: ' . mail_last_error());
                }
            }
        } catch (Throwable $e) { error_log('[MAIL][register] '.$e->getMessage()); }
        // === [/EMAIL][verify] ===
        record_audit($userId, 'user_registered', 'users', $userId, ['email' => $email, 'document_type' => $personType]);
        reset_rate_limit('register');

        // Redireciona para a página de verificação pendente (sem exibir link)
        error_log("[REGISTER] Redirecting to verify_email_pending after registration. userId=$userId, email=$email, personType=$personType");
        if (!login_user(['id' => $userId], false)) {
            flash_add('error', 'Nao foi possivel iniciar sua sessao automaticamente. Efetue login e tente novamente.');
            redirect('/login_system/login.php');
        }
        redirect('/login_system/verify_email_pending.php');
    }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="form-card auth-card" style="max-width:860px;margin:2rem auto 3.5rem;">
    <header class="auth-card__header">
        <h2>Crie sua conta</h2>
        <p>Entre para anunciar e acompanhar propostas. A confirmação de e-mail é necessária para publicar.</p>
    </header>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Aviso de Responsabilidade Legal -->
    <div class="alert alert-warning" style="border-left: 4px solid #d97706; background-color: #fef3c7; border-color: #fcd34d;">
        <div style="display: flex; gap: 1rem;">
            <i class="fas fa-exclamation-triangle" style="color: #92400e; font-size: 1.5rem; flex-shrink: 0; margin-top: 0.25rem;"></i>
            <div>
                <h3 style="margin: 0 0 0.5rem; color: #92400e; font-weight: 700;">Responsabilidade Legal</h3>
                <p style="margin: 0 0 0.75rem; color: #78350f;">Você é <strong>responsável pelas informações publicadas</strong> nesta plataforma. Mesmo que esteja publicando anúncio para outra pessoa, os dados fornecidos devem ser precisos e verdadeiros.</p>
                <p style="margin: 0; color: #78350f;"><strong>Informações falsas, enganosas ou que violam as regras da plataforma podem resultar em:</strong></p>
                <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem; color: #78350f;">
                    <li><strong>Bloqueio da conta</strong></li>
                    <li><strong>Remoção de anúncios</strong></li>
                    <li><strong>Ações legais</strong></li>
                    <li><strong>Penalidades financeiras</strong></li>
                </ul>
            </div>
        </div>
    </div>

    <?php if ($successLink): ?>
        <div class="alert alert-success">
            Cadastro realizado! Use o link a seguir para confirmar seu e-mail:<br>
            <a href="<?= e($successLink); ?>"><?= e($successLink); ?></a>
        </div>
    <?php else: ?>
        <form method="post" class="auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

            <section class="form-section" data-person-type-container>
                <h3>Tipo de cadastro</h3>
                <p class="section-hint">Escolha se será um cadastro pessoal (CPF) ou para empresa (CNPJ).</p>
                <div class="option-pills" role="radiogroup">
                    <label class="option-pill">
                        <input type="radio" name="person_type" value="cpf" <?= $personType === 'cpf' ? 'checked' : ''; ?> data-person-type="cpf">
                        <span>Pessoa Física (CPF)</span>
                    </label>
                    <label class="option-pill">
                        <input type="radio" name="person_type" value="cnpj" <?= $personType === 'cnpj' ? 'checked' : ''; ?> data-person-type="cnpj">
                        <span>Pessoa Jurídica (CNPJ)</span>
                    </label>
                </div>
            </section>

            <section class="form-section">
                <h3>Documento</h3>
                <div class="grid-2">
                    <div class="input-group input-group--document">
                        <label for="document_number" data-document-label>CPF/CNPJ</label>
                        <input
                            type="text"
                            id="document_number"
                            name="document_number"
                            value="<?= e($_POST['document_number'] ?? ''); ?>"
                            placeholder="<?= $personType === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00'; ?>"
                            inputmode="numeric"
                            maxlength="<?= $personType === 'cnpj' ? '18' : '14'; ?>"
                            data-mask="document"
                            data-document-type="<?= e($personType); ?>"
                            data-document-input
                            required
                        >
                    </div>
                </div>
            </section>

            <section class="form-section">
                <h3>Dados pessoais</h3>
                <div class="grid-2">
                    <div class="input-group grid-span-2">
                        <label for="name" data-person-label>Nome completo / Razão social</label>
                        <input type="text" id="name" name="name" value="<?= e($_POST['name'] ?? ''); ?>" required minlength="3">
                    </div>
                    <div class="input-group grid-span-2">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="input-group input-group--phone grid-span-2">
                        <label for="phone">Celular</label>
                        <div class="phone-field">
                            <select id="phone_country_iso" name="phone_country_iso" data-phone-country>
                                <?php foreach ($phoneCountries as $iso => $country): ?>
                                    <option value="<?= e($iso); ?>" <?= $selectedPhoneCountryIso === $iso ? 'selected' : ''; ?>>
                                        <?= e($country['label'] . ' ' . $country['dial_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= e($_POST['phone'] ?? ''); ?>"
                                placeholder="(00) 00000-0000"
                                inputmode="tel"
                                data-mask="phone"
                                required
                            >
                        </div>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="phone_is_whatsapp" value="1" <?= isset($_POST['phone_is_whatsapp']) ? 'checked' : ''; ?>>
                            Este número é WhatsApp
                        </label>
                    </div>
                    <div class="input-group input-group--password">
                        <label for="password">Senha</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" value="<?= e($_POST['password'] ?? ''); ?>" required minlength="8" data-password-toggle-target>
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Mostrar senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span class="input-hint">Mínimo de 8 caracteres, pelo menos 1 letra maiúscula e 1 número.</span>
                    </div>
                    <div class="input-group input-group--password">
                        <label for="password_confirmation">Confirmar senha</label>
                        <div class="password-field">
                            <input type="password" id="password_confirmation" name="password_confirmation" value="<?= e($_POST['password_confirmation'] ?? ''); ?>" required minlength="8" data-password-toggle-target>
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Mostrar senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="form-section">
            <h3>Endereço</h3>
            
            <!-- Google Maps Guidance -->
            <div style="background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.25rem;">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                    <span style="font-size: 1.5rem; flex-shrink: 0;">📍</span>
                    <div>
                        <strong style="color: #1976D2;">Importante: Endereço conforme Google Maps</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem; color: #555;">
                            Para facilitar o uso com Google Maps e Waze, informe o endereço <strong>EXATAMENTE COMO APARECE</strong> no Google Maps.
                        </p>
                        <div style="margin-top: 0.75rem; font-size: 0.9rem; color: #666;">
                            <div style="margin-bottom: 0.5rem;"><strong style="color: #2e7d32;">✓ Correto:</strong> Av. Paulista, 1000, apto 201</div>
                            <div><strong style="color: #c62828;">✗ Incorreto:</strong> Avenida, 1000, loja</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid-2">
            <div class="input-group grid-span-2">
            <label for="address_postal_code">CEP / Código postal</label>
            <input
                    type="text"
                    id="address_postal_code"
                name="address_postal_code"
                value="<?= e($_POST['address_postal_code'] ?? ''); ?>"
                    placeholder="00000-000"
                    inputmode="numeric"
                maxlength="12"
                data-mask="postal"
                    required
                >
            </div>
            <div class="input-group grid-span-2">
                <label for="address_street">Logradouro</label>
                <input type="text" id="address_street" name="address_street" value="<?= e($_POST['address_street'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
            <label for="address_number">Número</label>
                <input type="text" id="address_number" name="address_number" value="<?= e($_POST['address_number'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
            <label for="address_complement">Complemento</label>
            <input type="text" id="address_complement" name="address_complement" value="<?= e($_POST['address_complement'] ?? ''); ?>" placeholder="Opcional">
            </div>
            <div class="input-group">
            <label for="address_neighborhood">Bairro</label>
            <input type="text" id="address_neighborhood" name="address_neighborhood" value="<?= e($_POST['address_neighborhood'] ?? ''); ?>" required>
            </div>
            <div class="input-group">
                <label for="address_state">Estado</label>
                <select id="address_state" name="address_state" <?= empty($selectedState) ? 'disabled' : ''; ?> required>
                <option value="">Selecione um estado</option>
                <?php foreach ($stateOptions as $stateCode => $stateName): ?>
                <option value="<?= e($stateCode); ?>" <?= $selectedState === $stateCode ? 'selected' : ''; ?>>
                    <?= e($stateName); ?>
                </option>
            <?php endforeach; ?>
            </select>
            </div>
            <div class="input-group">
            <label for="address_city">Cidade</label>
            <input type="text" id="address_city" name="address_city" value="<?= e($_POST['address_city'] ?? ''); ?>" list="cities-list" <?= empty($selectedState) ? 'disabled' : ''; ?> required>
            <datalist id="cities-list"></datalist>
            </div>
            </div>
            </section>

            <div class="terms-wrapper">
                <label class="checkbox-inline">
                    <input type="checkbox" id="terms" name="terms" value="1" <?= isset($_POST['terms']) ? 'checked' : ''; ?> required>
                    <span>
                        Eu li e concordo com os
                        <a href="/termos-uso.php" target="_blank" rel="noopener">Termos de Uso</a>
                        e a
                        <a href="/politica-privacidade.php" target="_blank" rel="noopener">Política de Privacidade</a>.
                    </span>
                </label>
            </div>

            <button type="submit" class="button button-primary auth-submit">Criar conta</button>
            <p class="auth-switch">Já tem conta? <a href="/login_system/login.php">Fazer login</a></p>
        </form>
    <?php endif; ?>
</section>

<script>
// Função para carregar cidades de um estado
function loadCitiesForState(uf) {
    const datalist = document.getElementById('cities-list');
    datalist.innerHTML = ''; // Limpar opções anteriores

    if (uf && uf !== 'EX') {
        fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${uf}/municipios`)
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
}

// Autocomplete cidades por estado
document.getElementById('address_state').addEventListener('change', function() {
    loadCitiesForState(this.value);
});

// Função para buscar e preencher CEP
function fetchAndFillCEP(cep) {
    cep = cep.replace(/\D/g, '');
    if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('address_street').value = data.logradouro || '';
                    document.getElementById('address_neighborhood').value = data.bairro || '';
                    document.getElementById('address_city').value = data.localidade || '';
                    document.getElementById('address_state').value = data.uf || '';
                    // Habilitar campos estado e cidade
                    document.getElementById('address_state').disabled = false;
                    document.getElementById('address_city').disabled = false;
                    // Trigger change para carregar cidades
                    document.getElementById('address_state').dispatchEvent(new Event('change'));
                } else {
                    alert('CEP não encontrado.');
                    // Manter campos desabilitados se CEP inválido
                    document.getElementById('address_state').disabled = true;
                    document.getElementById('address_city').disabled = true;
                }
            })
            .catch(error => {
                console.error('Erro ao buscar CEP:', error);
                alert('Erro ao buscar CEP. Tente novamente.');
            });
    } else {
        // Se CEP incompleto, desabilitar campos
        document.getElementById('address_state').disabled = true;
        document.getElementById('address_city').disabled = true;
    }
}

// Busca CEP via ViaCEP ao perder foco
document.getElementById('address_postal_code').addEventListener('blur', function() {
    fetchAndFillCEP(this.value);
});

// Ao carregar a página, se houver CEP preenchido, executar busca
window.addEventListener('DOMContentLoaded', function() {
    const cepField = document.getElementById('address_postal_code');
    const stateField = document.getElementById('address_state');
    
    // Se há CEP preenchido e estado selecionado, carregar cidades
    if (cepField.value && stateField.value && stateField.value !== '') {
        loadCitiesForState(stateField.value);
        // Desabilitar os campos para forçar o usuário a clicar em buscar CEP novamente
        // OU deixar habilitado se quiser manter os dados
    }
    
    // Se há CEP incompleto, desabilitar campos
    const cep = cepField.value.replace(/\D/g, '');
    if (cep.length < 8) {
        stateField.disabled = true;
        document.getElementById('address_city').disabled = true;
    }
});

// Atualizar máscara de documento dinamicamente
document.querySelectorAll('input[name="person_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const type = this.value;
        const input = document.querySelector('#document_number[data-document-input]');
        if (input) {
            input.setAttribute('data-document-type', type);
            input.setAttribute('maxlength', type === 'cnpj' ? '18' : '14');
            input.value = '';
            input.placeholder = type === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00';
        }
    });
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
