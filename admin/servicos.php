<?php
// admin/servicos.php - Gestão de serviços automotivos cadastrados pelos vendedores.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$mailHelperLoaded = function_exists('mail_enqueue');
if (!$mailHelperLoaded) {
    $mailHelperPaths = [
        __DIR__ . '/../api/helpers_mail.php',
        __DIR__ . '/../../api/helpers_mail.php'
    ];
    foreach ($mailHelperPaths as $helperPath) {
        if (file_exists($helperPath)) {
            require_once $helperPath;
            $mailHelperLoaded = true;
            break;
        }
    }
}

$pageTitle = 'Serviços automotivos - Administração';
$activeNav = 'services';
$pdo = db();

$page = max(1, (int)(input_get('page') ?? 1));
$limit = min(500, max(10, (int)(input_get('limit') ?? 100))); // limit between 10 and 500
$offset = ($page - 1) * $limit;

$query = input_get('q');
$status = input_get('status');
$category = input_get('category');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!validate_csrf($_POST['csrf_token'] ?? null)) {
            flash_add('error', 'Token inválido. Tente novamente.');
            redirect('/admin/servicos.php');
        }

        $action = $_POST['action'] ?? '';
        $serviceId = (int)($_POST['service_id'] ?? 0);

    $serviceStmt = $pdo->prepare('SELECT * FROM user_service_listings WHERE id = :id LIMIT 1');
    $serviceStmt->execute([':id' => $serviceId]);
    $service = $serviceStmt->fetch();

    if (!$service) {
        flash_add('error', 'Serviço não encontrado.');
        redirect('/admin/servicos.php');
    }

    $ownerStmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id LIMIT 1');
    $ownerStmt->execute([':id' => (int)$service['user_id']]);
    $serviceOwner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

    if ($action === 'update') {
        $allowedStatus = ['pending_payment', 'active', 'suspended', 'archived'];
        $allowedPayment = ['pending', 'paid', 'failed', 'refunded'];
        $status = $_POST['status'] ?? '';
        $paymentStatus = $_POST['payment_status'] ?? '';
        $notes = trim((string)($_POST['admin_notes'] ?? ''));

        if (!in_array($status, $allowedStatus, true) || !in_array($paymentStatus, $allowedPayment, true)) {
            flash_add('error', 'Status inválido.');
            redirect('/admin/servicos.php');
        }

        $update = $pdo->prepare('UPDATE user_service_listings
            SET status = :status,
                payment_status = :payment_status,
                notes = :notes,
                published_at = CASE WHEN :status = "active" AND published_at IS NULL THEN NOW() ELSE published_at END,
                updated_at = NOW()
            WHERE id = :id');
        $update->execute([
            ':status' => $status,
            ':payment_status' => $paymentStatus,
            ':notes' => $notes !== '' ? $notes : $service['notes'],
            ':id' => $serviceId,
        ]);

        flash_add('success', 'Serviço atualizado com sucesso.');
    } elseif ($action === 'suspend') {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            flash_add('error', 'Informe o motivo da suspensão.');
        } else {
            try {
                // Simples UPDATE do status
                $update = $pdo->prepare('UPDATE user_service_listings SET status = ? WHERE id = ?');
                $update->execute(['suspended', $serviceId]);
                
                // Adicionar notas se fornecidas
                if (!empty($reason)) {
                    $notesUpdate = $pdo->prepare('UPDATE user_service_listings SET notes = ?, moderation_notes = ? WHERE id = ?');
                    $notesUpdate->execute([$reason, $reason, $serviceId]);
                }
                
                // Registrar auditoria
                if (function_exists('record_audit')) {
                    record_audit((int)current_user()['id'], 'service_suspended', 'user_service_listings', $serviceId, ['reason' => $reason]);
                }

                // Notificar vendedor (opcional, não quebra se falhar)
                if ($serviceOwner && !empty($serviceOwner['email']) && function_exists('mail_enqueue')) {
                    try {
                        $dashboardUrl = app_url('anuncios/meus.php?tab=services');
                        mail_enqueue(
                            $pdo,
                            (string)$serviceOwner['email'],
                            'Seu serviço foi suspenso - Classi Livre Autos',
                            'service_status',
                            [
                                'nome' => (string)($serviceOwner['name'] ?? ''),
                                'titulo' => (string)$service['name'],
                                'status_label' => 'Suspenso',
                                'motivo' => $reason,
                                'dashboard_url' => $dashboardUrl,
                            ],
                            (string)($serviceOwner['name'] ?? '')
                        );
                    } catch (Exception $mailEx) {
                        // Silenciosamente ignora erro de email
                    }
                }

                flash_add('success', 'Serviço suspenso com sucesso.');
            } catch (Exception $e) {
                flash_add('error', 'Erro ao suspender serviço: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'reactivate') {
        try {
            // Simples UPDATE do status
            $update = $pdo->prepare('UPDATE user_service_listings SET status = ? WHERE id = ?');
            $update->execute(['active', $serviceId]);
            
            // Registrar auditoria
            if (function_exists('record_audit')) {
                record_audit((int)current_user()['id'], 'service_reactivated', 'user_service_listings', $serviceId, []);
            }

            // Notificar vendedor (opcional, não quebra se falhar)
            if ($serviceOwner && !empty($serviceOwner['email']) && function_exists('mail_enqueue')) {
                try {
                    $dashboardUrl = app_url('anuncios/meus.php?tab=services');
                    mail_enqueue(
                        $pdo,
                        (string)$serviceOwner['email'],
                        'Seu serviço foi reativado - Classi Livre Autos',
                        'service_status',
                        [
                            'nome' => (string)($serviceOwner['name'] ?? ''),
                            'titulo' => (string)$service['name'],
                            'status_label' => 'Ativo',
                            'motivo' => 'Seu serviço foi reativado pela administração.',
                            'dashboard_url' => $dashboardUrl,
                        ],
                        (string)($serviceOwner['name'] ?? '')
                    );
                } catch (Exception $mailEx) {
                    // Silenciosamente ignora erro de email
                }
            }

            flash_add('success', 'Serviço reativado com sucesso.');
        } catch (Exception $e) {
            flash_add('error', 'Erro ao reativar serviço: ' . $e->getMessage());
        }
    } elseif ($action === 'delete') {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            flash_add('error', 'Informe o motivo da exclusão.');
        } else {
            try {
                // Notificar vendedor (opcional)
                if ($serviceOwner && !empty($serviceOwner['email']) && function_exists('mail_enqueue')) {
                    try {
                        $dashboardUrl = app_url('anuncios/meus.php?tab=services');
                        mail_enqueue(
                            $pdo,
                            (string)$serviceOwner['email'],
                            'Seu serviço foi removido - Classi Livre Autos',
                            'service_status',
                            [
                                'nome' => (string)($serviceOwner['name'] ?? ''),
                                'titulo' => (string)$service['name'],
                                'status_label' => 'Removido',
                                'motivo' => $reason,
                                'dashboard_url' => $dashboardUrl,
                            ],
                            (string)($serviceOwner['name'] ?? '')
                        );
                    } catch (Exception $mailEx) {
                        // Silenciosamente ignora erro de email
                    }
                }

                // Deletar o serviço
                $delete = $pdo->prepare('DELETE FROM user_service_listings WHERE id = ?');
                $delete->execute([$serviceId]);
                
                // Registrar auditoria
                if (function_exists('record_audit')) {
                    record_audit((int)current_user()['id'], 'service_deleted', 'user_service_listings', $serviceId, ['title' => $service['name'], 'reason' => $reason]);
                }
                
                flash_add('success', 'Serviço excluído permanentemente.');
            } catch (Exception $e) {
                flash_add('error', 'Erro ao excluir serviço: ' . $e->getMessage());
            }
        }
    } else {
        flash_add('error', 'Ação não reconhecida.');
    }

        redirect('/admin/servicos.php');
    } catch (Exception $e) {
        flash_add('error', 'Erro ao processar ação: ' . $e->getMessage());
        redirect('/admin/servicos.php');
    }
}

