<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');

echo "1. PHP iniciado\n";

// Tentar incluir o CampaignManager diretamente
try {
    echo "2. Incluindo sqlconnect\n";
    require_once __DIR__ . '/../../../sqlconnect.php';
    
    echo "3. Conectando BD\n";
    $pdo = db();
    
    echo "4. Incluindo CampaignManager\n";
    require_once __DIR__ . '/../../classes/CampaignManager.php';
    
    echo "5. Criando manager\n";
    $manager = new PropagandaCampaignManager($pdo);
    
    echo "6. Chamando getGeneralStats\n";
    $stats = $manager->getGeneralStats();
    
    echo "7. Stats obtidas com sucesso:\n";
    echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    echo "\n❌ ERRO:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
