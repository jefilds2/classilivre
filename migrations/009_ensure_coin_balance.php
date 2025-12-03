<?php
// migrations/009_ensure_coin_balance.php - Garantir coluna coin_balance existe

require_once __DIR__ . '/../sqlconnect.php';

echo "[009] Verificando coluna coin_balance...\n";

try {
    $pdo = db();
    
    // Verificar se a coluna existe
    $columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'coin_balance'");
    if ($columnCheck && $columnCheck->rowCount() > 0) {
        echo "[009] ✓ Coluna coin_balance já existe\n";
    } else {
        echo "[009] Criando coluna coin_balance...\n";
        $pdo->exec('ALTER TABLE users ADD COLUMN coin_balance INT NOT NULL DEFAULT 0 AFTER is_premium');
        echo "[009] ✓ Coluna coin_balance criada com sucesso\n";
    }
    
} catch (Exception $e) {
    echo "[009] ✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[009] Migração concluída\n";
?>
