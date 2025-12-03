-- Migração: Adicionar suporte a múltiplas sessões simultâneas
-- Propósito: Coluna max_concurrent_sessions na tabela users
-- Data: 2025-11-13
-- Executar em: mysql> SOURCE path/to/this/file;

-- Verificar se a coluna já existe (usando variável de sessão)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'max_concurrent_sessions'
);

-- Adicionar coluna apenas se não existir
SET @alter_stmt = IF(
    @col_exists = 0,
    'ALTER TABLE users ADD COLUMN max_concurrent_sessions INT UNSIGNED DEFAULT 1 AFTER last_session_token',
    'SELECT "Coluna max_concurrent_sessions já existe"'
);

PREPARE stmt FROM @alter_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Definir Premium users com 3 sessões (eles podem usar múltiplos dispositivos)
UPDATE users SET max_concurrent_sessions = 1 WHERE is_premium = 1;

-- Confirmar a migração
SELECT 'Migração concluída com sucesso!' as status;
SELECT COUNT(*) as usuarios_premium_com_3_sessoes FROM users WHERE is_premium = 1 AND max_concurrent_sessions = 3;
SELECT COUNT(*) as usuarios_livres_com_1_sessao FROM users WHERE is_premium = 0 AND max_concurrent_sessions = 1;
