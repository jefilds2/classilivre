<?php
/**
 * logs/ExpiredAdsLogger.php
 * Classe para registrar logs de anúncios expirados e excluídos
 * Cria logs estruturados por data em formato JSON
 */

declare(strict_types=1);

class ExpiredAdsLogger
{
    private string $logDir;
    private string $todayLogFile;

    public function __construct()
    {
        // Base: logs/anuncios_excluidos/
        $this->logDir = __DIR__ . '/anuncios_excluidos';
        
        // Garantir que a pasta existe
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }

        // Log do dia atual: logs/anuncios_excluidos/YYYY-MM-DD.log
        $this->todayLogFile = $this->logDir . '/' . date('Y-m-d') . '.log';
    }

    /**
     * Registra evento de limpeza
     */
    public function logEvent(string $eventType, string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $eventType,
            'message' => $message,
            'context' => $context,
        ];

        $this->appendToLog(json_encode($logEntry, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Registra anúncios deletados após 15 dias
     */
    public function logAdsDeletedAfter15Days(int $totalDeleted, array $ads): void
    {
        foreach ($ads as $ad) {
            $this->logAdDeleted(
                (int)$ad['id'],
                $ad['title'],
                (int)$ad['user_id'],
                'CLEANUP_15DAYS',
                [
                    'expires_at' => $ad['expires_at'],
                    'marked_expired_at' => $ad['updated_at'],
                    'reason' => 'Anúncio expirado há mais de 15 dias',
                ]
            );
        }

        // Resumo final
        $summary = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'CLEANUP_SUMMARY',
            'total_ads_deleted' => $totalDeleted,
            'message' => "$totalDeleted anúncios excluídos após 15 dias de expiração",
        ];
        $this->appendToLog(json_encode($summary, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Registra um anúncio deletado individualmente
     */
    public function logAdDeleted(
        int $adId,
        string $adTitle,
        int $userId,
        string $reason = 'MANUAL_DELETE',
        array $meta = []
    ): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'AD_DELETED',
            'ad_id' => $adId,
            'ad_title' => $adTitle,
            'user_id' => $userId,
            'reason' => $reason,
            'meta' => $meta,
        ];

        $this->appendToLog(json_encode($logEntry, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Registra erro durante a limpeza
     */
    public function logError(string $operation, string $errorMessage, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'ERROR',
            'operation' => $operation,
            'message' => $errorMessage,
            'context' => $context,
        ];

        $this->appendToLog(json_encode($logEntry, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Registra auto-renovação de anúncio
     */
    public function logAutoRenewal(int $adId, int $userId, string $status): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => 'AUTO_RENEWAL',
            'ad_id' => $adId,
            'user_id' => $userId,
            'status' => $status,
        ];

        $this->appendToLog(json_encode($logEntry, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Lê logs do dia atual
     */
    public function getTodayLogs(): array
    {
        if (!file_exists($this->todayLogFile)) {
            return [];
        }

        $logs = [];
        $lines = file($this->todayLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded !== null) {
                $logs[] = $decoded;
            }
        }

        return $logs;
    }

    /**
     * Lê logs de um dia específico (formato: Y-m-d)
     */
    public function getLogsByDate(string $date): array
    {
        $logFile = $this->logDir . '/' . $date . '.log';

        if (!file_exists($logFile)) {
            return [];
        }

        $logs = [];
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded !== null) {
                $logs[] = $decoded;
            }
        }

        return $logs;
    }

    /**
     * Retorna caminho da pasta de logs
     */
    public function getLogDirectory(): string
    {
        return $this->logDir;
    }

    /**
     * Retorna caminho do arquivo de log do dia
     */
    public function getTodayLogFile(): string
    {
        return $this->todayLogFile;
    }

    /**
     * Escreve linha no arquivo de log
     */
    private function appendToLog(string $logEntry): void
    {
        $line = $logEntry . PHP_EOL;
        @file_put_contents($this->todayLogFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Retorna estatísticas dos logs
     */
    public function getStatistics(): array
    {
        $stats = [
            'today_file' => $this->todayLogFile,
            'logs_directory' => $this->logDir,
            'today_logs_count' => count($this->getTodayLogs()),
            'all_log_files' => [],
        ];

        // Listar todos os arquivos de log
        if (is_dir($this->logDir)) {
            $files = scandir($this->logDir);
            foreach ($files as $file) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}\.log$/', $file)) {
                    $filePath = $this->logDir . '/' . $file;
                    $stats['all_log_files'][] = [
                        'date' => str_replace('.log', '', $file),
                        'file' => $file,
                        'size' => filesize($filePath),
                        'lines' => count(file($filePath)),
                    ];
                }
            }
        }

        return $stats;
    }
}
