<?php

declare(strict_types=1);

/**
 * Carregador de Campanhas EMBED
 * Arquivo: /propagandas/load_embed.php
 * 
 * Proporções Responsivas 16:9:
 * - Desktop: 700px × 394px
 * - Tablet: 90vw (max 600px) × auto (aspect-ratio 16:9)
 * - Mobile: 95vw (max 450px) × auto (aspect-ratio 16:9)
 * 
 * Iframes automaticamente enquadrados sem dimensões explícitas
 * Use isto em qualquer página do site
 */

if (!file_exists(__DIR__ . '/config.php')) return;
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/CampaignManager.php';

$script = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? 'index.php');
$page_name = str_replace('.php', '', $script);

$pages_to_check = [];
if ($page_name === 'index' || empty($page_name)) {
    $pages_to_check = ['home', 'index'];
} elseif (strpos($page_name, 'listar') !== false || strpos($page_name, 'anuncios') !== false) {
    $pages_to_check = ['anuncios'];
} elseif (strpos($page_name, 'servicos') !== false) {
    $pages_to_check = ['servicos'];
}

if (empty($pages_to_check)) return;

try {
    $pdo = db();
    $manager = new PropagandaCampaignManager($pdo);

    $campaigns_embed = [];
    foreach ($pages_to_check as $page) {
        $campaigns = $manager->getActiveByType(3, $page);
        $campaigns_embed = array_merge($campaigns_embed, $campaigns);
    }

    if (empty($campaigns_embed)) return;

    // Filtrar por modo de exibição (exclusive > prioritized > normal)
    $to_show = [];
    foreach ($campaigns_embed as $campaign) {
        if ($campaign['display_mode'] === 'exclusive') {
            $to_show = [$campaign];
            break;
        } elseif ($campaign['display_mode'] === 'prioritized') {
            array_unshift($to_show, $campaign);
        } else {
            $to_show[] = $campaign;
        }
    }

    if (!empty($to_show)):
        ?>
        <script type="application/json" id="propagandas-embed" data-campaigns="<?= htmlspecialchars(json_encode($to_show), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <link rel="stylesheet" href="/propagandas/frontend/assets/css/propaganda-embed.css?v=3">
    <script src="/propagandas/frontend/assets/js/propaganda_embed.js?v=2.4"></script>
        <?php
    endif;

} catch (Throwable $e) {
    error_log('[load_embed.php] Erro: ' . $e->getMessage());
}
