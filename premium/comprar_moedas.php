<?php
/**
 * premium/comprar_moedas.php
 *
 * Página de compra de moedas (CLivre Medal) com integração Mercado Pago.
 * Fluxo:
 * 1. Usuário escolhe um pacote.
 * 2. Criamos uma preferência no Mercado Pago.
 * 3. Registramos a intenção na tabela coin_purchase_orders.
 * 4. Após pagamento, o webhook (process_coin_purchase.php) credita as moedas.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../login_system/middleware.php';
require_once __DIR__ . '/../sqlconnect.php';

/**
 * Escreve logs de depuração em logs/coin_purchase_debug.log
 */
function coin_purchase_debug(string $message, array $context = []): void
{
    static $logPath = null;
    if ($logPath === null) {
        $logPath = __DIR__ . '/../logs/coin_purchase_debug.log';
    }
    $record = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $record .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    file_put_contents($logPath, $record . PHP_EOL, FILE_APPEND);
}

require_login();
ensure_user_not_blocked();

$user = current_user();
$userId = (int)$user['id'];
$userEmail = (string)$user['email'];
$userName = $user['name'] ?? $userEmail;

// Parâmetro opcional para retornar à tela de origem
$returnUrl = $_GET['return_to'] ?? null;
if (!is_string($returnUrl) || ($returnUrl !== '' && (!filter_var($returnUrl, FILTER_VALIDATE_URL) && strpos($returnUrl, '/') !== 0))) {
    $returnUrl = null;
}

$pdo = db();

// Preço base da medal (dinâmico, configurado no admin)
$coinPriceStmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'clivre_medal_price'");
$coinPriceStmt->execute();
$coinPriceRow = $coinPriceStmt->fetch();
$coinPrice = $coinPriceRow ? (float)$coinPriceRow['value'] : 10.00;

// Saldo atual do usuário
$userCoinBalance = get_user_coin_balance($userId);

// Pacotes disponíveis (quantidade base e bônus)
$packages = [
    [
        'id' => 1,
        'name' => 'Starter',
        'k' => 1,
        'bonus' => 0,
        'description' => 'Comece agora — o essencial para anunciar!',
        'badge' => null,
        'is_recommended' => false,
        'is_decoy' => false,
        'is_anchor' => false,
    ],
    [
        'id' => 2,
        'name' => 'Smart',
        'k' => 5,
        'bonus' => 1,
        'description' => 'Ganhe 1 moeda extra — economize já!',
        'badge' => '💰 Econômico',
        'is_recommended' => false,
        'is_decoy' => false,
        'is_anchor' => false,
    ],
    [
        'id' => 3,
        'name' => 'Popular',
        'k' => 10,
        'bonus' => 2,
        'description' => 'Mais escolhido — mais anúncios por menos!',
        'badge' => '⭐ Mais escolhido',
        'is_recommended' => false,
        'is_decoy' => true,
        'is_anchor' => false,
    ],
    [
        'id' => 4,
        'name' => 'Pro',
        'k' => 20,
        'bonus' => 7,
        'description' => 'Mais vantajoso — até 26% de economia por moeda!',
        'badge' => '🔥 Mais vantajoso',
        'is_recommended' => true,
        'is_decoy' => false,
        'is_anchor' => false,
    ],
    [
        'id' => 5,
        'name' => 'Mega',
        'k' => 50,
        'bonus' => 20,
        'description' => 'Pacote completo — o maior desconto!',
        'badge' => '🏆 Premium',
        'is_recommended' => false,
        'is_decoy' => false,
        'is_anchor' => true,
    ],
];

// Calcula valores formatados para exibição
foreach ($packages as &$pkg) {
    $pkg['total_coins'] = $pkg['k'] + $pkg['bonus'];
    $pkg['price'] = $pkg['k'] * $coinPrice;
    $pkg['display_price'] = max($pkg['price'] - 0.01, 0.0);
    $pkg['cost_per_coin'] = $pkg['total_coins'] > 0 ? round($pkg['price'] / $pkg['total_coins'], 2) : 0.0;
    $pkg['savings_pct'] = $pkg['cost_per_coin'] > 0 ? round((1 - ($pkg['cost_per_coin'] / $coinPrice)) * 100) : 0;
}
unset($pkg);

