<?php
/**
 * premium/sucesso.php
 * Página de confirmação após pagamento bem-sucedido
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_login();
ensure_user_not_blocked();

$user = current_user();
$pageTitle = 'Assinatura Confirmada — Classi Livre Autos';

// Buscar a assinatura que foi criada
$subscription = null;
try {
    $stmt = db()->prepare('SELECT ps.*, pp.name as plan_name, pp.price_decimal FROM premium_subscriptions ps JOIN premium_plans pp ON ps.plan_id = pp.id WHERE ps.user_id = ? ORDER BY ps.created_at DESC LIMIT 1');
    $stmt->execute([(int)$user['id']]);
    $subscription = $stmt->fetch();
} catch (Exception $e) {
    error_log('[premium/sucesso] Erro: ' . $e->getMessage());
}

$periodMap = ['M' => 'Mensal', 'T' => 'Trimestral', 'S' => 'Semestral'];

$amountPaidValue = null;
$amountCurrency = 'BRL';
if ($subscription) {
    if (isset($subscription['amount_paid']) && $subscription['amount_paid'] !== null) {
        $amountPaidValue = (float)$subscription['amount_paid'];
    }
    if ($amountPaidValue === null || $amountPaidValue <= 0) {
        $amountPaidValue = (float)($subscription['price_decimal'] ?? 0);
    }
    $amountCurrency = strtoupper((string)($subscription['payment_currency'] ?? 'BRL'));
    if ($amountCurrency === '') {
        $amountCurrency = 'BRL';
    }
}
$currencySymbol = $amountCurrency === 'BRL' ? 'R$' : ($amountCurrency . ' ');

include __DIR__ . '/../partials/header.php';
?>

<style>
.success-hero {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 50%, #84cc16 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: center;
}

.success-hero h1 {
    font-size: 2.5rem;
    margin: 0;
}

.success-icon {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    display: block;
    animation: bounce 0.6s ease-in-out;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

.success-container {
    max-width: 700px;
    margin: 2rem auto;
}

.success-details {
    background: #f0fdf4;
    border: 2px solid #16a34a;
    border-radius: 12px;
    padding: 2rem;
    margin: 2rem 0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #dcfce7;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #4b5563;
    font-weight: 500;
}

.detail-value {
    color: #166534;
    font-weight: 600;
}

.benefits-summary {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.benefits-summary h3 {
    color: #1f2937;
    margin-top: 0;
    font-size: 1.1rem;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.benefit-tag {
    background: #f0fdf4;
    border: 1px solid #86efac;
    color: #166534;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    text-align: center;
    font-weight: 500;
    font-size: 0.9rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.btn-primary {
    padding: 1rem 2rem;
    background: #16a34a;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-block;
    font-size: 1rem;
}

.btn-primary:hover {
    background: #15803d;
    transform: scale(1.02);
}

.btn-secondary {
    padding: 1rem 2rem;
    background: #f3f4f6;
    color: #1f2937;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-block;
    font-size: 1rem;
}

.btn-secondary:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
}

.congratulations-text {
    color: #4b5563;
    font-size: 1rem;
    line-height: 1.8;
    margin: 1.5rem 0;
    text-align: center;
}

.info-box {
    background: #f9fafb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 2rem;
    border: 1px solid #e5e7eb;
    text-align: center;
}

.info-box p {
    margin: 0;
    color: #6b7280;
    font-size: 0.95rem;
}
</style>

<div class="success-container">
    
    <!-- Hero Section -->
    <div class="success-hero">
        <span class="success-icon">🎉</span>
        <h1>Parabéns!</h1>
        <p style="margin: 0.5rem 0 0 0; font-size: 1.1rem; opacity: 0.95;">
            Sua assinatura Premium foi ativada com sucesso
        </p>
    </div>

    <!-- Detalhes da Assinatura -->
    <?php if ($subscription): ?>
    <div class="success-details">
        <div class="detail-row">
            <span class="detail-label">📦 Plano contratado</span>
            <span class="detail-value"><?= e($subscription['plan_name'] ?? 'Desconhecido'); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">💰 Valor pago</span>
            <span class="detail-value"><?= $currencySymbol; ?> <?= number_format((float)$amountPaidValue, 2, ',', '.'); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">📅 Período de acesso</span>
            <span class="detail-value">
                <?= format_date($subscription['started_at']); ?> a <?= format_date($subscription['expires_at']); ?>
            </span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">✅ Status</span>
            <span class="detail-value" style="background: #d1fae5; padding: 0.25rem 0.75rem; border-radius: 4px;">
                Ativo
            </span>
        </div>
    </div>
    <?php else: ?>
    <div class="success-details" style="background:#fff7ed;border-color:#f97316;">
        <p style="margin:0;color:#9a3412;">
            Estamos processando a confirmação do pagamento. Assim que o Mercado Pago aprovar a transação,
            sua assinatura será ativada automaticamente e você receberá um e-mail com os detalhes.
        </p>
    </div>
    <?php endif; ?>

    <!-- Benefícios Ativos -->
    <div class="benefits-summary">
        <h3>🎁 Seus benefícios estão ativos:</h3>
        <div class="benefits-grid">
            <div class="benefit-tag">📊 Destaque nas buscas</div>
            <div class="benefit-tag">🎥 Vídeos em anúncios</div>
            <div class="benefit-tag">🏪 Loja pessoal</div>
            <div class="benefit-tag">🔔 Alertas automáticos</div>
            <div class="benefit-tag">✅ Badge verificado</div>
            <div class="benefit-tag">📈 Métricas completas</div>
        </div>
    </div>

    <!-- Próximas Ações -->
    <div class="congratulations-text">
        <strong>Agora você pode:</strong><br>
        Criar anúncios com destaque prioritário, adicionar vídeos, acessar sua loja pessoal e monitorar métricas detalhadas de suas publicações.
    </div>

    <!-- Botões de Ação -->
    <div class="action-buttons">
        <a href="/anuncios/criar.php" class="btn-primary">
            ➕ Criar Novo Anúncio
        </a>
        <a href="/premium/minha-assinatura.php" class="btn-primary">
            👤 Gerenciar Assinatura
        </a>
    </div>

    <div class="action-buttons">
        <a href="/" class="btn-secondary">
            ← Voltar para Início
        </a>
    </div>

    <!-- Informação de Confirmação -->
    <div class="info-box">
        <p>
            <strong>📧 E-mail de confirmação</strong><br>
            Um resumo da sua compra foi enviado para o seu e-mail registrado
        </p>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
