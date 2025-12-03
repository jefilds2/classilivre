<?php
// servicos/pagamento.php - Página para finalizar pagamento do serviço

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/service_renewal_helpers.php';

require_login();
ensure_user_not_blocked();
ensure_verified_email();

$user = current_user();
$serviceId = (int)($_GET['serviceId'] ?? $_GET['service_id'] ?? 0);
$renewalType = (string)($_GET['type'] ?? 'new'); // 'new' ou 'renewal'

if ($serviceId <= 0) {
    http_response_code(400);
    exit('ID do serviço inválido.');
}

// Buscar serviço
$stmt = db()->prepare('SELECT * FROM user_service_listings WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $serviceId, ':user_id' => (int)$user['id']]);
$service = $stmt->fetch();

if (!$service) {
    http_response_code(404);
    exit('Serviço não encontrado.');
}

// ✅ LÓGICA DE VALIDAÇÃO: novo vs renovação
if ($renewalType === 'renewal') {
    $status = (string)($service['status'] ?? '');
    $paymentStatus = (string)($service['payment_status'] ?? '');
    $isPendingPayment = ($status === 'pending_payment');

    if (!in_array($status, ['active', 'expired', 'pending_payment'], true)) {
        http_response_code(400);
        exit('Este serviço não pode ser renovado. Status: ' . e($status));
    }

    if (!$isPendingPayment && function_exists('should_show_service_renewal_button') && !should_show_service_renewal_button($service)) {
        http_response_code(400);
        exit('Este serviço não está elegível para renovação.');
    }

    $servicesFreeForAll = setting_value_enabled(get_setting('services_free_for_all', '0'));
    if ($servicesFreeForAll && !$isPendingPayment) {
        try {
            db()->beginTransaction();

            $daysRemaining = get_service_days_remaining($service['expires_at']);
            if ($daysRemaining > 0) {
                $newExpiresAt = date('Y-m-d H:i:s', time() + ((30 + $daysRemaining) * 86400));
                $auditNote = "Renovação gratuita automática: +30 dias aos {$daysRemaining} dias restantes = " . ($daysRemaining + 30) . " dias totais";
            } else {
                $newExpiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
                $auditNote = "Renovação gratuita automática após expiração: +30 dias a partir de agora";
            }

            $updateStmt = db()->prepare('UPDATE user_service_listings SET
                status = "active",
                payment_status = "paid",
                published_at = :published_at,
                expires_at = :expires_at,
                updated_at = :updated_at
                WHERE id = :id AND user_id = :user_id');

            $nowTs = now();
            $updateStmt->execute([
                ':published_at' => $nowTs,
                ':expires_at' => $newExpiresAt,
                ':updated_at' => $nowTs,
                ':id' => $serviceId,
                ':user_id' => (int)$user['id'],
            ]);

            record_audit((int)$user['id'], 'service_free_renewal_auto', 'user_service_listings', $serviceId, [
                'name' => $service['name'],
                'reason' => 'Renovação automática gratuita (services_free_for_all=ON)',
                'expires_at' => $newExpiresAt,
                'note' => $auditNote,
            ]);

            db()->commit();

            include __DIR__ . '/../partials/header.php';
            ?>
            <section style="max-width:600px; margin:4rem auto; padding:2rem; text-align:center;">
                <div style="background:#ecfdf5; border:2px solid #86efac; border-radius:1rem; padding:2rem;">
                    <div style="font-size:3rem; color:#16a34a; margin-bottom:1rem;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 style="color:#166534; margin:1rem 0;">Serviço Renovado com Sucesso!</h1>
                    <p style="color:#4b5563; font-size:1.05rem; margin:1rem 0;">
                        <strong><?= e($service['name']); ?></strong>
                    </p>
                    <p style="color:#6b7280; margin:2rem 0;">
                        Seu serviço foi renovado automaticamente e está visível na vitrine por mais 30 dias!
                    </p>
                    <a href="/anuncios/meus.php?tab=services" class="button" style="
                        background:#16a34a; 
                        color:#fff; 
                        padding:1rem 2rem; 
                        text-decoration:none; 
                        border-radius:0.5rem; 
                        display:inline-block;
                        margin-top:1rem;
                    ">
                        <i class="fas fa-arrow-left"></i> Voltar aos Meus Serviços
                    </a>
                </div>
            </section>
            <?php
            include __DIR__ . '/../partials/footer.php';
            exit;
        } catch (Throwable $e) {
            db()->rollBack();
            error_log('[service_renewal_free_error] ' . $e->getMessage());
            exit('Erro ao renovar serviço: ' . e($e->getMessage()));
        }
    }

    if (!$isPendingPayment) {
        $updateStmt = db()->prepare('UPDATE user_service_listings SET status = "pending_payment", payment_status = "pending", updated_at = NOW() WHERE id = :id AND user_id = :user_id');
        $updateStmt->execute([
            ':id' => $serviceId,
            ':user_id' => (int)$user['id'],
        ]);

        $stmt = db()->prepare('SELECT * FROM user_service_listings WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':id' => $serviceId, ':user_id' => (int)$user['id']]);
        $service = $stmt->fetch();

        $status = (string)($service['status'] ?? '');
        $paymentStatus = (string)($service['payment_status'] ?? '');
        $isPendingPayment = ($status === 'pending_payment');
    }

    if (!$isPendingPayment) {
        http_response_code(400);
        exit('Falha ao preparar a renovação paga. Tente novamente.');
    }

    if (!in_array($paymentStatus, ['pending', 'pending_gateway', 'failed'], true)) {
        http_response_code(400);
        exit('Este serviço não está aguardando pagamento.');
    }
} else {
    // NOVO: deve estar em pending_payment
    if ($service['status'] === 'active' && $service['payment_status'] === 'paid') {
        http_response_code(409);
        exit('Este serviço já foi publicado.');
    }
    if ($service['payment_status'] !== 'pending' && $service['payment_status'] !== 'failed') {
        http_response_code(400);
        exit('Este serviço não está aguardando pagamento.');
    }
}

// Obter preço do serviço
$servicePrice = (float)get_setting('service_price', '15.00');

$pageTitle = $renewalType === 'renewal' ? 'Renovação do Serviço - Classi Livre' : 'Pagamento do Serviço - Classi Livre';

include __DIR__ . '/../partials/header.php';
?>

<section class="form-card" style="max-width:600px;">
    <h1><i class="fas fa-credit-card"></i> <?= $renewalType === 'renewal' ? 'Renovação do Serviço' : 'Pagamento do Serviço'; ?></h1>
    <p class="lead"><?= $renewalType === 'renewal' ? 'Renove seu serviço para manter ativo por mais 30 dias.' : 'Finalize o pagamento para publicar seu serviço na vitrine.'; ?></p>

    <!-- Preview do Serviço -->
    <div class="service-preview" style="border:1px solid #e5e7eb; padding:1.5rem; margin:1.5rem 0; border-radius:0.75rem; background:#f9fafb;">
        <h3 style="margin-top:0; color:#1f2937;">Dados do Serviço</h3>
        <div style="display:grid; gap:1rem;">
            <div>
                <strong style="color:#374151;">Serviço:</strong><br>
                <span style="color:#6b7280; font-size:1.05rem;"><?= e($service['name']); ?></span>
            </div>
            <div>
                <strong style="color:#374151;">Categoria:</strong><br>
                <span style="color:#6b7280;">
                    <?= e($service['category']); ?>
                    <?php if ($service['custom_category']): ?>
                        - <?= e($service['custom_category']); ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($service['address_city']): ?>
            <div>
                <strong style="color:#374151;">Localização:</strong><br>
                <span style="color:#6b7280;">
                    <?= e($service['address_city']); ?>/<?= e($service['address_state']); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detalhes do Pagamento -->
    <div class="payment-details" style="background:#f0f9ff; border:1px solid #7dd3fc; padding:1.5rem; border-radius:0.75rem; margin:1.5rem 0;">
        <h3 style="margin-top:0; color:#0369a1;">Detalhes da Publicação</h3>
        <div style="display:grid; gap:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #cffafe; padding-bottom:0.75rem;">
                <span style="color:#374151;">Valor da Publicação:</span>
                <strong style="font-size:1.3rem; color:#0369a1;">R$ <?= number_format($servicePrice, 2, ',', '.'); ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:#374151;">Duração:</span>
                <span style="color:#6b7280;"><i class="fas fa-calendar-days"></i> 30 dias</span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:#374151;">Pagamento:</span>
                <span style="color:#6b7280;"><i class="fab fa-mercadopago"></i> Mercado Pago</span>
            </div>
            <p style="background:#fef3c7; border:1px solid #fcd34d; border-radius:0.5rem; padding:0.75rem; margin:0; font-size:0.95rem; color:#78350f;">
                <i class="fas fa-info-circle"></i> Após o pagamento, seu serviço será publicado automaticamente.
            </p>
        </div>
    </div>

    <!-- Ações -->
    <div class="payment-actions" style="display:grid; gap:1rem; margin:2rem 0;">
        <button type="button" id="payButton" class="button" style="background:#009ee3; color:#fff; padding:1rem 2rem; font-size:1.1rem; border:none; border-radius:0.5rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.5rem;">
            <i class="fab fa-mercadopago"></i> Pagar R$ <?= number_format($servicePrice, 2, ',', '.'); ?> com Mercado Pago
        </button>
        <a href="/anuncios/meus.php?tab=services" class="button" style="background:#6b7280; color:#fff; padding:1rem 2rem; text-align:center; text-decoration:none; border-radius:0.5rem; cursor:pointer;">
            <i class="fas fa-arrow-left"></i> Cancelar
        </a>
    </div>

    <!-- Aviso de Segurança -->
    <div style="background:#ecfdf5; border:1px solid #86efac; border-radius:0.5rem; padding:1rem; text-align:center; font-size:0.9rem; color:#166534;">
        <i class="fas fa-shield-alt"></i> Ambiente seguro. Seus dados são protegidos.
    </div>
</section>

<!-- Mercado Pago SDK -->
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    document.getElementById('payButton').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

        // Chamar API para criar preferência de pagamento
        fetch('/api/mercado_pago_service_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?= e(csrf_token()); ?>',
                serviceId: <?= $serviceId; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.init_point) {
                // Redirecionar para o checkout do Mercado Pago
                window.location.href = data.init_point;
            } else {
                this.disabled = false;
                this.innerHTML = '<i class="fab fa-mercadopago"></i> Pagar R$ <?= number_format($servicePrice, 2, ',', '.'); ?> com Mercado Pago';
                alert('Erro ao processar pagamento: ' + (data.error || 'Tente novamente'));
                console.error('Erro na resposta:', data);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            this.disabled = false;
            this.innerHTML = '<i class="fab fa-mercadopago"></i> Pagar R$ <?= number_format($servicePrice, 2, ',', '.'); ?> com Mercado Pago';
            alert('Erro ao processar pagamento. Tente novamente.');
        });
    });
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
