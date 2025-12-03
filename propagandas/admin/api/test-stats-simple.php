<?php
// Teste SEM incluir middleware (que manda headers)
error_reporting(E_ALL);
ini_set('display_errors', 'on');

// NÃO incluir config.php que inclui middleware
// Incluir APENAS sqlconnect e classes

try {
    echo "1. Incluindo sqlconnect\n";
    require_once __DIR__ . '/../../../sqlconnect.php';
    
    echo "2. Conectando BD\n";
    $pdo = db();
    
    echo "3. Incluindo CampaignManager\n";
    require_once __DIR__ . '/../../classes/CampaignManager.php';
    
    echo "4. Criando manager\n";
    $manager = new PropagandaCampaignManager($pdo);
    
    echo "5. Chamando getGeneralStats\n";
    $stats = $manager->getGeneralStats();
    
    echo "6. ✅ Stats obtidas!\n\n";
    echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    echo "❌ ERRO:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
