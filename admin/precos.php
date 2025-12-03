<?php
// admin/precos.php - Configuração de preços dos anúncios, moedas e premium

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$pageTitle = 'Configuração de Preços - Administracao';
$activeNav = 'prices';
$tab = input_get('tab', 'moedas');

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalido.';
    } else {
        $action = input_post('action', '');
        
        if ($action === 'save_moedas') {
            $servicePrice = (float)($_POST['service_price'] ?? 15.00);
            $cliveMedalPrice = (float)($_POST['clive_medal_price'] ?? 9.90);
            $adsFreeForAll = isset($_POST['ads_free_for_all']) ? 1 : 0;
            $servicesFreeForAll = isset($_POST['services_free_for_all']) ? 1 : 0;
            $newAccountCoins = (int)($_POST['new_account_coins'] ?? 0);
            
            if ($servicePrice < 0) {
                $errors[] = 'Preço de serviço deve ser positivo.';
            } elseif ($cliveMedalPrice < 0.01) {
                $errors[] = 'Preço da CLivre Medal deve ser maior que R$ 0,00.';
            } elseif ($newAccountCoins < 0) {
                $errors[] = 'Saldo para conta nova deve ser não-negativo.';
            } else {
                update_setting('service_price', number_format($servicePrice, 2, '.', ''));
                update_setting('clivre_medal_price', number_format($cliveMedalPrice, 2, '.', ''));
                update_setting('ads_free_for_all', (string)$adsFreeForAll);
                update_setting('services_free_for_all', (string)$servicesFreeForAll);
                update_setting('new_account_coins', (string)$newAccountCoins);
                
                record_audit((int)current_user()['id'], 'prices_and_free_settings_updated', 'settings', 0, [
                    'service_price' => $servicePrice,
                    'clivre_medal_price' => $cliveMedalPrice,
                    'ads_free_for_all' => $adsFreeForAll,
                    'services_free_for_all' => $servicesFreeForAll,
                    'new_account_coins' => $newAccountCoins
                ]);
                
                $success = 'Preços de moedas atualizados com sucesso.';
                $tab = 'moedas';
            }
        }
        
        if ($action === 'save_premium') {
            $premium_base_mensal = (float)($_POST['premium_base_mensal'] ?? 29.90);
            $desconto_trimestral_pct = (int)($_POST['desconto_trimestral_pct'] ?? 11);
            $desconto_semestral_pct = (int)($_POST['desconto_semestral_pct'] ?? 17);
            
            if ($premium_base_mensal < 0.01) {
                $errors[] = 'Valor base do Premium deve ser maior que R$ 0,00.';
            } elseif ($desconto_trimestral_pct < 0 || $desconto_trimestral_pct > 100) {
                $errors[] = 'Desconto trimestral deve estar entre 0 e 100%.';
            } elseif ($desconto_semestral_pct < 0 || $desconto_semestral_pct > 100) {
                $errors[] = 'Desconto semestral deve estar entre 0 e 100%.';
            } else {
                update_setting('premium_base_mensal', number_format($premium_base_mensal, 2, '.', ''));
                update_setting('desconto_trimestral_pct', (string)$desconto_trimestral_pct);
                update_setting('desconto_semestral_pct', (string)$desconto_semestral_pct);
                
                record_audit((int)current_user()['id'], 'premium_prices_updated', 'settings', 0, [
                    'premium_base_mensal' => $premium_base_mensal,
                    'desconto_trimestral_pct' => $desconto_trimestral_pct,
                    'desconto_semestral_pct' => $desconto_semestral_pct
                ]);
                
                $success = 'Preços Premium atualizados com sucesso! Os novos valores serão refletidos imediatamente na vitrine e no Mercado Pago.';
                $tab = 'premium';
            }
        }
    }
}

$currentServicePrice = (float)get_setting('service_price', '15.00');
$cliveMedalPrice = (float)get_setting('clivre_medal_price', '9.90');
$adsFreeForAll = (int)get_setting('ads_free_for_all', '0');
$servicesFreeForAll = (int)get_setting('services_free_for_all', '0');
$newAccountCoins = (int)get_setting('new_account_coins', '0');

