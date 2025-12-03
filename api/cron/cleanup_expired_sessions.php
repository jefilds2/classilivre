<?php
// api/cron/cleanup_expired_sessions.php - Limpa sessões expiradas do banco

declare(strict_types=1);

// ✅ SINCRONIZAÇÃO: Garantir UTC em qualquer servidor/timezone
date_default_timezone_set('UTC');

require_once __DIR__ . '/../../sqlconnect.php';
require_once __DIR__ . '/../helpers_mail.php';
require_once __DIR__ . '/../../login_system/session_manager.php';

// ✅ SINCRONIZAÇÃO: Forçar MySQL usar UTC também
$pdo = db();
$pdo->exec("SET SESSION time_zone = '+00:00'");

// Verificar se é chamada via CLI ou via HTTP com token secreto
$isCliCall = php_sapi_name() === 'cli';
$isValidHttpCall = (
    $_SERVER['REQUEST_METHOD'] === 'POST' && 
    !empty($_POST['cron_token']) && 
    $_POST['cron_token'] === (getenv('CRON_TOKEN') ?: 'secret-cron-token')
);

if (!$isCliCall && !$isValidHttpCall) {
    http_response_code(401);
    exit('Unauthorized');
}

try {
    $pdo = db();
    $deleted = cleanup_expired_sessions($pdo);
    
    if ($isCliCall) {
        echo "[" . date('Y-m-d H:i:s') . "] Sessões expiradas limpas: $deleted\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'deleted_sessions' => $deleted]);
    }
} catch (Throwable $e) {
    error_log('[CRON][cleanup_sessions] ' . $e->getMessage());
    
    if ($isCliCall) {
        echo "[" . date('Y-m-d H:i:s') . "] Erro: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
