<?php
/**
 * Migration 008: Adicionar sistema de moedas Clivre Medal
 * Data: 28 de Outubro de 2025
 * 
 * Cria infraestrutura para sistema de moedas:
 * - Coluna coin_balance em users
 * - Tabela coin_transactions (auditoria completa)
 * - Settings de configuração
 */

declare(strict_types=1);

function migrate_008_add_coin_system()
{
    try {
        $pdo = db();

        // ============================================
        // 1. Adicionar coluna coin_balance em users
        // ============================================
        echo "[008] Verificando coluna users.coin_balance...\n";
        
        $column = $pdo->query('SHOW COLUMNS FROM users LIKE "coin_balance"')->fetch();
        if (!$column) {
            $pdo->exec('ALTER TABLE users ADD COLUMN coin_balance INT NOT NULL DEFAULT 0 AFTER is_premium');
            echo "[008] ✓ Adicionada coluna users.coin_balance\n";
        } else {
            echo "[008] ℹ Coluna coin_balance já existe\n";
        }

        // ============================================
        // 2. Criar tabela coin_transactions (auditoria)
        // ============================================
        echo "[008] Criando tabela coin_transactions...\n";
        
        $pdo->exec('CREATE TABLE IF NOT EXISTS coin_transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type ENUM("buy", "spend", "admin_add", "admin_remove", "refund", "bonus", "ad_publish", "ad_feature") NOT NULL COMMENT "Tipo de transação",
            amount INT NOT NULL COMMENT "Positivo=entrada, Negativo=saída",
            balance_before INT NOT NULL COMMENT "Saldo antes",
            balance_after INT NOT NULL COMMENT "Saldo depois",
            reference_id INT UNSIGNED NULL COMMENT "ID do anúncio/pedido/admin_id",
            reference_type VARCHAR(50) NULL COMMENT "ads|purchase|manual|system",
            payment_method VARCHAR(50) NULL COMMENT "mercado_pago|manual|system",
            external_reference VARCHAR(255) NULL COMMENT "ID externo (MP preference id)",
            description TEXT NULL COMMENT "Descrição legível",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            CONSTRAINT fk_coin_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_created_at (created_at),
            INDEX idx_external_ref (external_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        
        echo "[008] ✓ Criada tabela coin_transactions\n";

        // ============================================
        // 3. Adicionar settings de configuração
        // ============================================
        echo "[008] Adicionando settings...\n";
        
        ensureSetting($pdo, 'clivre_medal_price', '15.00');
        echo "[008] ✓ Setting clivre_medal_price = 15.00\n";
        
        ensureSetting($pdo, 'coins_per_ad', '1');
        echo "[008] ✓ Setting coins_per_ad = 1\n";
        
        ensureSetting($pdo, 'coins_ad_duration_days', '30');
        echo "[008] ✓ Setting coins_ad_duration_days = 30\n";
        
        ensureSetting($pdo, 'min_coin_purchase', '1');
        echo "[008] ✓ Setting min_coin_purchase = 1\n";

        echo "\n[008] ✅ MIGRATION COMPLETA COM SUCESSO\n";
        return true;

    } catch (Throwable $e) {
        echo "[008] ✗ ERRO: " . $e->getMessage() . "\n";
        return false;
    }
}

// Executar migração
migrate_008_add_coin_system();
?>