$premium_base_mensal = (float)get_setting('premium_base_mensal', '29.90');
$desconto_trimestral_pct = (int)get_setting('desconto_trimestral_pct', '11');
$desconto_semestral_pct = (int)get_setting('desconto_semestral_pct', '17');

$premium_mensal = $premium_base_mensal;
$premium_trimestral = round($premium_base_mensal * 3 * (1 - $desconto_trimestral_pct / 100), 2);
$premium_semestral = round($premium_base_mensal * 6 * (1 - $desconto_semestral_pct / 100), 2);

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>

<section style="margin:2rem 0;">
    <h1>Configuração de Preços</h1>
    <p>Gerencie os preços de moedas, premium e serviços automotivos.</p>
    
    <?php if ($errors): ?>
        <div class="alert alert-error" style="margin-top:1rem;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-top:1rem;"><?= e($success); ?></div>
    <?php endif; ?>
    
    <!-- Abas -->
    <nav style="display:flex; gap:0.5rem; margin:2rem 0; border-bottom:2px solid #ddd; flex-wrap:wrap;">
        <a href="?tab=moedas" style="padding:1rem 1.5rem; color:<?= $tab === 'moedas' ? '#667eea' : '#666'; ?>; border-bottom:<?= $tab === 'moedas' ? '3px solid #667eea' : 'none'; ?>; text-decoration:none; cursor:pointer;">
            💰 Moedas
        </a>
        <a href="?tab=servicos" style="padding:1rem 1.5rem; color:<?= $tab === 'servicos' ? '#f59e0b' : '#666'; ?>; border-bottom:<?= $tab === 'servicos' ? '3px solid #f59e0b' : 'none'; ?>; text-decoration:none; cursor:pointer;">
            🚗 Serviços
        </a>
        <a href="?tab=premium" style="padding:1rem 1.5rem; color:<?= $tab === 'premium' ? '#16a34a' : '#666'; ?>; border-bottom:<?= $tab === 'premium' ? '3px solid #16a34a' : 'none'; ?>; text-decoration:none; cursor:pointer;">
            👑 Premium
        </a>
    </nav>
    
    <!-- TAB: MOEDAS -->
    <?php if ($tab === 'moedas'): ?>
    <form method="post" class="search-card admin-filter-form" style="margin-top:2rem; max-width:600px;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="save_moedas">

        <div class="input-group">
            <label for="service_price">Preço de Serviços (R$)</label>
            <input type="number" id="service_price" name="service_price" min="0" step="0.01"
                   value="<?= number_format($currentServicePrice, 2, '.', ''); ?>" required>
            <small style="color:#666;">Valor mensal cobrado por serviços automotivos</small>
        </div>

        <div class="input-group">
            <label for="clive_medal_price">Preço da CLivre Medal (R$)</label>
            <input type="number" id="clive_medal_price" name="clive_medal_price" min="0.01" step="0.01"
                   value="<?= number_format($cliveMedalPrice, 2, '.', ''); ?>" required>
            <small style="color:#666;">Preço base da moeda Clivre. Pacotes de compra têm bônus calculados dinamicamente.</small>
        </div>
        
        <div style="margin:1rem 0; padding:1rem; background:#e3f2fd; border-radius:8px; border-left:4px solid #2196f3;">
            <strong>📊 Pacotes de Moedas (Calculados Automaticamente):</strong><br>
            <small style="color:#555; line-height:1.8;">
                • <strong>Starter:</strong> 1 moeda = R$ <?= number_format($cliveMedalPrice, 2, ',', '.'); ?><br>
                • <strong>Smart:</strong> 5 moedas (+1 bonus) = R$ <?= number_format($cliveMedalPrice * 6, 2, ',', '.'); ?><br>
                • <strong>Popular:</strong> 10 moedas (+2 bonus) = R$ <?= number_format($cliveMedalPrice * 12, 2, ',', '.'); ?><br>
                • <strong>Pro:</strong> 20 moedas (+7 bonus) = R$ <?= number_format($cliveMedalPrice * 27, 2, ',', '.'); ?><br>
                • <strong>Mega:</strong> 50 moedas (+20 bonus) = R$ <?= number_format($cliveMedalPrice * 70, 2, ',', '.'); ?>
            </small>
        </div>

        <h3 style="margin-top:2rem; margin-bottom:1rem;">Configurações de Gratuidade</h3>

        <div class="input-group">
            <label style="display:flex; align-items:center; gap:0.5rem;">
                <input type="checkbox" id="ads_free_for_all" name="ads_free_for_all" value="1" <?= $adsFreeForAll ? 'checked' : ''; ?>>
                Anúncios gratuitos para todos os usuários
            </label>
            <small style="color:#666;">Quando ativado, nenhum usuário paga por anúncios</small>
        </div>

        <div class="input-group">
            <label style="display:flex; align-items:center; gap:0.5rem;">
                <input type="checkbox" id="services_free_for_all" name="services_free_for_all" value="1" <?= $servicesFreeForAll ? 'checked' : ''; ?>>
                Serviços gratuitos para todos os usuários
            </label>
            <small style="color:#666;">Quando ativado, nenhum usuário paga por serviços</small>
        </div>

        <h3 style="margin-top:2rem; margin-bottom:1rem;">Saldo para Contas Novas</h3>

        <div class="input-group">
            <label for="new_account_coins">Moedas Clivre para conta nova</label>
            <input type="number" id="new_account_coins" name="new_account_coins" min="0"
            value="<?= $newAccountCoins; ?>">
            <small style="color:#666;">Quantidade de moedas que cada novo usuário receberá ao criar conta (0 = sem saldo inicial)</small>
        </div>

        <div style="margin-top:1rem; padding:1rem; background:#f8f9fa; border-radius:8px;">
            <strong>📋 Resumo de Configurações:</strong><br>
            - Duração anúncios: 30 dias após aprovação<br>
            - Duração serviços: 30 dias (renovação mensal)<br>
            - Preço da CLivre Medal: <strong>R$ <?= number_format($cliveMedalPrice, 2, ',', '.'); ?></strong><br>
            - Moedas para conta nova: <strong><?= $newAccountCoins; ?> moeda<?= $newAccountCoins !== 1 ? 's' : ''; ?></strong><br>
            <?php if ($adsFreeForAll): ?>
            - Anúncios: GRATUITOS para todos os usuários<br>
            <?php else: ?>
            - Anúncios: Cobrados com 1 CLivre Medal (R$ <?= number_format($cliveMedalPrice, 2, ',', '.'); ?>)<br>
            <?php endif; ?>
            <?php if ($servicesFreeForAll): ?>
            - Serviços: GRATUITOS para todos os usuários<br>
            <?php else: ?>
            - Serviços: R$ <?= number_format($currentServicePrice, 2, ',', '.'); ?> (mensal)<br>
            <?php endif; ?>
        </div>

        <div class="admin-filter-actions" style="margin-top:1.5rem;">
            <button type="submit" class="button button-admin admin-filter-button">Salvar Preços de Moedas</button>
        </div>
    </form>
    <?php endif; ?>
    
    <!-- TAB: SERVIÇOS -->
    <?php if ($tab === 'servicos'): ?>
    <div style="margin-top:2rem; padding:2rem; background:#fff3cd; border:1px solid #ffc107; border-radius:8px;">
        <strong>🔧 Serviços Automotivos</strong><br>
        <p style="color:#856404; margin:0.5rem 0 0 0;">O preço dos serviços é configurado na aba de <strong>Moedas</strong> (campo "Preço de Serviços").</p>
    </div>
    <?php endif; ?>
    
    <!-- TAB: PREMIUM -->
    <?php if ($tab === 'premium'): ?>
    <form method="post" class="search-card admin-filter-form" style="margin-top:2rem; max-width:700px;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <input type="hidden" name="action" value="save_premium">
        
        <p style="color:#666; margin-bottom:1.5rem;">Configure o valor-base do Premium Mensal e os descontos percentuais para períodos maiores. Os preços de Trimestral e Semestral são calculados automaticamente.</p>
        
        <!-- Base Mensal -->
        <div style="background:#f9f9f9; padding:1.5rem; border-radius:8px; margin-bottom:1.5rem; border-left:4px solid #16a34a;">
            <label style="display:block; margin-bottom:0.5rem; font-weight:600;">
                Valor Base - Premium Mensal (R$)
            </label>
            <input type="number" name="premium_base_mensal" value="<?= number_format($premium_base_mensal, 2, '.', ''); ?>" 
                   step="0.01" min="0.01" max="999.99" required
                   style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:6px; font-size:1rem;">
            <small style="display:block; color:#999; margin-top:0.5rem;">
                Este é o valor base usado para calcular os outros períodos automaticamente.
            </small>
        </div>
        
        <!-- Desconto Trimestral -->
        <div style="background:#f9f9f9; padding:1.5rem; border-radius:8px; margin-bottom:1.5rem; border-left:4px solid #f59e0b;">
            <label style="display:block; margin-bottom:0.5rem; font-weight:600;">
                Desconto Trimestral (%)
            </label>
            <input type="number" name="desconto_trimestral_pct" value="<?= (int)$desconto_trimestral_pct; ?>" 
                   step="1" min="0" max="100" required
                   style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:6px; font-size:1rem;">
            <small style="display:block; color:#999; margin-top:0.5rem;">
                Trimestral = (Mensal × 3) × (1 - desconto%)
            </small>
        </div>
        
        <!-- Desconto Semestral -->
        <div style="background:#f9f9f9; padding:1.5rem; border-radius:8px; margin-bottom:2rem; border-left:4px solid #dc2626;">
            <label style="display:block; margin-bottom:0.5rem; font-weight:600;">
                Desconto Semestral (%)
            </label>
            <input type="number" name="desconto_semestral_pct" value="<?= (int)$desconto_semestral_pct; ?>" 
                   step="1" min="0" max="100" required
                   style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:6px; font-size:1rem;">
            <small style="display:block; color:#999; margin-top:0.5rem;">
                Semestral = (Mensal × 6) × (1 - desconto%)
            </small>
        </div>
        
        <!-- Preview dos Valores -->
        <div style="background:#e8f5e9; padding:1.5rem; border-radius:8px; margin-bottom:2rem; border-left:4px solid #4caf50;">
            <strong style="display:block; margin-bottom:1rem; color:#2e7d32;">📊 Prévia dos Valores Calculados:</strong>
            <div style="display:grid; gap:1rem;">
                <div style="background:#fff; padding:1rem; border-radius:6px; border-left:3px solid #4caf50;">
                    <small style="color:#666; display:block;">Mensal (1 mês)</small>
                    <strong style="font-size:1.3rem; color:#2e7d32;">
                        R$ <?= number_format($premium_mensal, 2, ',', '.'); ?>
                    </strong>
                </div>
                <div style="background:#fff; padding:1rem; border-radius:6px; border-left:3px solid #f59e0b;">
                    <small style="color:#666; display:block;">Trimestral (3 meses) - <?= (int)$desconto_trimestral_pct; ?>% desconto</small>
                    <strong style="font-size:1.3rem; color:#f59e0b;">
                        R$ <?= number_format($premium_trimestral, 2, ',', '.'); ?>
                    </strong>
                    <small style="color:#999; display:block; margin-top:0.3rem;">
                        Economia: R$ <?= number_format(($premium_mensal * 3) - $premium_trimestral, 2, ',', '.'); ?>
                    </small>
                </div>
                <div style="background:#fff; padding:1rem; border-radius:6px; border-left:3px solid #dc2626;">
                    <small style="color:#666; display:block;">Semestral (6 meses) - <?= (int)$desconto_semestral_pct; ?>% desconto</small>
                    <strong style="font-size:1.3rem; color:#dc2626;">
                        R$ <?= number_format($premium_semestral, 2, ',', '.'); ?>
                    </strong>
                    <small style="color:#999; display:block; margin-top:0.3rem;">
                        Economia: R$ <?= number_format(($premium_mensal * 6) - $premium_semestral, 2, ',', '.'); ?>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="admin-filter-actions" style="margin-top:1.5rem;">
            <button type="submit" class="button button-admin admin-filter-button" style="background:#16a34a;">
                ✓ Salvar Preços Premium
            </button>
        </div>
    </form>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
