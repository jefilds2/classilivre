<?php

declare(strict_types=1);

/**
 * API: Obter campanhas ativas para uma página
 * Endpoint: /propagandas/admin/api/listar-campanhas.php
 * Método: GET
 * Parâmetro: ?page=index.php
 */

header('Content-Type: application/json; charset=utf-8');

// Evitar erros sendo exibidos como JSON inválido
error_reporting(E_ALL);
ini_set('display_errors', '0');

try {
    // Incluir middleware do site para usar mesma sessão
    require_once __DIR__ . '/../../../login_system/middleware.php';
    
    // Carregar configuração de propagandas
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../classes/CampaignManager.php';
    
    // Usar banco de dados já configurado
    $pdo = db();
    $manager = new PropagandaCampaignManager($pdo);

    // Obter parâmetro de página
    $page = $_GET['page'] ?? basename($_SERVER['SCRIPT_NAME']);

    if (empty($page)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Página não informada',
            'success' => false
        ]);
        exit;
    }

    // Obter campanhas ativas para esta página
    $campaigns = $manager->getActiveForPage($page);

    // Formatar resposta
    $response = [];
    foreach ($campaigns as $campaign) {
        $response[] = [
            'id' => (int)$campaign['id'],
            'title' => (string)$campaign['title'],
            'description' => (string)$campaign['description'],
            'image_url' => (string)($campaign['image_url'] ?? ''),
            'affiliate_link' => (string)$campaign['affiliate_link'],
            'cta_text' => (string)($campaign['cta_text'] ?? 'Saiba Mais'),
            'position' => (string)$campaign['position'],
            'display_frequency' => (string)$campaign['display_frequency'],
            'max_displays_per_period' => (int)$campaign['max_displays_per_period'],
            'display_mode' => (string)$campaign['display_mode'],
            'rotation_type' => (string)$campaign['rotation_type'],
            'priority_weight' => (int)$campaign['priority_weight'],
            'initial_delay_ms' => (int)$campaign['initial_delay_ms'],
            'reopen_delay_ms' => (int)$campaign['reopen_delay_ms'],
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $response,
        'count' => count($response),
        'page' => $page
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false
    ]);
}
