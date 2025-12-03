<?php
/**
 * Teste de Conexão com Banco de Dados
 */

const DB_SERVERNAME = 'localhost';
const DB_PORT       = 3306;
const DB_USERNAME   = 'hg8cf561_admin';
const DB_PASSWORD   = 'lZkS2LAQBWbq';
const DB_NAME       = 'hg8cf561_classiautos';

echo "<h2>🔍 Teste de Conexão com Banco</h2>";
echo "<hr>";

// Teste 1: Verificar se extensão PDO existe
echo "<h3>Teste 1: Extensão PDO</h3>";
if (extension_loaded('pdo')) {
    echo "✅ Extensão PDO carregada<br>";
} else {
    echo "❌ Extensão PDO NÃO carregada<br>";
}

if (extension_loaded('pdo_mysql')) {
    echo "✅ Extensão PDO MySQL carregada<br>";
} else {
    echo "❌ Extensão PDO MySQL NÃO carregada<br>";
}

echo "<hr>";

// Teste 2: Tentar conexão
echo "<h3>Teste 2: Conectar ao Banco</h3>";

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    DB_SERVERNAME,
    DB_PORT,
    DB_NAME
);

echo "DSN: " . htmlspecialchars($dsn) . "<br>";
echo "User: " . htmlspecialchars(DB_USERNAME) . "<br><br>";

try {
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ <strong>Conexão bem-sucedida!</strong><br>";
    
    // Teste 3: Query simples
    echo "<hr>";
    echo "<h3>Teste 3: Query Simples</h3>";
    
    $result = $pdo->query("SELECT 1 as test");
    $data = $result->fetch();
    
    if ($data && isset($data['test']) && $data['test'] == 1) {
        echo "✅ Query funcionando: SELECT 1 retornou " . $data['test'] . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro na conexão:</strong><br>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<br><strong>Código do erro:</strong> " . $e->getCode();
} catch (Throwable $e) {
    echo "❌ <strong>Erro desconhecido:</strong><br>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

echo "<hr>";
echo "<a href='/'>&larr; Voltar ao site</a>";
?>
