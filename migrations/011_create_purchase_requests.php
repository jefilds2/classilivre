<?php
// migrations/011_create_purchase_requests.php - Criar tabela de solicitacoes de compra.

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';

echo "[011] Verificando tabela purchase_requests...\n";

try {
    $pdo = db();

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS purchase_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            vehicle_type ENUM("carro","moto","caminhao","outros") NOT NULL DEFAULT "carro",
            brand VARCHAR(100) NULL,
            model VARCHAR(120) NULL,
            min_year SMALLINT UNSIGNED NULL,
            max_year SMALLINT UNSIGNED NULL,
            transmission_preference VARCHAR(60) NULL,
            fuel_preference VARCHAR(60) NULL,
            color_preference VARCHAR(60) NULL,
            max_mileage INT UNSIGNED NULL,
            budget_max DECIMAL(12,2) NULL,
            city VARCHAR(120) NOT NULL,
            state VARCHAR(2) NOT NULL DEFAULT "MG",
            details TEXT NULL,
            contact_phone VARCHAR(25) NULL,
            contact_whatsapp VARCHAR(25) NULL,
            contact_method ENUM("whatsapp","phone","both") NOT NULL DEFAULT "whatsapp",
            status ENUM("active","paused","closed") NOT NULL DEFAULT "active",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            expires_at DATETIME NULL,
            CONSTRAINT fk_purchase_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_purchase_requests_user (user_id),
            INDEX idx_purchase_requests_status_created (status, created_at),
            INDEX idx_purchase_requests_location (state, city)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ');

    echo "[011] ✓ Tabela purchase_requests pronta\n";
} catch (Throwable $e) {
    echo "[011] ✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[011] Migracao concluida\n";

