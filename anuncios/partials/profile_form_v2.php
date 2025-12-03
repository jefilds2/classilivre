<?php
/**
 * anuncios/partials/profile_form_v2.php
 * Tab "Dados Pessoais" do portal meus.php - VERSÃO REORGANIZADA
 * 
 * Estrutura:
 * 1. TOPO: Informações de Identificação (CPF/CNPJ + Email) - SOMENTE LEITURA
 * 2. MEIO: Formulário editável (Dados pessoais e endereço)
 * 3. FIM: Preferências de exibição (Checkboxes destacados) + Sobre você
 * 
 * Variáveis esperadas:
 * - $profileFormData (array)
 * - $profileErrors (array)
 * - $csrfToken (string)
 * - $phoneCountries (array)
 * - $stateOptions (array)
 * - $user (array)
 */

if (!isset($profileFormData, $phoneCountries, $stateOptions, $user, $csrfToken)) {
    die('Erro: Variáveis necessárias não disponíveis em profile_form_v2.php');
}
?>

<section class="account-section" data-tab-panel="profile">
    <h2>Dados pessoais e preferências</h2>
    <p class="lead">Atualize informações de contato e defina o que aparece em seus anúncios e vitrine.</p>

    <!-- ========================================
         SEÇÃO 1: SALDO DE MOEDAS (Informativo)
         ======================================== -->
    <div style="padding:1.5rem; background:linear-gradient(135deg, #15803d 0%, #22c55e 100%); border-radius:12px; color:white; margin-bottom:2rem; box-shadow: 0 8px 24px rgba(21, 128, 61, 0.25);">
        <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
            <div style="flex:1; min-width:200px;">
                <div style="font-size:0.9rem; opacity:0.9; margin-bottom:0.5rem;">Seu Saldo de Moedas</div>
                <div style="font-size:2rem; font-weight:bold; display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                    <img src="/assets/img/classilivre-logo.png" alt="CLivre Medal" 
                         style="height:2rem; vertical-align:middle;" onerror="this.style.display='none'">
                    <span><?= (int)$profileFormData['coin_balance'] ?? 0; ?> moedas</span>
                </div>
                <div style="font-size:0.85rem; opacity:0.9;">Cada moeda = 1 anúncio por 30 dias</div>
            </div>
            <div style="text-align:center;">
                <a href="/premium/comprar_moedas.php" class="button" style="background:#fbbf24; color:#000; border:none; font-weight:600; padding:0.75rem 1.5rem; cursor:pointer; text-decoration:none; display:inline-block; border-radius:8px;">
                    🛒 Adquirir Mais Moedas
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================
         SEÇÃO 2: IDENTIFICAÇÃO (SOMENTE LEITURA)
         ======================================== -->
    <div style="background:#f8fafc; border:2px solid #e2e8f0; border-radius:0.75rem; padding:1.5rem; margin-bottom:2rem;">
        <h3 style="font-size:1.1rem; font-weight:600; color:#1f2937; margin-top:0; margin-bottom:1rem;">
            <i class="fas fa-id-card" style="color:#3b82f6; margin-right:0.5rem;"></i> Informações de Identificação
        </h3>
        
        <div style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));">
            <!-- Documento (CPF/CNPJ) -->
            <div>
                <label style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280; display:block; margin-bottom:0.5rem; font-weight:600;">Documento</label>
                <div class="document-readonly" style="padding:1rem; background:white; border:1px solid #cbd5e1; border-radius:0.5rem;">
                    <span class="document-readonly__type" style="font-weight:700; color:#dc2626; text-transform:uppercase; min-width:60px;">
                        <?= $profileFormData['document_type'] === 'cnpj' ? 'CNPJ' : 'CPF'; ?>
                    </span>
                    <span style="color:#111827; font-weight:600; flex:1; margin-left:1rem;">
                        <?php 
                            $documentFormattedValue = $profileFormData['document_number'] !== ''
                                ? format_document($profileFormData['document_number'], $profileFormData['document_type'])
                                : null;
                        ?>
                        <?= $documentFormattedValue ? e($documentFormattedValue) : '<span style="color:#6b7280;">Não informado</span>'; ?>
                    </span>
                </div>
                <p style="font-size:0.75rem; color:#6b7280; margin-top:0.5rem; margin-bottom:0;">
                    <i class="fas fa-lock" style="margin-right:0.25rem;"></i> 
                    Para atualizar, entre em contato com o suporte.
                </p>
            </div>

            <!-- Email de Contato -->
            <div>
                <label style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280; display:block; margin-bottom:0.5rem; font-weight:600;">Email Cadastrado</label>
                <div class="document-readonly" style="padding:1rem; background:white; border:1px solid #cbd5e1; border-radius:0.5rem;">
                    <span style="color:#111827; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-envelope" style="color:#3b82f6;"></i>
                        <?= e($user['email'] ?? 'Não informado'); ?>
                    </span>
                </div>
                <p style="font-size:0.75rem; color:#6b7280; margin-top:0.5rem; margin-bottom:0;">
                    <i class="fas fa-lock" style="margin-right:0.25rem;"></i> 
                    Para atualizar, entre em contato com o suporte.
                </p>
            </div>
        </div>
    </div>

    <!-- Mensagens de erro -->
    <?php if ($profileErrors): ?>
        <div class="alert alert-error" style="margin-bottom:1.5rem;">
            <ul style="list-style:disc; padding-left:1.25rem; margin:0;">
                <?php foreach ($profileErrors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- ========================================
         SEÇÃO 3: FORMULÁRIO EDITÁVEL
         ======================================== -->
    <form method="post" class="form-grid" style="display:grid; gap:1.5rem; margin-bottom:2rem;">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="tab" value="profile">

        <div style="border-top:2px solid #e5e7eb; padding-top:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:600; color:#1f2937; margin-top:0; margin-bottom:1rem;">
                <i class="fas fa-user" style="color:#3b82f6; margin-right:0.5rem;"></i> Dados Pessoais
            </h3>

            <!-- Nome Completo -->
            <div class="input-group" style="margin-bottom:1rem;">
                <label for="name" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Nome completo *</label>
                <input type="text" id="name" name="name" value="<?= e($profileFormData['name']); ?>" required 
                       style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
            </div>

            <!-- País e Telefone em grid -->
            <div style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:1rem;">
                <div class="input-group">
                    <label for="phone_country_iso" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">País</label>
                    <select id="phone_country_iso" name="phone_country_iso" 
                            style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
                        <?php foreach ($phoneCountries as $iso => $country): ?>
                            <option value="<?= e($iso); ?>" data-dial="<?= e($country['dial_code']); ?>" 
                                    <?= $profileFormData['phone_country_iso'] === $iso ? 'selected' : ''; ?>>
                                <?= e($country['label'] . ' (' . $country['dial_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="input-group">
                    <label for="phone" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Telefone</label>
                    <input type="text" id="phone" name="phone" value="<?= e($profileFormData['phone']); ?>" 
                           placeholder="Ex: 31988887777" data-mask="phone"
                           style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
                </div>
            </div>

            <!-- WhatsApp -->
            <label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:1.5rem; cursor:pointer;">
                <input type="checkbox" name="phone_is_whatsapp" value="1" <?= $profileFormData['phone_is_whatsapp'] ? 'checked' : ''; ?> 
                       style="width:18px; height:18px; cursor:pointer;">
                <span style="font-weight:500; color:#374151;">Este telefone também é WhatsApp</span>
            </label>
        </div>

        <!-- Endereço -->
        <div style="border-top:2px solid #e5e7eb; padding-top:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:600; color:#1f2937; margin-top:0; margin-bottom:1rem;">
                <i class="fas fa-map-marker-alt" style="color:#3b82f6; margin-right:0.5rem;"></i> Endereço
            </h3>

            <!-- CEP (fullwidth) -->
            <div class="input-group" style="margin-bottom:1rem;">
                <label for="address_postal_code" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">CEP</label>
                <input type="text" id="address_postal_code" name="address_postal_code" 
                       value="<?= e($profileFormData['address_postal_code']); ?>" 
                       maxlength="12" placeholder="00000-000" inputmode="numeric" data-mask="postal"
                       style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
            </div>

            <!-- Rua (fullwidth) -->
            <div class="input-group" style="margin-bottom:1rem;">
                <label for="address_street" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Rua</label>
                <input type="text" id="address_street" name="address_street" 
                       value="<?= e($profileFormData['address_street']); ?>"
                       style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
            </div>

            <!-- Número, Complemento, Bairro -->
            <div style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); margin-bottom:1rem;">
                <div class="input-group">
                    <label for="address_number" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Número</label>
                    <input type="text" id="address_number" name="address_number" 
                           value="<?= e($profileFormData['address_number']); ?>"
                           style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
                </div>

                <div class="input-group">
                    <label for="address_complement" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Complemento</label>
                    <input type="text" id="address_complement" name="address_complement" 
                           value="<?= e($profileFormData['address_complement']); ?>"
                           style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
                </div>

                <div class="input-group">
                    <label for="address_neighborhood" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Bairro</label>
                    <input type="text" id="address_neighborhood" name="address_neighborhood" 
                           value="<?= e($profileFormData['address_neighborhood']); ?>"
                           style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
                </div>
            </div>

            <!-- Estado e Cidade -->
            <div style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));">
                <div class="input-group">
                    <label for="profile_state" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Estado</label>
                    <select id="profile_state" name="address_state"
                            style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
                        <option value="">Selecione um estado</option>
                        <?php foreach ($stateOptions as $code => $label): ?>
                            <option value="<?= e($code); ?>" <?= strtoupper((string)$profileFormData['address_state']) === $code ? 'selected' : ''; ?>>
                                <?= e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="profile_city" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Cidade</label>
                    <input type="text" id="profile_city" name="address_city" 
                           value="<?= e($profileFormData['address_city']); ?>" 
                           list="profile_cities_list"
                           style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem;">
                    <datalist id="profile_cities_list"></datalist>
                </div>
            </div>
        </div>

        <!-- ========================================
             SEÇÃO 4: PREFERÊNCIAS DE EXIBIÇÃO
             ======================================== -->
        <div style="border-top:2px solid #e5e7eb; padding-top:1.5rem; background:#f0f9ff; border-radius:0.75rem; padding:1.5rem; border:2px solid #bfdbfe;">
            <h3 style="font-size:1.1rem; font-weight:600; color:#1e40af; margin-top:0; margin-bottom:0.5rem;">
                <i class="fas fa-eye" style="color:#3b82f6; margin-right:0.5rem;"></i> Preferências de Exibição
            </h3>
            <p style="color:#1e40af; font-size:0.9rem; margin-bottom:1rem;">
                Escolha quais informações seus clientes podem ver em seus anúncios e vitrine:
            </p>

            <div style="display:grid; gap:0.75rem;">
                <label style="display:flex; gap:0.75rem; align-items:center; cursor:pointer; padding:0.75rem; background:white; border-radius:0.5rem; border:1px solid #bfdbfe; hover:background:#f0f9ff;">
                    <input type="checkbox" name="show_phone" value="1" <?= $profileFormData['show_phone'] ? 'checked' : ''; ?> 
                           style="width:20px; height:20px; cursor:pointer; accent-color:#3b82f6;">
                    <span style="flex:1;">
                        <strong style="color:#1e40af; display:block;">Mostrar telefone</strong>
                        <small style="color:#6b7280;">Seu número será visível nos anúncios</small>
                    </span>
                </label>

                <label style="display:flex; gap:0.75rem; align-items:center; cursor:pointer; padding:0.75rem; background:white; border-radius:0.5rem; border:1px solid #bfdbfe;">
                    <input type="checkbox" name="show_whatsapp" value="1" <?= $profileFormData['show_whatsapp'] ? 'checked' : ''; ?> 
                           style="width:20px; height:20px; cursor:pointer; accent-color:#3b82f6;">
                    <span style="flex:1;">
                        <strong style="color:#1e40af; display:block;">Permitir contato por WhatsApp</strong>
                        <small style="color:#6b7280;">Clientes podem clicar para conversar direto</small>
                    </span>
                </label>

                <label style="display:flex; gap:0.75rem; align-items:center; cursor:pointer; padding:0.75rem; background:white; border-radius:0.5rem; border:1px solid #bfdbfe;">
                    <input type="checkbox" name="show_email" value="1" <?= $profileFormData['show_email'] ? 'checked' : ''; ?> 
                           style="width:20px; height:20px; cursor:pointer; accent-color:#3b82f6;">
                    <span style="flex:1;">
                        <strong style="color:#1e40af; display:block;">Mostrar email de contato</strong>
                        <small style="color:#6b7280;">Seu email será visível publicamente</small>
                    </span>
                </label>

                <label style="display:flex; gap:0.75rem; align-items:center; cursor:pointer; padding:0.75rem; background:white; border-radius:0.5rem; border:1px solid #bfdbfe;">
                    <input type="checkbox" name="show_address" value="1" <?= $profileFormData['show_address'] ? 'checked' : ''; ?> 
                           style="width:20px; height:20px; cursor:pointer; accent-color:#3b82f6;">
                    <span style="flex:1;">
                        <strong style="color:#1e40af; display:block;">Exibir endereço na vitrine</strong>
                        <small style="color:#6b7280;">Localização será mostrada no seu perfil público</small>
                    </span>
                </label>

                <label style="display:flex; gap:0.75rem; align-items:center; cursor:pointer; padding:0.75rem; background:white; border-radius:0.5rem; border:1px solid #bfdbfe;">
                    <input type="checkbox" name="show_document" value="1" <?= $profileFormData['show_document'] ? 'checked' : ''; ?> 
                           style="width:20px; height:20px; cursor:pointer; accent-color:#3b82f6;">
                    <span style="flex:1;">
                        <strong style="color:#1e40af; display:block;">Exibir CPF/CNPJ publicamente</strong>
                        <small style="color:#6b7280;">Seu documento será visível no perfil público</small>
                    </span>
                </label>
            </div>
        </div>

        <!-- Sobre você / Loja -->
        <div style="border-top:2px solid #e5e7eb; padding-top:1.5rem;">
            <h3 style="font-size:1.1rem; font-weight:600; color:#1f2937; margin-top:0; margin-bottom:1rem;">
                <i class="fas fa-pen-fancy" style="color:#3b82f6; margin-right:0.5rem;"></i> Sobre Você / Loja
            </h3>

            <div class="input-group">
                <label for="about" style="font-weight:600; color:#374151; margin-bottom:0.5rem; display:block;">Descrição</label>
                <textarea id="about" name="about" rows="5" placeholder="Conte um pouco sobre sua atuação, diferencas e atendimento. Máximo 1000 caracteres."
                          style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:0.5rem; font-size:1rem; font-family:inherit; resize:vertical;"><?= e($profileFormData['about']); ?></textarea>
                <small style="color:#6b7280; display:block; margin-top:0.25rem;">
                    <span id="about_counter">0</span> / 1000 caracteres
                </small>
            </div>
        </div>

        <!-- Botão de Envio -->
        <div style="border-top:2px solid #e5e7eb; padding-top:1.5rem;">
            <button type="submit" class="button" 
                    style="background:linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color:white; border:none; padding:0.875rem 1.75rem; font-weight:600; border-radius:0.5rem; cursor:pointer; font-size:1rem;">
                <i class="fas fa-save" style="margin-right:0.5rem;"></i> Salvar Dados
            </button>
        </div>
    </form>
</section>

<script>
// Counter de caracteres para o campo "about"
document.addEventListener('DOMContentLoaded', function() {
    const aboutTextarea = document.getElementById('about');
    const counterSpan = document.getElementById('about_counter');
    
    if (aboutTextarea && counterSpan) {
        const updateCounter = () => {
            counterSpan.textContent = aboutTextarea.value.length;
        };
        
        aboutTextarea.addEventListener('input', updateCounter);
        updateCounter(); // Atualizar no carregamento
    }
});
</script>
