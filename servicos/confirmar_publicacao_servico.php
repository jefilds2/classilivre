<?php
// servicos/confirmar_publicacao_servico.php - Confirma publicação gratuita de serviço

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json');
require_post();

if (!validate_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

// Validar se usuário está bloqueado
if ($user['status'] === 'blocked') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sua conta está bloqueada. Você não pode publicar serviços.']);
    exit;
}

$serviceId = (int)($_POST['service_id'] ?? 0);
$renewalType = (string)($_POST['type'] ?? 'new'); // 'new' ou 'renewal'

if ($serviceId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

// Verificar se serviço pertence ao usuário
// Pode estar em pending_payment (gratuito) ou em pending_payment com payment_status=paid (pago)
$stmt = db()->prepare('SELECT * FROM user_service_listings WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $serviceId, ':user_id' => (int)$user['id']]);
$service = $stmt->fetch();

if (!$service) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Serviço não encontrado']);
    exit;
}

// Validar se pode ser publicado
// Caso 1: Gratuito (services_free_for_all=ON) - já vem com status=active
// Caso 2: Pago (services_free_for_all=OFF) - vem com status=pending_payment e payment_status=pending
//         Após webhook: status=pending_payment e payment_status=paid (aguardando ativação)

$servicesFreeForAll = (bool)get_setting('services_free_for_all', false);

if ($renewalType === 'renewal') {
    // ✅ RENOVAÇÃO: Pode estar 'active' (com <= 7 dias) ou 'expired' (até 15 dias)
    if (!in_array($service['status'], ['active', 'expired'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Este serviço não pode ser renovado. Status: ' . $service['status']]);
        exit;
    }
    if (!should_show_service_renewal_button($service)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Este serviço não está elegível para renovação']);
        exit;
    }
    
    // Se modo GRATUITO: renovar sem pagamento
    if ($servicesFreeForAll && $service['payment_status'] !== 'paid') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Serviço deve estar com pagamento pago para renovação gratuita']);
        exit;
    }
} else {
    // ❌ NOVO: Validação original
    if ($service['status'] === 'active' && $service['payment_status'] === 'paid') {
        // Já está ativo
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Este serviço já está publicado']);
        exit;
    }
    
    // Validar estado conforme modo (gratuito/pago)
    if ($servicesFreeForAll) {
        // Modo gratuito: deve estar com status=active
        if ($service['status'] !== 'active') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Serviço não está pronto para publicação']);
            exit;
        }
    } else {
        // Modo pago: deve estar com payment_status=paid para ativar
        if ($service['payment_status'] !== 'paid') {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'Pagamento ainda não foi confirmado. Verifique seu email ou tente novamente em alguns instantes.'
            ]);
            exit;
        }
        
        if ($service['status'] !== 'pending_payment') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Serviço não está em estado válido para publicação']);
            exit;
        }
    }
}

try {
    // Iniciar transação
    db()->beginTransaction();

    // ✅ CALCULAR expires_at DIFERENTE PARA NOVO vs RENOVAÇÃO
    $now = now();
    
    if ($renewalType === 'renewal') {
        // RENOVAÇÃO: Adicionar 30 dias aos dias restantes que o serviço ainda tem
        $daysRemaining = get_service_days_remaining($service['expires_at']);
        
        if ($daysRemaining > 0) {
            // Ainda não expirou: somar 30 dias aos dias restantes
            $expiresAt = date('Y-m-d H:i:s', time() + ((30 + $daysRemaining) * 86400));
            $auditNote = "Renovação: +30 dias aos {$daysRemaining} dias restantes = " . ($daysRemaining + 30) . " dias totais";
        } else {
            // Já expirou: somar 30 dias a partir de agora
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
            $auditNote = "Renovação após expiração: +30 dias a partir de agora";
        }
    } else {
        // NOVO: 30 dias a partir de agora
        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 86400));
        $auditNote = null;
    }
    
    $update = db()->prepare('UPDATE user_service_listings SET
        status = "active",
        payment_status = "paid",
        published_at = :published_at,
        expires_at = :expires_at,
        updated_at = :updated_at
        WHERE id = :id');
    
    $update->execute([
        ':published_at' => $now,
        ':expires_at' => $expiresAt,
        ':updated_at' => $now,
        ':id' => $serviceId
    ]);

    // Commit
    db()->commit();

    // Determinar mensagem conforme modo e tipo
    if ($renewalType === 'renewal') {
        if ($servicesFreeForAll) {
            $message = 'Serviço renovado gratuitamente!';
            $auditAction = 'service_free_renewed';
        } else {
            $message = 'Pagamento confirmado! Seu serviço foi renovado por mais 30 dias.';
            $auditAction = 'service_paid_renewed';
        }
    } else {
        if ($servicesFreeForAll) {
            $message = 'Serviço publicado gratuitamente!';
            $auditAction = 'service_free_published';
        } else {
            $message = 'Pagamento confirmado! Seu serviço foi publicado com sucesso.';
            $auditAction = 'service_paid_published';
        }
    }

    record_audit((int)$user['id'], $auditAction, 'user_service_listings', $serviceId, [
        'name' => $service['name'],
        'expires_at' => $expiresAt,
        'payment_status' => $service['payment_status'],
        'mode' => $servicesFreeForAll ? 'free' : 'paid',
        'type' => $renewalType,
        'note' => $auditNote
    ]);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'redirect' => '/anuncios/meus.php?tab=services'
    ]);

} catch (Throwable $e) {
    db()->rollBack();
    error_log('Service publish error: ' . $e->getMessage() . ' | Stack: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao publicar serviço: ' . $e->getMessage()]);
}
?>
