<?php
// api/anuncios/denuncia.php - Recebe denúncias de anúncios (AJAX).

declare(strict_types=1);

require_once __DIR__ . '/../../login_system/middleware.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido.']);
    exit;
}

$adId = (int)($_GET['id'] ?? 0);
if ($adId <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'Anúncio inválido.']);
    exit;
}

$currentUser = current_user();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['message' => 'Entre na sua conta para denunciar um anúncio.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '[]', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

if (!validate_csrf($payload['csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode(['message' => 'Sessão expirada. Recarregue a página e tente novamente.']);
    exit;
}

$allowedReasons = [
    'informations_false' => 'Informações falsas ou enganosas',
    'photo_inadequate' => 'Foto indevida ou ofensiva',
    'scam' => 'Golpe / veículo não existe',
    'duplicated' => 'Anúncio duplicado',
    'other' => 'Outro motivo',
];

$reasonKey = (string)($payload['reason'] ?? '');
$reason = $allowedReasons[$reasonKey] ?? null;
$details = trim((string)($payload['details'] ?? ''));
$evidence = trim((string)($payload['evidence'] ?? ''));

if (!$reason) {
    http_response_code(422);
    echo json_encode(['message' => 'Selecione um motivo para a denúncia.']);
    exit;
}

if ($details !== '' && mb_strlen($details) < 10) {
    http_response_code(422);
    echo json_encode(['message' => 'Conte em pelo menos 10 caracteres o que aconteceu.']);
    exit;
}

if ($evidence !== '' && !filter_var($evidence, FILTER_VALIDATE_URL)) {
    http_response_code(422);
    echo json_encode(['message' => 'Informe um link válido para o campo de evidências.']);
    exit;
}

$adStmt = db()->prepare('SELECT id, status FROM ads WHERE id = :id LIMIT 1');
$adStmt->execute([':id' => $adId]);
$adExists = $adStmt->fetch();

if (!$adExists) {
    http_response_code(404);
    echo json_encode(['message' => 'Anúncio não encontrado.']);
    exit;
}

$duplicateCheck = db()->prepare('SELECT id FROM ad_reports WHERE ad_id = :ad AND reporter_user_id = :user AND status IN ("open", "under_review") LIMIT 1');
$duplicateCheck->execute([
    ':ad' => $adId,
    ':user' => (int)$currentUser['id'],
]);

if ($duplicateCheck->fetch()) {
    http_response_code(409);
    echo json_encode(['message' => 'Você já possui uma denúncia em análise para este anúncio.']);
    exit;
}

$insert = db()->prepare('INSERT INTO ad_reports (ad_id, reporter_user_id, status, reason, description, evidence_url, created_at, updated_at)
    VALUES (:ad, :user, "open", :reason, :description, :evidence_url, :created_at, :updated_at)');
$now = now();
$insert->execute([
    ':ad' => $adId,
    ':user' => (int)$currentUser['id'],
    ':reason' => $reason,
    ':description' => $details !== '' ? $details : null,
    ':evidence_url' => $evidence !== '' ? $evidence : null,
    ':created_at' => $now,
    ':updated_at' => $now,
]);

$reportId = (int)db()->lastInsertId();
record_audit((int)$currentUser['id'], 'report_created', 'ad_reports', $reportId, ['reason' => $reason]);

echo json_encode([
    'success' => true,
    'message' => 'Recebemos sua denúncia. Obrigado por colaborar!',
]);
