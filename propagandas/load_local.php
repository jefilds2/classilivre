<?php

declare(strict_types=1);

/**
 * Carregador de Campanhas LOCAL
 * Arquivo: /propagandas/load_local.php
 * 
 * Inclui apenas campanhas do tipo LOCAL (ad_type=2) com phone/whatsapp
 * Use isto em qualquer página do site
 */

static $local_campaigns_loaded = false;
if ($local_campaigns_loaded) return;
$local_campaigns_loaded = true;

if (!function_exists('db')) return;

try {
    if (!file_exists(__DIR__ . '/config.php')) return;
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/classes/CampaignManager.php';

    $script = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? 'index.php');
    $page_name = str_replace('.php', '', $script);
    
    $pages_to_check = [];
    if ($page_name === 'index' || empty($page_name)) $pages_to_check = ['home', 'index'];
    elseif (strpos($page_name, 'listar') !== false || strpos($page_name, 'anuncios') !== false) $pages_to_check = ['anuncios'];
    elseif (strpos($page_name, 'servicos') !== false) $pages_to_check = ['servicos'];

    $pdo = db();
    $manager = new PropagandaCampaignManager($pdo);

    $campaigns_local = [];
    foreach ($pages_to_check as $page) {
        $campaigns = $manager->getActiveByType(2, $page);
        $campaigns_local = array_merge($campaigns_local, $campaigns);
    }

    if (empty($campaigns_local)) return;

    // Filtrar por modo de exibição
    $to_show = [];
    foreach ($campaigns_local as $campaign) {
        if ($campaign['display_mode'] === 'exclusive') {
            $to_show = [$campaign];
            break;
        } elseif ($campaign['display_mode'] === 'prioritized') {
            array_unshift($to_show, $campaign);
        } else {
            $to_show[] = $campaign;
        }
    }

    if (!empty($to_show)) {
        echo '<script type="application/json" id="propagandas-local" data-campaigns="' . htmlspecialchars(json_encode($to_show), ENT_QUOTES, 'UTF-8') . '"></script>';
        echo '<link rel="stylesheet" href="/propagandas/frontend/assets/css/propaganda-local.css">';
        echo '<script src="/propagandas/frontend/assets/js/propaganda_local.js"></script>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const script = document.getElementById("propagandas-local");
                if (script) {
                    try {
                        const campaigns = JSON.parse(script.getAttribute("data-campaigns"));
                        if (campaigns && campaigns.length > 0) {
                            new PropagandaLocalPopupManager(campaigns);
                        }
                    } catch (e) {
                        console.error("Erro ao carregar campanhas LOCAL:", e);
                    }
                }
            });
        </script>';
    }
} catch (Throwable $e) {
    error_log('load_local.php - ' . $e->getMessage());
}
