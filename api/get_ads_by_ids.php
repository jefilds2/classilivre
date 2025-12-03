<?php
/**
 * api/get_ads_by_ids.php
 * Retorna informações detalhadas de anúncios por IDs
 */

require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json');

// Função helper para normalizar caminhos de assets
function asset_path(string $path): string
{
    return '/' . ltrim($path, '/');
}

// Verificar autenticação
if (!current_user()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['ids']) || !is_array($data['ids'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid ids']);
        exit;
    }

    // Limpar e validar IDs
    $ids = array_map(function($id) { 
        return (int)$id; 
    }, $data['ids']);
    
    $ids = array_filter($ids, function($id) { 
        return $id > 0; 
    });

    if (empty($ids)) {
        echo json_encode([]);
        exit;
    }

    // Preparar placeholders para a query
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $today = date('Y-m-d');

    $query = "
        SELECT 
            a.id,
            a.user_id,
            u.name AS owner_name,
            a.title,
            a.slug,
            a.price,
            a.manufacture_year,
            a.model_year,
            a.mileage,
            a.transmission,
            a.fuel_type,
            a.city,
            a.state,
            a.verified_badge AS is_verified
        FROM ads a
        JOIN users u ON u.id = a.user_id
        WHERE a.id IN ($placeholders)
        ORDER BY FIELD(a.id, $placeholders)
    ";

    $stmt = db()->prepare($query);
    
    // Bind IDs apenas uma vez (para IN e FIELD)
    $params = array_merge($ids, $ids);
    $stmt->execute($params);
    
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar todas as imagens dos anúncios
    $adIds = array_map(function($ad) { return (int)$ad['id']; }, $ads);
    
    $mediaMap = [];
    if (!empty($adIds)) {
        $mediaPlaceholders = implode(',', array_fill(0, count($adIds), '?'));
        $mediaStmt = db()->prepare("SELECT ad_id, filepath FROM ad_media WHERE ad_id IN ($mediaPlaceholders) AND type = 'image' ORDER BY id ASC");
        $mediaStmt->execute($adIds);
        
        while ($row = $mediaStmt->fetch(PDO::FETCH_ASSOC)) {
            $mediaMap[(int)$row['ad_id']][] = $row['filepath'];
        }
    }
    
    // Formatar respostas
    $ads = array_map(function($ad) use ($mediaMap) {
        $adId = (int)$ad['id'];
        $rawImages = $mediaMap[$adId] ?? ['assets/uploads/images/sample-car-1.jpg'];
        $images = array_map('asset_path', $rawImages);
        
        return [
            'id' => $adId,
            'title' => $ad['title'],
            'slug' => $ad['slug'],
            'price' => (float)$ad['price'],
            'manufacture_year' => (int)$ad['manufacture_year'],
            'model_year' => (int)$ad['model_year'],
            'mileage' => (int)$ad['mileage'],
            'transmission' => $ad['transmission'],
            'fuel_type' => $ad['fuel_type'],
            'city' => $ad['city'],
            'state' => $ad['state'],
            'is_verified' => (bool)$ad['is_verified'],
            'store_id' => (int)$ad['user_id'],
            'owner_name' => $ad['owner_name'],
            'images' => $images
        ];
    }, $ads);

    echo json_encode($ads);

} catch (Exception $e) {
    error_log('Erro em get_ads_by_ids.php: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'details' => $e->getMessage()]);
}
?>
