<?php
/**
 * Consolidador de Analytics
 * Lê logs diários e gera resumos estruturados
 * Deve ser executado via CRON 1x por dia (ex: 23:59)
 */

class AnalyticsConsolidator {
    private static $logsDir = __DIR__ . '/logs';
    private $db;

    public function __construct() {
        try {
            require_once __DIR__ . '/../../sqlconnect.php';
            $this->db = db();
        } catch (Throwable $e) {
            throw new RuntimeException('Não foi possível conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    /**
     * Consolida logs do dia anterior e gera JSON resumido
     */
    public function consolidatePreviousDay() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $rawLogFile = self::$logsDir . "/raw_{$yesterday}.log";
        
        if (!file_exists($rawLogFile)) {
            return ['success' => false, 'message' => 'Nenhum log encontrado para ' . $yesterday];
        }

        $analytics = $this->processRawLog($rawLogFile);
        $this->saveConsolidatedAnalytics($yesterday, $analytics);
        
        return ['success' => true, 'message' => "Analytics consolidado para {$yesterday}"];
    }

    /**
     * Processa arquivo de log bruto e extrai estatísticas
     */
    private function processRawLog($logFile) {
        $events = [];
        $handles = fopen($logFile, 'r');
        
        while (($line = fgets($handles)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $event = json_decode($line, true);
            if ($event) {
                $events[] = $event;
            }
        }
        fclose($handles);

        return $this->aggregateEvents($events);
    }

    /**
     * Agrupa eventos por usuário e tipo
     */
    private function aggregateEvents($events) {
        $summary = [
            'summary' => [
                'total_page_loads' => 0,
                'total_ad_clicks' => 0,
                'total_ad_views' => 0,
                'total_whatsapp_clicks' => 0,
                'total_store_visits' => 0,
                'total_service_views' => 0,
                'total_service_contacts' => 0,
            ],
            'users' => [],
        ];

        foreach ($events as $event) {
            $type = $event['type'];
            
            switch ($type) {
                case 'page_load':
                    $summary['summary']['total_page_loads']++;
                    break;
                case 'ad_click':
                    $summary['summary']['total_ad_clicks']++;
                    $this->addUserStat($summary, (int)$event['owner_user'], 'ad_click', $event);
                    break;
                case 'ad_detail_view':
                    $summary['summary']['total_ad_views']++;
                    $this->addUserStat($summary, (int)$event['owner_user'], 'ad_view', $event);
                    break;
                case 'whatsapp_click':
                    $summary['summary']['total_whatsapp_clicks']++;
                    $this->addUserStat($summary, (int)$event['owner_user'], 'whatsapp_click', $event);
                    break;
                case 'store_visit':
                    $summary['summary']['total_store_visits']++;
                    $this->addUserStat($summary, (int)$event['store_owner'], 'store_visit', $event);
                    break;
                case 'service_view':
                    $summary['summary']['total_service_views']++;
                    $this->addUserStat($summary, (int)$event['owner_user'], 'service_view', $event);
                    break;
                case 'service_contact':
                    $summary['summary']['total_service_contacts']++;
                    $this->addUserStat($summary, (int)$event['owner_user'], 'service_contact', $event);
                    break;
            }
        }

        return $summary;
    }

    /**
     * Adiciona estatísticas de um evento ao usuário
     */
    private function addUserStat(&$summary, $userId, $eventType, $event) {
        if ($userId <= 0) return;

        if (!isset($summary['users'][$userId])) {
            $this->initializeUserStats($summary, $userId);
        }

        $user = &$summary['users'][$userId];

        switch ($eventType) {
            case 'ad_click':
                $adId = $event['ad_id'];
                if (!isset($user['ads'][$adId])) {
                    $this->initializeAdStats($user, $adId);
                }
                $user['ads'][$adId]['clicks']++;
                $user['total_interactions']++;
                break;

            case 'ad_view':
                $adId = $event['ad_id'];
                if (!isset($user['ads'][$adId])) {
                    $this->initializeAdStats($user, $adId);
                }
                $user['ads'][$adId]['views']++;
                $user['total_interactions']++;
                break;

            case 'whatsapp_click':
                $adId = $event['ad_id'];
                if (!isset($user['ads'][$adId])) {
                    $this->initializeAdStats($user, $adId);
                }
                $user['ads'][$adId]['whatsapp_clicks']++;
                $user['total_interactions']++;
                break;

            case 'store_visit':
                $user['store_visits']++;
                $user['total_interactions']++;
                break;

            case 'service_view':
                $serviceId = $event['service_id'];
                if (!isset($user['services'][$serviceId])) {
                    $this->initializeServiceStats($user, $serviceId);
                }
                $user['services'][$serviceId]['views']++;
                $user['total_interactions']++;
                break;

            case 'service_contact':
                $serviceId = $event['service_id'];
                if (!isset($user['services'][$serviceId])) {
                    $this->initializeServiceStats($user, $serviceId);
                }
                $user['services'][$serviceId]['contacts']++;
                $user['total_interactions']++;
                break;
        }
    }

    /**
     * Inicializa estrutura de dados do usuário
     */
    private function initializeUserStats(&$summary, $userId) {
        $stmt = $this->db->prepare('SELECT id, name, email FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $summary['users'][$userId] = [
            'user_id' => $userId,
            'name' => $user ? $user['name'] : 'Usuário #' . $userId,
            'email' => $user ? $user['email'] : '',
            'ads' => [],
            'services' => [],
            'store_visits' => 0,
            'total_interactions' => 0,
        ];
    }

    /**
     * Inicializa estrutura de dados do anúncio
     */
    private function initializeAdStats(&$user, $adId) {
        $stmt = $this->db->prepare('SELECT id, title FROM ads WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $adId]);
        $ad = $stmt->fetch(PDO::FETCH_ASSOC);

        $user['ads'][$adId] = [
            'ad_id' => $adId,
            'title' => $ad ? $ad['title'] : 'Anúncio #' . $adId,
            'views' => 0,
            'clicks' => 0,
            'whatsapp_clicks' => 0,
        ];
    }

    /**
     * Inicializa estrutura de dados do serviço
     */
    private function initializeServiceStats(&$user, $serviceId) {
        $user['services'][$serviceId] = [
            'service_id' => $serviceId,
            'title' => 'Serviço #' . $serviceId,
            'views' => 0,
            'contacts' => 0,
        ];
    }

    /**
     * Salva analytics consolidado em JSON
     */
    private function saveConsolidatedAnalytics($date, $analytics) {
        $file = self::$logsDir . "/analytics_{$date}.json";
        file_put_contents($file, json_encode($analytics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Gera índice rápido de usuários com mais interações
     */
    public function generateUserRanking($date) {
        $analyticsFile = self::$logsDir . "/analytics_{$date}.json";
        
        if (!file_exists($analyticsFile)) {
            return [];
        }

        $analytics = json_decode(file_get_contents($analyticsFile), true);
        $users = $analytics['users'];

        usort($users, function($a, $b) {
            return $b['total_interactions'] <=> $a['total_interactions'];
        });

        $ranking = [
            'date' => $date,
            'generated_at' => date('Y-m-d H:i:s'),
            'top_users' => array_slice($users, 0, 50),
        ];

        $rankingFile = self::$logsDir . "/ranking_{$date}.json";
        file_put_contents($rankingFile, json_encode($ranking, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $ranking;
    }
}

// Se chamado via CLI/CRON
if (php_sapi_name() === 'cli' || (isset($_GET['cron']) && $_GET['cron'] === '1')) {
    require_once __DIR__ . '/../../sqlconnect.php';
    
    $consolidator = new AnalyticsConsolidator();
    $result = $consolidator->consolidatePreviousDay();
    
    if (php_sapi_name() === 'cli') {
        echo json_encode($result) . PHP_EOL;
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
