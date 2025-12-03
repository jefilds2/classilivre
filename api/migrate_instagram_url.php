<?php
/**
 * Migração: Adiciona coluna instagram_url na tabela users
 * Esta migração é chamada automaticamente quando necessário
 */

require_once __DIR__ . '/../sqlconnect.php';

try {
    $pdo = db();
    
    // Verifica se a coluna já existe
    $checkColumn = $pdo->query(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = '" . DB_NAME . "_users' 
        AND TABLE_SCHEMA = '" . DB_NAME . "' 
        AND COLUMN_NAME = 'instagram_url'"
    );
    
    // Se a coluna não existe, cria
    if ($checkColumn->rowCount() === 0) {
        $pdo->exec(
            'ALTER TABLE users 
            ADD COLUMN instagram_url VARCHAR(500) NULL DEFAULT NULL 
            AFTER store_theme_config'
        );
        error_log('[migrate_instagram_url.php] Coluna instagram_url criada com sucesso.');
        echo "✅ Coluna instagram_url criada com sucesso!\n";
    } else {
        echo "✅ Coluna instagram_url já existe.\n";
    }
} catch (Exception $e) {
    error_log('[migrate_instagram_url.php] Erro: ' . $e->getMessage());
    echo "❌ Erro: " . $e->getMessage() . "\n";
    http_response_code(500);
}
?>
