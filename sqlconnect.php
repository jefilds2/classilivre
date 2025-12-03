<?php
// sqlconnect.php — Responsável pela conexão PDO, migrações e dados iniciais do Site Carros.

declare(strict_types=1);

// ✅ PADRÃO GLOBAL: UTC (compatível com CRON em qualquer timezone)
// Conversão de timezone (se necessário na UI) deve ser feita no PHP, não no banco
date_default_timezone_set('UTC');

// ✅ Cache global para configurações (settings) - deve estar no topo para ser acessível
$_SETTINGS_CACHE = [];

const DB_SERVERNAME = 'localhost';
const DB_PORT       = 3306;                 // HostGator padrão
const DB_USERNAME   = 'hg8cf561_admin';
const DB_PASSWORD   = 'lZkS2LAQBWbq';     // troque pela sua senha atual
const DB_NAME       = 'hg8cf561_classiautos';

/**
 * Retorna uma instância PDO singleton pronta para uso.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_SERVERNAME,
        DB_PORT,
        DB_NAME
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        // opcional (charset já está no DSN)
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        // ✅ SINCRONIZAR TIMEZONE: MySQL usar UTC igual ao PHP
        $pdo->exec("SET SESSION time_zone = '+00:00'");
        return $pdo;
    } catch (Throwable $e) {
        error_log('[DB][conn] '.$e->getMessage());
        http_response_code(500);
        exit('Erro de conexão ao banco. Tente novamente em instantes.');
    }
}

/**
 * Executa as migrações e seeds iniciais apenas uma vez por requisição.
 */
