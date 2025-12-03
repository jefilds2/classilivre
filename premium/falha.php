<?php
/**
 * premium/falha.php
 * Página quando o pagamento é rejeitado
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_login();
ensure_user_not_blocked();

$pageTitle = 'Pagamento Recusado — Classi Livre Autos';

include __DIR__ . '/../partials/header.php';
?>

<style>
.failure-hero {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: center;
}

.failure-hero h1 {
    font-size: 2.5rem;
    margin: 0;
}

.failure-icon {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    display: block;
}

.failure-container {
    max-width: 700px;
    margin: 2rem auto;
}

.error-details {
    background: #fef2f2;
    border: 2px solid #fca5a5;
    border-radius: 12px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.error-details h3 {
    color: #dc2626;
    margin-top: 0;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.error-details ul {
    margin: 1rem 0;
    padding-left: 1.5rem;
    color: #7f1d1d;
}

.error-details li {
    margin: 0.5rem 0;
}

.solution-box {
    background: #f0fdf4;
    border-left: 4px solid #16a34a;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.solution-box h3 {
    color: #166534;
    margin-top: 0;
    font-size: 1.1rem;
}

.solution-box ol {
    margin: 1rem 0;
    padding-left: 1.5rem;
    color: #4b5563;
}

.solution-box li {
    margin: 0.75rem 0;
    line-height: 1.6;
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
    line-height: 1.8;
    font-size: 0.95rem;
    margin: 1rem 0;
}
</style>

<div class="failure-container">
    
    <!-- Hero Section -->
    <div class="failure-hero">
        <span class="failure-icon">❌</span>
        <h1>Pagamento Recusado</h1>
        <p style="margin: 0.5rem 0 0 0; font-size: 1.1rem; opacity: 0.95;">
            Infelizmente, sua transação não pôde ser processada
        </p>
    </div>

    <!-- Motivos -->
    <div class="error-details">
        <h3>❓ Possíveis motivos da recusa:</h3>
        <ul>
            <li><strong>Saldo insuficiente</strong> no cartão de crédito</li>
            <li><strong>Cartão expirado ou bloqueado</strong> pelo banco</li>
            <li><strong>Limite de transações</strong> atingido</li>
            <li><strong>Dados incorretos</strong> informados</li>
            <li><strong>Falha temporária</strong> na operadora do cartão</li>
        </ul>
    </div>

    <!-- Soluções -->
    <div class="solution-box">
        <h3>✅ Como resolver:</h3>
        <ol>
            <li>
                <strong>Verifique os dados do seu cartão</strong>
                <br>
                <span class="info-text">Certifique-se que número, validade e CVV estão corretos</span>
            </li>
            <li>
                <strong>Tente outro método de pagamento</strong>
                <br>
                <span class="info-text">Você pode usar PIX, boleto ou outro cartão</span>
            </li>
            <li>
                <strong>Contate seu banco</strong>
                <br>
                <span class="info-text">Às vezes o banco bloqueia a transação por segurança</span>
            </li>
            <li>
                <strong>Tente novamente em alguns minutos</strong>
                <br>
                <span class="info-text">Pode ser uma falha temporária na operadora</span>
            </li>
        </ol>
    </div>

    <!-- Botões de Ação -->
    <div class="action-buttons">
        <a href="/premium/status.php" class="btn-primary">
            🔄 Tentar Novamente
        </a>
        <a href="/" class="btn-secondary">
            ← Voltar para Início
        </a>
    </div>

    <!-- Informação Adicional -->
    <div style="background: #f9fafb; border-radius: 8px; padding: 1.5rem; margin-top: 2rem; text-align: center;">
        <p class="info-text">
            <strong>Ainda com dúvidas?</strong><br>
            Entre em contato com nosso suporte através do botão "Conta" no menu principal
        </p>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
