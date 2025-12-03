-- Migration: Converter reference_id de INT UNSIGNED para BIGINT UNSIGNED
-- Executar no phpMyAdmin ou MySQL client

ALTER TABLE coin_transactions MODIFY COLUMN reference_id BIGINT UNSIGNED NULL COMMENT 'ID do anúncio/pedido/admin_id/payment_id';

-- Adicionar índice se não existir
ALTER TABLE coin_transactions ADD INDEX idx_reference_id (reference_id);
