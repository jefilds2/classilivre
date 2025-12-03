<?php
/**
 * logs/anuncios_expirados/ExpiredAdsLogger.php
 * 
 * Sistema de logs para rastreamento de anúncios expirados
 * Cria um arquivo por dia com registro detalhado dos eventos
 * 
 * Uso:
 * $logger = new ExpiredAdsLogger();
 * $logger->logAdsMarkedAsExpired(5);  // 5 anúncios marcados como expirados
 * $logger->logAdsDeletedAfter15Days(3);  // 3 anúncios deletados após 15 dias
 * $logger->logError('Algum erro no processo');
 */

class ExpiredAdsLogger
{
    private string $logsDir;
    private string $todayFile;
    private string $today;

    public function __construct()
    {
        $this->logsDir = __DIR__;
        $this->today = date('Y-m-d');
        $this->todayFile = $this->logsDir . '/' . $this->today . '.log';
        
        // Garantir que o arquivo existe
        if (!file_exists($this->todayFile)) {
            $this->createLogFile();
        }
    }

    /**
     * Criar arquivo de log com cabeçalho do dia
     */
    private function createLogFile(): void
    {
        $header = str_repeat('=', 80) . PHP_EOL;
        $header .= "LOGS DE ANÚNCIOS EXPIRADOS - " . $this->today . PHP_EOL;
        $header .= "Data: " . date('d/m/Y H:i:s') . PHP_EOL;
        $header .= str_repeat('=', 80) . PHP_EOL . PHP_EOL;

        $result = @file_put_contents($this->todayFile, $header);
        
        if ($result === false) {
            error_log('[ExpiredAdsLogger] Erro ao criar arquivo de log: ' . $this->todayFile);
            // Tentar criar em diretório alternativo
            $altDir = sys_get_temp_dir() . '/classilivre_logs';
            if (!is_dir($altDir)) {
                mkdir($altDir, 0777, true);
            }
            $this->todayFile = $altDir . '/' . $this->today . '.log';
            @file_put_contents($this->todayFile, $header);
        }
    }

    /**
     * Registrar anúncios marcados como expirados
     * 
     * @param int $count Quantidade de anúncios marcados
     * @param array $ads Array com detalhes dos anúncios (opcional)
     */
    public function logAdsMarkedAsExpired(int $count, array $ads = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $log = "[$timestamp] ✓ ANÚNCIOS MARCADOS COMO EXPIRADOS: $count\n";

        if (!empty($ads)) {
            foreach ($ads as $ad) {
                $log .= "  • ID #{$ad['id']}: {$ad['title']} (Expirado em: {$ad['expires_at']})\n";
            }
        }

        $log .= "\n";
        $this->writeLog($log);
    }

    /**
     * Registrar anúncios deletados após 15 dias expirados
     * 
     * @param int $count Quantidade de anúncios deletados
     * @param array $ads Array com detalhes dos anúncios (opcional)
     */
    public function logAdsDeletedAfter15Days(int $count, array $ads = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $log = "[$timestamp] 🗑️  ANÚNCIOS DELETADOS (após 15 dias expirados): $count\n";

        if (!empty($ads)) {
            foreach ($ads as $ad) {
                $log .= "  • ID #{$ad['id']}: {$ad['title']} (Status anterior: {$ad['status']} | Criado em: {$ad['created_at']})\n";
            }
        }

        $log .= "\n";
        $this->writeLog($log);
    }

    /**
     * Método auxiliar para escrever no arquivo de log com tratamento de erro
     */
    private function writeLog(string $content): void
    {
        $result = @file_put_contents($this->todayFile, $content, FILE_APPEND);
        
        if ($result === false) {
            error_log('[ExpiredAdsLogger] Erro ao escrever log em: ' . $this->todayFile);
            // Tentar em diretório alternativo
            $altDir = sys_get_temp_dir() . '/classilivre_logs';
            if (!is_dir($altDir)) {
                @mkdir($altDir, 0777, true);
            }
            $altFile = $altDir . '/' . $this->today . '.log';
            @file_put_contents($altFile, $content, FILE_APPEND);
        }
    }

    /**
     * Registrar erro no processamento
     * 
     * @param string $message Mensagem de erro
     * @param string $context Contexto do erro (opcional)
     */
    public function logError(string $message, string $context = ''): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $log = "[$timestamp] ❌ ERRO: $message\n";

        if (!empty($context)) {
            $log .= "   Contexto: $context\n";
        }

        $log .= "\n";
        $this->writeLog($log);
    }

    /**
     * Registrar event personalizado
     * 
     * @param string $event Tipo de evento
     * @param string $message Mensagem
     * @param array $details Detalhes adicionais (opcional)
     */
    public function logEvent(string $event, string $message, array $details = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $log = "[$timestamp] 📝 $event: $message\n";

        if (!empty($details)) {
            foreach ($details as $key => $value) {
                $log .= "   • $key: $value\n";
            }
        }

        $log .= "\n";
        $this->writeLog($log);
    }

    /**
     * Registrar resumo de execução
     * 
     * @param array $stats Array com estatísticas
     *   - 'ads_marked_expired' => quantidade
     *   - 'ads_deleted' => quantidade
     *   - 'errors' => quantidade
     *   - 'execution_time' => tempo em segundos
     */
    public function logSummary(array $stats): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $separator = str_repeat('-', 80) . "\n";

        $log = $separator;
        $log .= "[$timestamp] 📊 RESUMO DE EXECUÇÃO:\n";
        $log .= "   • Anúncios marcados como expirados: " . ($stats['ads_marked_expired'] ?? 0) . "\n";
        $log .= "   • Anúncios deletados (15+ dias): " . ($stats['ads_deleted'] ?? 0) . "\n";
        $log .= "   • Erros detectados: " . ($stats['errors'] ?? 0) . "\n";

        if (!empty($stats['execution_time'])) {
            $log .= "   • Tempo de execução: " . number_format($stats['execution_time'], 2) . "s\n";
        }

        $log .= $separator . "\n";
        $this->writeLog($log);
    }

    /**
     * Obter caminho do arquivo de log atual
     */
    public function getLogFilePath(): string
    {
        return $this->todayFile;
    }

    /**
     * Obter conteúdo do log atual
     */
    public function getLogContent(): string
    {
        if (file_exists($this->todayFile)) {
            return file_get_contents($this->todayFile);
        }
        return '';
    }

    /**
     * Listar todos os arquivos de log disponíveis
     * 
     * @return array Array com informações dos arquivos
     */
    public function listLogFiles(): array
    {
        $files = array_diff(scandir($this->logsDir), ['.', '..']);
        $logFiles = [];

        foreach ($files as $file) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}\.log$/', $file)) {
                $filePath = $this->logsDir . '/' . $file;
                $logFiles[$file] = [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'date' => substr($file, 0, 10),
                    'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                ];
            }
        }

        // Ordenar por data decrescente
        krsort($logFiles);
        return $logFiles;
    }

    /**
     * Obter logs dos últimos N dias
     * 
     * @param int $days Quantidade de dias
     * @return array Array com conteúdo dos logs
     */
    public function getLogsFromLastDays(int $days = 7): array
    {
        $logs = [];

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $file = $this->logsDir . '/' . $date . '.log';

            if (file_exists($file)) {
                $logs[$date] = file_get_contents($file);
            }
        }

        return $logs;
    }
}
?>
