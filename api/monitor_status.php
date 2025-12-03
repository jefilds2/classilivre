<?php
/**
 * Monitor de Processos e Status do Sistema
 * Acesse via: https://seu-dominio.com/api/monitor_status.php
 */

// Impedir output buffering para ver resultado em tempo real
if (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/plain; charset=utf-8');

// Função para obter informações do servidor
function getServerInfo() {
    $info = [];
    
    // Informações PHP
    $info['PHP Version'] = phpversion();
    $info['PHP Memory Limit'] = ini_get('memory_limit');
    $info['PHP Max Execution Time'] = ini_get('max_execution_time');
    
    // Informações do Sistema
    $info['Server OS'] = php_uname();
    $info['Current Time'] = date('Y-m-d H:i:s');
    $info['Server Uptime'] = shell_exec('uptime 2>/dev/null') ?: 'N/A (Windows Server)';
    
    return $info;
}

function getProcessInfo() {
    $processes = [];
    
    // Tenta obter processos PHP
    $phpProcesses = @shell_exec('ps aux | grep php | grep -v grep | wc -l 2>/dev/null');
    $processes['PHP Processes'] = $phpProcesses ?: 'N/A (Windows Server)';
    
    // Tenta obter processos MySQL
    $mysqlProcesses = @shell_exec('ps aux | grep mysql | grep -v grep | wc -l 2>/dev/null');
    $processes['MySQL Processes'] = $mysqlProcesses ?: 'N/A (Windows Server)';
    
    // Tenta obter CPU
    $cpuUsage = @shell_exec('top -bn1 | grep "Cpu(s)" | awk \'{print $2}\' 2>/dev/null');
    $processes['CPU Usage'] = $cpuUsage ?: 'N/A (Windows Server)';
    
    // Tenta obter Memory
    $memUsage = @shell_exec('free -m | awk \'NR==2{printf "%s/%sMB (%.2f%%)", $3,$2,$3*100/$2 }\' 2>/dev/null');
    $processes['Memory Usage'] = $memUsage ?: 'N/A (Windows Server)';
    
    return $processes;
}

function getDatabaseInfo() {
    $info = [];
    
    // Conectar ao banco
    try {
        require_once __DIR__ . '/../sqlconnect.php';
        
        if ($pdo) {
            // Informações do banco
            $stmt = $pdo->query('SELECT VERSION() as version');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $info['MySQL Version'] = $result['version'] ?? 'N/A';
            
            // Número de conexões
            $stmt = $pdo->query('SHOW PROCESSLIST');
            $connections = $stmt->fetchAll();
            $info['Active Connections'] = count($connections);
            
            // Tamanho do banco
            $stmt = $pdo->query('SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $info['Database Size'] = $result['size_mb'] . ' MB';
            
            // Fila de emails (se tabela existir)
            try {
                $stmt = $pdo->query('SELECT COUNT(*) as total FROM email_queue WHERE status = "pending"');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $info['Pending Emails in Queue'] = $result['total'] ?? 'N/A';
            } catch (Exception $e) {
                $info['Email Queue'] = 'Table not found';
            }
            
            // Anúncios expirados
            try {
                $stmt = $pdo->query('SELECT COUNT(*) as total FROM anuncios WHERE status = "expired"');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $info['Expired Ads'] = $result['total'] ?? 'N/A';
            } catch (Exception $e) {
                $info['Expired Ads'] = 'N/A';
            }
            
        } else {
            $info['MySQL Status'] = 'Connection Failed';
        }
    } catch (Exception $e) {
        $info['MySQL Status'] = 'Error: ' . $e->getMessage();
    }
    
    return $info;
}

// Output
echo "═══════════════════════════════════════════════════════════════\n";
echo "               CLASSILIVRE - MONITOR DE STATUS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Informações do Servidor
echo "🖥️  INFORMAÇÕES DO SERVIDOR\n";
echo "───────────────────────────────────────────────────────────────\n";
$serverInfo = getServerInfo();
foreach ($serverInfo as $key => $value) {
    printf("%-30s : %s\n", $key, trim($value));
}

echo "\n";

// Informações de Processos
echo "⚙️  PROCESSOS E SISTEMA\n";
echo "───────────────────────────────────────────────────────────────\n";
$processInfo = getProcessInfo();
foreach ($processInfo as $key => $value) {
    printf("%-30s : %s\n", $key, trim($value));
}

echo "\n";

// Informações do Banco de Dados
echo "🗄️  BANCO DE DADOS\n";
echo "───────────────────────────────────────────────────────────────\n";
$dbInfo = getDatabaseInfo();
foreach ($dbInfo as $key => $value) {
    printf("%-30s : %s\n", $key, $value);
}

echo "\n";

// Status dos CRON Jobs
echo "⏰ STATUS DOS CRON JOBS (Baseado em Lock Files)\n";
echo "───────────────────────────────────────────────────────────────\n";

$cronJobs = [
    '/tmp/classilivre_cron_send_mail_worker.lock' => 'Send Mail Worker',
    '/tmp/classilivre_cron_check_ads_expiration.lock' => 'Check Ads Expiration',
    '/tmp/classilivre_cron_expire_services.lock' => 'Expire Services',
    '/tmp/classilivre_cron_check_premium_expiration.lock' => 'Check Premium Expiration',
    '/tmp/classilivre_cron_cleanup_expired_ads_after_15days.lock' => 'Cleanup Expired Ads',
    '/tmp/classilivre_cron_check_service_auto_renewal.lock' => 'Service Auto-Renewal'
];

foreach ($cronJobs as $lockFile => $jobName) {
    if (file_exists($lockFile)) {
        $lockAge = time() - filemtime($lockFile);
        $status = $lockAge > 600 ? '⚠️  STUCK' : '🔄 RUNNING';
        printf("%-35s : %s (Age: %ds)\n", $jobName, $status, $lockAge);
    } else {
        printf("%-35s : ✅ Idle\n", $jobName);
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Última atualização: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
?>