// Build filters
$conditions = [];
$params = [];

if ($query) {
    $conditions[] = '(sl.name LIKE :q OR u.name LIKE :q OR u.email LIKE :q)';
    $params[':q'] = '%' . $query . '%';
}

if ($status) {
    $conditions[] = 'sl.status = :status';
    $params[':status'] = $status;
}

if ($category) {
    $conditions[] = 'sl.category LIKE :category';
    $params[':category'] = '%' . $category . '%';
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_service_listings sl JOIN users u ON u.id = sl.user_id $whereSql");
$countStmt->execute($params);
$totalServices = (int)$countStmt->fetch()['total'];
$totalPages = ceil($totalServices / $limit);

// Get paginated results
$servicesStmt = $pdo->prepare("SELECT sl.*, u.name AS user_name, u.email AS user_email, u.is_premium, u.document_number, u.document_type
    FROM user_service_listings sl
    JOIN users u ON u.id = sl.user_id $whereSql
    ORDER BY sl.created_at DESC LIMIT :limit OFFSET :offset");
$servicesStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$servicesStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $servicesStmt->bindValue($key, $value);
}
$servicesStmt->execute();
$services = $servicesStmt->fetchAll();

$flashMessages = flash_get();

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>
<section class="form-card" style="max-width:1200px;margin:2rem auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:2rem; padding-bottom:1.5rem; border-bottom:2px solid #e5e7eb;">
        <div>
            <h1 class="admin-title" style="margin:0 0 0.5rem 0;">Serviços Automotivos</h1>
            <p class="text-sm text-gray-500" style="margin:0;">Total: <strong><?= number_format($totalServices); ?></strong> serviços encontrados.</p>
        </div>
    </div>

    <form method="get" class="search-card admin-filter-form" style="margin-bottom:2rem;">
        <div class="search-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; align-items:flex-end;">
            <div class="input-group" style="display:flex; flex-direction:column;">
                <label for="q" style="font-weight:600; margin-bottom:0.5rem; font-size:0.9rem;">Busca por Nome/Usuário</label>
                <input type="text" id="q" name="q" value="<?= e($query ?? ''); ?>" placeholder="Digite nome do serviço, usuário..." style="padding:0.65rem; border:1px solid #d1d5db; border-radius:6px; font-size:0.9rem;">
            </div>
            <div class="input-group" style="display:flex; flex-direction:column;">
                <label for="status" style="font-weight:600; margin-bottom:0.5rem; font-size:0.9rem;">Status</label>
                <select id="status" name="status" style="padding:0.65rem; border:1px solid #d1d5db; border-radius:6px; font-size:0.9rem;">
                    <option value="">Todos</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="suspended" <?= $status === 'suspended' ? 'selected' : ''; ?>>Suspenso</option>

                </select>
            </div>
            <div class="input-group" style="display:flex; flex-direction:column;">
                <label for="category" style="font-weight:600; margin-bottom:0.5rem; font-size:0.9rem;">Categoria</label>
                <input type="text" id="category" name="category" value="<?= e($category ?? ''); ?>" placeholder="Filtrar por categoria..." style="padding:0.65rem; border:1px solid #d1d5db; border-radius:6px; font-size:0.9rem;">
            </div>
            <div class="input-group" style="display:flex; flex-direction:column;">
                <label for="limit" style="font-weight:600; margin-bottom:0.5rem; font-size:0.9rem;">Itens por Página</label>
                <select id="limit" name="limit" style="padding:0.65rem; border:1px solid #d1d5db; border-radius:6px; font-size:0.9rem;">
                    <option value="10" <?= $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?= $limit == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?= $limit == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="250" <?= $limit == 250 ? 'selected' : ''; ?>>250</option>
                    <option value="500" <?= $limit == 500 ? 'selected' : ''; ?>>500</option>
                </select>
            </div>
        </div>
        <div style="display:flex; gap:0.8rem; margin-top:1.5rem;">
            <button type="submit" class="button" style="padding:0.7rem 1.8rem; font-weight:600;">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            <a href="?" class="button secondary" style="padding:0.7rem 1.8rem; font-weight:600;">
                <i class="fa-solid fa-xmark"></i> Limpar Filtros
            </a>
        </div>
    </form>

    <?php if ($flashMessages): ?>
        <?php foreach ($flashMessages as $flash): ?>
            <div class="alert alert-<?= e($flash['type']); ?>" style="margin-bottom:1rem;">
                <?= e($flash['message']); ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!$services): ?>
        <div class="alert alert-info">Nenhum serviço cadastrado até o momento.</div>
    <?php else: ?>
        <div style="display:grid; gap:1.5rem; margin-top:2rem;">
            <?php foreach ($services as $service): ?>
                <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <!-- Cabeçalho do Card -->
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid #f3f4f6;">
                        <div>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <span style="background:#136f33; color:#ffffff; padding:0.3rem 0.7rem; border-radius:6px; font-weight:600; font-size:0.85rem;">#<?= (int)$service['id']; ?></span>
                                <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:#0f172a;"><?= e($service['name']); ?></h3>
                            </div>
                            <div style="margin-top:0.5rem; color:#666; font-size:0.9rem;">
                                <strong><?= e($service['category']); ?></strong><?= $service['custom_category'] ? ' · ' . e($service['custom_category']) : ''; ?>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <span style="display:inline-block; padding:0.4rem 0.8rem; border-radius:6px; font-weight:600; font-size:0.85rem; <?php if ($service['status'] === 'active') echo 'background:#d1fae5; color:#065f46;'; elseif ($service['status'] === 'suspended') echo 'background:#fee2e2; color:#991b1b;'; elseif ($service['status'] === 'pending_payment') echo 'background:#fef3c7; color:#92400e;'; else echo 'background:#f3f4f6; color:#374151;'; ?>">
                                <?= ucfirst(str_replace('_', ' ', e($service['status']))); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Descrição do Serviço -->
                    <?php if (!empty($service['description'])): ?>
                        <div style="background:#f0f9ff; border-left:4px solid #0ea5e9; padding:1rem; border-radius:6px; margin-bottom:1rem;">
                            <div style="font-size:0.85rem; color:#0369a1; font-weight:600; margin-bottom:0.5rem; text-transform:uppercase;">DESCRIÇÃO</div>
                            <div style="color:#334155; line-height:1.6; font-size:0.95rem;">
                                <?= e($service['description']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Grid de Informações -->
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:1.5rem; margin-bottom:1.5rem;">
                        <!-- Contato do Serviço -->
                        <div>
                            <div style="font-size:0.85rem; color:#6b7280; font-weight:600; margin-bottom:0.5rem; text-transform:uppercase;">CONTATO DO SERVIÇO</div>
                            <div style="background:#f9fafb; padding:1rem; border-radius:8px; border-left:3px solid #136f33;">
                                <?php if (!empty($service['address_city'])): ?>
                                    <div style="margin-bottom:0.5rem; font-size:0.9rem;">
                                        <i class="fa-solid fa-location-dot" style="color:#f59e0b; margin-right:0.5rem;"></i>
                                        <strong><?= e($service['address_city']); ?></strong><?= $service['address_state'] ? ' / ' . e($service['address_state']) : ''; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($service['phone'])): ?>
                                    <div style="margin-bottom:0.5rem; font-size:0.9rem;">
                                        <i class="fa-solid fa-phone" style="color:#136f33; margin-right:0.5rem; width:16px;"></i>
                                        <strong><?= e($service['phone']); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($service['whatsapp'])): ?>
                                    <div style="margin-bottom:0.5rem; font-size:0.9rem;">
                                        <i class="fab fa-whatsapp" style="color:#1b9c45; margin-right:0.5rem; width:16px;"></i>
                                        <strong><?= e($service['whatsapp']); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($service['email'])): ?>
                                    <div style="font-size:0.9rem;">
                                        <i class="fa-solid fa-envelope" style="color:#8b5cf6; margin-right:0.5rem; width:16px;"></i>
                                        <strong><?= e($service['email']); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Vendedor / CPF -->
                        <div>
                            <div style="font-size:0.85rem; color:#6b7280; font-weight:600; margin-bottom:0.5rem; text-transform:uppercase;">VENDEDOR</div>
                            <div style="background:#f9fafb; padding:1rem; border-radius:8px; border-left:3px solid #8b5cf6;">
                                <div style="margin-bottom:0.5rem;">
                                    <strong style="font-size:0.95rem; color:#0f172a;"><?= e($service['user_name']); ?></strong>
                                </div>
                                <div style="margin-bottom:0.5rem; font-size:0.85rem; color:#666;">
                                    <i class="fa-solid fa-envelope" style="margin-right:0.4rem;"></i>
                                    <?= e($service['user_email']); ?>
                                </div>
                                <div style="margin-bottom:0.7rem; padding:0.6rem; background:#ffffff; border-radius:6px; border:1px solid #e5e7eb; font-size:0.85rem;">
                                    <strong><?= e($service['document_type'] ?? 'CPF'); ?>:</strong> 
                                    <?php 
                                        $docNum = (string)($service['document_number'] ?? '');
                                        if ($service['document_type'] === 'CNPJ' && strlen($docNum) === 14) {
                                            echo e(substr($docNum, 0, 2) . '.' . substr($docNum, 2, 3) . '.' . substr($docNum, 5, 3) . '/' . substr($docNum, 8, 4) . '-' . substr($docNum, 12));
                                        } elseif (strlen($docNum) === 11) {
                                            echo e(substr($docNum, 0, 3) . '.' . substr($docNum, 3, 3) . '.' . substr($docNum, 6, 3) . '-' . substr($docNum, 9));
                                        } else {
                                            echo e($docNum);
                                        }
                                    ?>
                                </div>
                                <div style="font-size:0.85rem;">
                                    <?php if (!empty($service['is_premium'])): ?>
                                        <span style="color:#16a34a;"><i class="fa-solid fa-star" style="margin-right:0.4rem;"></i><strong>Plano Premium</strong></span>
                                    <?php else: ?>
                                        <span style="color:#6b7280;">Plano Gratuito</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Status de Pagamento e Expiração -->
                        <div>
                            <div style="font-size:0.85rem; color:#6b7280; font-weight:600; margin-bottom:0.5rem; text-transform:uppercase;">PAGAMENTO & EXPIRAÇÃO</div>
                            <div style="background:#f9fafb; padding:1rem; border-radius:8px; border-left:3px solid #f59e0b;">
                                <div style="margin-bottom:1rem;">
                                    <div style="font-size:0.85rem; color:#6b7280; margin-bottom:0.3rem;"><strong>Status Pagamento:</strong></div>
                                    <span style="display:inline-block; padding:0.4rem 0.8rem; border-radius:6px; font-weight:600; font-size:0.85rem; <?php if ($service['payment_status'] === 'paid') echo 'background:#d1fae5; color:#065f46;'; elseif ($service['payment_status'] === 'pending') echo 'background:#fef3c7; color:#92400e;'; elseif ($service['payment_status'] === 'failed') echo 'background:#fee2e2; color:#991b1b;'; else echo 'background:#f3f4f6; color:#374151;'; ?>">
                                        <?= ucfirst(e($service['payment_status'])); ?>
                                    </span>
                                </div>
                                <div>
                                    <div style="font-size:0.85rem; color:#6b7280; margin-bottom:0.3rem;"><strong>Expiração:</strong></div>
                                    <?php if (!empty($service['expires_at'])): ?>
                                        <div style="font-size:0.9rem; margin-bottom:0.3rem;">
                                            <i class="fa-solid fa-calendar" style="margin-right:0.4rem;"></i>
                                            <?= format_date($service['expires_at']); ?>
                                        </div>
                                        <?php 
                                            $expiresTime = strtotime($service['expires_at']);
                                            $nowTime = time();
                                            $daysLeft = intdiv(($expiresTime - $nowTime), 86400);
                                        ?>
                                        <span style="display:inline-block; padding:0.3rem 0.6rem; border-radius:4px; font-weight:600; font-size:0.8rem;
                                            <?php 
                                                if ($daysLeft < 0) echo 'background:#fee2e2; color:#991b1b;';
                                                elseif ($daysLeft === 0) echo 'background:#fef3c7; color:#92400e;';
                                                elseif ($daysLeft <= 7) echo 'background:#fef3c7; color:#92400e;';
                                                else echo 'background:#d1fae5; color:#065f46;';
                                            ?>">
                                            <?php 
                                                if ($daysLeft < 0) echo '<i class="fa-solid fa-exclamation-circle"></i> Expirado';
                                                elseif ($daysLeft === 0) echo '<i class="fa-solid fa-hourglass-end"></i> Expira hoje';
                                                elseif ($daysLeft <= 7) echo '<i class="fa-solid fa-hourglass-half"></i> ' . $daysLeft . ' dia' . ($daysLeft > 1 ? 's' : '');
                                                else echo '<i class="fa-solid fa-check-circle"></i> ' . $daysLeft . ' dias';
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#9ca3af; font-size:0.9rem;">Sem data de expiração</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div style="display:flex; gap:0.8rem; padding-top:1rem; border-top:1px solid #f3f4f6;">
                        <?php if ($service['status'] !== 'suspended'): ?>
                            <button type="button" class="button secondary" style="flex:1; padding:0.6rem 1rem; font-size:0.9rem;" onclick="suspendService(<?= (int)$service['id']; ?>, '<?= e($service['name']); ?>')">
                                <i class="fa-solid fa-pause"></i> Suspender
                            </button>
                        <?php else: ?>
                            <button type="button" class="button secondary" style="flex:1; padding:0.6rem 1rem; font-size:0.9rem; background:#10b981;color:#fff;" onclick="reactivateService(<?= (int)$service['id']; ?>, '<?= e($service['name']); ?>')">
                                <i class="fa-solid fa-play"></i> Reativar
                            </button>
                        <?php endif; ?>
                        <button type="button" class="button secondary" style="flex:1; padding:0.6rem 1rem; font-size:0.9rem; background:#fff;color:#e63946;border:1px solid rgba(230,57,70,0.3);" onclick="deleteService(<?= (int)$service['id']; ?>, '<?= e($service['name']); ?>')">
                            <i class="fa-solid fa-trash"></i> Excluir
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top:2rem; text-align:center;">
                <?php
                $queryParams = $_GET;
                unset($queryParams['page']); // Remove page from query for clean links

                if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page - 1])); ?>" class="button secondary">← Anterior</a>
                <?php endif; ?>

                <span style="margin:0 1rem;">Página <?= $page; ?> de <?= $totalPages; ?> (<?= number_format($totalServices); ?> total)</span>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $page + 1])); ?>" class="button secondary">Próxima →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Modal de Suspensão -->
    <div id="suspendModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('suspendModal')">&times;</span>
            <h2>Suspender Serviço</h2>
            <p id="suspendServiceName"></p>
            <p><strong>Atenção:</strong> O serviço será suspenso e ficará invisível para os usuários.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="service_id" id="suspendServiceId">
                <input type="hidden" name="action" value="suspend">
                <div class="input-group">
                    <label for="suspendReason">Motivo da Suspensão</label>
                    <textarea id="suspendReason" name="reason" rows="3" required placeholder="Descreva o motivo da suspensão..."></textarea>
                </div>
                <button type="submit" class="button secondary" style="background:#e63946;color:#fff;">Suspender Serviço</button>
            </form>
        </div>
    </div>

    <!-- Modal de Reativação -->
    <div id="reactivateModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('reactivateModal')">&times;</span>
            <h2>Reativar Serviço</h2>
            <p id="reactivateServiceName"></p>
            <p><strong>Atenção:</strong> O serviço será reativado e ficará visível novamente para os usuários.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="service_id" id="reactivateServiceId">
                <input type="hidden" name="action" value="reactivate">
                <button type="submit" class="button secondary" style="background:#10b981;color:#fff;">Reativar Serviço</button>
            </form>
        </div>
    </div>

    <!-- Modal de Exclusão Definitiva -->
    <div id="deleteModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h2>Excluir Serviço</h2>
            <p id="deleteServiceName"></p>
            <p><strong>Atenção:</strong> Esta ação é permanente e notificaremos o vendedor por e-mail com sua justificativa.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="service_id" id="deleteServiceId">
                <input type="hidden" name="action" value="delete">
                <div class="input-group">
                    <label for="deleteReason">Motivo da exclusão</label>
                    <textarea id="deleteReason" name="reason" rows="3" required placeholder="Explique o motivo da remoção..."></textarea>
                </div>
                <button type="submit" class="button secondary" style="background:#e63946;color:#fff;">Excluir definitivamente</button>
            </form>
        </div>
    </div>

    <script>
        function suspendService(id, name) {
            document.getElementById('suspendServiceId').value = id;
            document.getElementById('suspendServiceName').textContent = 'Suspender: ' + name;
            document.getElementById('suspendReason').value = '';
            document.getElementById('suspendModal').style.display = 'block';
        }

        function reactivateService(id, name) {
            document.getElementById('reactivateServiceId').value = id;
            document.getElementById('reactivateServiceName').textContent = 'Reativar: ' + name;
            document.getElementById('reactivateModal').style.display = 'block';
        }

        function deleteService(id, name) {
            document.getElementById('deleteServiceId').value = id;
            document.getElementById('deleteServiceName').textContent = 'Excluir: ' + name;
            document.getElementById('deleteReason').value = '';
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
    </style>

</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
