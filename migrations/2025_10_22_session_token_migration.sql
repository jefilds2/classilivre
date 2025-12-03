-- Migração: Adicionar suporte a sessão única por usuário

-- Tabela para rastrear sessões ativas
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL COMMENT 'Token único da sessão',
    ip_address VARCHAR(45) COMMENT 'IP do navegador',
    user_agent TEXT COMMENT 'User agent do navegador',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME COMMENT 'Quando a sessão expira',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=ativo, 0=invalidado',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_active (is_active)
);

-- Adicionar coluna de último token de sessão na tabela users (para quick lookup)
ALTER TABLE users ADD COLUMN last_session_token VARCHAR(255) 
    COMMENT 'Token da última sessão ativa' AFTER updated_at;

-- Index para performance
ALTER TABLE users ADD INDEX idx_session_token (last_session_token);

-- Limpar sessões expiradas ou de usuários deletados é feito via cron