function runMigrations(): void
{
    static $ran = false;
    if ($ran) {
        return;
    }

    $pdo = db();

    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        phone VARCHAR(25) DEFAULT NULL,
        password_hash VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) NOT NULL DEFAULT 0,
        is_premium TINYINT(1) NOT NULL DEFAULT 0,
        email_verified_at DATETIME NULL,
        status ENUM("active","blocked") NOT NULL DEFAULT "active",
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS coin_purchase_orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        package_id INT UNSIGNED NOT NULL,
        package_label VARCHAR(120) NULL,
        coins_base INT NOT NULL,
        coins_bonus INT NOT NULL,
        coins_total INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) NOT NULL DEFAULT "BRL",
        external_reference VARCHAR(80) NOT NULL UNIQUE,
        preference_id VARCHAR(80) NULL,
        status ENUM("pending","approved","cancelled","expired") NOT NULL DEFAULT "pending",
        payment_id BIGINT UNSIGNED NULL,
        payment_status VARCHAR(32) NULL,
        raw_payload LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        approved_at DATETIME NULL,
        cancelled_at DATETIME NULL,
        CONSTRAINT fk_coin_purchase_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_coin_purchase_user (user_id),
        INDEX idx_coin_purchase_status (status),
        INDEX idx_coin_purchase_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN coins_base INT NOT NULL DEFAULT 0 AFTER package_id');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN package_label VARCHAR(120) NULL AFTER package_id');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders MODIFY COLUMN coins_base INT NOT NULL DEFAULT 0 AFTER package_label');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN coins_bonus INT NOT NULL DEFAULT 0 AFTER coins_base');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN coins_total INT NOT NULL DEFAULT 0 AFTER coins_bonus');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER coins_total');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT "BRL" AFTER amount');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN preference_id VARCHAR(80) NULL AFTER external_reference');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN status ENUM("pending","approved","cancelled","expired") NOT NULL DEFAULT "pending" AFTER preference_id');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN payment_id BIGINT UNSIGNED NULL AFTER status');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN payment_status VARCHAR(32) NULL AFTER payment_id');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN raw_payload LONGTEXT NULL AFTER payment_status');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN approved_at DATETIME NULL AFTER updated_at');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE coin_purchase_orders ADD COLUMN cancelled_at DATETIME NULL AFTER approved_at');
    } catch (Throwable $exception) {
    }

    // Garantir que coluna coin_balance existe (Fase 1 - Sistema de Moedas)
    try {
        $columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'coin_balance'");
        if (!$columnCheck || $columnCheck->rowCount() === 0) {
            $pdo->exec('ALTER TABLE users ADD COLUMN coin_balance INT NOT NULL DEFAULT 0 AFTER is_premium');
        }
    } catch (Exception $e) {
        // Silently ignore if column already exists
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS email_verifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_email_verifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS password_resets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS ads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    vehicle_type ENUM("carro","moto","caminhao","outros") NOT NULL DEFAULT "carro",
    title VARCHAR(160) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    city VARCHAR(120) NOT NULL,
    state VARCHAR(2) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(120) NOT NULL,
    manufacture_year SMALLINT UNSIGNED NOT NULL,
    model_year SMALLINT UNSIGNED NOT NULL,
    mileage INT UNSIGNED NOT NULL DEFAULT 0,
    transmission VARCHAR(50) NOT NULL,
    fuel_type VARCHAR(50) NOT NULL,
    color VARCHAR(50) NOT NULL,
    doors TINYINT UNSIGNED NULL,
    license_plate VARCHAR(12) NULL,
    plate_last_digit TINYINT UNSIGNED NULL,
    extra_attributes TEXT NULL,
    status ENUM("draft","pending","approved","rejected","blocked","expired") NOT NULL DEFAULT "draft",
    reason_rejected TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    sold_at DATETIME NULL,
    sold_source ENUM("platform","external") NULL,
    expires_at DATETIME NULL,
        CONSTRAINT fk_ads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_ads_user_id (user_id),
        INDEX idx_ads_status (status),
        INDEX idx_ads_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS ad_media (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ad_id INT UNSIGNED NOT NULL,
        type ENUM("image","video") NOT NULL,
        sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        filepath VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_ad_media_ad FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        INDEX idx_ad_media_ad_id (ad_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS user_service_listings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        name VARCHAR(160) NOT NULL,
        category VARCHAR(80) NOT NULL,
        custom_category VARCHAR(80) NULL,
        phone VARCHAR(30) NULL,
        whatsapp VARCHAR(30) NULL,
        email VARCHAR(120) NULL,
        address_street VARCHAR(160) NULL,
        address_number VARCHAR(30) NULL,
        address_complement VARCHAR(80) NULL,
        address_neighborhood VARCHAR(120) NULL,
        address_city VARCHAR(120) NULL,
        address_state VARCHAR(60) NULL,
        address_postal_code VARCHAR(12) NULL,
        status ENUM("pending_payment","active","suspended","archived","expired") NOT NULL DEFAULT "active",
        expires_at DATETIME NULL,
        auto_renewal_enabled TINYINT(1) NOT NULL DEFAULT 0,
        last_renewed_at DATETIME NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        published_at DATETIME NULL,
        CONSTRAINT fk_user_service_listings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_service_listings_user (user_id),
        INDEX idx_user_service_listings_status (status),
        INDEX idx_user_service_listings_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $userColumnsToAdd = [
        'document_type' => 'ALTER TABLE users ADD COLUMN document_type ENUM("cpf","cnpj") NOT NULL DEFAULT "cpf" AFTER phone',
        'document_number' => 'ALTER TABLE users ADD COLUMN document_number VARCHAR(24) NULL AFTER document_type',
        'phone_country_iso' => 'ALTER TABLE users ADD COLUMN phone_country_iso CHAR(2) NULL AFTER phone',
        'phone_country_code' => 'ALTER TABLE users ADD COLUMN phone_country_code VARCHAR(8) NULL AFTER phone_country_iso',
        'phone_is_whatsapp' => 'ALTER TABLE users ADD COLUMN phone_is_whatsapp TINYINT(1) NOT NULL DEFAULT 0 AFTER phone_country_code',
        'show_phone' => 'ALTER TABLE users ADD COLUMN show_phone TINYINT(1) NOT NULL DEFAULT 1 AFTER phone_is_whatsapp',
        'show_whatsapp' => 'ALTER TABLE users ADD COLUMN show_whatsapp TINYINT(1) NOT NULL DEFAULT 1 AFTER show_phone',
        'show_email' => 'ALTER TABLE users ADD COLUMN show_email TINYINT(1) NOT NULL DEFAULT 0 AFTER email',
        'address_street' => 'ALTER TABLE users ADD COLUMN address_street VARCHAR(160) NULL AFTER document_number',
        'address_number' => 'ALTER TABLE users ADD COLUMN address_number VARCHAR(12) NULL AFTER address_street',
        'address_complement' => 'ALTER TABLE users ADD COLUMN address_complement VARCHAR(80) NULL AFTER address_number',
        'address_neighborhood' => 'ALTER TABLE users ADD COLUMN address_neighborhood VARCHAR(160) NULL AFTER address_complement',
        'address_city' => 'ALTER TABLE users ADD COLUMN address_city VARCHAR(160) NULL AFTER address_neighborhood',
        'address_state' => 'ALTER TABLE users ADD COLUMN address_state VARCHAR(80) NULL AFTER address_city',
        'address_postal_code' => 'ALTER TABLE users ADD COLUMN address_postal_code VARCHAR(12) NULL AFTER address_state',
        'show_address' => 'ALTER TABLE users ADD COLUMN show_address TINYINT(1) NOT NULL DEFAULT 0 AFTER address_postal_code',
        'show_document' => 'ALTER TABLE users ADD COLUMN show_document TINYINT(1) NOT NULL DEFAULT 0 AFTER show_address',
        'terms_accepted_at' => 'ALTER TABLE users ADD COLUMN terms_accepted_at DATETIME NULL AFTER address_postal_code',
        'google_id' => 'ALTER TABLE users ADD COLUMN google_id VARCHAR(64) NULL AFTER terms_accepted_at',
        'google_avatar' => 'ALTER TABLE users ADD COLUMN google_avatar VARCHAR(255) NULL AFTER google_id',
        'about' => 'ALTER TABLE users ADD COLUMN about TEXT NULL AFTER google_avatar',
        'platform_sales_count' => 'ALTER TABLE users ADD COLUMN platform_sales_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER about',
        'external_sales_count' => 'ALTER TABLE users ADD COLUMN external_sales_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER platform_sales_count',
        'last_session_token' => 'ALTER TABLE users ADD COLUMN last_session_token CHAR(64) NULL AFTER external_sales_count'
    ];

    foreach ($userColumnsToAdd as $columnName => $alterSql) {
        $columnExists = $pdo->query('SHOW COLUMNS FROM users LIKE ' . $pdo->quote($columnName))->fetch(PDO::FETCH_ASSOC);
        if (!$columnExists) {
            $pdo->exec($alterSql);
        }
    }

    $serviceColumnsToAdd = [
        'document_type' => 'ALTER TABLE user_service_listings ADD COLUMN document_type ENUM("cpf","cnpj") NULL AFTER user_id',
        'document_number' => 'ALTER TABLE user_service_listings ADD COLUMN document_number VARCHAR(24) NULL AFTER document_type',
        'show_document' => 'ALTER TABLE user_service_listings ADD COLUMN show_document TINYINT(1) NOT NULL DEFAULT 0 AFTER document_number',
        'external_reference' => 'ALTER TABLE user_service_listings ADD COLUMN external_reference VARCHAR(255) NULL UNIQUE AFTER show_document',
        'payment_status' => 'ALTER TABLE user_service_listings ADD COLUMN payment_status ENUM("pending","pending_gateway","paid","failed","refunded","cancelled","free") NOT NULL DEFAULT "pending" AFTER external_reference',
        'payment_method' => 'ALTER TABLE user_service_listings ADD COLUMN payment_method VARCHAR(50) NULL AFTER payment_status',
        'renewal_count' => 'ALTER TABLE user_service_listings ADD COLUMN renewal_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER payment_method',
        'description' => 'ALTER TABLE user_service_listings ADD COLUMN description LONGTEXT NULL AFTER renewal_count',
        'auto_renewal_enabled' => 'ALTER TABLE user_service_listings ADD COLUMN auto_renewal_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER expires_at',
        'last_renewed_at' => 'ALTER TABLE user_service_listings ADD COLUMN last_renewed_at DATETIME NULL AFTER auto_renewal_enabled',
    ];

    foreach ($serviceColumnsToAdd as $columnName => $alterSql) {
        $columnExists = $pdo->query('SHOW COLUMNS FROM user_service_listings LIKE ' . $pdo->quote($columnName))->fetch(PDO::FETCH_ASSOC);
        if (!$columnExists) {
            $pdo->exec($alterSql);
        }
    }

    $googleIndexExists = $pdo->query('SHOW INDEX FROM users WHERE Key_name = "idx_users_google_id"')->fetch(PDO::FETCH_ASSOC);
    if (!$googleIndexExists) {
        $pdo->exec('CREATE UNIQUE INDEX idx_users_google_id ON users (google_id)');
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        selector CHAR(18) NOT NULL UNIQUE,
        validator_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        last_used_at DATETIME NULL,
        CONSTRAINT fk_remember_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_remember_tokens_user (user_id),
        INDEX idx_remember_tokens_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS user_sessions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        session_token CHAR(64) NOT NULL UNIQUE,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        is_active BOOLEAN DEFAULT 1,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        last_used_at DATETIME NULL,
        CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_sessions_user (user_id),
        INDEX idx_user_sessions_token (session_token),
        INDEX idx_user_sessions_active (is_active),
        INDEX idx_user_sessions_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "vehicle_type"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN vehicle_type ENUM("carro","moto","caminhao","outros") NOT NULL DEFAULT "carro" AFTER user_id');
            $pdo->exec('UPDATE ads SET vehicle_type = "carro" WHERE vehicle_type IS NULL');
        }
    } catch (Throwable $exception) {
        // ignora falhas de migração incremental
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "doors"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN doors TINYINT UNSIGNED NULL AFTER fuel_type');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "plate_last_digit"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN plate_last_digit TINYINT UNSIGNED NULL AFTER license_plate');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "extra_attributes"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN extra_attributes TEXT NULL AFTER plate_last_digit');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "manufacture_year"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN manufacture_year SMALLINT UNSIGNED NOT NULL DEFAULT 2000 AFTER model');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "model_year"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN model_year SMALLINT UNSIGNED NOT NULL DEFAULT 2000 AFTER manufacture_year');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "mileage"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN mileage INT UNSIGNED NOT NULL DEFAULT 0 AFTER model_year');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "transmission"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN transmission VARCHAR(50) NOT NULL DEFAULT "" AFTER mileage');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "fuel_type"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN fuel_type VARCHAR(50) NOT NULL DEFAULT "" AFTER transmission');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "color"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN color VARCHAR(50) NOT NULL DEFAULT "" AFTER fuel_type');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "sold_at"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN sold_at DATETIME NULL AFTER approved_at');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "sold_source"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN sold_source ENUM("platform","external") NULL AFTER sold_at');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "expires_at"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN expires_at DATETIME NULL AFTER sold_source');
        }
    } catch (Throwable $exception) {
    }

    try {
    $column = $pdo->query('SHOW COLUMNS FROM ad_media LIKE "sort_order"')->fetch(PDO::FETCH_ASSOC);
    if (!$column) {
    $pdo->exec('ALTER TABLE ad_media ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER type');
    }
    } catch (Throwable $exception) {
    }

    // Garantir que o enum de status na tabela ads está correto
    try {
    $pdo->exec('ALTER TABLE ads MODIFY COLUMN status ENUM("draft","pending","approved","rejected","blocked","expired") NOT NULL DEFAULT "draft"');
    } catch (Throwable $exception) {
    // ignora se já está correto
    }

    // Adicionar colunas para sistema de verificação de anúncios premium
    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "verified_badge"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN verified_badge TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "verification_document_path"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN verification_document_path VARCHAR(500) NULL AFTER verified_badge');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "verification_reviewed_at"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN verification_reviewed_at DATETIME NULL AFTER verification_document_path');
        }
    } catch (Throwable $exception) {
    }

    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "verification_reviewed_by"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN verification_reviewed_by INT UNSIGNED NULL AFTER verification_reviewed_at');
        }
    } catch (Throwable $exception) {
    }

    // Adicionar coluna payment_status para rastreamento de pagamento
    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "payment_status"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN payment_status ENUM("pending","paid","failed","refunded") NOT NULL DEFAULT "pending" AFTER verification_reviewed_by');
        }
    } catch (Throwable $exception) {
    }

    // Adicionar coluna auto_renewal_enabled para renovação automática
    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "auto_renewal_enabled"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN auto_renewal_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER payment_status');
        }
    } catch (Throwable $exception) {
    }

    // Adicionar coluna last_renewed_at para rastrear última renovação
    try {
        $column = $pdo->query('SHOW COLUMNS FROM ads LIKE "last_renewed_at"')->fetch(PDO::FETCH_ASSOC);
        if (!$column) {
            $pdo->exec('ALTER TABLE ads ADD COLUMN last_renewed_at DATETIME NULL AFTER auto_renewal_enabled');
        }
    } catch (Throwable $exception) {
    }

    // Adicionar novos tipos de transação (ad_renewal, ad_auto_renewal) ao ENUM
    try {
        $typeColumn = $pdo->query('SHOW COLUMNS FROM coin_transactions WHERE Field = "type"')->fetch(PDO::FETCH_ASSOC);
        if ($typeColumn && (strpos($typeColumn['Type'], 'ad_renewal') === false || strpos($typeColumn['Type'], 'service_auto_renewal') === false)) {
            // O ENUM não contém os novos tipos, precisamos alterar
            $pdo->exec('ALTER TABLE coin_transactions MODIFY COLUMN type ENUM("buy", "spend", "admin_add", "admin_remove", "refund", "bonus", "ad_publish", "ad_feature", "ad_renewal", "ad_auto_renewal", "service_renewal", "service_auto_renewal") NOT NULL COMMENT "Tipo de transação"');
        }
    } catch (Throwable $exception) {
        // Se falhar, log mas continue - pode ser que os tipos já existam
        error_log('[sqlconnect] Aviso ao tentar adicionar tipos de renovação ao ENUM: ' . $exception->getMessage());
    }

    // Adicionar índices para performance
    try {
        $indexes = $pdo->query('SHOW INDEX FROM ads WHERE Key_name = "idx_ads_verified_badge"')->fetchAll();
        if (empty($indexes)) {
            $pdo->exec('ALTER TABLE ads ADD INDEX idx_ads_verified_badge (verified_badge)');
        }
    } catch (Throwable $exception) {
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS premium_plans (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        description TEXT NULL,
        price_decimal DECIMAL(10,2) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS premium_subscriptions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        plan_id INT UNSIGNED NOT NULL,
        started_at DATETIME NOT NULL,
        expires_at DATETIME NULL,
        payment_id BIGINT UNSIGNED DEFAULT NULL,
        payment_status VARCHAR(32) DEFAULT NULL,
        amount_paid DECIMAL(10,2) DEFAULT NULL,
        payment_currency VARCHAR(3) DEFAULT "BRL",
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        CONSTRAINT fk_premium_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_premium_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES premium_plans(id) ON DELETE CASCADE,
        INDEX idx_premium_subscriptions_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    // Garantir colunas adicionadas em vers�es anteriores
    try {
        $pdo->exec('ALTER TABLE premium_subscriptions ADD COLUMN payment_id BIGINT UNSIGNED DEFAULT NULL AFTER expires_at');
    } catch (Throwable $exception) {
        // pode j� existir
    }
    try {
        $pdo->exec('ALTER TABLE premium_subscriptions ADD COLUMN payment_status VARCHAR(32) DEFAULT NULL AFTER payment_id');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE premium_subscriptions ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT NULL AFTER payment_status');
    } catch (Throwable $exception) {
    }
    try {
        $pdo->exec('ALTER TABLE premium_subscriptions ADD COLUMN payment_currency VARCHAR(3) DEFAULT "BRL" AFTER amount_paid');
    } catch (Throwable $exception) {
    }

    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(120) NOT NULL UNIQUE,
        `value` TEXT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS audit_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        actor_user_id INT UNSIGNED NULL,
        action VARCHAR(100) NOT NULL,
        entity VARCHAR(60) NULL,
        entity_id INT NULL,
        meta TEXT NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_audit_logs_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_audit_logs_action (action),
        INDEX idx_audit_logs_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS saved_searches (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        search_name VARCHAR(255) NOT NULL,
        search_params JSON NOT NULL,
        notify TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_saved_searches_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_saved_searches_user (user_id),
        INDEX idx_saved_searches_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS search_notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        saved_search_id INT UNSIGNED NOT NULL,
        ad_id INT UNSIGNED NOT NULL,
        notified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_search_notif_search FOREIGN KEY (saved_search_id) REFERENCES saved_searches(id) ON DELETE CASCADE,
        CONSTRAINT fk_search_notif_ad FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        UNIQUE KEY uniq_search_ad (saved_search_id, ad_id),
        INDEX idx_search_notif_created (notified_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS rate_limits (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        route VARCHAR(120) NOT NULL,
        attempts INT NOT NULL DEFAULT 0,
        last_attempt DATETIME NOT NULL,
        UNIQUE KEY uniq_rate_limits (ip_address, route)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS ad_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_id INT UNSIGNED NOT NULL,
    reporter_user_id INT UNSIGNED NOT NULL,
    status ENUM("open","under_review","resolved","dismissed") NOT NULL DEFAULT "open",
    reason VARCHAR(120) NOT NULL,
    description TEXT NULL,
    evidence_url VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    admin_notes TEXT NULL,
    handled_by INT UNSIGNED NULL,
    CONSTRAINT fk_ad_reports_ad FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    CONSTRAINT fk_ad_reports_reporter FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ad_reports_handler FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ad_reports_status (status),
    INDEX idx_ad_reports_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    $pdo->exec('CREATE TABLE IF NOT EXISTS user_blacklist (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        original_user_id INT UNSIGNED NULL,
        name VARCHAR(120) NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(25) NULL,
        document_type ENUM("cpf","cnpj") NULL,
        document_number VARCHAR(24) NULL,
        reason TEXT NULL,
        banned_by INT UNSIGNED NULL,
        banned_at DATETIME NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        CONSTRAINT fk_user_blacklist_banned_by FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_blacklist_email (email),
        INDEX idx_user_blacklist_document (document_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    // Criar tabela coin_transactions para auditoria de moedas
    $pdo->exec('CREATE TABLE IF NOT EXISTS coin_transactions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        type ENUM("buy", "spend", "admin_add", "admin_remove", "refund", "bonus", "ad_publish", "ad_feature", "ad_renewal", "ad_auto_renewal", "service_renewal", "service_auto_renewal") NOT NULL COMMENT "Tipo de transação",
        amount INT NOT NULL COMMENT "Positivo=entrada, Negativo=saída",
        balance_before INT NOT NULL COMMENT "Saldo antes",
        balance_after INT NOT NULL COMMENT "Saldo depois",
        reference_id BIGINT UNSIGNED NULL COMMENT "ID do anúncio/pedido/admin_id/payment_id",
        reference_type VARCHAR(50) NULL COMMENT "ads|purchase|manual|system|coin_purchase",
        payment_method VARCHAR(50) NULL COMMENT "mercado_pago|manual|system",
        external_reference VARCHAR(255) NULL COMMENT "ID externo (MP preference id)",
        description TEXT NULL COMMENT "Descrição legível",
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        CONSTRAINT fk_coin_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_type (type),
        INDEX idx_created_at (created_at),
        INDEX idx_external_ref (external_reference),
        INDEX idx_reference_id (reference_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

    seedInitialData($pdo);
    ensureUploadFolders();

    $ran = true;
}

/**
 * Cria dados iniciais essenciais para funcionamento do sistema.
 */
function seedInitialData(PDO $pdo): void
{
    $now = now();

    $planStmt = $pdo->prepare('SELECT id FROM premium_plans WHERE name = :name LIMIT 1');
    $planStmt->execute([':name' => 'Premium Ilimitado']);
    if (!$planStmt->fetch()) {
        $insert = $pdo->prepare('INSERT INTO premium_plans (name, description, price_decimal, is_active, created_at, updated_at) VALUES (:name, :description, :price_decimal, :is_active, :created_at, :updated_at)');
        $insert->execute([
            ':name' => 'Premium Ilimitado',
            ':description' => 'Anuncie veículos sem limites, incluindo vídeo, enquanto testamos o sistema de pagamentos.',
            ':price_decimal' => 0,
            ':is_active' => 1,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    // COMENTADO: Usuários de teste foram removidos. Descomente se precisar recriá-los.
    // $adminEmail = 'admin@site.com';
    // $userEmail  = 'user@site.com';
    //
    // $adminStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    // $adminStmt->execute([':email' => $adminEmail]);
    // if (!$adminStmt->fetch()) {
    //     $insertAdmin = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, is_admin, is_premium, email_verified_at, status, created_at, updated_at) VALUES (:name, :email, :phone, :password_hash, :is_admin, :is_premium, :email_verified_at, :status, :created_at, :updated_at)');
    //     $insertAdmin->execute([
    //         ':name' => 'Administrador',
    //         ':email' => $adminEmail,
    //         ':phone' => '+55 (11) 99999-0000',
    //         ':password_hash' => password_hash('Admin@123456', PASSWORD_DEFAULT),
    //         ':is_admin' => 1,
    //         ':is_premium' => 1,
    //         ':email_verified_at' => $now,
    //         ':status' => 'active',
    //         ':created_at' => $now,
    //         ':updated_at' => $now,
    //     ]);
    // }
    //
    // $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    // $userStmt->execute([':email' => $userEmail]);
    // if (!$userStmt->fetch()) {
    //     $insertUser = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, is_admin, is_premium, email_verified_at, status, created_at, updated_at) VALUES (:name, :email, :phone, :password_hash, :is_admin, :is_premium, :email_verified_at, :status, :created_at, :updated_at)');
    //     $insertUser->execute([
    //         ':name' => 'Usuário Demo',
    //         ':email' => $userEmail,
    //         ':phone' => '+55 (11) 98888-1234',
    //         ':password_hash' => password_hash('User@123456', PASSWORD_DEFAULT),
    //         ':is_admin' => 0,
    //         ':is_premium' => 0,
    //         ':email_verified_at' => null,
    //         ':status' => 'active',
    //         ':created_at' => $now,
    //         ':updated_at' => $now,
    //     ]);
    // }

    ensureSetting($pdo, 'free_ads_per_month', '3');
    ensureSetting($pdo, 'max_images', '12');
    ensureSetting($pdo, 'allow_video_premium', '1');
    ensureSetting($pdo, 'max_video_size_mb', '30');
    ensureSetting($pdo, 'max_image_size_mb', '2');

    seedSampleAds($pdo);
}

/**
 * Garante que uma configuração exista com valor padrão.
 */
function ensureSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare('SELECT id FROM settings WHERE `key` = :key LIMIT 1');
    $stmt->execute([':key' => $key]);
    if (!$stmt->fetch()) {
        $insert = $pdo->prepare('INSERT INTO settings (`key`, `value`, updated_at) VALUES (:key, :value, :updated_at)');
        $insert->execute([
            ':key' => $key,
            ':value' => $value,
            ':updated_at' => now(),
        ]);
    }
}

/**
 * Cria anúncios demo caso ainda não existam.
 */
function seedSampleAds(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM ads');
    $total = (int)($stmt->fetch()['total'] ?? 0);
    if ($total > 0) {
        return;
    }

    $now = now();

    $userId = (int)$pdo->query("SELECT id FROM users WHERE email = 'user@site.com' LIMIT 1")->fetchColumn();
    if (!$userId) {
        return;
    }

    $adsData = [
        [
            'title' => 'Honda Civic Touring 2020 Turbo Completo',
            'slug' => slugify('Honda Civic Touring 2020 Turbo Completo'),
            'description' => 'Veículo em excelente estado, todas as revisões feitas na concessionária, único dono.',
            'price' => 148900.00,
            'city' => 'São Paulo',
            'state' => 'SP',
            'brand' => 'Honda',
            'model' => 'Civic',
            'manufacture_year' => 2020,
            'model_year' => 2020,
            'mileage' => 32000,
            'transmission' => 'Automático',
            'fuel_type' => 'Gasolina',
            'color' => 'Cinza',
            'license_plate' => null,
            'status' => 'approved',
            'approved_at' => $now,
        ],
        [
            'title' => 'Jeep Compass Limited 2019 4x4 Diesel',
            'slug' => slugify('Jeep Compass Limited 2019 4x4 Diesel'),
            'description' => 'SUV completo com pacote de segurança, bancos em couro e multimídia com CarPlay.',
            'price' => 169990.00,
            'city' => 'Curitiba',
            'state' => 'PR',
            'brand' => 'Jeep',
            'model' => 'Compass',
            'manufacture_year' => 2019,
            'model_year' => 2019,
            'mileage' => 61000,
            'transmission' => 'Automático',
            'fuel_type' => 'Diesel',
            'color' => 'Branco',
            'license_plate' => null,
            'status' => 'approved',
            'approved_at' => $now,
        ],
        [
            'title' => 'Volkswagen Gol 1.0 2021 Completo',
            'slug' => slugify('Volkswagen Gol 1.0 2021 Completo'),
            'description' => 'Ótima opção para o dia a dia, baixo consumo e manutenção em dia. Carro aguardando aprovação.',
            'price' => 55900.00,
            'city' => 'Belo Horizonte',
            'state' => 'MG',
            'brand' => 'Volkswagen',
            'model' => 'Gol',
            'manufacture_year' => 2021,
            'model_year' => 2021,
            'mileage' => 41000,
            'transmission' => 'Manual',
            'fuel_type' => 'Flex',
            'color' => 'Prata',
            'license_plate' => null,
            'status' => 'pending',
            'approved_at' => null,
        ],
    ];

    $insertAd = $pdo->prepare('INSERT INTO ads (
        user_id, title, slug, description, price, city, state, brand, model, manufacture_year, model_year, mileage,
        transmission, fuel_type, color, license_plate, status, reason_rejected, created_at, updated_at, approved_at
    ) VALUES (
        :user_id, :title, :slug, :description, :price, :city, :state, :brand, :model, :manufacture_year, :model_year, :mileage,
        :transmission, :fuel_type, :color, :license_plate, :status, NULL, :created_at, :updated_at, :approved_at
    )');

    $insertMedia = $pdo->prepare('INSERT INTO ad_media (ad_id, type, filepath, created_at) VALUES (:ad_id, :type, :filepath, :created_at)');

    foreach ($adsData as $ad) {
        $insertAd->execute([
            ':user_id' => $userId,
            ':title' => $ad['title'],
            ':slug' => $ad['slug'],
            ':description' => $ad['description'],
            ':price' => $ad['price'],
            ':city' => $ad['city'],
            ':state' => $ad['state'],
            ':brand' => $ad['brand'],
            ':model' => $ad['model'],
            ':manufacture_year' => $ad['manufacture_year'],
            ':model_year' => $ad['model_year'],
            ':mileage' => $ad['mileage'],
            ':transmission' => $ad['transmission'],
            ':fuel_type' => $ad['fuel_type'],
            ':color' => $ad['color'],
            ':license_plate' => $ad['license_plate'],
            ':status' => $ad['status'],
            ':created_at' => $now,
            ':updated_at' => $now,
            ':approved_at' => $ad['approved_at'],
        ]);

        $adId = (int)$pdo->lastInsertId();
        $placeholders = [
            'assets/uploads/images/sample-car-1.jpg',
            'assets/uploads/images/sample-car-2.jpg',
            'assets/uploads/images/sample-car-3.jpg',
        ];
        foreach ($placeholders as $i => $path) {
            if ($i > 1 && $ad['status'] === 'pending') {
                break;
            }
            $insertMedia->execute([
                ':ad_id' => $adId,
                ':type' => 'image',
                ':filepath' => $path,
                ':created_at' => $now,
            ]);
        }
    }
}

/**
 * Garante que as pastas de upload existam com proteção .htaccess.
 */
function ensureUploadFolders(): void
{
    $base = __DIR__ . '/assets/uploads';
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }
    foreach (['', '/images', '/videos'] as $path) {
        $fullPath = $base . $path;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        $htaccessPath = $fullPath . '/.htaccess';
        $content = "<FilesMatch \.php$>\n    Deny from all\n</FilesMatch>\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps\nphp_flag engine off\nOptions -ExecCGI\n";
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, $content, LOCK_EX);
        }
    }

    ensureSampleImages();
}

/**
 * Cria imagens placeholder simples para os anúncios demo.
 */
function ensureSampleImages(): void
{
    $images = [
        __DIR__ . '/assets/uploads/images/sample-car-1.jpg',
        __DIR__ . '/assets/uploads/images/sample-car-2.jpg',
        __DIR__ . '/assets/uploads/images/sample-car-3.jpg',
    ];
    foreach ($images as $index => $path) {
        if (file_exists($path)) {
            continue;
        }
        $color = [
            [200, 40, 40],
            [40, 140, 200],
            [40, 200, 120],
        ][$index % 3];
        createPlaceholderImage($path, $color);
    }
}

/**
 * Gera uma imagem JPEG simples usando GD.
 */
function createPlaceholderImage(string $path, array $rgb): void
{
    $width = 1200;
    $height = 675;
    $image = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    imagefill($image, 0, 0, $background);

    $textColor = imagecolorallocate($image, 255, 255, 255);
    $fontSize = 5;
    $text = 'SiteCarros Demo';
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    $x = (int)(($width - $textWidth) / 2);
    $y = (int)(($height - $textHeight) / 2);
    imagestring($image, $fontSize, $x, $y, $text, $textColor);

    imagejpeg($image, $path, 80);
    imagedestroy($image);
}

/** Helper para data/hora atual em formato MySQL. */
function now(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * Gera um slug amigável simples para títulos.
 */
function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    if (empty($text)) {
        return 'anuncio';
    }
    return $text;
}

/**
 * Garante que o slug do anúncio seja único na tabela ads.
 */
function generate_unique_ad_slug(PDO $pdo, string $title, ?int $ignoreId = null, int $maxLength = 160): string
{
    $base = slugify($title);
    if ($base === '') {
        $base = 'anuncio';
    }

    $base = substr($base, 0, $maxLength);
    $candidate = $base;
    $suffix = 2;

    $query = 'SELECT COUNT(*) FROM ads WHERE slug = :slug';
    if ($ignoreId !== null) {
        $query .= ' AND id <> :ignore';
    }
    $stmt = $pdo->prepare($query);

    while (true) {
        $params = [':slug' => $candidate];
        if ($ignoreId !== null) {
            $params[':ignore'] = $ignoreId;
        }

        $stmt->execute($params);
        if ((int)$stmt->fetchColumn() === 0) {
            return $candidate;
        }

        $suffixString = (string)$suffix;
        $separator = '-';
        $allowedBaseLength = max(1, $maxLength - strlen($suffixString) - strlen($separator));
        $candidate = substr($base, 0, $allowedBaseLength) . $separator . $suffixString;
        $suffix += 1;
    }
}

/**
 * Retorna um valor de configuração a partir do banco.
 */
function get_setting(string $key, $default = null)
{
    global $_SETTINGS_CACHE;
    
    if (array_key_exists($key, $_SETTINGS_CACHE)) {
        return $_SETTINGS_CACHE[$key];
    }
    
    $stmt = db()->prepare('SELECT `value` FROM settings WHERE `key` = :key LIMIT 1');
    $stmt->execute([':key' => $key]);
    $value = $stmt->fetchColumn();
    if ($value === false) {
        $_SETTINGS_CACHE[$key] = $default;
        return $default;
    }
    $_SETTINGS_CACHE[$key] = $value;
    return $value;
}

/** Atualiza um setting existente. */
function update_setting(string $key, string $value): void
{
    global $_SETTINGS_CACHE;
    
    // Limpar cache para forçar releitura do BD na próxima chamada
    unset($_SETTINGS_CACHE[$key]);
    
    $stmt = db()->prepare('INSERT INTO settings (`key`, `value`, updated_at) VALUES (:key, :value, :updated_at)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = VALUES(updated_at)');
    $stmt->execute([
        ':key' => $key,
        ':value' => $value,
        ':updated_at' => now(),
    ]);
}

/**
 * Retorna a URL base da aplicação com fallback para o host atual.
 */
function app_base_url(): string
{
    static $baseUrl = null;
    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $configured = trim((string)getenv('APP_URL'));
    if ($configured === '') {
        $configured = trim((string)get_setting('app_url', ''));
    }

    if ($configured !== '') {
        $baseUrl = rtrim($configured, '/');
        return $baseUrl;
    }

    $scheme = 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $forwarded = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO']);
        $schemeCandidate = strtolower(trim((string)$forwarded[0]));
        if ($schemeCandidate === 'http' || $schemeCandidate === 'https') {
            $scheme = $schemeCandidate;
        }
    } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
        $schemeCandidate = strtolower((string)$_SERVER['REQUEST_SCHEME']);
        if ($schemeCandidate === 'http' || $schemeCandidate === 'https') {
            $scheme = $schemeCandidate;
        }
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443') {
        $scheme = 'https';
    } else {
        $scheme = 'http';
    }

    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
    if ($host === '') {
        $fallbackHost = trim((string)getenv('APP_FALLBACK_HOST'));
        if ($fallbackHost === '') {
            $fallbackHost = 'classilivre.com.br';
        }
        $host = $fallbackHost;
    }

    $baseUrl = rtrim($scheme . '://' . $host, '/');
    return $baseUrl;
}

/**
 * Monta uma URL absoluta a partir da base da aplicação.
 */
function app_url(string $path = ''): string
{
    $base = app_base_url();
    $path = trim($path);

    if ($path === '' || $path === '/') {
        return $base;
    }

    $first = $path[0] ?? '';
    if ($first === '?' || $first === '#') {
        return $base . $path;
    }

    return $base . '/' . ltrim($path, '/');
}

/**
 * Registra evento na tabela audit_logs.
 */
function record_audit(?int $actorId, string $action, ?string $entity = null, ?int $entityId = null, array $meta = []): void
{
    $stmt = db()->prepare('INSERT INTO audit_logs (actor_user_id, action, entity, entity_id, meta, created_at) VALUES (:actor_user_id, :action, :entity, :entity_id, :meta, :created_at)');
    $stmt->execute([
        ':actor_user_id' => $actorId,
        ':action' => $action,
        ':entity' => $entity,
        ':entity_id' => $entityId,
        ':meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        ':created_at' => now(),
    ]);
}

/**
 * Formata valores monetários no padrão BRL.
 */
function format_currency(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Aplica máscara simples ao telefone armazenado.
 */
function mask_phone(?string $phone): string
{
    if (!$phone) {
        return 'Não informado';
    }
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) === 11) {
        return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
    }
    if (strlen($digits) === 10) {
        return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
    }
    return $phone;
}

/**
 * Formata documento CPF/CNPJ.
 */
function format_document(?string $document, string $type = 'cpf'): string
{
    if (!$document) {
        return '';
    }
    $digits = preg_replace('/\D+/', '', (string)$document);
    if ($type === 'cnpj' && strlen($digits) === 14) {
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($digits, 0, 2),
            substr($digits, 2, 3),
            substr($digits, 5, 3),
            substr($digits, 8, 4),
            substr($digits, 12)
        );
    }
    if (strlen($digits) === 11) {
        return sprintf(
            '%s.%s.%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3),
            substr($digits, 9)
        );
    }
    return $digits !== '' ? $digits : (string)$document;
}

/**
 * Calcula o total de anúncios criados pelo usuário no mês atual.
 */
function count_user_ads_in_month(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM ads WHERE user_id = :user_id AND status NOT IN ("rejected","blocked") AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())');
    $stmt->execute([':user_id' => $userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Retorna true se o usuário possui assinatura premium ativa.
 */
function user_is_premium(array $user): bool
{
    if (!empty($user['is_premium'])) {
        return true;
    }
    $stmt = db()->prepare('SELECT 1 FROM premium_subscriptions WHERE user_id = :user_id AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1');
    $stmt->execute([':user_id' => (int)$user['id']]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Verifica se um anúncio possui vídeo cadastrado.
 */
function ad_has_video(int $adId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM ad_media WHERE ad_id = :ad_id AND type = "video" LIMIT 1');
    $stmt->execute([':ad_id' => $adId]);
    return (bool)$stmt->fetchColumn();
}

runMigrations();


// === [MIGRATIONS][EMAIL] início — idempotente e seguro ===
if (!function_exists('ensure_email_queue_schema')) {
    function ensure_email_queue_schema($pdo) {
        if (!($pdo instanceof PDO)) { return; }
        try {
            // 1) Tabela da fila
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS email_queue (
                  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  to_email VARCHAR(190) NOT NULL,
                  to_name  VARCHAR(190) NULL,
                  subject  VARCHAR(190) NOT NULL,
                  body_html MEDIUMTEXT NOT NULL,
                  body_text MEDIUMTEXT NULL,
                  attachments_json JSON NULL,
                  status ENUM('queued','sent','error') NOT NULL DEFAULT 'queued',
                  tries TINYINT UNSIGNED NOT NULL DEFAULT 0,
                  last_error TEXT NULL,
                  scheduled_at DATETIME NULL,
                  sent_at DATETIME NULL,
                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // 2) Ajustes/índices úteis (idempotentes via IGNORE erros)
            try { $pdo->exec("ALTER TABLE email_queue MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"); } catch (Throwable $e) {}
            try { $pdo->exec("CREATE INDEX idx_email_queue_status_sched ON email_queue (status, scheduled_at, created_at)"); } catch (Throwable $e) {}
            try { $pdo->exec("CREATE INDEX idx_email_queue_created ON email_queue (created_at)"); } catch (Throwable $e) {}

            // 3) Colunas no users para verificação e reset (ignora duplicadas)
            $alterStmts = [
                "ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL",
                "ALTER TABLE users ADD COLUMN email_verify_token VARCHAR(64) NULL",
                "ALTER TABLE users ADD COLUMN email_pending VARCHAR(190) NULL",
                "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL",
                "ALTER TABLE users ADD COLUMN reset_expires_at DATETIME NULL",
            ];
            foreach ($alterStmts as $sql) {
                try { $pdo->exec($sql); } catch (Throwable $e) { /* ignora 'duplicate column' e similares */ }
            }
        } catch (Throwable $e) {
            error_log('[MIGRATION][email_queue] '.$e->getMessage());
        }
    }
}
// Executa automaticamente a migração (não quebra a página se falhar)
try {
    $___pdo_mig = function_exists('db') ? db() : (isset($pdo) ? $pdo : null);
    if ($___pdo_mig instanceof PDO) { ensure_email_queue_schema($___pdo_mig); }
} catch (Throwable $e) {
    error_log('[MIGRATION][email_queue][runner] '.$e->getMessage());
}
// === [MIGRATIONS][EMAIL] fim ===

/**
 * Retorna configurações do Mercado Pago (credenciais + URLs)
 * Lê do arquivo config/mercadopago.php baseado no modo configurado
 */
function get_mercadopago_config(): array
{
    static $config = null;
    
    if ($config !== null) {
        return $config;
    }

    $configFile = __DIR__ . '/config/mercadopago.php';
    
    if (!file_exists($configFile)) {
        error_log('[MP] Arquivo de configuração não encontrado: ' . $configFile);
        return [
            'public_key' => '',
            'access_token' => '',
        ];
    }

    $allConfig = require $configFile;
    $mode = $allConfig['mode'] ?? 'test';
    $modeConfig = $allConfig[$mode] ?? [];

    $config = [
        'mode' => $mode,
        'public_key' => $modeConfig['public_key'] ?? '',
        'access_token' => $modeConfig['access_token'] ?? '',
        'webhook_url' => $allConfig['webhook_url'] ?? '',
        'success_url' => $allConfig['success_url'] ?? '',
        'failure_url' => $allConfig['failure_url'] ?? '',
    ];

    return $config;
}

/**
 * ============================================================================
 * SISTEMA DE MOEDAS CLIVRE MEDAL
 * ============================================================================
 */

/**
 * Adiciona ou remove moedas de um usuário com auditoria completa
 * 
 * @param int $userId ID do usuário
 * @param int $amount Quantidade (positivo = entrada, negativo = saída)
 * @param string $type buy|spend|admin_add|admin_remove|refund|bonus
 * @param int|null $referenceId ID do anúncio/pedido/admin
 * @param string|null $referenceType ads|purchase|manual|system
 * @param string|null $description Descrição legível
 * @return bool True se sucesso, false se erro
 */
function adjust_user_coins(int $userId, int $amount, string $type, ?int $referenceId = null, ?string $referenceType = null, ?string $description = null): bool
{
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    try {
        $pdo = db();
        
        // Log do início
        $logMsg = sprintf(
            '[adjust_user_coins] START: userId=%d, amount=%d, type=%s, ref_id=%s, ref_type=%s',
            $userId, $amount, $type, $referenceId ?? 'null', $referenceType ?? 'null'
        );
        @file_put_contents($logDir . '/coin-adjust.log', '[' . date('Y-m-d H:i:s') . '] ' . $logMsg . PHP_EOL, FILE_APPEND);
        
        $pdo->beginTransaction();

        // 1. Pegar saldo atual (com lock)
        $stmt = $pdo->prepare('SELECT coin_balance FROM users WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Usuário não encontrado no banco de dados');
        }

        $balanceBefore = (int)$user['coin_balance'];
        $balanceAfter = $balanceBefore + $amount;

        @file_put_contents($logDir . '/coin-adjust.log', '[' . date('Y-m-d H:i:s') . '] [adjust_user_coins] Saldo antes: ' . $balanceBefore . ', depois será: ' . $balanceAfter . PHP_EOL, FILE_APPEND);

        // 2. Validar que saldo não fica negativo
        if ($balanceAfter < 0) {
            throw new Exception('Saldo insuficiente: ' . $balanceBefore . ' < ' . abs($amount));
        }

        // 3. Atualizar saldo
        $update = $pdo->prepare('UPDATE users SET coin_balance = :balance, updated_at = :updated_at WHERE id = :id');
        $result = $update->execute([
            ':balance' => $balanceAfter,
            ':updated_at' => now(),
            ':id' => $userId
        ]);
        
        if (!$result) {
            throw new Exception('Falha ao executar UPDATE no banco de dados');
        }
        
        @file_put_contents($logDir . '/coin-adjust.log', '[' . date('Y-m-d H:i:s') . '] [adjust_user_coins] UPDATE executado com sucesso' . PHP_EOL, FILE_APPEND);

        // 4. Registrar transação
        $insert = $pdo->prepare('
            INSERT INTO coin_transactions 
            (user_id, type, amount, balance_before, balance_after, reference_id, reference_type, description, created_at) 
            VALUES (:user_id, :type, :amount, :balance_before, :balance_after, :reference_id, :reference_type, :description, :created_at)
        ');
        $result = $insert->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':amount' => $amount,
            ':balance_before' => $balanceBefore,
            ':balance_after' => $balanceAfter,
            ':reference_id' => $referenceId,
            ':reference_type' => $referenceType,
            ':description' => $description,
            ':created_at' => now()
        ]);
        
        if (!$result) {
            throw new Exception('Falha ao executar INSERT na tabela coin_transactions');
        }
        
        @file_put_contents($logDir . '/coin-adjust.log', '[' . date('Y-m-d H:i:s') . '] [adjust_user_coins] INSERT em coin_transactions executado com sucesso' . PHP_EOL, FILE_APPEND);

        $pdo->commit();
        
        @file_put_contents($logDir . '/coin-adjust.log', '[' . date('Y-m-d H:i:s') . '] [adjust_user_coins] COMMIT executado - SUCESSO!' . PHP_EOL, FILE_APPEND);
        
        return true;

    } catch (Throwable $e) {
        @file_put_contents($logDir . '/coin-adjust.log', '[' . date('Y-m-d H:i:s') . '] [adjust_user_coins] ERRO: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        
        try {
            db()->rollBack();
            @file_put_contents($logDir . '/coin-adjust.log', '[' . date('Y-m-d H:i:s') . '] [adjust_user_coins] ROLLBACK executado' . PHP_EOL, FILE_APPEND);
        } catch (Throwable $rb) {
            @file_put_contents($logDir . '/coin-adjust.log', '[' . date('Y-m-d H:i:s') . '] [adjust_user_coins] ERRO no ROLLBACK: ' . $rb->getMessage() . PHP_EOL, FILE_APPEND);
        }
        error_log("adjust_user_coins error (user=$userId, amount=$amount): " . $e->getMessage());
        return false;
    }
}

/**
 * Cache de saldo de moedas (usado por get_user_coin_balance)
 * Armazenado em $_SESSION para poder ser invalidado
 */
$GLOBALS['_coin_cache'] = $GLOBALS['_coin_cache'] ?? [];

/**
 * Retorna saldo de moedas do usuário
 * Usa cache em memória para melhor performance
 */
function get_user_coin_balance(int $userId): int
{
    // Verificar cache global
    if (isset($GLOBALS['_coin_cache'][$userId])) {
        return $GLOBALS['_coin_cache'][$userId];
    }
    
    $stmt = db()->prepare('SELECT coin_balance FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    $balance = (int)($user['coin_balance'] ?? 0);
    $GLOBALS['_coin_cache'][$userId] = $balance;
    return $balance;
}

/**
 * Invalida cache de saldo de moedas (chamar após ajuste)
 * Limpa do cache global para forçar releitura do BD
 */
function invalidate_coin_cache(int $userId): void
{
    // ✅ CORRIGIDO: Remove do cache global
    if (isset($GLOBALS['_coin_cache'][$userId])) {
        unset($GLOBALS['_coin_cache'][$userId]);
    }
}

/**
 * Deleta um anúncio e todas as suas mídias associadas
 * Função centralizada usado por: admin, usuário, cron de limpeza
 * 
 * @param int $adId ID do anúncio a deletar
 * @return bool true se deletou com sucesso, false se não encontrou
 */
function delete_ad_with_media(int $adId): bool
{
    try {
        $pdo = db();
        
        // Carregar função delete_media_file se não existir
        if (!function_exists('delete_media_file')) {
            require_once __DIR__ . '/anuncios/upload_handler.php';
        }
        
        $pdo->beginTransaction();
        
        // 1. Buscar o anúncio
        $stmt = $pdo->prepare('SELECT id, user_id, title FROM ads WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $adId]);
        $ad = $stmt->fetch();
        
        if (!$ad) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
        
        // 2. Buscar e deletar mídias (ad_media)
        $mediaStmt = $pdo->prepare('SELECT filepath FROM ad_media WHERE ad_id = :ad_id');
        $mediaStmt->execute([':ad_id' => $adId]);
        $mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Deletar arquivo físico de cada mídia
        foreach ($mediaFiles as $media) {
            @delete_media_file($media['filepath']);
        }
        
        // 3. Deletar registros de mídias no BD (CASCADE fará isso)
        // 4. Deletar anúncio
        $deleteStmt = $pdo->prepare('DELETE FROM ads WHERE id = :id');
        $deleteStmt->execute([':id' => $adId]);
        
        $pdo->commit();
        
        // 5. Log
        error_log("[delete_ad_with_media] Anúncio #$adId deletado com sucesso. Título: {$ad['title']}");
        
        return true;
        
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("[delete_ad_with_media] ERRO ao deletar ad #$adId: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se um serviço automotivo deve exibir o botão de renovação
 * 
 * Lógica:
 * - Se status = 'active' E dias restantes <= 7 → VISÍVEL
 * - Se status = 'expired' E dias desde expiração <= 15 → VISÍVEL
 * - Caso contrário → INVISÍVEL
 * 
 * @param array $service Array com campos: status, expires_at
 * @return bool true se botão deve ser visível
 */
function should_show_service_renewal_button(array $service): bool
{
    $status = (string)($service['status'] ?? '');
    $expiresAt = $service['expires_at'] ?? null;

    if (!$expiresAt || !is_string($expiresAt)) {
        return false;
    }

    $daysRemaining = get_service_days_remaining($expiresAt);

    if ($status === 'active') {
        return $daysRemaining > 0 && $daysRemaining <= 7;
    }

    if ($status === 'expired') {
        return $daysRemaining >= -15 && $daysRemaining <= 0;
    }

    return false;
}

/**
 * Calcula os dias restantes até expiração de um serviço
 * 
 * Positivo = dias até expirar
 * Zero/Negativo = dias desde que expirou
 * 
 * @param string $expiresAt Data de expiração (formato MySQL: YYYY-MM-DD HH:MM:SS)
 * @return int Dias restantes (positivo) ou desde expiração (negativo)
 */
function get_service_days_remaining(string $expiresAt): int
{
    $expiresAtTime = strtotime($expiresAt);
    if ($expiresAtTime === false) {
        return 0;
    }

    $secondsRemaining = $expiresAtTime - time();
    return (int)ceil($secondsRemaining / 86400);
}

// ✅ Incluir middleware para disponibilizar funções de autenticação e flash
require_once __DIR__ . '/login_system/middleware.php';

