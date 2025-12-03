<?php
/**
 * premium/status.php
 * Página de Assinatura Premium (Vitrine + Checkout Mercado Pago)
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_login();
ensure_user_not_blocked();

$user = current_user();
$pageTitle = 'Premium — Classi Livre Autos';

// ============================================
// 1. CARREGAR DADOS DE PREÇO
// ============================================

$premium_base_mensal = (float)get_setting('premium_base_mensal', '29.90');
$desconto_trimestral_pct = (int)get_setting('desconto_trimestral_pct', '11');
$desconto_semestral_pct = (int)get_setting('desconto_semestral_pct', '17');

// Calcular preços
$premium_mensal = $premium_base_mensal;
$premium_trimestral = round($premium_base_mensal * 3 * (1 - $desconto_trimestral_pct / 100), 2);
$premium_semestral = round($premium_base_mensal * 6 * (1 - $desconto_semestral_pct / 100), 2);

// ============================================
// 2. VERIFICAR ASSINATURA ATIVA
// ============================================

$current_subscription = null;
$is_premium = user_is_premium($user);

try {
    $stmt = db()->prepare(
        'SELECT ps.*, pp.name AS plan_name
           FROM premium_subscriptions ps
           LEFT JOIN premium_plans pp ON pp.id = ps.plan_id
          WHERE ps.user_id = :user_id
            AND ps.payment_status = "approved"
            AND (ps.expires_at IS NULL OR ps.expires_at > NOW())
          ORDER BY ps.expires_at DESC
          LIMIT 1'
    );
    $stmt->execute([':user_id' => (int)$user['id']]);
    $current_subscription = $stmt->fetch();
} catch (Exception $e) {
    error_log('[premium/status.php] Erro ao buscar assinatura: ' . $e->getMessage());
}

include __DIR__ . '/../partials/header.php';
?>

<style>
.premium-hero {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 50%, #84cc16 100%);
    color: white;
    padding: 3rem 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: center;
}

.premium-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.premium-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.premium-card:hover {
    border-color: #16a34a;
    box-shadow: 0 12px 24px rgba(22, 163, 74, 0.15);
    transform: translateY(-4px);
}

.premium-card.badge-featured {
    border-color: #f59e0b;
    box-shadow: 0 12px 24px rgba(245, 158, 11, 0.15);
}

.premium-card.badge-best {
    border-color: #dc2626;
    box-shadow: 0 12px 24px rgba(220, 38, 38, 0.15);
}

.premium-card-header {
    padding: 1.5rem;
    background: #f9f9f9;
    border-bottom: 2px solid #e5e7eb;
}

.premium-card-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #fef08a;
    color: #713f12;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.premium-card-badge.best {
    background: #fee2e2;
    color: #991b1b;
}

.premium-card-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: #1f2937;
    margin: 0.5rem 0;
}

.premium-card-price {
    font-size: 2.5rem;
    font-weight: bold;
    color: #16a34a;
    margin: 1rem 0;
}

.premium-card-period {
    font-size: 0.9rem;
    color: #6b7280;
}

.premium-card-body {
    padding: 1.5rem;
    flex: 1;
}

.premium-benefits {
    list-style: none;
    margin: 0;
    padding: 0;
}

.premium-benefits li {
    padding: 0.75rem 0;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.95rem;
    color: #4b5563;
}

.premium-benefits li::before {
    content: '✓';
    font-weight: bold;
    color: #16a34a;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.premium-card-footer {
    padding: 1.5rem;
    background: #f9f9f9;
    border-top: 2px solid #e5e7eb;
    margin-top: auto;
}

.premium-cta {
    width: 100%;
    padding: 1rem;
    background: #16a34a;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.premium-cta:hover {
    background: #15803d;
    transform: scale(1.02);
}

.premium-cta.loading {
    opacity: 0.6;
    cursor: not-allowed;
}

.premium-benefits-section {
    background: #f0fdf4;
    border-left: 4px solid #16a34a;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.premium-faq {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.premium-faq-question {
    padding: 1rem;
    background: #f9f9f9;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.premium-faq-answer {
    padding: 1rem;
    background: white;
    display: none;
    border-top: 1px solid #e5e7eb;
}

.premium-faq.open .premium-faq-answer {
    display: block;
}
</style>

<section style="max-width:1200px; margin:0 auto; padding:2rem;">
    
    <!-- Hero -->
    <div class="premium-hero">
        <h1 style="font-size:2.5rem; margin:0 0 1rem 0;">
            Ative o Premium e venda mais rápido
        </h1>
        <p style="font-size:1.1rem; margin:0; opacity:0.95;">
            Destaque, vídeo, loja pessoal e métricas para impulsionar suas vendas
        </p>
    </div>
    
    <!-- Status Atual -->
    <?php if ($is_premium && $current_subscription): ?>
    <div style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #155724; padding: 2rem; border-radius: 12px; margin-bottom: 2.5rem; box-shadow: 0 4px 6px rgba(21, 87, 36, 0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;">
            <div>
                <h3 style="margin: 0 0 0.5rem 0; color: #155724; font-size: 1.3rem;">✅ Você é Premium!</h3>
                <p style="margin: 0; color: #155724; font-size: 1.05rem;">
                    <?= $current_subscription['plan_name'] ? 'Plano ' . e($current_subscription['plan_name']) . ' ativo.' : 'Assinatura ativa.' ?>
                    <?php if (!empty($current_subscription['expires_at'])): ?>
                        <br>Válida até <strong><?= format_date($current_subscription['expires_at']); ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <a href="/premium/minha-assinatura.php" style="background: #155724; color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; display: inline-block; white-space: nowrap; transition: background 0.3s ease;">
                Gerenciar Assinatura
            </a>
        </div>
    </div>
    <?php elseif ($is_premium): ?>
    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #b45309; padding: 2rem; border-radius: 12px; margin-bottom: 2.5rem; box-shadow: 0 4px 6px rgba(180, 83, 9, 0.1);">
        <h3 style="margin: 0 0 0.75rem 0; color: #92400e; font-size: 1.2rem;">Pagamento em processamento</h3>
        <p style="margin: 0; color: #b45309; font-size: 1.05rem;">
            Recebemos sua solicitação de assinatura e estamos aguardando a confirmação do Mercado Pago.
            Assim que o pagamento for aprovado, os detalhes aparecerão aqui automaticamente.
        </p>
    </div>
    <?php endif; ?>
    
    <!-- Cards de Preço -->
    <div class="premium-cards">
        
        <!-- MENSAL -->
        <div class="premium-card">
            <div class="premium-card-header">
                <div class="premium-card-title">Mensal</div>
                <div class="premium-card-price">R$ <?= number_format($premium_mensal, 2, ',', '.'); ?></div>
                <div class="premium-card-period">1 mês de acesso</div>
            </div>
            <div class="premium-card-body">
                <ul class="premium-benefits">
                    <li>Destaque na index e nas buscas</li>
                    <li>Loja pessoal do vendedor</li>
                    <li>Salvar Busca + alertas por e-mail</li>
                    <li>Badge de Verificado</li>
                    <li>Métricas de visitas e ações</li>
                    <li>1 vídeo por anúncio</li>
                </ul>
            </div>
            <div class="premium-card-footer">
                <button class="premium-cta" onclick="initiatePremiumCheckout('M', <?= $premium_mensal; ?>)" data-period="M">
                    Assinar agora
                </button>
            </div>
        </div>
        
        <!-- TRIMESTRAL -->
        <div class="premium-card badge-featured" style="border-width:3px;">
            <div class="premium-card-header" style="background:linear-gradient(135deg, #fef08a 0%, #fef3c7 100%); position:relative;">
                <div style="position:absolute; top:-15px; right:20px; font-size:2.5rem;"></div>
                <div class="premium-card-badge" style="background:#fbbf24; color:#78350f; font-size:1rem; padding:0.75rem 1.5rem; display:inline-block;">
                    ⭐ MAIS ESCOLHIDO
                </div>
                <div class="premium-card-title" style="color:#d97706; margin-top:1rem;">Trimestral</div>
                <div class="premium-card-price" style="color:#f59e0b;">R$ <?= number_format($premium_trimestral, 2, ',', '.'); ?></div>
                <div class="premium-card-period">3 meses de acesso</div>
                <small style="display:block; margin-top:0.5rem; color:#92400e;">
                    Economia: R$ <?= number_format(($premium_mensal * 3) - $premium_trimestral, 2, ',', '.'); ?> (-<?= $desconto_trimestral_pct; ?>%)
                </small>
            </div>
            <div class="premium-card-body">
                <ul class="premium-benefits">
                    <li>Destaque na index e nas buscas</li>
                    <li>Loja pessoal do vendedor</li>
                    <li>Salvar Busca + alertas por e-mail</li>
                    <li>Badge de Verificado</li>
                    <li>Métricas de visitas e ações</li>
                    <li>1 vídeo por anúncio</li>
                </ul>
            </div>
            <div class="premium-card-footer">
                <button class="premium-cta" style="background:#f59e0b; color:white;" onclick="initiatePremiumCheckout('T', <?= $premium_trimestral; ?>)" data-period="T">
                    Assinar agora
                </button>
            </div>
        </div>
        
        <!-- SEMESTRAL -->
        <div class="premium-card badge-best" style="border-width:3px;">
            <div class="premium-card-header" style="background:linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); position:relative;">
                <div style="position:absolute; top:-15px; right:20px; font-size:2.5rem;"></div>
                <div class="premium-card-badge best" style="background:#fca5a5; color:#7f1d1d; font-size:1rem; padding:0.75rem 1.5rem; display:inline-block;">
                    🏆 MELHOR VALOR
                </div>
                <div class="premium-card-title" style="color:#dc2626; margin-top:1rem;">Semestral</div>
                <div class="premium-card-price" style="color:#dc2626;">R$ <?= number_format($premium_semestral, 2, ',', '.'); ?></div>
                <div class="premium-card-period">6 meses de acesso</div>
                <small style="display:block; margin-top:0.5rem; color:#7f1d1d;">
                    Economia: R$ <?= number_format(($premium_mensal * 6) - $premium_semestral, 2, ',', '.'); ?> (-<?= $desconto_semestral_pct; ?>%)
                </small>
            </div>
            <div class="premium-card-body">
                <ul class="premium-benefits">
                    <li>Destaque na index e nas buscas</li>
                    <li>Loja pessoal do vendedor</li>
                    <li>Salvar Busca + alertas por e-mail</li>
                    <li>Badge de Verificado</li>
                    <li>Métricas de visitas e ações</li>
                    <li>1 vídeo por anúncio</li>
                </ul>
            </div>
            <div class="premium-card-footer">
                <button class="premium-cta" style="background:#dc2626; color:white;" onclick="initiatePremiumCheckout('S', <?= $premium_semestral; ?>)" data-period="S">
                    Assinar agora
                </button>
            </div>
        </div>
        
    </div>
    
    <!-- Benefícios Detalhados -->
    <section class="premium-benefits-section">
        <h2 style="margin-top:0; color:#166534;">
            🎁 Benefícios do Premium
        </h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem; margin-top:1.5rem;">
            <div>
                <h4 style="color:#166534; margin-top:0;">📊 Destaque nas Buscas</h4>
                <p style="color:#4b5563; margin:0;">Seus anúncios aparecem com prioridade na página inicial e nos resultados de busca, aumentando a visibilidade.</p>
            </div>
            <div>
                <h4 style="color:#166534; margin-top:0;">🎥 Vídeo por Anúncio</h4>
                <p style="color:#4b5563; margin:0;">Adicione 1 vídeo a cada um de seus anúncios para mostrar mais detalhes do veículo.</p>
            </div>
            <div>
                <h4 style="color:#166534; margin-top:0;">🏪 Loja Pessoal</h4>
                <p style="color:#4b5563; margin:0;">Tenha sua própria loja pública onde clientes podem ver todos os seus anúncios num único lugar.</p>
            </div>
            <div>
                <h4 style="color:#166534; margin-top:0;">🔔 Alertas de Busca</h4>
                <p style="color:#4b5563; margin:0;">Salve suas buscas favoritas e receba alertas por e-mail sobre novos anúncios que correspondem aos seus critérios.</p>
            </div>
            <div>
                <h4 style="color:#166534; margin-top:0;">✅ Badge Verificado</h4>
                <p style="color:#4b5563; margin:0;">Envie documentos de verificação e ganhe um selo de confiança que destaca sua credibilidade.</p>
            </div>
            <div>
                <h4 style="color:#166534; margin-top:0;">📈 Métricas Completas</h4>
                <p style="color:#4b5563; margin:0;">Acompanhe visitas à sua loja, cliques em WhatsApp, ligações e rotas consultadas em seus anúncios.</p>
            </div>
        </div>
    </section>
    
    <!-- FAQ -->
    <section style="margin:2rem 0;">
        <h2>Perguntas Frequentes</h2>
        
        <div class="premium-faq" onclick="this.classList.toggle('open')">
            <div class="premium-faq-question">
                O Premium substitui moedas?
                <span style="font-size:1.5rem;">+</span>
            </div>
            <div class="premium-faq-answer">
                <p>Não. Premium e moedas são complementares. Moedas servem para publicar ou renovar anúncios, enquanto Premium aumenta sua visibilidade e fornece recursos extras como vídeo, loja e métricas.</p>
            </div>
        </div>
        
        <div class="premium-faq" onclick="this.classList.toggle('open')">
            <div class="premium-faq-question">
                Posso cancelar a qualquer momento?
                <span style="font-size:1.5rem;">+</span>
            </div>
            <div class="premium-faq-answer">
                <p>Sim. Você pode cancelar sua assinatura a qualquer momento na página "Minha Assinatura". Os benefícios continuam válidos até o final do período já pago.</p>
            </div>
        </div>
        
        <div class="premium-faq" onclick="this.classList.toggle('open')">
            <div class="premium-faq-question">
                Como funciona o Badge Verificado?
                <span style="font-size:1.5rem;">+</span>
            </div>
            <div class="premium-faq-answer">
                <p>Ao ativar Premium, você poderá fazer upload de uma foto ou PDF do seu documento. Nossa equipe valida e, após aprovação, o selo de verificado aparecerá nos seus anúncios, aumentando a confiança dos compradores.</p>
            </div>
        </div>
        
        <div class="premium-faq" onclick="this.classList.toggle('open')">
            <div class="premium-faq-question">
                Qual é a política de reembolso?
                <span style="font-size:1.5rem;">+</span>
            </div>
            <div class="premium-faq-answer">
                <p>Não oferecemos reembolsos de assinaturas Premium. Você pode cancelar a qualquer momento, e o acesso será mantido até o final do período pago. Em caso de problemas técnicos, entre em contato com nosso suporte.</p>
            </div>
        </div>
        
    </section>
    
</section>

<script>
async function initiatePremiumCheckout(period, price) {
    const btn = event.target;
    btn.disabled = true;
    btn.classList.add('loading');
    btn.textContent = 'Processando...';
    
    try {
        // Criar preferência no Mercado Pago
        const response = await fetch('/api/premium/criar_preferencia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'period=' + encodeURIComponent(period) + '&price=' + encodeURIComponent(price)
        });
        
        if (!response.ok) {
            throw new Error('Erro ao criar preferência');
        }
        
        const data = await response.json();
        
        if (data.success && data.init_point) {
            // Redirecionar para Mercado Pago
            window.location.href = data.init_point;
        } else {
            alert('Erro: ' + (data.error || 'Não foi possível processar sua assinatura'));
            btn.disabled = false;
            btn.classList.remove('loading');
            btn.textContent = 'Assinar agora';
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao processar assinatura. Tente novamente.');
        btn.disabled = false;
        btn.classList.remove('loading');
        btn.textContent = 'Assinar agora';
    }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
