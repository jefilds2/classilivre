<?php
// Debug script to check if index.php filter is working correctly

require_once __DIR__ . '/login_system/middleware.php';

$pdo = db();

// Simular GET com categoria
$_GET['category'] = isset($_GET['category']) ? $_GET['category'] : '';

// Same categoryOptions as index.php
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

$categories = array_combine(
    array_keys($categoryOptions),
    array_map(function(array $values) { return $values[0]; }, $categoryOptions)
);

echo "<h2>Debug do Filtro de Categoria</h2>";
echo "<hr>";
echo "<h3>Valor recebido em GET['category']: " . htmlspecialchars($_GET['category']) . "</h3>";

$rawCategory = $_GET['category'];
echo "<p><strong>rawCategory:</strong> " . htmlspecialchars($rawCategory) . "</p>";

$category = array_key_exists((string)$rawCategory, $categories) ? (string)$rawCategory : '';
echo "<p><strong>category (após validação):</strong> " . htmlspecialchars($category) . "</p>";

if ($category === '') {
    echo "<p style='color: red;'><strong>ERRO: Categoria vazia após validação!</strong></p>";
    echo "<p>Chaves esperadas em \$categories: " . implode(', ', array_keys($categories)) . "</p>";
    exit;
}

// Construir query como index.php faz
$whereParts = [
    "sl.status = 'active'",
    "sl.payment_status = 'paid'"
];
$params = [];

if ($category !== '') {
    if (isset($categoryOptions[$category])) {
        $categoryVariants = $categoryOptions[$category];
        $placeholders = [];
        foreach ($categoryVariants as $i => $variant) {
            $placeholderKey = ':category_variant_' . $i;
            $placeholders[] = $placeholderKey;
            $params[$placeholderKey] = $variant;
        }
        $whereParts[] = 'sl.category IN (' . implode(', ', $placeholders) . ')';
        echo "<p style='color: green;'><strong>Variantes encontradas:</strong> " . implode(', ', $categoryVariants) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>ERRO: Categoria não encontrada em \$categoryOptions!</strong></p>";
        exit;
    }
}

$whereSql = 'WHERE ' . implode(' AND ', $whereParts);
$sql = "SELECT COUNT(*) FROM user_service_listings sl $whereSql";

echo "<p><strong>SQL Query:</strong></p>";
echo "<code>" . htmlspecialchars($sql) . "</code>";

echo "<p><strong>Parâmetros:</strong></p>";
echo "<pre>";
print_r($params);
echo "</pre>";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key_param => $value) {
        $stmt->bindValue($key_param, $value);
    }
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    echo "<p style='color: green;'><strong>Total de serviços encontrados: " . $count . "</strong></p>";
    
    if ($count > 0) {
        $sqlDetails = "SELECT id, name, category FROM user_service_listings sl $whereSql LIMIT 5";
        $stmt = $pdo->prepare($sqlDetails);
        foreach ($params as $key_param => $value) {
            $stmt->bindValue($key_param, $value);
        }
        $stmt->execute();
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Primeiros 5 serviços:</strong></p>";
        echo "<ul>";
        foreach ($services as $svc) {
            echo "<li>ID: {$svc['id']} - {$svc['name']} (Categoria BD: '" . htmlspecialchars($svc['category']) . "')</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERRO na execução:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
