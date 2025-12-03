<?php
/**
 * Dashboard Visual de Monitoramento Semanal
 * Mostra gráficos e histórico de 28 dias
 * Registra dados a cada acesso
 */

// Primeiro, registrar os dados atuais
$logDir = __DIR__ . '/../logs/monitor';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Coletar dados atuais
$data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'date_only' => date('Y-m-d'),
    'time_only' => date('H:i:s'),
    'php_processes' => (int)trim(shell_exec('ps aux 2>/dev/null | grep php | grep -v grep | wc -l') ?? 0),
    'mysql_processes' => (int)trim(shell_exec('ps aux 2>/dev/null | grep mysql | grep -v grep | wc -l') ?? 0),
    'cpu_usage' => trim(shell_exec('top -bn1 2>/dev/null | grep "Cpu(s)" | awk \'{print $2}\' | cut -d\'%\' -f1') ?? 0),
    'memory_usage' => trim(shell_exec('free -h 2>/dev/null | awk \'NR==2{print $3 "/" $2}\' | head -1') ?? 'N/A'),
];

// Tentar coletar dados do banco
try {
    require_once __DIR__ . '/../sqlconnect.php';
    
    if (isset($pdo) && $pdo) {
        // Conexões ativas
        $stmt = $pdo->query('SHOW PROCESSLIST');
        $data['active_connections'] = $stmt->rowCount();
        
        // Emails pendentes
        try {
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM email_queue WHERE status = "pending"');
            if ($stmt) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $data['pending_emails'] = (int)($result['count'] ?? 0);
            }
        } catch (Exception $e) {
            $data['pending_emails'] = 0;
        }
        
        // Anúncios expirados
        try {
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM anuncios WHERE status = "expired"');
            if ($stmt) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $data['expired_ads'] = (int)($result['count'] ?? 0);
            }
        } catch (Exception $e) {
            $data['expired_ads'] = 0;
        }
    }
} catch (Exception $e) {
    // Silenciar erros do banco
}

// Status dos CRON jobs
$data['cron_status'] = [];
$cronJobs = [
    'send_mail_worker' => '/tmp/classilivre_cron_send_mail_worker.lock',
    'check_ads_expiration' => '/tmp/classilivre_cron_check_ads_expiration.lock',
    'expire_services' => '/tmp/classilivre_cron_expire_services.lock',
    'check_premium_expiration' => '/tmp/classilivre_cron_check_premium_expiration.lock',
    'cleanup_expired_ads' => '/tmp/classilivre_cron_cleanup_expired_ads_after_15days.lock',
    'service_auto_renewal' => '/tmp/classilivre_cron_check_service_auto_renewal.lock'
];

foreach ($cronJobs as $name => $lockFile) {
    if (file_exists($lockFile)) {
        $lockAge = time() - filemtime($lockFile);
        $data['cron_status'][$name] = [
            'running' => $lockAge < 600,
            'lock_age_seconds' => $lockAge
        ];
    } else {
        $data['cron_status'][$name] = [
            'running' => false,
            'lock_age_seconds' => 0
        ];
    }
}

// Carregar histórico e adicionar novo registro
$currentWeekFile = $logDir . '/weekly_monitor_' . date('Y-W') . '.json';
$history = [];
if (file_exists($currentWeekFile)) {
    $history = json_decode(file_get_contents($currentWeekFile), true) ?? [];
}

// Adicionar novo registro
$history[] = $data;

// Limitar a 672 registros (1 por hora, 28 dias)
if (count($history) > 672) {
    $history = array_slice($history, -672);
}

