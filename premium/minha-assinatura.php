<?php
/**
 * premium/minha-assinatura.php
 * Dashboard para gerenciar assinatura premium ativa
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_login();
ensure_user_not_blocked();

$user = current_user();
$pageTitle = 'Minha Assinatura Premium — Classi Livre Autos';

// Carregar assinatura ativa
$subscription = null;
try {
    $stmt = db()->prepare('SELECT ps.*, pp.name as plan_name, pp.price_decimal FROM premium_subscriptions ps JOIN premium_plans pp ON ps.plan_id = pp.id WHERE ps.user_id = ? AND (ps.expires_at IS NULL OR ps.expires_at > NOW()) ORDER BY ps.expires_at DESC LIMIT 1');
    $stmt->execute([(int)$user['id']]);
    $subscription = $stmt->fetch();
} catch (Exception $e) {
    error_log('[minha-assinatura] Erro: ' . $e->getMessage());
}

// Se não tem assinatura ativa, redireciona para vitrine
if (!$subscription) {
    header('Location: /premium/status.php');
    exit;
}

// Mapa de períodos
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
.subscription-hero {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 50%, #84cc16 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.subscription-detail {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #6b7280;
    font-weight: 500;
}

.detail-value {
    font-weight: 600;
    color: #1f2937;
}

.status-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #d1fae5;
    color: #065f46;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.cta-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.button-secondary {
    padding: 1rem;
    background: #f3f4f6;
    color: #1f2937;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s ease;
}

.button-secondary:hover {
    background: #e5e7eb;
}

.button-danger {
    padding: 1rem;
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s ease;
}

.button-danger:hover {
    background: #fecaca;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.benefit-item {
    background: #f0fdf4;
    border-left: 4px solid #16a34a;
    padding: 1rem;
    border-radius: 8px;
}

.benefit-item h4 {
    margin: 0 0 0.5rem 0;
    color: #166534;
}

.benefit-item p {
    margin: 0;
    color: #4b5563;
    font-size: 0.9rem;
}

.timeline {
    position: relative;
    padding: 2rem 0;
}

.timeline-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.timeline-dot {
    width: 12px;
    height: 12px;
    background: #16a34a;
    border-radius: 50%;
    margin-top: 0.5rem;
    flex-shrink: 0;
}

.timeline-content {
    flex: 1;
}

.timeline-content strong {
    color: #1f2937;
}

.timeline-content small {
    color: #6b7280;
}
</style>

<section style="max-width:900px; margin:0 auto; padding:2rem;">
    
    <!-- Hero -->
    <div class="subscription-hero">
        <h1 style="margin:0 0 0.5rem 0; font-size:2rem;">Sua Assinatura Premium</h1>
        <p style="margin:0; opacity:0.9;">Gerenciar seu plano e desfrutar de todos os benefícios</p>
    </div>
    
    <!-- Status Atual -->
    <div class="subscription-detail">
        <h2 style="margin-top:0; margin-bottom:1.5rem;">Status da Assinatura</h2>
        
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="status-badge">✅ Ativo</span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Plano</span>
            <span class="detail-value"><?= e($subscription['plan_name'] ?? 'Desconhecido'); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Início da Assinatura</span>
            <span class="detail-value"><?= format_date($subscription['started_at']); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Válido até</span>
            <span class="detail-value">
                <?= format_date($subscription['expires_at']); ?>
                
                <?php 
                $dias_restantes = (int)((strtotime($subscription['expires_at']) - time()) / 86400);
                if ($dias_restantes > 0):
                ?>
                    <small style="display:block; margin-top:0.25rem; color:#6b7280;">(<?= $dias_restantes; ?> dias restantes)</small>
                <?php endif; ?>
            </span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Valor Pago</span>
            <span class="detail-value"><?= $currencySymbol; ?> <?= number_format((float)$amountPaidValue, 2, ',', '.'); ?></span>
        </div>
    </div>
    
    <!-- Benefícios Ativos -->
    <div class="subscription-detail">
        <h2 style="margin-top:0; margin-bottom:1.5rem;">Seus Benefícios</h2>
        
        <div class="benefits-grid">
            <div class="benefit-item">
                <h4>📊 Destaque nas Buscas</h4>
                <p>Seus anúncios aparecem com prioridade na página inicial e resultados de busca.</p>
            </div>
            <div class="benefit-item">
                <h4>🎥 Vídeo por Anúncio</h4>
                <p>Adicione 1 vídeo a cada anúncio para mostrar mais detalhes do veículo.</p>
            </div>
            <div class="benefit-item">
                <h4>🏪 Loja Pessoal</h4>
                <p>Tenha sua loja pública onde clientes veem todos os seus anúncios.</p>
            </div>
            <div class="benefit-item">
                <h4>🔔 Alertas de Busca</h4>
                <p>Salve buscas e receba alertas sobre novos anúncios que correspondem.</p>
            </div>
            <div class="benefit-item">
                <h4>✅ Badge Verificado</h4>
                <p>Envie documentos e ganhe um selo de confiança nos seus anúncios.</p>
            </div>
            <div class="benefit-item">
                <h4>📈 Métricas Completas</h4>
                <p>Acompanhe visitas, cliques, ligações e rotas consultadas.</p>
            </div>
        </div>
    </div>
    
    <!-- Próximas Ações -->
    <div class="subscription-detail">
        <h2 style="margin-top:0; margin-bottom:1.5rem;">Próximas Ações</h2>
        
        <div class="cta-group">
            <a href="/anuncios/criar.php" class="button-secondary">
                ➕ Criar Novo Anúncio
            </a>
            <a href="/premium/status.php" class="button-secondary">
                🔄 Renovar Plano
            </a>
        </div>
    </div>
    
    <!-- Link para Voltar -->
    <div style="text-align:center; margin:2rem 0;">
        <a href="/" style="color:#16a34a; text-decoration:none; font-weight:500;">
            ← Voltar para a página principal
        </a>
    </div>
    
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
