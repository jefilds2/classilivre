<?php
/**
 * logs/anuncios_expirados/view_logs.php
 * 
 * Interface para visualizar logs de anúncios expirados
 * Mostrar logs de um dia específico ou dos últimos N dias
 */

declare(strict_types=1);

require_once __DIR__ . '/../../login_system/middleware.php';
require_once __DIR__ . '/ExpiredAdsLogger.php';

// Apenas admins podem acessar
require_admin();

$pageTitle = 'Logs de Anúncios Expirados';
$activeNav = 'logs';

$logger = new ExpiredAdsLogger();
$logFiles = $logger->listLogFiles();

// Determinar qual log visualizar
$viewDate = $_GET['date'] ?? date('Y-m-d');
$logFile = __DIR__ . '/' . $viewDate . '.log';
$logContent = '';

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
} else {
    $logContent = "Nenhum log disponível para a data: $viewDate";
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../admin/partials/admin_nav.php';
?>

<style>
.logs-container {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.logs-sidebar {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 1.5rem;
    height: fit-content;
    border: 1px solid #e0e0e0;
}

.logs-sidebar h3 {
    margin: 0 0 1rem 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #333;
    text-transform: uppercase;
    border-bottom: 2px solid #166534;
    padding-bottom: 0.5rem;
}

.logs-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 400px;
    overflow-y: auto;
}

.logs-list-item {
    padding: 0.75rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s;
    text-decoration: none;
    display: block;
    border-left: 3px solid transparent;
}

.logs-list-item:hover {
    background: #e8f5e9;
    border-left-color: #166534;
}

.logs-list-item.active {
    background: #166534;
    color: #fff;
    border-left-color: #fff;
}

.logs-list-item-date {
    font-weight: 600;
    display: block;
}

.logs-list-item-size {
    font-size: 0.8rem;
    opacity: 0.7;
}

.logs-main {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.logs-header {
    background: #fafafa;
    padding: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logs-header h2 {
    margin: 0;
    font-size: 1.2rem;
    color: #333;
}

.logs-actions {
    display: flex;
    gap: 0.75rem;
}

.logs-actions .button {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-block;
}

.logs-actions .button.primary {
    background: #166534;
    color: #fff;
}

.logs-actions .button.primary:hover {
    background: #0d4429;
}

.logs-actions .button.secondary {
    background: #e0e0e0;
    color: #333;
}

.logs-actions .button.secondary:hover {
    background: #d0d0d0;
}

.logs-content {
    padding: 1.5rem;
    max-height: 600px;
    overflow-y: auto;
    background: #fafafa;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    color: #333;
    white-space: pre-wrap;
    word-break: break-word;
}

.logs-empty {
    text-align: center;
    padding: 2rem;
    color: #999;
}

@media (max-width: 768px) {
    .logs-container {
        grid-template-columns: 1fr;
    }
    
    .logs-sidebar {
        max-height: 300px;
    }
}
</style>

<section style="margin: 2rem 0;">
    <h1 style="margin: 0 0 0.5rem 0; font-size: 1.8rem;">📋 Logs de Anúncios Expirados</h1>
    <p style="margin: 0; color: #666; font-size: 0.95rem;">Histórico de eventos do sistema de expiração e limpeza</p>
</section>

<div class="logs-container">
    <!-- Sidebar com lista de logs -->
    <aside class="logs-sidebar">
        <h3>📅 Logs Disponíveis</h3>
        <div class="logs-list">
            <?php foreach ($logFiles as $file => $info): ?>
                <a 
                    href="?date=<?= urlencode($info['date']); ?>" 
                    class="logs-list-item <?= $info['date'] === $viewDate ? 'active' : ''; ?>"
                    title="<?= $info['modified']; ?>"
                >
                    <span class="logs-list-item-date"><?= date('d/m/Y', strtotime($info['date'])); ?></span>
                    <span class="logs-list-item-size"><?= number_format($info['size'] / 1024, 1); ?>KB</span>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Área principal com conteúdo do log -->
    <main class="logs-main">
        <div class="logs-header">
            <h2>
                <?php if (file_exists($logFile)): ?>
                    Log de <?= date('d/m/Y', strtotime($viewDate)); ?>
                <?php else: ?>
                    Sem logs para exibir
                <?php endif; ?>
            </h2>
            <div class="logs-actions">
                <?php if (file_exists($logFile)): ?>
                    <a href="?date=<?= urlencode(date('Y-m-d')); ?>" class="button primary">Hoje</a>
                    <a href="#" class="button secondary" onclick="location.reload(); return false;">↻ Recarregar</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="logs-content">
            <?php if ($logContent && $logContent !== "Nenhum log disponível para a data: $viewDate"): ?>
                <?= e($logContent); ?>
            <?php else: ?>
                <div class="logs-empty">
                    <p>📭 Nenhum log disponível para a data: <?= date('d/m/Y', strtotime($viewDate)); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
