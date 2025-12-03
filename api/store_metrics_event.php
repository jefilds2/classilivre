<?php
/**
 * api/store_metrics_event.php
 * Recebe eventos de interacao da vitrine premium e grava na tabela store_metrics.
 */

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json; charset=utf-8');

function ensureStoreMetricsTable(PDO $connection): void
{
    static $tableChecked = false;
    if ($tableChecked) {
        return;
    }

    $tableChecked = true;

    try {
        $tableCheck = $connection->query("SHOW TABLES LIKE 'store_metrics'");
        if (!$tableCheck || $tableCheck->rowCount() === 0) {
            $connection->exec(
                "CREATE TABLE IF NOT EXISTS store_metrics (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    store_id INT UNSIGNED NOT NULL,
                    ad_id INT UNSIGNED NULL,
                    event_type VARCHAR(48) NOT NULL,
                    viewer_id INT UNSIGNED NULL,
                    metadata LONGTEXT NULL,
                    user_agent VARCHAR(255) NULL,
                    ip_address VARCHAR(64) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_store_created (store_id, created_at),
                    INDEX idx_store_event (store_id, event_type),
                    INDEX idx_ad_event (ad_id, event_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }
    } catch (Throwable $schemaError) {
        error_log('[store_metrics_event] Falha ao garantir tabela store_metrics: ' . $schemaError->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$storeId = isset($payload['store_id']) ? (int)$payload['store_id'] : 0;
$eventType = isset($payload['event_type']) ? trim((string)$payload['event_type']) : '';
$adId = isset($payload['ad_id']) ? (int)$payload['ad_id'] : null;
$metadata = isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : [];

if ($storeId <= 0 || $eventType === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$allowedEvents = [
    'store_visit',
    'ad_click',
    'favorite_added',
    'favorite_removed',
    'whatsapp_click',
    'phone_click',
    'route_google',
    'route_waze',
    'filter_interaction',
    'sort_interaction',
    'share_whatsapp',
    'share_instagram',
    'share_facebook',
    'share_email',
    'share_copy_link',
    'share_copy_message',
];

if (!in_array($eventType, $allowedEvents, true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Event not allowed']);
    exit;
}

$user = current_user();
$viewerId = $user ? (int)$user['id'] : null;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

try {
    $connection = db();
    ensureStoreMetricsTable($connection);
    $stmt = $connection->prepare('INSERT INTO store_metrics (store_id, ad_id, event_type, viewer_id, metadata, user_agent, ip_address, created_at) VALUES (:store_id, :ad_id, :event_type, :viewer_id, :metadata, :user_agent, :ip_address, NOW())');
    $stmt->execute([
        ':store_id' => $storeId,
        ':ad_id' => $adId ?: null,
        ':event_type' => $eventType,
        ':viewer_id' => $viewerId,
        ':metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ':user_agent' => $userAgent,
        ':ip_address' => $ipAddress,
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('[store_metrics_event] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
