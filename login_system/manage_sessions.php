<?php
// login_system/manage_sessions.php - Gerenciar sessões ativas

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/session_manager.php';

require_login();

$pageTitle = 'Gerenciar Sessões - Classi Livre Autos';
$user = current_user();
$userId = (int)$user['id'];
$currentSessionToken = $_SESSION['session_token'] ?? null;
$maxConcurrentSessions = (int)($user['max_concurrent_sessions'] ?? 1);

// Se veio POST para deslogar outra sessão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['logout_session_id'])) {
    if (!validate_csrf($_POST['csrf_token'] ?? null)) {
        flash_add('error', 'Token CSRF inválido.');
    } else {
        $sessionId = (int)$_POST['logout_session_id'];
        
        // Buscar a sessão para verificar se pertence ao usuário
        $stmt = db()->prepare('SELECT session_token FROM user_sessions WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':id' => $sessionId, ':user_id' => $userId]);
        $session = $stmt->fetch();
        
        if ($session) {
            if (invalidate_session(db(), $userId, $session['session_token'])) {
                flash_add('success', 'Sessão desconectada com sucesso!');
            } else {
                flash_add('error', 'Erro ao desconectar sessão.');
            }
        } else {
            flash_add('error', 'Sessão não encontrada.');
        }
    }
    redirect('/login_system/manage_sessions.php');
}

// Buscar todas as sessões ativas
$sessions = get_user_sessions(db(), $userId);
$activeSessions = count($sessions);

include __DIR__ . '/../partials/header.php';
?>

<section class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Gerenciar Sessões Ativas</h1>
    
    <!-- Limite de Sessões -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-blue-900 mb-2">
                    <i class="fas fa-lock mr-2"></i>Limite de Acessos Simultâneos
                </p>
                <p class="text-sm text-blue-800">
                    Você pode ter até <strong><?= $maxConcurrentSessions; ?></strong> acessos simultâneos da sua conta.
                </p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-indigo-600">
                    <?= $activeSessions; ?> / <?= $maxConcurrentSessions; ?>
                </div>
                <div class="text-xs text-blue-700 mt-1">Sessões ativas</div>
            </div>
        </div>
        <?php if ($maxConcurrentSessions > 1): ?>
            <p class="text-xs text-green-700 mt-3 flex items-center">
                <i class="fas fa-check-circle mr-1"></i>
                <strong>✅ Conta Premium</strong> - Você pode acessar de até <?= $maxConcurrentSessions; ?> dispositivos ao mesmo tempo!
            </p>
        <?php else: ?>
            <p class="text-xs text-gray-700 mt-3 flex items-center">
                <i class="fas fa-info-circle mr-1"></i>
                Interessado em acessar de vários dispositivos? Considere fazer upgrade para Premium!
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($sessions)): ?>
        <div class="space-y-4">
            <?php foreach ($sessions as $session): 
                $isCurrent = ($session['session_token'] === $currentSessionToken);
                $createdAt = new DateTime($session['created_at']);
                $lastActivity = new DateTime($session['last_activity_at']);
                $now = new DateTime();
                $duration = $createdAt->diff($now);
                $timeSinceActivity = $lastActivity->diff($now);
            ?>
                <div class="bg-white border rounded-lg p-4 shadow-sm <?= $isCurrent ? 'border-green-400 bg-green-50' : '' ?>">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center">
                                <?php if ($isCurrent): ?>
                                    <span class="inline-block w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                    <span class="text-green-700">Sessão Atual</span>
                                <?php else: ?>
                                    <span class="inline-block w-3 h-3 bg-gray-400 rounded-full mr-2"></span>
                                    <span>Outra Sessão</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                📍 IP: <code class="bg-gray-100 px-2 py-1 rounded"><?= e($session['ip_address'] ?? 'Desconhecido'); ?></code>
                            </p>
                            <p class="text-sm text-gray-600 mt-1 max-w-full overflow-hidden">
                                🖥️ Navegador: <span class="text-xs text-gray-500"><?= e(substr($session['user_agent'] ?? 'Desconhecido', 0, 60)); ?>...</span>
                            </p>
                        </div>
                        <?php if (!$isCurrent): ?>
                            <form method="post" class="inline" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                <input type="hidden" name="logout_session_id" value="<?= (int)$session['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Desconectar esta sessão?');">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Desconectar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-xs text-gray-500 space-y-1 border-t pt-3">
                        <p>
                            ⏱️ Conectado há: <strong><?= sprintf('%d dias, %d horas', $duration->d, $duration->h); ?></strong>
                        </p>
                        <p>
                            ⏰ Última atividade há: <strong><?= sprintf('%d minutos', (int)($timeSinceActivity->days * 1440 + $timeSinceActivity->h * 60 + $timeSinceActivity->i)); ?></strong>
                        </p>
                        <p>
                            📅 Criada em: <strong><?= $createdAt->format('d/m/Y \à\s H:i'); ?></strong>
                        </p>
                        <?php if ($session['expires_at']): ?>
                            <p>
                                🔔 Expira em: <strong><?= (new DateTime($session['expires_at']))->format('d/m/Y \à\s H:i'); ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-yellow-800">Nenhuma sessão ativa no momento.</p>
        </div>
    <?php endif; ?>

    <div class="mt-8 pt-6 border-t">
        <a href="/anuncios/meus.php" class="button button-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
