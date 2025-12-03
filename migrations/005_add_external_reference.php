<?php
/**
 * Migration 005: Adicionar coluna external_reference na tabela ads
 * 
 * Esta coluna é usada para rastrear o ID do pagamento do Mercado Pago
 * Formato: ad_payment_{userId}_{timestamp}
 */

require_once __DIR__ . '/../sqlconnect.php';

try {
    $pdo = db();
    
    // Verificar se coluna já existe
    $checkStmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'external_reference'");
    
    if ($checkStmt->rowCount() > 0) {
        echo "[005] ✓ Coluna 'external_reference' já existe\n";
        exit(0);
    }
    
    // Adicionar coluna
    $pdo->exec("
        ALTER TABLE ads 
        ADD COLUMN external_reference VARCHAR(255) NULL UNIQUE AFTER status
    ");
    
    echo "[005] ✓ Coluna 'external_reference' criada com sucesso\n";
    exit(0);
    
} catch (Exception $e) {
    error_log("[005] Erro: " . $e->getMessage());
    echo "[005] ✗ Erro ao criar coluna: " . $e->getMessage() . "\n";
    exit(1);
}
?>
