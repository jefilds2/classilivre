<?php
/**
 * Sistema de Log de Monitoramento Semanal
 * Registra dados diários de processos, CPU, memória e fila de emails
 */

header('Content-Type: application/json; charset=utf-8');

$logDir = __DIR__ . '/../logs/monitor';
$logFile = $logDir . '/weekly_monitor_' . date('Y-W') . '.json';

// Criar diretório se não existir
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
    } else {
        $data['database_status'] = 'Connection Failed';
    }
} catch (Exception $e) {
    $data['database_status'] = 'Error: ' . $e->getMessage();
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

// Carregar histórico existente
$history = [];
if (file_exists($logFile)) {
    $history = json_decode(file_get_contents($logFile), true) ?? [];
}

// Adicionar novo registro
$history[] = $data;

// Limitar a 672 registros (1 por hora, 28 dias)
if (count($history) > 672) {
    $history = array_slice($history, -672);
}

// Salvar atualizado
file_put_contents($logFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Se for requisição AJAX, retornar JSON
if (isset($_GET['json'])) {
    echo json_encode([
        'success' => true,
        'current' => $data,
        'log_file' => $logFile,
        'records_count' => count($history)
    ]);
} else {
    // Senão, redirecionar para dashboard
    header('Location: monitor_dashboard.php');
}
?>