// Processa requisição AJAX (POST) para criar preference
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageId = (int)($_POST['package_id'] ?? 0);

    $selectedPackage = null;
    foreach ($packages as $pkg) {
        if ($pkg['id'] === $packageId) {
            $selectedPackage = $pkg;
            break;
        }
    }

    if (!$selectedPackage) {
        coin_purchase_debug('invalid package request', ['package_id' => $packageId, 'user_id' => $userId]);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Pacote inválido']);
        exit;
    }

    try {
        $mpConfig = require __DIR__ . '/../config/mercadopago.php';
        $mode = $mpConfig['mode'] ?? 'production';
        $credentials = $mpConfig[$mode] ?? [];
        $accessToken = $credentials['access_token'] ?? '';
        if ($accessToken === '') {
            throw new RuntimeException('Credencial do Mercado Pago ausente');
        }

        $externalReference = sprintf('coin_purchase_%d_%d_%d', $userId, $packageId, time());
        $preferencePayload = [
            'items' => [[
                'title' => 'CLivre Medal - Pacote ' . $selectedPackage['name'],
                'description' => $selectedPackage['total_coins'] . ' moedas (inclui bônus)',
                'quantity' => 1,
                'unit_price' => (float)$selectedPackage['price'],
                'currency_id' => 'BRL',
            ]],
            'payer' => [
                'email' => $userEmail,
                'name' => $userName,
            ],
            'external_reference' => $externalReference,
            'back_urls' => [
                'success' => 'https://' . $_SERVER['HTTP_HOST'] . '/premium/comprar_moedas.php?status=success&user_id=' . $userId,
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
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($preferencePayload),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('CURL Error: ' . $curlError);
        }
        if (!in_array($httpCode, [200, 201], true)) {
            $decoded = json_decode($response, true);
            throw new RuntimeException('MP Error (' . $httpCode . '): ' . ($decoded['message'] ?? 'desconhecido'));
        }

        $preference = json_decode($response, true);
        if (!isset($preference['id'], $preference['init_point'])) {
            throw new RuntimeException('Resposta do Mercado Pago inválida');
        }

        // Garante que pedidos anteriores não pagos/cancelados sejam deletados
        // Isso evita duplicações e garante que apenas a transação mais recente será processada
        $deleteSql = 'DELETE FROM coin_purchase_orders
            WHERE user_id = :user_id
              AND status IN ("pending", "cancelled")';

        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $deleteStmt->execute();

        coin_purchase_debug('Deleted pending/cancelled orders', [
            'user_id' => $userId,
            'deleted_rows' => $deleteStmt->rowCount(),
        ]);

        // Registrar a nova tentativa de compra
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
                payment_id = VALUES(payment_id),
                payment_status = VALUES(payment_status),
                raw_payload = VALUES(raw_payload),
                approved_at = VALUES(approved_at),
                cancelled_at = VALUES(cancelled_at),
                updated_at = VALUES(updated_at)';

        $orderParams = [
            $userId,
            $packageId,
            $selectedPackage['name'],
            (int)$selectedPackage['k'],
            (int)$selectedPackage['bonus'],
            (int)$selectedPackage['total_coins'],
            (float)$selectedPackage['price'],
            'BRL',
            $externalReference,
            $preference['id'],
            'pending',
            now(),
            now(),
        ];

        $orderStmt = $pdo->prepare($insertSql);
        $orderStmt->execute($orderParams);
        coin_purchase_debug('coin_purchase_order recorded', [
            'user_id' => $userId,
            'external_reference' => $externalReference,
            'params_count' => count($orderParams),
        ]);

        $_SESSION['pending_coin_purchase'] = [
            'preference_id' => $preference['id'],
            'package_id' => $packageId,
            'total_coins' => $selectedPackage['total_coins'],
            'price' => $selectedPackage['price'],
            'created_at' => now(),
        ];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'init_point' => $preference['init_point'],
            'preference_id' => $preference['id'],
        ]);
        exit;
    } catch (Throwable $exception) {
        coin_purchase_debug('exception during create preference', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao processar pagamento: ' . $exception->getMessage(),
        ]);
        exit;
    }
}

// Mensagens de retorno (pós redirecionamento do Mercado Pago)
$status = $_GET['status'] ?? null;
$alertMessage = '';
$alertType = '';
switch ($status) {
    case 'success':
        $alertMessage = '✅ Pagamento processado! Moedas serão adicionadas em breve.';
        $alertType = 'success';
        break;
    case 'failure':
        $alertMessage = '❌ Pagamento não foi realizado. Tente novamente.';
        $alertType = 'danger';
        break;
    case 'pending':
        $alertMessage = '⏳ Pagamento pendente. Aguarde confirmação.';
        $alertType = 'warning';
        break;
}

require __DIR__ . '/../partials/header.php';
?>

