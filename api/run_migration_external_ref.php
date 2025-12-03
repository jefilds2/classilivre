<?php
/**
 * api/run_migration_external_ref.php - Executa migração 005
 */

require_once __DIR__ . '/../sqlconnect.php';

header('Content-Type: application/json');

try {
    $pdo = db();
    
    // Verificar se coluna já existe
    $checkStmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'external_reference'");
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Coluna external_reference já existe',
            'status' => 'already_exists'
        ]);
        exit;
    }
    
    // Adicionar coluna
    $pdo->exec("
        ALTER TABLE ads 
        ADD COLUMN external_reference VARCHAR(255) NULL UNIQUE AFTER status
    ");
    
    echo json_encode([
        'success' => true,
        'message' => 'Coluna external_reference criada com sucesso',
        'status' => 'created'
    ]);
    exit;
    
} catch (Exception $e) {
    error_log("[Migration] Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
?>
