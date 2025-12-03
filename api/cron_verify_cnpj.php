<?php
// api/cron_verify_cnpj.php - Verifica CNPJs de forma assincrona
// Pode ser chamado via CRON job a cada hora
// $ curl https://seu-site.com/api/cron_verify_cnpj.php

declare(strict_types=1);

// ✅ SINCRONIZAÇÃO: Garantir UTC em qualquer servidor/timezone
date_default_timezone_set('UTC');

require_once __DIR__ . '/../sqlconnect.php';

// ✅ SINCRONIZAÇÃO: Forçar MySQL usar UTC também
$pdo = db();
$pdo->exec("SET SESSION time_zone = '+00:00'");

// Apenas aceitar chamadas POST com secret
$secret = $_ENV['CRON_SECRET'] ?? get_setting('cron_secret', null);
$providedSecret = $_POST['secret'] ?? $_GET['secret'] ?? null;

if (!$secret || $secret !== $providedSecret) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

/**
 * Verifica um CNPJ contra a API ReceitaWS
 */
function verificarCNPJReceita($cnpj) {
    $url = "https://www.receitaws.com.br/v1/cnpj/" . $cnpj;
    $context = stream_context_create([
        "http" => [
            "timeout" => 10,
            "user_agent" => "Mozilla/5.0 (compatible; PHP script)"
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        error_log("[CNPJ_VERIFY] API error for $cnpj");
        return null;  // null = couldn't verify
    }
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] === 'ERROR') {
        error_log("[CNPJ_VERIFY] CNPJ invalid: $cnpj");
        return false;  // false = invalid
    }
    error_log("[CNPJ_VERIFY] CNPJ valid: $cnpj");
    return true;  // true = valid
}

$pdo = db();
$stmt = $pdo->prepare('
    SELECT id, document_number 
    FROM users 
    WHERE document_type = "cnpj" 
    AND (cnpj_verified_at IS NULL OR cnpj_verified_at = "")
    AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    LIMIT 10
');
$stmt->execute();
$usersToVerify = $stmt->fetchAll();

$verified = 0;
$failed = 0;
$errors = [];

foreach ($usersToVerify as $user) {
    $cnpj = $user['document_number'];
    $result = verificarCNPJReceita($cnpj);
    
    if ($result === true) {
        // CNPJ válido
        $upd = $pdo->prepare('UPDATE users SET cnpj_verified_at = NOW() WHERE id = :id');
        $upd->execute([':id' => $user['id']]);
        $verified++;
        record_audit($user['id'], 'cnpj_verified', 'users', $user['id'], ['cnpj' => $cnpj]);
    } elseif ($result === false) {
        // CNPJ inválido
        $upd = $pdo->prepare('UPDATE users SET cnpj_verified_at = "invalid" WHERE id = :id');
        $upd->execute([':id' => $user['id']]);
        $failed++;
        record_audit($user['id'], 'cnpj_invalid', 'users', $user['id'], ['cnpj' => $cnpj, 'reason' => 'not_found_receita_ws']);
    }
}

echo json_encode([
    'success' => true,
    'verified' => $verified,
    'failed' => $failed,
    'timestamp' => date('c'),
]);
