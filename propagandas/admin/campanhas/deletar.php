<?php
// Deletar Campanha Web
require_once __DIR__ . '/../../../login_system/middleware.php';
require_admin();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../classes/CampaignManager.php';

$pdo = db();
$manager = new PropagandaCampaignManager($pdo);
$campaign_id = (int)($_GET['id'] ?? 0);

if ($campaign_id <= 0) {
    header('Location: /propagandas/admin/index.php?msg=erro');
    exit;
}

try {
    $campaign = $manager->getById($campaign_id);
    
    if (!$campaign) {
        header('Location: /propagandas/admin/index.php?msg=nao-encontrada');
        exit;
    }

    // Deletar campanha
    if ($manager->delete($campaign_id)) {
        header('Location: /propagandas/admin/index.php?msg=deletada');
        exit;
    } else {
        header('Location: /propagandas/admin/index.php?msg=erro');
        exit;
    }
} catch (Throwable $e) {
    error_log('Erro ao deletar campanha: ' . $e->getMessage());
    header('Location: /propagandas/admin/index.php?msg=erro');
    exit;
}