<div class="container my-5">
    <div class="coin-header text-center mb-4">
        <h1><i class="fas fa-coins"></i> CLivre Medal</h1>
        <p>Compre moedas para publicar e destacar seus anúncios</p>
    </div>

    <?php if ($alertMessage): ?>
        <div class="alert alert-<?php echo e($alertType); ?> alert-dismissible fade show" role="alert">
            <?php echo e($alertMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="current-balance mb-4">
        <i class="fas fa-wallet"></i>
        Seu saldo atual: <strong><?php echo e($userCoinBalance); ?> moedas</strong>
    </div>

    <div class="packages-container d-flex flex-wrap gap-3 justify-content-center">
        <?php foreach ($packages as $pkg): ?>
            <div class="package-card <?php echo $pkg['is_recommended'] ? 'featured' : ''; ?> <?php echo $pkg['is_decoy'] ? 'decoy' : ''; ?> <?php echo $pkg['is_anchor'] ? 'anchor' : ''; ?>">
                <?php if ($pkg['badge']): ?>
                    <div class="package-badge"><?php echo e($pkg['badge']); ?></div>
                <?php endif; ?>

                <div class="package-header">
                    <img src="/assets/img/classilivre-logo.png" alt="CLivre Medal" class="coin-icon">
                    <h3 class="package-name"><?php echo e($pkg['name']); ?></h3>
                </div>

                <p class="package-description"><?php echo e($pkg['description']); ?></p>

                <div class="package-coins">
                    <div class="coin-count"><?php echo e($pkg['total_coins']); ?></div>
                    <div class="coin-label">moedas</div>
                </div>

                <div class="package-composition">
                    <span class="purchased">
                        <img src="/assets/img/classilivre-logo.png" alt="Moeda" style="height:0.8rem;">
                        <?php echo e($pkg['k']); ?> compradas
                    </span>
                    <span class="bonus">🎁 +<?php echo e($pkg['bonus']); ?> bônus</span>
                </div>

                <div class="package-pricing">
                    <span class="price-charge">R$ <?php echo e(number_format($pkg['price'], 2, ',', '.')); ?></span>
                </div>

                <div class="package-rate">
                    <strong>R$ <?php echo e(number_format($pkg['cost_per_coin'], 2, ',', '.')); ?> por moeda</strong>
                    <?php if ($pkg['savings_pct'] > 0): ?>
                        <span class="savings">-<?php echo e($pkg['savings_pct']); ?>%</span>
                    <?php endif; ?>
                </div>

                <button class="package-button <?php echo $pkg['is_recommended'] ? 'primary' : ''; ?>"
                        onclick="buyPackage(<?php echo e($pkg['id']); ?>)">
                    <i class="fas fa-shopping-cart"></i>
                    Comprar
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="faq-section mt-5">
        <h3><i class="fas fa-question-circle"></i> Perguntas Frequentes</h3>
        <div class="faq-item">
            <div class="faq-question">1 moeda = ?</div>
            <div class="faq-answer">1 moeda = 1 anúncio publicado por 30 dias.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Posso usar as moedas mais de uma vez?</div>
            <div class="faq-answer">Cada moeda ativa um anúncio por 30 dias. Após expirar, é necessário outra moeda para renovar.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Quanto tempo demora para as moedas chegarem?</div>
            <div class="faq-answer">Geralmente menos de 1 minuto após a confirmação do pagamento.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Posso solicitar reembolso?</div>
            <div class="faq-answer">Segue a política do Mercado Pago. Entre em contato com o suporte em caso de dúvidas.</div>
        </div>
    </div>

    <div class="back-link mt-4">
        <a href="<?php echo $returnUrl ? e($returnUrl) : '/anuncios/meus.php'; ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="loading-spinner" id="loadingSpinner" style="display:none;">
    <div class="spinner"></div>
    <p>Processando seu pagamento...</p>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
<script src="/assets/js/bootstrap.min.js"></script>
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    const mpConfig = <?php echo json_encode(['public_key' => require __DIR__ . '/../config/mercadopago.php']); ?>;
    const mercadopago = new MercadoPago(
        (mpConfig.public_key.production && mpConfig.public_key.production.public_key) ||
        (mpConfig.public_key.test && mpConfig.public_key.test.public_key) ||
        ''
    );

    async function buyPackage(packageId) {
        const spinner = document.getElementById('loadingSpinner');
        spinner.style.display = 'flex';

        try {
            const response = await fetch('/premium/comprar_moedas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'package_id=' + encodeURIComponent(packageId),
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Erro ao criar preferência de pagamento');
            }
            window.location.href = data.init_point;
        } catch (error) {
            alert('Erro: ' + error.message);
            console.error('Pagamento', error);
        } finally {
            spinner.style.display = 'none';
        }
    }
</script>
