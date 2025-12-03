<?php
/**
 * anuncios/partials/profile_form.php
 * Tab "Dados Pessoais" do portal meus.php
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
    die('Erro: Variáveis necessárias não disponíveis em profile_form.php');
}
?>

<section class="account-section" data-tab-panel="profile">
    <h2>Dados pessoais e preferencias</h2>
    <p class="lead">Atualize informacoes de contato e defina o que aparece em seus anuncios e vitrine.</p>

    <!-- SEÇÃO DE MOEDAS CLIVRE -->
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

    <?php if ($profileErrors): ?>
        <div class="alert alert-error" style="margin-bottom:1rem;">
            <ul style="list-style:disc;padding-left:1.25rem;">
                <?php foreach ($profileErrors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid" style="display:grid;gap:1rem;">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="tab" value="profile">

        <div class="input-group">
            <label for="name">Nome completo</label>
            <input type="text" id="name" name="name" value="<?= e($profileFormData['name']); ?>" required>
        </div>

        <div class="input-grid" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
            <div class="input-group">
                <label for="phone_country_iso">Pais</label>
                <select id="phone_country_iso" name="phone_country_iso">
                    <?php foreach ($phoneCountries as $iso => $country): ?>
                        <option value="<?= e($iso); ?>" data-dial="<?= e($country['dial_code']); ?>" <?= $profileFormData['phone_country_iso'] === $iso ? 'selected' : ''; ?>>
                            <?= e($country['label'] . ' (' . $country['dial_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="input-group">
            <label for="phone">Telefone</label>
            <input type="text" id="phone" name="phone" value="<?= e($profileFormData['phone']); ?>" placeholder="Ex: 31988887777" data-mask="phone">
            </div>
        </div>
        <label style="display:flex;gap:0.5rem;align-items:center;">
            <input type="checkbox" name="phone_is_whatsapp" value="1" <?= $profileFormData['phone_is_whatsapp'] ? 'checked' : ''; ?>>
            Telefone tambem e WhatsApp
        </label>

        <fieldset style="border:1px solid #e5e7eb;border-radius:0.75rem;padding:1rem;">
            <legend style="padding:0 0.5rem;font-weight:600;color:#374151;">Preferencias de exibicao</legend>
            <div style="display:grid;gap:0.5rem;">
                <label style="display:flex;gap:0.5rem;align-items:center;">
                    <input type="checkbox" name="show_phone" value="1" <?= $profileFormData['show_phone'] ? 'checked' : ''; ?>>
                    Mostrar telefone nos anuncios
                </label>
                <label style="display:flex;gap:0.5rem;align-items:center;">
                    <input type="checkbox" name="show_whatsapp" value="1" <?= $profileFormData['show_whatsapp'] ? 'checked' : ''; ?>>
                    Permitir contato por WhatsApp
                </label>
                <label style="display:flex;gap:0.5rem;align-items:center;">
                    <input type="checkbox" name="show_address" value="1" <?= $profileFormData['show_address'] ? 'checked' : ''; ?>>
                    Exibir endereco na vitrine
                </label>
            </div>
        </fieldset>

        <?php
            $documentTypeLabel = $profileFormData['document_type'] === 'cnpj' ? 'CNPJ' : 'CPF';
            $documentFormattedValue = $profileFormData['document_number'] !== ''
                ? format_document($profileFormData['document_number'], $profileFormData['document_type'])
                : null;
        ?>
        <div class="input-group">
            <label>Documento (CPF/CNPJ)</label>
            <div class="document-readonly">
                <span class="document-readonly__type"><?= e($documentTypeLabel); ?></span>
                <?php if ($documentFormattedValue): ?>
                    <span class="document-readonly__value"><?= e($documentFormattedValue); ?></span>
                <?php else: ?>
                    <span class="document-readonly__placeholder">Nao informado</span>
                <?php endif; ?>
            </div>
            <p class="document-support-hint">Para atualizar o CPF/CNPJ cadastrado, entre em contato com o suporte.</p>
        </div>
        <label style="display:flex;gap:0.5rem;align-items:center;">
            <input type="checkbox" name="show_document" value="1" <?= $profileFormData['show_document'] ? 'checked' : ''; ?>>
            Exibir CPF/CNPJ publicamente
        </label>

        <div class="input-group">
            <label>Email de contato</label>
            <div class="document-readonly">
                <span class="document-readonly__value"><?= e($user['email'] ?? 'Não informado'); ?></span>
            </div>
            <p class="document-support-hint">Para atualizar o email, entre em contato com o suporte.</p>
        </div>
        <label style="display:flex;gap:0.5rem;align-items:center;">
            <input type="checkbox" name="show_email" value="1" <?= $profileFormData['show_email'] ? 'checked' : ''; ?>>
            Exibir email de contato publicamente
        </label>

        <h3 style="font-size:1.1rem;font-weight:600;color:#1f2937;">Endereco</h3>
        <div class="input-grid" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        <div class="input-group" style="grid-column:1/-1;">
        <label for="address_postal_code">CEP</label>
        <input type="text" id="address_postal_code" name="address_postal_code" value="<?= e($profileFormData['address_postal_code']); ?>" maxlength="12" placeholder="00000-000" inputmode="numeric" data-mask="postal">
        </div>
        <div class="input-group" style="grid-column:1/-1;">
        <label for="address_street">Rua</label>
        <input type="text" id="address_street" name="address_street" value="<?= e($profileFormData['address_street']); ?>">
        </div>
        <div class="input-group">
        <label for="address_number">Numero</label>
        <input type="text" id="address_number" name="address_number" value="<?= e($profileFormData['address_number']); ?>">
        </div>
        <div class="input-group">
        <label for="address_complement">Complemento</label>
        <input type="text" id="address_complement" name="address_complement" value="<?= e($profileFormData['address_complement']); ?>">
        </div>
        <div class="input-group">
        <label for="address_neighborhood">Bairro</label>
        <input type="text" id="address_neighborhood" name="address_neighborhood" value="<?= e($profileFormData['address_neighborhood']); ?>">
        </div>
        <div class="input-group">
            <label for="profile_state">Estado</label>
        <select id="profile_state" name="address_state">
            <option value="">Selecione um estado</option>
        <?php foreach ($stateOptions as $code => $label): ?>
            <option value="<?= e($code); ?>" <?= strtoupper((string)$profileFormData['address_state']) === $code ? 'selected' : ''; ?>>
            <?= e($label); ?>
        </option>
        <?php endforeach; ?>
        </select>
        </div>
        <div class="input-group">
            <label for="profile_city">Cidade</label>
        <input type="text" id="profile_city" name="address_city" value="<?= e($profileFormData['address_city']); ?>" list="profile_cities_list">
        <datalist id="profile_cities_list"></datalist>
        </div>
        </div>

        <div class="input-group">
            <label for="about">Sobre voce / loja</label>
            <textarea id="about" name="about" rows="4" placeholder="Conte um pouco sobre sua atuacao, diferencas e atendimento."><?= e($profileFormData['about']); ?></textarea>
        </div>

        <button type="submit" class="button">Salvar dados</button>
    </form>
</section>
