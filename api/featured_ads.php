<?php

declare(strict_types=1);

use PDO;

require_once __DIR__ . '/../login_system/middleware.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function api_asset_path(string $path): string
{
    return '/' . ltrim($path, '/');
}

function fetch_featured_ads_data(int $limit = 6): array
{
    $pdo = db();

        $stmt = $pdo->prepare('
                SELECT ads.*, users.name AS owner_name
                FROM ads
                JOIN users ON users.id = ads.user_id
                WHERE ads.status = "approved"
                    AND (ads.expires_at IS NULL OR ads.expires_at > NOW())
                ORDER BY COALESCE(ads.approved_at, ads.updated_at, ads.created_at) DESC
                LIMIT :limit
        ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $ads = $stmt->fetchAll();

    if (!$ads) {
        return [];
    }

    $adIds = array_map(static fn(array $ad): int => (int)$ad['id'], $ads);
    $placeholders = implode(',', array_fill(0, count($adIds), '?'));

    $mediaStmt = $pdo->prepare("SELECT ad_id, filepath FROM ad_media WHERE ad_id IN ($placeholders) AND type = 'image' ORDER BY id ASC");
    $mediaStmt->execute($adIds);

    $mediaMap = [];
    while ($row = $mediaStmt->fetch()) {
        $mediaMap[(int)$row['ad_id']][] = $row['filepath'];
    }

    foreach ($ads as &$ad) {
        $id = (int)$ad['id'];
        $ad['images'] = $mediaMap[$id] ?? ['assets/uploads/images/sample-car-1.jpg'];
        $ad['is_verified'] = (int)$ad['verified_badge'] === 1;
    }
    unset($ad);

    return $ads;
}

try {
    $ads = fetch_featured_ads_data();

    $payload = [
        'data' => array_map(static function (array $ad): array {
            return [
                'id' => (int)$ad['id'],
                'title' => $ad['title'],
                'price' => (float)$ad['price'],
                'price_formatted' => format_currency((float)$ad['price']),
                'city' => $ad['city'],
                'state' => $ad['state'],
                'model_year' => (int)$ad['model_year'],
                'color' => $ad['color'],
                'mileage' => (int)$ad['mileage'],
                'transmission' => $ad['transmission'],
                'fuel_type' => $ad['fuel_type'],
                'url' => ad_public_url($ad),
                'is_verified' => (bool)$ad['is_verified'],
                'store_id' => (int)$ad['user_id'],
                'owner_name' => $ad['owner_name'],
                'images' => array_map('api_asset_path', $ad['images']),
            ];
        }, $ads),
    ];

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Não foi possível carregar os anúncios em destaque.',
    ], JSON_UNESCAPED_UNICODE);
}