// Salvar atualizado
file_put_contents($currentWeekFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Carregar todas as semanas para histórico de 28 dias
$allData = [];
for ($i = 0; $i < 4; $i++) {
    $weekNum = date('Y-W', strtotime("-$i weeks"));
    $file = $logDir . '/weekly_monitor_' . $weekNum . '.json';
    if (file_exists($file)) {
        $weekData = json_decode(file_get_contents($file), true) ?? [];
        $allData = array_merge($weekData, $allData);
    }
}

// Preparar dados para gráficos
$timestamps = [];
$phpProcesses = [];
$cpuUsage = [];
$pendingEmails = [];
$expiredAds = [];

foreach ($allData as $record) {
    $timestamps[] = $record['timestamp'];
    $phpProcesses[] = $record['php_processes'] ?? 0;
    $cpuUsage[] = (float)($record['cpu_usage'] ?? 0);
    $pendingEmails[] = $record['pending_emails'] ?? 0;
    $expiredAds[] = $record['expired_ads'] ?? 0;
}
$statsPhp = [
    'current' => count($phpProcesses) > 0 ? end($phpProcesses) : 0,
    'avg' => count($phpProcesses) > 0 ? round(array_sum($phpProcesses) / count($phpProcesses), 2) : 0,
    'max' => count($phpProcesses) > 0 ? max($phpProcesses) : 0,
    'min' => count($phpProcesses) > 0 ? min($phpProcesses) : 0,
];

$statsCpu = [
    'current' => count($cpuUsage) > 0 ? end($cpuUsage) : 0,
    'avg' => count($cpuUsage) > 0 ? round(array_sum($cpuUsage) / count($cpuUsage), 2) : 0,
    'max' => count($cpuUsage) > 0 ? max($cpuUsage) : 0,
];

$statsEmails = [
    'current' => count($pendingEmails) > 0 ? end($pendingEmails) : 0,
    'avg' => count($pendingEmails) > 0 ? round(array_sum($pendingEmails) / count($pendingEmails), 2) : 0,
    'max' => count($pendingEmails) > 0 ? max($pendingEmails) : 0,
];

$statsAds = [
    'current' => count($expiredAds) > 0 ? end($expiredAds) : 0,
    'max' => count($expiredAds) > 0 ? max($expiredAds) : 0,
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classilivre - Dashboard de Monitoramento</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .refresh-btn:hover {
            background: #5568d3;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-value.good {
            color: #27ae60;
        }
        
        .stat-value.warning {
            color: #f39c12;
        }
        
        .stat-value.danger {
            color: #e74c3c;
        }
        
        .stat-details {
            font-size: 12px;
            color: #999;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .stat-details span {
            display: flex;
            justify-content: space-between;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            height: 400px;
        }
        
        .chart-container h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .chart-canvas {
            position: relative;
            height: calc(100% - 40px);
        }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .table-container h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }
        
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .footer {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Dashboard de Monitoramento - Classilivre</h1>
            <p>Acompanhamento contínuo de processos, recursos e filas | Atualizado: <?php echo date('d/m/Y H:i:s'); ?></p>
            <form method="POST" style="display: inline;">
                <button class="refresh-btn" type="button" onclick="location.reload()">🔄 Atualizar Agora</button>
            </form>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Processos PHP</h3>
                <div class="stat-value <?php echo $statsPhp['current'] <= 10 ? 'good' : ($statsPhp['current'] <= 20 ? 'warning' : 'danger'); ?>">
                    <?php echo $statsPhp['current']; ?>
                </div>
                <div class="stat-details">
                    <span>Média: <strong><?php echo $statsPhp['avg']; ?></strong></span>
                    <span>Máx: <strong><?php echo $statsPhp['max']; ?></strong></span>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>🔥 Uso de CPU</h3>
                <div class="stat-value <?php echo $statsCpu['current'] <= 30 ? 'good' : ($statsCpu['current'] <= 70 ? 'warning' : 'danger'); ?>">
                    <?php echo $statsCpu['current']; ?>%
                </div>
                <div class="stat-details">
                    <span>Média: <strong><?php echo $statsCpu['avg']; ?>%</strong></span>
                    <span>Máx: <strong><?php echo $statsCpu['max']; ?>%</strong></span>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>📧 Emails Pendentes</h3>
                <div class="stat-value <?php echo $statsEmails['current'] == 0 ? 'good' : ($statsEmails['current'] <= 50 ? 'warning' : 'danger'); ?>">
                    <?php echo $statsEmails['current']; ?>
                </div>
                <div class="stat-details">
                    <span>Média: <strong><?php echo $statsEmails['avg']; ?></strong></span>
                    <span>Máx: <strong><?php echo $statsEmails['max']; ?></strong></span>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>📋 Anúncios Expirados</h3>
                <div class="stat-value">
                    <?php echo $statsAds['current']; ?>
                </div>
                <div class="stat-details">
                    <span>Máximo: <strong><?php echo $statsAds['max']; ?></strong></span>
                </div>
            </div>
        </div>
        
        <div class="charts-grid">
            <div class="chart-container">
                <h3>📈 Processos PHP - Últimas 28 dias</h3>
                <div class="chart-canvas">
                    <canvas id="phpChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>🔥 CPU Usage - Últimas 28 dias</h3>
                <div class="chart-canvas">
                    <canvas id="cpuChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>📧 Fila de Emails - Últimas 28 dias</h3>
                <div class="chart-canvas">
                    <canvas id="emailChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>📋 Anúncios Expirados - Últimas 28 dias</h3>
                <div class="chart-canvas">
                    <canvas id="adsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <h3>📊 Histórico Detalhado - Últimas 24 horas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Processos PHP</th>
                        <th>MySQL</th>
                        <th>CPU %</th>
                        <th>Emails Pendentes</th>
                        <th>Anúncios Expirados</th>
                        <th>Status CRONs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Mostrar apenas últimas 24 horas
                    $last24h = array_slice($history, -24);
                    foreach (array_reverse($last24h) as $record):
                    ?>
                    <tr>
                        <td><?php echo $record['timestamp']; ?></td>
                        <td>
                            <span class="status-badge <?php echo $record['php_processes'] <= 10 ? 'status-ok' : ($record['php_processes'] <= 20 ? 'status-warning' : 'status-danger'); ?>">
                                <?php echo $record['php_processes']; ?>
                            </span>
                        </td>
                        <td><?php echo $record['mysql_processes'] ?? 0; ?></td>
                        <td><?php echo $record['cpu_usage']; ?>%</td>
                        <td>
                            <span class="status-badge <?php echo $record['pending_emails'] == 0 ? 'status-ok' : ($record['pending_emails'] <= 50 ? 'status-warning' : 'status-danger'); ?>">
                                <?php echo $record['pending_emails']; ?>
                            </span>
                        </td>
                        <td><?php echo $record['expired_ads']; ?></td>
                        <td>
                            <?php
                            $runningCrons = 0;
                            foreach ($record['cron_status'] as $status) {
                                if ($status['running']) $runningCrons++;
                            }
                            echo '<span class="status-badge ' . ($runningCrons == 0 ? 'status-ok' : 'status-warning') . '">' . $runningCrons . ' running</span>';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>💡 Os dados são registrados automaticamente a cada acesso. Recarregue a página para capturar novos dados.</p>
            <p>Arquivo de log: <code><?php echo $currentWeekFile; ?></code> | Total de registros: <strong><?php echo count($history); ?></strong></p>
        </div>
    </div>
    
    <script>
        // Dados para gráficos
        const timestamps = <?php echo json_encode($timestamps); ?>;
        const phpProcesses = <?php echo json_encode($phpProcesses); ?>;
        const cpuUsage = <?php echo json_encode($cpuUsage); ?>;
        const pendingEmails = <?php echo json_encode($pendingEmails); ?>;
        const expiredAds = <?php echo json_encode($expiredAds); ?>;
        
        // Configuração comum dos gráficos
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#999'
                    },
                    grid: {
                        color: '#eee'
                    }
                },
                x: {
                    ticks: {
                        color: '#999'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        };
        
        // Gráfico de Processos PHP
        new Chart(document.getElementById('phpChart'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Processos PHP',
                    data: phpProcesses,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: chartOptions
        });
        
        // Gráfico de CPU
        new Chart(document.getElementById('cpuChart'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'CPU %',
                    data: cpuUsage,
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: chartOptions
        });
        
        // Gráfico de Emails
        new Chart(document.getElementById('emailChart'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Emails Pendentes',
                    data: pendingEmails,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: chartOptions
        });
        
        // Gráfico de Anúncios
        new Chart(document.getElementById('adsChart'), {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Anúncios Expirados',
                    data: expiredAds,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: chartOptions
        });
        
        // Auto-atualizar a cada 1 hora
        setTimeout(() => {
            location.reload();
        }, 3600000);
    </script>
</body>
</html>
