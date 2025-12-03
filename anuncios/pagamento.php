<?php
// anuncios/pagamento.php - Página para finalizar pagamento do anúncio

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/upload_handler.php';

require_login();
ensure_user_not_blocked();
ensure_verified_email();

$user = current_user();
$adId = (int)($_GET['id'] ?? 0);

if ($adId <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

$stmt = db()->prepare('SELECT * FROM ads WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $adId, ':user_id' => (int)$user['id']]);
$ad = $stmt->fetch();

if (!$ad || $ad['status'] !== 'draft') {
    http_response_code(404);
    exit('Anúncio não encontrado ou já processado.');
}

// Verificar se é gratuito
// (removido: sistema de créditos descontinuado)
// TODO: Aplicar desconto de moedas Clivre aqui

$isFree = (int)get_setting('ads_free_for_all', '0') === 1;
$price = 0.00;

if (!$isFree) {
    // TODO: Conectar com sistema de moedas - descontar 1 moeda por anúncio
    // Por enquanto, sem preço fixo em reais, será cobrado via moedas
    $price = 0.00;
}

$pageTitle = 'Pagamento do Anúncio - Site Carros';

include __DIR__ . '/../partials/header.php';
?>

<section class="form-card">
    <h1>Pagamento do Anúncio</h1>
    <p>Revise os detalhes do seu anúncio e finalize o pagamento para enviá-lo à moderação.</p>

    <!-- Preview do Anúncio -->
    <div class="ad-preview" style="border:1px solid #ddd; padding:1rem; margin:1rem 0; border-radius:8px;">
        <h3>Preview do Anúncio</h3>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem;">
            <div>
                <strong>Título:</strong> <?= e($ad['title']); ?><br>
                <strong>Preço:</strong> <?= format_currency((float)$ad['price']); ?><br>
                <strong>Marca/Modelo:</strong> <?= e($ad['brand']); ?> <?= e($ad['model']); ?><br>
                <strong>Ano:</strong> <?= e($ad['manufacture_year']); ?>/<?= e($ad['model_year']); ?><br>
                <strong>Localização:</strong> <?= e($ad['city']); ?>/<?= e($ad['state']); ?>
            </div>
            <div>
                <strong>Descrição:</strong><br>
                <div style="max-height:100px; overflow-y:auto; border:1px solid #eee; padding:0.5rem; border-radius:4px;">
                    <?= nl2br(e($ad['description'])); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalhes do Pagamento -->
    <div class="payment-details" style="background:#f8f9fa; padding:1rem; border-radius:8px; margin:1rem 0;">
        <h3>Detalhes da Publicação</h3>
        <?php if ($isFree): ?>
            <p style="color:#28a745; font-weight:bold;"><i class="fas fa-check-circle"></i> Anúncio GRATUITO!</p>
            <p>Seu anúncio será enviado diretamente para moderação sem custos.</p>
        <?php else: ?>
            <p><strong>Valor:</strong> 1 moeda Clivre</p>
            <p><strong>Duração:</strong> 30 dias de exposição após aprovação</p>
            <p><strong>Pagamento via:</strong> Mercado Pago (seguro e automático)</p>
        <?php endif; ?>
    </div>

    <!-- Ações -->
    <div class="payment-actions" style="text-align:center; margin-top:2rem;">
        <?php if ($isFree): ?>
            <button type="button" id="publishButton" class="button" style="background:#28a745;color:#fff;padding:1rem 2rem;font-size:1.1rem;">
                <i class="fas fa-paper-plane"></i> Publicar Gratuitamente
            </button>
            <p style="margin-top:1rem; font-size:0.9rem; color:#666;">
                Seu anúncio será enviado diretamente para moderação.
            </p>
        <?php else: ?>
            <button type="button" id="payButton" class="button" style="background:#009ee3;color:#fff;padding:1rem 2rem;font-size:1.1rem;">
                <i class="fab fa-mercadopago"></i> Pagar com Mercado Pago
            </button>
            <p style="margin-top:1rem; font-size:0.9rem; color:#666;">
                Após o pagamento, seu anúncio será enviado automaticamente para moderação.
            </p>
        <?php endif; ?>
    </div>

    <!-- Voltar -->
    <div style="text-align:center; margin-top:2rem;">
        <a href="/anuncios/editar.php?id=<?= $adId; ?>" class="button secondary">Editar anúncio</a>
    </div>
</section>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    const mp = new MercadoPago('APP_USR-9c463263-4af7-41f8-8317-fc8ddb576778'); // Chave pública Mercado Pago

    <?php if (!$isFree): ?>
    document.getElementById('payButton').addEventListener('click', function() {
        // Criar preferência de pagamento
        fetch('/api/criar_pagamento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                csrf_token: '<?= csrf_token(); ?>',
                ad_id: '<?= $adId; ?>',
                amount: '<?= $price; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mp.checkout({
                    preference: {
                        id: data.preference_id
                    },
                    autoOpen: true
                });
            } else {
                alert('Erro ao criar pagamento: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar pagamento.');
        });
    });
    <?php else: ?>
    document.getElementById('publishButton').addEventListener('click', function() {
        // Publicar gratuitamente
        fetch('/anuncios/confirmar_publicacao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': '<?= csrf_token(); ?>'
            },
            body: new URLSearchParams({
                csrf_token: '<?= csrf_token(); ?>',
                ad_id: <?= $adId; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = data.redirect;
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao publicar anúncio.');
        });
    });
    <?php endif; ?>
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
