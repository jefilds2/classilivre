<?php
/**
 * Sistema de Rastreamento de Analytics
 * Localização: /propagandas/analytics/tracker.php
 * Registra acessos e eventos em arquivos JSON/LOG
 */

// Não requer autenticação para tracking
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

class AnalyticsTracker {
    private static $logsDir = __DIR__ . '/logs';
    private static $configFile = __DIR__ . '/config.php';

    public static function initialize() {
        if (!is_dir(self::$logsDir)) {
            @mkdir(self::$logsDir, 0755, true);
        }
        if (!is_dir(self::$logsDir . '/archive')) {
            @mkdir(self::$logsDir . '/archive', 0755, true);
        }
    }

    /**
     * Registra um evento no arquivo de log do dia
     */
    public static function trackEvent($type, $data = []) {
        try {
            self::initialize();
            
            $today = date('Y-m-d');
            $timestamp = date('Y-m-d H:i:s');
            $logFile = self::$logsDir . "/raw_{$today}.log";
            
            // Preparar dados do evento
            $eventData = [
                'timestamp' => $timestamp,
                'type' => $type,
                'user_id' => isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0,
                'ip' => self::getClientIP(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
            ];
            
            // Mesclar com dados específicos do evento
            $eventData = array_merge($eventData, $data);
            
            // Append ao arquivo de log (não bloqueia)
            @error_log(json_encode($eventData) . PHP_EOL, 3, $logFile);
        } catch (Throwable $e) {
            // Falha silenciosa - não interrompe o site
            // error_log('Analytics tracking error: ' . $e->getMessage());
        }
    }

    /**
     * Registra carregamento de página
     */
    public static function trackPageLoad($page) {
        self::trackEvent('page_load', [
            'page' => $page,
        ]);
    }

    /**
     * Registra clique em anúncio
     */
    public static function trackAdClick($adId, $ownerId) {
        self::trackEvent('ad_click', [
            'ad_id' => (int)$adId,
            'owner_user' => (int)$ownerId,
        ]);
    }

    /**
     * Registra visualização de detalhes do anúncio
     */
    public static function trackAdDetailView($adId, $ownerId) {
        self::trackEvent('ad_detail_view', [
            'ad_id' => (int)$adId,
            'owner_user' => (int)$ownerId,
        ]);
    }

    /**
     * Registra clique em WhatsApp
     */
    public static function trackWhatsAppClick($adId, $ownerId) {
        self::trackEvent('whatsapp_click', [
            'ad_id' => (int)$adId,
            'owner_user' => (int)$ownerId,
        ]);
    }

    /**
     * Registra visita à vitrine do usuário
     */
    public static function trackStoreVisit($storeOwnerId) {
        self::trackEvent('store_visit', [
            'store_owner' => (int)$storeOwnerId,
        ]);
    }

    /**
     * Registra visualização de serviço
     */
    public static function trackServiceView($serviceId, $ownerId) {
        self::trackEvent('service_view', [
            'service_id' => (int)$serviceId,
            'owner_user' => (int)$ownerId,
        ]);
    }

    /**
     * Registra solicitação de contato em serviço
     */
    public static function trackServiceContact($serviceId, $ownerId) {
        self::trackEvent('service_contact', [
            'service_id' => (int)$serviceId,
            'owner_user' => (int)$ownerId,
        ]);
    }

    /**
     * Retorna o IP do cliente
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
}

// API REST para tracking via fetch()
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de evento não especificado']);
        exit;
    }
    
    $type = $input['type'];
    $data = array_diff_key($input, ['type' => true]);
    
    AnalyticsTracker::trackEvent($type, $data);
    
    echo json_encode(['success' => true, 'message' => 'Evento rastreado']);
    exit;
}
