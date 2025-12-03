<?php
/**
 * servicos/pagamento_confirmando.php
 * Página exibida enquanto aguarda confirmação do pagamento
 * Faz polling do status e redireciona quando confirmado
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_login();
ensure_user_not_blocked();

$user = current_user();
$serviceId = (int)($_GET['serviceId'] ?? 0);
$paymentStatus = $_GET['payment_status'] ?? null;
$transactionType = $_GET['type'] ?? 'new'; // 'new' ou 'renewal'

if ($serviceId <= 0) {
    exit('ID de serviço inválido');
}

// Verificar que serviço pertence ao usuário
$stmt = db()->prepare('SELECT id, name, status, payment_status FROM user_service_listings WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $serviceId, ':user_id' => (int)$user['id']]);
$service = $stmt->fetch();

if (!$service) {
    exit('Serviço não encontrado');
}

$pageTitle = 'Confirmando Pagamento - Classi Livre';
include __DIR__ . '/../partials/header.php';
?>

<section style="max-width:600px; margin:4rem auto; padding:2rem; text-align:center;">
    <div style="background:#ecfdf5; border:2px solid #86efac; border-radius:1rem; padding:2rem;">
        <div style="font-size:3rem; color:#16a34a; margin-bottom:1rem;">
            <i class="fas fa-hourglass-half fa-spin"></i>
        </div>
        <h1 style="color:#166534; margin:1rem 0;">Confirmando seu pagamento...</h1>
        <p style="color:#4b5563; font-size:1.05rem; margin:1rem 0;">
            <strong><?= e($service['name']); ?></strong>
        </p>
        <p style="color:#6b7280; font-size:0.95rem; margin:2rem 0;">
            Seu pagamento está sendo processado. Você será redirecionado em breve.
        </p>
        <div style="background:rgba(255,255,255,0.5); border-radius:0.5rem; padding:1rem; margin:2rem 0;">
            <small style="color:#6b7280;">
                <i class="fas fa-info-circle"></i> Se você não for redirecionado automaticamente, não feche esta página.
            </small>
        </div>
        <p style="font-size:0.85rem; color:#9ca3af; margin-top:2rem;">
            Status: <strong id="status-text">Aguardando confirmação...</strong>
        </p>
    </div>
</section>

<script>
    const serviceId = <?= (int)$serviceId; ?>;
    const transactionType = '<?= e($transactionType); ?>';
    const csrfToken = '<?= e(csrf_token()); ?>';
    let pollCount = 0;
    const maxPolls = 120; // 2 minutos com polling de 1 segundo

    function checkPaymentStatus() {
        pollCount++;
        if (pollCount > maxPolls) {
            document.getElementById('status-text').textContent = 'Timeout na verificação';
            alert('Verificação de pagamento expirou. Verifique seu email ou entre em contato com o suporte.');
            window.location.href = '/anuncios/meus.php?tab=services';
            return;
        }

        fetch(`/api/check_service_payment_status.php?serviceId=${serviceId}&type=${transactionType}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Erro ao verificar status:', data.error);
                    setTimeout(checkPaymentStatus, 1000);
                    return;
                }

                console.log('Status atual:', data);
                document.getElementById('status-text').textContent = 
                    data.is_paid ? 'Pagamento confirmado!' : 'Aguardando confirmação...';

                if (data.is_paid && data.is_active) {
                    // Pagamento confirmado e serviço ativo
                    document.querySelector('section').innerHTML = `
                        <div style="background:#ecfdf5; border:2px solid #86efac; border-radius:1rem; padding:2rem; text-align:center;">
                            <div style="font-size:3rem; color:#16a34a; margin-bottom:1rem;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h1 style="color:#166534; margin:1rem 0;">Pagamento Confirmado!</h1>
                            <p style="color:#4b5563; font-size:1.05rem; margin:1rem 0;">
                                <strong>${document.querySelector('h1').nextElementSibling.textContent.trim()}</strong>
                            </p>
                            <p style="color:#6b7280; margin:2rem 0;">
                                Seu serviço foi publicado com sucesso e está visível na vitrine!
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
                    `;
                    return;
                }

                if (data.is_paid) {
                    // Pagamento confirmado mas ainda precisa ativar via formulário
                    publicarServico();
                    return;
                }

                // Continuar polling
                setTimeout(checkPaymentStatus, 1000);
            })
            .catch(error => {
                console.error('Erro ao verificar status:', error);
                setTimeout(checkPaymentStatus, 1000);
            });
    }

    function publicarServico() {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('service_id', serviceId);
        formData.append('type', transactionType);

        fetch('/servicos/confirmar_publicacao_servico.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar a página com sucesso
                document.querySelector('section').innerHTML = `
                    <div style="background:#ecfdf5; border:2px solid #86efac; border-radius:1rem; padding:2rem; text-align:center;">
                        <div style="font-size:3rem; color:#16a34a; margin-bottom:1rem;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h1 style="color:#166534; margin:1rem 0;">Pagamento Confirmado!</h1>
                        <p style="color:#6b7280; margin:2rem 0;">
                            Seu serviço foi publicado com sucesso e está visível na vitrine!
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
                `;
                setTimeout(() => {
                    window.location.href = '/anuncios/meus.php?tab=services&success=service_published';
                }, 2000);
            } else {
                console.error('Erro ao publicar:', data.error);
                document.getElementById('status-text').textContent = data.error || 'Erro ao publicar serviço';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            setTimeout(checkPaymentStatus, 2000);
        });
    }

    // Iniciar polling quando página carregar
    document.addEventListener('DOMContentLoaded', checkPaymentStatus);
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
