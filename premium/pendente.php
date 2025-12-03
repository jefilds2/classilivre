<?php
/**
 * premium/pendente.php
 * Página para quando o pagamento está pendente
 * Verifica periodicamente o status do pagamento e redireciona se aprovado
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_login();
ensure_user_not_blocked();

$user = current_user();
$pageTitle = 'Pagamento Pendente — Classi Livre Autos';

// ============================================
// VERIFICAR STATUS DO PAGAMENTO
// ============================================

$payment_id = $_GET['payment_id'] ?? null;
$collection_id = $_GET['collection_id'] ?? null;
$payment_confirmed = false;

if ($payment_id || $collection_id) {
    try {
        $mp_config = require_once __DIR__ . '/../config/mercadopago.php';
        $mode = $mp_config['mode'] ?? 'production';
        $credentials = $mp_config[$mode] ?? [];
        $access_token = $credentials['access_token'] ?? '';
        
        if (!empty($access_token)) {
            $check_id = $payment_id ?: $collection_id;
            
            // Buscar status do pagamento na API do Mercado Pago
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $check_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200) {
                $payment = json_decode($response, true);
                $status = $payment['status'] ?? null;
                
                // Se pagamento foi aprovado, redirecionar para sucesso
                if ($status === 'approved') {
                    $payment_confirmed = true;
                    header('Location: /premium/sucesso.php', true, 302);
                    exit;
                }
            }
        }
    } catch (Exception $e) {
        error_log('[premium/pendente] Erro ao verificar status: ' . $e->getMessage());
    }
}

include __DIR__ . '/../partials/header.php';
?>

<style>
.pending-hero {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: center;
}

.pending-hero h1 {
    font-size: 2.5rem;
    margin: 0;
}

.pending-icon {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    display: block;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.pending-container {
    max-width: 700px;
    margin: 2rem auto;
}

.info-box {
    background: #fef3c7;
    border: 2px solid #f59e0b;
    border-radius: 12px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.info-box h3 {
    color: #92400e;
    margin-top: 0;
    font-size: 1.1rem;
}

.status-timeline {
    background: #f9fafb;
    border-radius: 12px;
    padding: 2rem;
    margin: 2rem 0;
}

.timeline-step {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    align-items: flex-start;
}

.timeline-step:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    width: 24px;
    height: 24px;
    background: #f59e0b;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.85rem;
}

.timeline-content {
    flex: 1;
}

.timeline-content strong {
    color: #1f2937;
    display: block;
    margin-bottom: 0.25rem;
}

.timeline-content p {
    margin: 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.success-indicators {
    background: #f0fdf4;
    border-left: 4px solid #16a34a;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.success-indicators h3 {
    color: #166534;
    margin-top: 0;
}

.success-indicators ul {
    margin: 1rem 0;
    padding-left: 1.5rem;
    color: #4b5563;
}

.success-indicators li {
    margin: 0.5rem 0;
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

.info-text {
    color: #6b7280;
    font-size: 0.95rem;
    line-height: 1.6;
}
</style>

<div class="pending-container">
    
    <!-- Hero Section -->
    <div class="pending-hero">
        <span class="pending-icon">⏳</span>
        <h1>Pagamento Pendente</h1>
        <p style="margin: 0.5rem 0 0 0; font-size: 1.1rem; opacity: 0.95;">
            Estamos processando sua transação
        </p>
    </div>

    <!-- Timeline de Status -->
    <div class="status-timeline">
        <h3 style="margin-top: 0; color: #1f2937;">📊 Status da sua transação:</h3>
        
        <div class="timeline-step">
            <div class="timeline-dot">✓</div>
            <div class="timeline-content">
                <strong>Pagamento enviado</strong>
                <p>Sua solicitação de pagamento foi recebida com sucesso</p>
            </div>
        </div>
        
        <div class="timeline-step">
            <div class="timeline-dot">⏳</div>
            <div class="timeline-content">
                <strong>Processamento</strong>
                <p>Mercado Pago está processando sua transação neste momento</p>
            </div>
        </div>
        
        <div class="timeline-step">
            <div class="timeline-dot">?</div>
            <div class="timeline-content">
                <strong>Confirmação</strong>
                <p>Em breve você receberá a confirmação por e-mail</p>
            </div>
        </div>
    </div>

    <!-- Informações -->
    <div class="info-box">
        <h3>⏱️ Tempo de espera</h3>
        <p class="info-text">
            Geralmente o processamento leva alguns minutos, mas pode chegar até 2 horas dependendo do seu banco ou do método de pagamento usado.
        </p>
    </div>

    <!-- Próximos Passos -->
    <div class="success-indicators">
        <h3>✅ Quando sua transação for aprovada:</h3>
        <ul>
            <li>Você receberá um <strong>e-mail de confirmação</strong></li>
            <li>Seu acesso Premium será <strong>ativado automaticamente</strong></li>
            <li>Você poderá acessar <strong>minha-assinatura.php</strong> para gerenciar</li>
            <li>Todos os <strong>benefícios ficarão disponíveis</strong></li>
        </ul>
    </div>

    <!-- Botões de Ação -->
    <div class="action-buttons">
        <button class="btn-primary" onclick="verificarStatusPagamento()" id="btn-verificar">
            🔄 Verificar Status Agora
        </button>
        <a href="/" class="btn-secondary">
            ← Voltar para Início
        </a>
    </div>

    <!-- Aviso de Suporte -->
    <div style="background: #f9fafb; border-radius: 8px; padding: 1.5rem; margin-top: 2rem; border: 1px solid #e5e7eb;">
        <p class="info-text" style="margin: 0; text-align: center;">
            <strong>⏱️ Atualizando automaticamente a cada 10 segundos...</strong><br>
            Você será redirecionado automaticamente quando o pagamento for confirmado.</p>
    </div>

</div>

<script>
// Auto-refresh a cada 10 segundos
let tentativas = 0;
const maxTentativas = 30; // 5 minutos

function verificarStatusPagamento() {
    const btnVerificar = document.getElementById('btn-verificar');
    if (btnVerificar) {
        btnVerificar.disabled = true;
        btnVerificar.textContent = '⏳ Verificando...';
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const paymentId = urlParams.get('payment_id');
    const collectionId = urlParams.get('collection_id');
    
    if (paymentId || collectionId) {
        // Recarregar página para verificar o status
        window.location.reload();
    }
}

// Verificar automaticamente a cada 10 segundos
setInterval(function() {
    if (tentativas < maxTentativas) {
        tentativas++;
        verificarStatusPagamento();
    }
}, 10000);

// Tentar na primeira carga após 2 segundos
setTimeout(function() {
    verificarStatusPagamento();
}, 2000);
</script>
            Entre em contato com nosso suporte através do botão "Conta" no menu
        </p>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
