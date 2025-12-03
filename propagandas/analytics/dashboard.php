<?php
/**
 * Dashboard de Analytics
 * Exibe métricas detalhadas por usuário
 */

session_start();
require_once __DIR__ . '/../../login_system/middleware.php';

$logsDir = __DIR__ . '/logs';
$user = current_user();

// Parâmetros
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$filterUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Validar data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Carregar analytics do dia
$analyticsFile = $logsDir . "/analytics_{$selectedDate}.json";
$analytics = [];

if (file_exists($analyticsFile)) {
    $analytics = json_decode(file_get_contents($analyticsFile), true) ?: [];
}

// Se solicitou um usuário específico
$detailUser = null;
if ($filterUserId && isset($analytics['users'][$filterUserId])) {
    $detailUser = $analytics['users'][$filterUserId];
}

$pageTitle = 'Dashboard de Métricas - Classi Livre';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        input[type="date"],
        select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
        }

        button {
            padding: 8px 16px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        button:hover {
            background: #d97706;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .summary-card .label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #f59e0b;
        }

        .users-section,
        .detail-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .users-section h2,
        .detail-section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #333;
            border-bottom: 2px solid #eee;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #fafafa;
        }

        a {
            color: #f59e0b;
            text-decoration: none;
            cursor: pointer;
        }

        a:hover {
            text-decoration: underline;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #f59e0b;
            color: white;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-weight: 600;
        }

        .stat-value {
            color: #f59e0b;
            font-weight: bold;
        }

        .back-link {
            margin-bottom: 15px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 100px;
            height: 100px;
            opacity: 0.3;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <h1>📊 Dashboard de Métricas</h1>
            <div class="header-controls">
                <div class="form-group">
                    <label for="dateSelect">Data:</label>
                    <input type="date" id="dateSelect" value="<?= $selectedDate; ?>">
                </div>
                <button onclick="updateDate()">Buscar</button>
                <button onclick="exportCSV()">📥 Exportar CSV</button>
                <a href="/propagandas/admin/index.php" style="display: inline-block; padding: 8px 16px; background: #6b7280; color: white; border-radius: 4px; text-decoration: none;">← Voltar</a>
            </div>
        </header>

        <?php if (empty($analytics) || empty($analytics['users'])): ?>
            <div class="empty-state">
                <p>📭 Nenhum dado disponível para esta data</p>
                <small>Os dados estarão disponíveis após consolidação diária (próximo dia às 00:00)</small>
            </div>
        <?php else: ?>
            <!-- Summary Cards -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="label">Acessos de Página</div>
                    <div class="value"><?= $analytics['summary']['total_page_loads'] ?? 0; ?></div>
                </div>
                <div class="summary-card">
                    <div class="label">Cliques em Anúncios</div>
                    <div class="value"><?= $analytics['summary']['total_ad_clicks'] ?? 0; ?></div>
                </div>
                <div class="summary-card">
                    <div class="label">Contatos WhatsApp</div>
                    <div class="value"><?= $analytics['summary']['total_whatsapp_clicks'] ?? 0; ?></div>
                </div>
                <div class="summary-card">
                    <div class="label">Visitas à Loja</div>
                    <div class="value"><?= $analytics['summary']['total_store_visits'] ?? 0; ?></div>
                </div>
                <div class="summary-card">
                    <div class="label">Visualizações Serviço</div>
                    <div class="value"><?= $analytics['summary']['total_service_views'] ?? 0; ?></div>
                </div>
                <div class="summary-card">
                    <div class="label">Contatos Serviço</div>
                    <div class="value"><?= $analytics['summary']['total_service_contacts'] ?? 0; ?></div>
                </div>
            </div>

            <?php if ($detailUser): ?>
                <!-- Detalhamento de Usuário Específico -->
                <div class="detail-section">
                    <div class="back-link">
                        <a href="?date=<?= urlencode($selectedDate); ?>">← Voltar à lista</a>
                    </div>
                    <h2>👤 <?= e($detailUser['name']); ?> (ID: <?= $detailUser['user_id']; ?>)</h2>
                    
                    <div class="stat-row">
                        <span class="stat-label">Email:</span>
                        <span><?= e($detailUser['email']); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Total de Interações:</span>
                        <span class="stat-value"><?= $detailUser['total_interactions']; ?></span>
                    </div>

                    <?php if (!empty($detailUser['ads'])): ?>
                        <h3 style="margin-top: 20px; margin-bottom: 10px;">📢 Anúncios</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Visualizações</th>
                                    <th>Cliques</th>
                                    <th>WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailUser['ads'] as $ad): ?>
                                    <tr>
                                        <td><?= e($ad['title']); ?></td>
                                        <td><?= $ad['views'] ?? 0; ?></td>
                                        <td><?= $ad['clicks'] ?? 0; ?></td>
                                        <td><span class="badge"><?= $ad['whatsapp_clicks'] ?? 0; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if (!empty($detailUser['services'])): ?>
                        <h3 style="margin-top: 20px; margin-bottom: 10px;">🔧 Serviços</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Serviço</th>
                                    <th>Visualizações</th>
                                    <th>Contatos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailUser['services'] as $service): ?>
                                    <tr>
                                        <td><?= e($service['title']); ?></td>
                                        <td><?= $service['views'] ?? 0; ?></td>
                                        <td><span class="badge"><?= $service['contacts'] ?? 0; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if ($detailUser['store_visits'] > 0): ?>
                        <h3 style="margin-top: 20px; margin-bottom: 10px;">🏬 Loja</h3>
                        <div class="stat-row">
                            <span class="stat-label">Visitas à Vitrine:</span>
                            <span class="stat-value"><?= $detailUser['store_visits']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Ranking de Usuários -->
                <div class="users-section">
                    <h2>🏆 Ranking de Usuários (Por Interações)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Interações Totais</th>
                                <th>Cliques Anúncios</th>
                                <th>WhatsApp</th>
                                <th>Visitas Loja</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $users = $analytics['users'];
                            usort($users, function($a, $b) {
                                return $b['total_interactions'] <=> $a['total_interactions'];
                            });
                            
                            foreach (array_slice($users, 0, 50) as $userData): 
                            ?>
                                <tr>
                                    <td><?= e($userData['name']); ?></td>
                                    <td><?= e($userData['email']); ?></td>
                                    <td><strong><?= $userData['total_interactions']; ?></strong></td>
                                    <td><?= array_sum(array_column($userData['ads'], 'clicks')); ?></td>
                                    <td><?= array_sum(array_column($userData['ads'], 'whatsapp_clicks')); ?></td>
                                    <td><?= $userData['store_visits']; ?></td>
                                    <td>
                                        <a href="?date=<?= urlencode($selectedDate); ?>&user_id=<?= $userData['user_id']; ?>">
                                            Ver Detalhes
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        function updateDate() {
            const date = document.getElementById('dateSelect').value;
            window.location.href = `?date=${date}`;
        }

        function exportCSV() {
            const date = '<?= $selectedDate; ?>';
            window.location.href = `/propagandas/analytics/export.php?date=${date}`;
        }
    </script>
</body>
</html>
