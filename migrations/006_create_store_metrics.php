<?php
/**
 * Migration 006: Cria tabela store_metrics e adiciona configuracoes de vitrine na tabela users.
 */

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';

try {
    $connection = db();

    // Criar tabela store_metrics se ainda nao existir
    $createTableSql = <<<SQL
        CREATE TABLE IF NOT EXISTS store_metrics (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            store_id INT UNSIGNED NOT NULL,
            ad_id INT UNSIGNED NULL,
            event_type VARCHAR(48) NOT NULL,
            viewer_id INT UNSIGNED NULL,
            metadata LONGTEXT NULL,
            user_agent VARCHAR(255) NULL,
            ip_address VARCHAR(64) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_store_created (store_id, created_at),
            INDEX idx_store_event (store_id, event_type),
            INDEX idx_ad_event (ad_id, event_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    SQL;

    $connection->exec($createTableSql);
    echo "[006] ✓ Tabela 'store_metrics' pronta para uso\n";

    // Adicionar coluna store_theme_config em users se necessario
    $columnCheck = $connection->query("SHOW COLUMNS FROM users LIKE 'store_theme_config'");
    if (!$columnCheck || $columnCheck->rowCount() === 0) {
        $connection->exec("ALTER TABLE users ADD COLUMN store_theme_config LONGTEXT NULL AFTER about");
        echo "[006] ✓ Coluna 'store_theme_config' adicionada na tabela users\n";
    } else {
        echo "[006] • Coluna 'store_theme_config' ja existe\n";
    }

    exit(0);
} catch (Throwable $e) {
    echo "[006] ✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
