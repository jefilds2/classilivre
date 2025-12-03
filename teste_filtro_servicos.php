<?php
// Teste para verificar como o filtro está funcionando

require_once __DIR__ . '/login_system/middleware.php';

$pdo = db();

// Array de categorias do index.php
$categoryOptions = [
    'mechanic' => ['Oficina mecânica', 'Oficina mecanica'],
    'bodyshop' => ['Funilaria e pintura'],
    'detailing' => ['Estética automotiva', 'Estetica automotiva'],
    'tire' => ['Pneus e suspensão', 'Pneus e suspensao'],
    'alignment' => ['Alinhamento e balanceamento'],
    'electric' => ['Elétrica automotiva', 'Eletrica automotiva'],
    'air_conditioning' => ['Ar-condicionado automotivo', 'Ar condicionado automotivo'],
    'battery' => ['Baterias e elétrica leve', 'Baterias e eletrica leve'],
    'paintless_dent' => ['Martelinho de ouro'],
    'glass' => ['Vidros e para-brisas', 'Vidros e para brisas'],
    'parts' => ['Autopeças', 'Autopecas'],
    'tow' => ['Guincho e reboque'],
    'inspection' => ['Vistoria veicular'],
    'wash' => ['Lavagem e polimento'],
    'car_audio' => ['Som e multimídia', 'Som e multimedia'],
    'insurance' => ['Corretora de seguros'],
    'tracker' => ['Rastreamento veicular'],
    'custom' => ['Outro serviço', 'Outros serviços', 'Outro servico'],
];

echo "<h2>Teste de Filtro de Categorias</h2>";
echo "<hr>";

// 1. Mostrar categorias na BD
echo "<h3>1. Categorias atuais na BD:</h3>";
$stmt = $pdo->query("SELECT DISTINCT category FROM user_service_listings WHERE status = 'active' AND payment_status = 'paid' ORDER BY category");
$dbCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
foreach ($dbCategories as $cat) {
    echo "- $cat\n";
}
echo "</pre>";

echo "<hr>";

// 2. Testar cada categoria
echo "<h3>2. Resultado de filtro para cada categoria:</h3>";

foreach ($categoryOptions as $key => $variants) {
    $escapedVariants = array_map(function($v) use ($pdo) {
        return $pdo->quote($v);
    }, $variants);
    
    $sql = "SELECT COUNT(*) FROM user_service_listings 
            WHERE status = 'active' 
            AND payment_status = 'paid'
            AND category IN (" . implode(', ', $escapedVariants) . ")";
    
    $stmt = $pdo->query($sql);
    $count = $stmt->fetchColumn();
    
    echo "<strong>$key</strong> (variantes: " . implode(', ', $variants) . ")<br>";
    echo "↳ <strong>$count serviços encontrados</strong><br>";
    
    // Mostrar quais serviços foram encontrados
    if ($count > 0) {
        $sqlDetails = "SELECT id, name, category FROM user_service_listings 
                       WHERE status = 'active' 
                       AND payment_status = 'paid'
                       AND category IN (" . implode(', ', $escapedVariants) . ")
                       LIMIT 3";
        $stmt = $pdo->query($sqlDetails);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($services as $svc) {
            echo "  - {$svc['id']}: {$svc['name']} (categoria BD: {$svc['category']})<br>";
        }
    }
    
    echo "<br>";
}

echo "<hr>";

// 3. Mostrar categorias que não estão sendo capturadas
echo "<h3>3. Categorias na BD que NÃO estão sendo capturadas:</h3>";

$allVariants = [];
foreach ($categoryOptions as $variants) {
    $allVariants = array_merge($allVariants, $variants);
}

$quotedVariants = array_map(function($v) use ($pdo) {
    return $pdo->quote($v);
}, $allVariants);

$sql = "SELECT DISTINCT category FROM user_service_listings 
        WHERE status = 'active' 
        AND payment_status = 'paid'
        AND category NOT IN (" . implode(', ', $quotedVariants) . ")";

$stmt = $pdo->query($sql);
$uncaptured = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($uncaptured)) {
    echo "<strong style='color: green;'>✓ Nenhuma categoria não capturada!</strong>";
} else {
    echo "<strong style='color: red;'>Categorias não capturadas:</strong><br>";
    foreach ($uncaptured as $cat) {
        echo "- $cat<br>";
    }
}

echo "<hr>";
echo "<h3>Total de serviços na BD (ativos e pagos): </h3>";
$stmt = $pdo->query("SELECT COUNT(*) FROM user_service_listings WHERE status = 'active' AND payment_status = 'paid'");
$total = $stmt->fetchColumn();
echo "<strong>$total serviços</strong>";
?>
