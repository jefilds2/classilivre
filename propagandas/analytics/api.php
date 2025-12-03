<?php
/**
 * API de Tracking
 * Endpoint para rastrear eventos via fetch() do JavaScript
 */

// Não requer autenticação
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/tracker.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de evento não especificado']);
    exit;
}

$type = $input['type'];
$data = array_diff_key($input, ['type' => true]);

AnalyticsTracker::trackEvent($type, $data);

echo json_encode(['success' => true, 'message' => 'Evento rastreado']);
