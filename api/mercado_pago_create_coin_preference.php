<?php
/**
 * api/mercado_pago_create_coin_preference.php
 * 
 * Endpoint para criar preference de Mercado Pago
 * Chamado via AJAX da página comprar_moedas.php
 * 
 * Retorna: { success, init_point, preference_id }
 * 
 * Segurança:
 * - Autentica usuário (require_login)
 * - Valida package_id contra lista hard-coded
 * - Prepara dados no backend antes de enviar MP
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require '../vendor/autoload.php';
    require '../login_system/middleware.php';
    require '../sqlconnect.php';

    if (!function_exists('coin_purchase_debug')) {
        function coin_purchase_debug(string $message, array $context = []): void
        {
            static $logPath = null;
            if ($logPath === null) {
                $logPath = __DIR__ . '/../logs/coin_purchase_debug.log';
            }
            $entry = '[' . date('Y-m-d H:i:s') . '] ' . $message;
            if (!empty($context)) {
                $entry .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            file_put_contents($logPath, $entry . PHP_EOL, FILE_APPEND);
        }
    }

    // Valida método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Autentica
    require_login();
    $current_user = current_user();
    $user_id = (int)$current_user['id'];
    $user_email = $current_user['email'];
    $user_name = $current_user['name'] ?? $user_email;

    // Lê input
    $input = json_decode(file_get_contents('php://input'), true);
    $package_id = (int)($input['package_id'] ?? 0);

    // Valida package
    if ($package_id < 1 || $package_id > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid package']);
        exit;
    }

    // Define pacotes (mesmo do comprar_moedas.php)
    $packages = [
        1 => ['name' => 'Starter', 'coins' => 1, 'bonus' => 0],
        2 => ['name' => 'Smart', 'coins' => 5, 'bonus' => 1],
        3 => ['name' => 'Popular', 'coins' => 10, 'bonus' => 2],
        4 => ['name' => 'Pro', 'coins' => 20, 'bonus' => 7],
        5 => ['name' => 'Mega', 'coins' => 50, 'bonus' => 20],
    ];

    $pkg = $packages[$package_id];

    // Calcula preço
    $sql_price = "SELECT value FROM settings WHERE `key` = 'clivre_medal_price'";
    $stmt = db()->prepare($sql_price);
    $stmt->execute();
    $price_row = $stmt->fetch();
    $coin_price = $price_row ? (float)$price_row['value'] : 10.00;

    $price = $pkg['coins'] * $coin_price;
    $bonus_coins = (int)$pkg['bonus'];
    $total_coins = $pkg['coins'] + $bonus_coins;

    // Cria preference no MP
    $mp_config = require '../config/mercadopago.php';
    $mode = $mp_config['mode'] ?? 'production';
    $credentials = $mp_config[$mode];
    $access_token = $credentials['access_token'];

    $external_reference = 'coin_purchase_' . $user_id . '_' . $package_id . '_' . time();

    $preference_data = [
        'items' => [
            [
                'title' => 'CLivre Medal - Pacote ' . $pkg['name'],
                'description' => $total_coins . ' moedas',
                'quantity' => 1,
                'unit_price' => (float)$price,
                'currency_id' => 'BRL',
            ],
        ],
        'payer' => [
            'email' => $user_email,
            'name' => $user_name,
        ],
        'external_reference' => $external_reference,
        'back_urls' => [
            'success' => 'https://' . $_SERVER['HTTP_HOST'] . '/premium/comprar_moedas.php?status=success',
            'failure' => 'https://' . $_SERVER['HTTP_HOST'] . '/premium/comprar_moedas.php?status=failure',
            'pending' => 'https://' . $_SERVER['HTTP_HOST'] . '/premium/comprar_moedas.php?status=pending',
        ],
        'notification_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/mercado_pago_webhook.php',
        'auto_return' => 'approved',
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token,
        ],
        CURLOPT_POSTFIELDS => json_encode($preference_data),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception('CURL Error: ' . $curl_error);
    }

    if ($http_code !== 201 && $http_code !== 200) {
        $mp_error = json_decode($response, true);
        throw new Exception('MP Error (' . $http_code . '): ' . ($mp_error['message'] ?? 'Unknown'));
    }

    $mp_response = json_decode($response, true);

    if (!isset($mp_response['id']) || !isset($mp_response['init_point'])) {
        throw new Exception('Invalid MP response');
    }

    $pdo = db();
    $nowTs = now();

    $cancelStmt = $pdo->prepare('UPDATE coin_purchase_orders SET status = "cancelled", payment_status = :payment_status, cancelled_at = :now, updated_at = :now WHERE user_id = :user_id AND status = "pending" AND external_reference <> :external_reference');
    $cancelStmt->execute([
        ':payment_status' => 'cancelled_by_user',
        ':now' => $nowTs,
        ':user_id' => $user_id,
        ':external_reference' => $external_reference,
    ]);

    $insertSql = 'INSERT INTO coin_purchase_orders (
            user_id,
            package_id,
            package_label,
            coins_base,
            coins_bonus,
            coins_total,
            amount,
            currency,
            external_reference,
            preference_id,
            status,
            created_at,
            updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
        ON DUPLICATE KEY UPDATE
            package_id = VALUES(package_id),
            package_label = VALUES(package_label),
            coins_base = VALUES(coins_base),
            coins_bonus = VALUES(coins_bonus),
            coins_total = VALUES(coins_total),
            amount = VALUES(amount),
            currency = VALUES(currency),
            preference_id = VALUES(preference_id),
            status = VALUES(status),
            payment_id = NULL,
            payment_status = NULL,
            raw_payload = NULL,
            approved_at = NULL,
            cancelled_at = NULL,
            updated_at = VALUES(updated_at)';
    $orderStmt = $pdo->prepare($insertSql);
    $orderParams = [
        $user_id,
        $package_id,
        $pkg['name'],
        (int)$pkg['coins'],
        $bonus_coins,
        $total_coins,
        $price,
        'BRL',
        $external_reference,
        $mp_response['id'],
        'pending',
        $nowTs,
        $nowTs,
    ];
    try {
        $orderStmt->execute($orderParams);
        coin_purchase_debug('api coin_purchase_order recorded', [
            'user_id' => $user_id,
            'external_reference' => $external_reference,
            'params_count' => count($orderParams),
        ]);
    } catch (PDOException $e) {
        coin_purchase_debug('api coin_purchase_order insert failed', [
            'error' => $e->getMessage(),
            'sql' => $insertSql,
            'params' => $orderParams,
        ]);
        throw $e;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'init_point' => $mp_response['init_point'],
        'preference_id' => $mp_response['id'],
    ]);

} catch (Exception $e) {
    error_log('MP Preference Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
