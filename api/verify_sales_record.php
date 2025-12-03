<?php
/**
 * Verifica se o registro foi criado em 'sales'
 */

$servername = '69.6.249.20';
$port = 3306;
$username = 'hg8cf561_admin';
$password = 'lZkS2LAQBWbq';
$dbname = 'hg8cf561_classiautos';

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $servername, $port, $dbname);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ VERIFICANDO REGISTROS DE VENDAS\n";
    echo "==========================================\n\n";
    
    // 1. Contar registros na tabela sales
    echo "1. Total de registros em 'sales':\n";
    $count = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
    echo "   → $count registros\n\n";
    
    // 2. Listar todos os registros
    echo "2. Últimos registros criados:\n";
    $records = $pdo->query("SELECT id, user_id, ad_id, source, created_at FROM sales ORDER BY id DESC LIMIT 5")->fetchAll();
    
    if (empty($records)) {
        echo "   Nenhum registro encontrado\n";
    } else {
        foreach ($records as $record) {
            echo "   ID {$record['id']}: user_id={$record['user_id']}, ad_id={$record['ad_id']}, source={$record['source']}, created_at={$record['created_at']}\n";
        }
    }
    
    echo "\n✅ VERIFICAÇÃO CONCLUÍDA\n";
    
} catch (Throwable $e) {
    echo "❌ ERRO:\n";
    echo $e->getMessage() . "\n";
}
?>
