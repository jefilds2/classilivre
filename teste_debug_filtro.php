<?php
// Teste para debugar a query de filtro do index.php

require_once __DIR__ . '/login_system/middleware.php';

$pdo = db();

// Simular o que o index.php faz
$categoryOptions = array(
    'mechanic' => array('Oficina mecânica', 'Oficina mecanica'),
    'bodyshop' => array('Funilaria e pintura'),
    'detailing' => array('Estética automotiva', 'Estetica automotiva'),
    'tire' => array('Pneus e suspensão', 'Pneus e suspensao'),
    'alignment' => array('Alinhamento e balanceamento'),
    'electric' => array('Elétrica automotiva', 'Eletrica automotiva'),
    'air_conditioning' => array('Ar-condicionado automotivo', 'Ar condicionado automotivo'),
    'battery' => array('Baterias e elétrica leve', 'Baterias e eletrica leve'),
    'paintless_dent' => array('Martelinho de ouro'),
    'glass' => array('Vidros e para-brisas', 'Vidros e para brisas'),
    'parts' => array('Autopeças', 'Autopecas'),
    'tow' => array('Guincho e reboque'),
    'inspection' => array('Vistoria veicular'),
    'wash' => array('Lavagem e polimento'),
    'car_audio' => array('Som e multimídia', 'Som e multimedia'),
    'insurance' => array('Corretora de seguros'),
    'tracker' => array('Rastreamento veicular'),
    'custom' => array('Outro serviço', 'Outros serviços', 'Outro servico'),
);

echo "<h2>Debug de Filtro de Categorias</h2>";
echo "<hr>";

// Testar cada categoria
foreach ($categoryOptions as $key => $variants) {
    echo "<h3>Categoria: <strong>$key</strong></h3>";
    echo "Variantes procuradas: " . implode(', ', $variants) . "<br>";
    
    // Montar a query como o index.php faz
    $whereParts = array(
        "sl.status = 'active'",
        "sl.payment_status = 'paid'"
    );
    $params = array();
    
    $placeholders = array();
    foreach ($variants as $i => $variant) {
        $placeholderKey = ':category_variant_' . $i;
        $placeholders[] = $placeholderKey;
        $params[$placeholderKey] = $variant;
    }
    $whereParts[] = 'sl.category IN (' . implode(', ', $placeholders) . ')';
    
    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);
    
    $sql = "SELECT COUNT(*) FROM user_service_listings sl $whereSql";
    
    echo "<strong>SQL Query:</strong><br>";
    echo "<code>" . htmlspecialchars($sql) . "</code><br>";
    
    echo "<strong>Parâmetros:</strong><br>";
    echo "<pre>";
    print_r($params);
    echo "</pre>";
    
    // Executar
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key_param => $value) {
            $stmt->bindValue($key_param, $value);
        }
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        echo "<strong style='color: green;'>Resultados encontrados: $count</strong><br>";
        
        if ($count > 0) {
            $sqlDetails = "SELECT id, name, category FROM user_service_listings sl $whereSql LIMIT 3";
            $stmt = $pdo->prepare($sqlDetails);
            foreach ($params as $key_param => $value) {
                $stmt->bindValue($key_param, $value);
            }
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<ul>";
            foreach ($services as $svc) {
                echo "<li>ID: {$svc['id']} - {$svc['name']} (BD: '" . htmlspecialchars($svc['category']) . "')</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<strong style='color: red;'>ERRO: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
    }
    
    echo "<hr>";
}
?>
