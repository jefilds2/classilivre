<?php
// admin/moedas.php - Gerenciamento manual de moedas Clivre

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';
require_once __DIR__ . '/../login_system/middleware.php';

require_admin();

$pageTitle = 'Gerenciar Moedas CLivre - Administração';
$activeNav = 'moedas';

$errors = [];
$success = null;
$userFound = null;
$userTransactions = [];

// Verificar se voltou de um redirect de sucesso
if (!empty($_GET['success']) && !empty($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    $success = 'Moedas do usuário ajustadas com sucesso!';
    
    // Recarregar dados do usuário
    $userQuery = "SELECT id, name, email, coin_balance FROM users WHERE id = :id LIMIT 1";
    $userStmt = db()->prepare($userQuery);
    $userStmt->execute([':id' => $userId]);
    $userFound = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userFound) {
        // Recarregar transações
        $txQuery = "SELECT * FROM coin_transactions 
                   WHERE user_id = :user_id 
                   ORDER BY created_at DESC 
                   LIMIT 50";
        $txStmt = db()->prepare($txQuery);
        $txStmt->execute([':user_id' => $userId]);
        $userTransactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF inválido.';
    } else {
        $action = $_POST['action'] ?? null;
        
        if ($action === 'search') {
            $searchInput = trim($_POST['search_input'] ?? '');
            
            if (empty($searchInput)) {
                $errors[] = 'Por favor, informe um email ou ID de usuário.';
            } else {
                // Buscar usuário por email ou ID
                try {
                    $query = "SELECT id, name, email, coin_balance FROM users WHERE email = :search OR id = :id LIMIT 1";
                    $stmt = db()->prepare($query);
                    $stmt->execute([
                        ':search' => $searchInput,
                        ':id' => is_numeric($searchInput) ? (int)$searchInput : 0
                    ]);
                    
                    $userFound = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$userFound) {
                        $errors[] = 'Usuário não encontrado.';
                    } else {
                        // Carregar transações do usuário
                        $txQuery = "SELECT * FROM coin_transactions 
                                   WHERE user_id = :user_id 
                                   ORDER BY created_at DESC 
                                   LIMIT 50";
                        $txStmt = db()->prepare($txQuery);
                        $txStmt->execute([':user_id' => (int)$userFound['id']]);
                        $userTransactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    $errors[] = 'Erro ao buscar usuário: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'adjust') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $amount = (int)($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            
            if ($userId <= 0) {
                $errors[] = 'ID de usuário inválido.';
            } elseif ($amount === 0) {
                $errors[] = 'Informe uma quantidade válida (positiva para adicionar, negativa para remover).';
            } elseif (empty($description)) {
                $errors[] = 'Descrição é obrigatória.';
            } else {
                try {
                    // Determinar tipo de transação
                    $type = $amount > 0 ? 'admin_add' : 'admin_remove';
                    
                    // Ajustar saldo
                    $adjusted = adjust_user_coins(
                        $userId,
                        $amount,
                        $type,
                        0,
                        'admin_operation',
                        $description
                    );
                    
                    if ($adjusted) {
                        record_audit(
                            (int)current_user()['id'],
                            'admin_coin_adjustment',
                            'users',
                            $userId,
                            [
                                'amount' => $amount,
                                'type' => $type,
                                'description' => $description
                            ]
                        );
                        
                        // Invalidar cache
                        invalidate_coin_cache($userId);
                        
                        // Redirecionar para evitar duplicação ao recarregar
                        redirect("/admin/moedas.php?success=1&user_id={$userId}");
                    } else {
                        $errors[] = 'Erro ao ajustar moedas (saldo insuficiente para remoção?).';
                    }
                } catch (Exception $e) {
                    $errors[] = 'Erro ao ajustar moedas: ' . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/partials/admin_nav.php';
?>

<section style="margin:2rem 0;">
    <h1>Gerenciamento de Moedas CLivre</h1>
    <p>Busque usuários e adicione ou remova moedas manualmente com auditoria completa.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error" style="margin-top:1rem;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-top:1rem;"><?= e($success); ?></div>
    <?php endif; ?>

    <!-- Formulário de Busca -->
    <div class="search-card admin-filter-form" style="margin-top:2rem;">
        <h3>1️⃣ Buscar Usuário</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <input type="hidden" name="action" value="search">
            
            <div class="input-group">
                <label for="search_input">Email ou ID do Usuário</label>
                <div style="display:flex; gap:0.5rem; align-items:flex-end;">
                    <input type="text" id="search_input" name="search_input" placeholder="exemplo@email.com ou 123"
                           style="flex:1;" required>
                    <button type="submit" class="button button-admin">Buscar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Resultado da Busca e Ajuste -->
    <?php if ($userFound): ?>
        <div class="search-card admin-filter-form" style="margin-top:2rem;">
            <h3>👤 Dados do Usuário</h3>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                <div>
                    <strong>ID:</strong> <?= (int)$userFound['id']; ?><br>
                    <strong>Nome:</strong> <?= e($userFound['name']); ?><br>
                    <strong>Email:</strong> <?= e($userFound['email']); ?>
                </div>
                <div style="padding:1rem; background:#fff3cd; border-radius:8px; text-align:center;">
                    <div style="font-size:0.9rem; color:#666;">Saldo Atual</div>
                    <div style="font-size:2rem; font-weight:bold; color:#ff9800;">
                        <img src="/assets/img/classilivre-logo.png" alt="CLivre Medal" 
                             style="height:2rem; vertical-align:middle; margin-right:0.5rem;">
                        <?= (int)$userFound['coin_balance']; ?>
                    </div>
                </div>
            </div>

            <!-- Formulário de Ajuste -->
            <h3 style="margin-top:2rem;">💰 Adicionar/Remover Moedas</h3>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                <input type="hidden" name="action" value="adjust">
                <input type="hidden" name="user_id" value="<?= (int)$userFound['id']; ?>">
                
                <div class="input-group">
                    <label for="amount"><strong>Quantidade de Moedas</strong></label>
                    <input type="number" id="amount" name="amount" 
                           placeholder="Ex: 5 (adiciona) ou -3 (remove)" required autofocus>
                    <small style="color:#666;">
                        ✅ <strong>Valores positivos</strong> = Adicionar moedas<br>
                        ❌ <strong>Valores negativos</strong> = Remover moedas (ex: -5 remove 5 moedas)
                    </small>
                </div>

                <div class="input-group">
                    <label for="description"><strong>Descrição/Motivo</strong></label>
                    <input type="text" id="description" name="description" 
                           placeholder="Ex: Bônus promocional, Correção de erro, etc."
                           required>
                    <small style="color:#666;">Será registrada na auditoria e no histórico do usuário.</small>
                </div>

                <div class="admin-filter-actions" style="margin-top:1.5rem;">
                    <button type="submit" class="button button-admin admin-filter-button">Confirmar Ajuste</button>
                </div>
            </form>

            <!-- Histórico de Transações -->
            <h3 style="margin-top:2rem;">📊 Histórico de Transações (últimas 50)</h3>
            
            <?php if ($userTransactions): ?>
                <div style="overflow-x:auto; margin-top:1rem;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <thead>
                            <tr style="background:#f5f5f5; border-bottom:2px solid #ddd;">
                                <th style="padding:0.75rem; text-align:left;">Data/Hora</th>
                                <th style="padding:0.75rem; text-align:left;">Tipo</th>
                                <th style="padding:0.75rem; text-align:center;">Moedas</th>
                                <th style="padding:0.75rem; text-align:left;">Descrição</th>
                                <th style="padding:0.75rem; text-align:center;">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userTransactions as $tx): ?>
                                <tr style="border-bottom:1px solid #eee;">
                                    <td style="padding:0.75rem;">
                                        <?= date('d/m/Y H:i', strtotime($tx['created_at'])); ?>
                                    </td>
                                    <td style="padding:0.75rem;">
                                        <span style="display:inline-block; padding:0.25rem 0.5rem; border-radius:4px; 
                                                     background:<?php 
                                                     $types = [
                                                         'spend' => '#ffebee',
                                                         'purchase' => '#e8f5e9',
                                                         'admin_add' => '#e3f2fd',
                                                         'admin_remove' => '#fff3e0',
                                                         'refund' => '#f3e5f5'
                                                     ];
                                                     echo $types[$tx['type']] ?? '#f5f5f5';
                                                     ?>; font-size:0.85rem;">
                                            <?= e($tx['type']); ?>
                                        </span>
                                    </td>
                                    <td style="padding:0.75rem; text-align:center; font-weight:bold;
                                              color:<?= $tx['amount'] > 0 ? '#4caf50' : '#f44336'; ?>;">
                                        <?= ($tx['amount'] > 0 ? '+' : '') . (int)$tx['amount']; ?>
                                    </td>
                                    <td style="padding:0.75rem;">
                                        <?= e($tx['description']); ?>
                                    </td>
                                    <td style="padding:0.75rem; text-align:center;">
                                        <strong><?= (int)$tx['balance_after']; ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding:1rem; background:#f5f5f5; border-radius:8px; text-align:center; color:#666;">
                    Nenhuma transação encontrada para este usuário.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Informações Gerais -->
    <div style="margin-top:2rem; padding:1rem; background:#e8f5e9; border-radius:8px; border-left:4px solid #4caf50;">
        <strong>ℹ️ Informações Importantes:</strong>
        <ul style="margin:0.5rem 0; padding-left:1.5rem; font-size:0.95rem;">
            <li>Todas as operações são registradas na auditoria com timestamps e informações do admin.</li>
            <li>Saldo negativo é prevenido automaticamente (remoção falha se saldo for insuficiente).</li>
            <li>O histórico mostra as últimas 50 transações do usuário.</li>
            <li>Use descrições claras para facilitar auditorias posteriores.</li>
        </ul>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
