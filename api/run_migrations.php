<?php
/**
 * Script para executar todas as migrations
 * Acesso: /api/run_migrations.php
 */

require_once __DIR__ . '/../sqlconnect.php';

echo "<pre style='background: #f4f4f4; padding: 20px; font-family: monospace;'>";
echo "<h2>Executando Migrations...</h2>\n\n";

// Listar todas as migration files
$migrationsDir = __DIR__ . '/../migrations';
$migrations = glob($migrationsDir . '/*.php');
sort($migrations);

$executed = [];
foreach ($migrations as $file) {
    $filename = basename($file);
    echo "Processando: $filename\n";
    
    try {
        include $file;
        $executed[] = $filename;
    } catch (Throwable $e) {
        echo "  ✗ ERRO: " . $e->getMessage() . "\n";
    }
}

echo "\n<h3>✅ Migrations Executadas:</h3>\n";
foreach ($executed as $f) {
    echo "  ✓ $f\n";
}

echo "\n<h3>Verificando Tabelas...</h3>\n";

// Verificar se coin_transactions existe
$pdo = db();
$tables = $pdo->query("SHOW TABLES LIKE 'coin_transactions'")->fetchAll();
echo "Tabela 'coin_transactions': " . (count($tables) > 0 ? "✓ Existe" : "✗ NÃO EXISTE") . "\n";

// Verificar coluna coin_balance
$columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'coin_balance'")->fetchAll();
echo "Coluna 'users.coin_balance': " . (count($columns) > 0 ? "✓ Existe" : "✗ NÃO EXISTE") . "\n";

echo "\n<h3>Descrição da Tabela coin_transactions:</h3>\n";
$structure = $pdo->query("DESCRIBE coin_transactions")->fetchAll();
foreach ($structure as $col) {
    printf("  %s: %s %s\n", $col['Field'], $col['Type'], ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL'));
}

echo "\n✅ Migrations completas!\n";
echo "</pre>";
