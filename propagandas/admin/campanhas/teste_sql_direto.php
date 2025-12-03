<?php
// Teste direto do SQL - sem classes, sem complicações

require_once __DIR__ . '/../../../sqlconnect.php';

$pdo = db();

// Pegar a 1ª campanha
$campaigns = $pdo->query("SELECT id, status FROM propagandas_campanhas LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);

if (empty($campaigns)) {
    echo "Nenhuma campanha encontrada!";
    exit;
}

$campaign = $campaigns[0];
$id = $campaign['id'];
$current_status = $campaign['status'];
$new_status = ($current_status === 'ativa') ? 'pausada' : 'ativa';

echo "<h2>Teste de UPDATE direto no PDO</h2>";
echo "<pre>";
echo "ID: $id\n";
echo "Status atual: $current_status\n";
echo "Novo status: $new_status\n\n";

// Teste 1: Usando exec()
echo "=== Teste com exec() ===\n";
$sql1 = "UPDATE propagandas_campanhas SET status = '$new_status' WHERE id = $id";
echo "SQL: $sql1\n";
$result1 = $pdo->exec($sql1);
echo "Resultado: " . var_export($result1, true) . "\n";

// Verificar
$check1 = $pdo->query("SELECT status FROM propagandas_campanhas WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
echo "Status no banco após exec: " . $check1['status'] . "\n\n";

// Revert
$revert_status = ($new_status === 'ativa') ? 'pausada' : 'ativa';
$pdo->exec("UPDATE propagandas_campanhas SET status = '$revert_status' WHERE id = $id");

echo "</pre>";
?>
