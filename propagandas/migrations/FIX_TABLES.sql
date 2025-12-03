-- ========================================================
-- 🔧 CORREÇÃO DO BANCO DE DADOS - propagandas_campanhas
-- ========================================================
-- VERSÃO 2.0 - Sistema de Identificação por Tipo
-- ========================================================

-- ✅ PASSO 1: Adicionar coluna ad_type (CRÍTICO!) - Identificação numérica
-- 1 = Anúncio WEB (com link afiliado ou HTML customizado)
-- 2 = Anúncio LOCAL (com telefone e/ou whatsapp)
ALTER TABLE propagandas_campanhas 
ADD COLUMN ad_type TINYINT DEFAULT 1 AFTER title;

-- ✅ PASSO 2: Adicionar coluna campaign_type (compatibilidade)
ALTER TABLE propagandas_campanhas 
ADD COLUMN campaign_type ENUM('web', 'local') DEFAULT 'web' AFTER ad_type;

-- ✅ PASSO 3: Adicionar coluna company_name
ALTER TABLE propagandas_campanhas 
ADD COLUMN company_name VARCHAR(255) NULL AFTER campaign_type;

-- ✅ PASSO 4: Adicionar coluna whatsapp
ALTER TABLE propagandas_campanhas 
ADD COLUMN whatsapp VARCHAR(20) NULL AFTER company_name;

-- ✅ PASSO 5: Adicionar coluna phone
ALTER TABLE propagandas_campanhas 
ADD COLUMN phone VARCHAR(20) NULL AFTER whatsapp;

-- ✅ PASSO 6: Adicionar coluna city (para campanhas locais)
ALTER TABLE propagandas_campanhas 
ADD COLUMN city VARCHAR(255) NULL AFTER phone;

-- ✅ PASSO 7: Adicionar coluna company_image
ALTER TABLE propagandas_campanhas 
ADD COLUMN company_image VARCHAR(500) NULL AFTER city;

-- ✅ PASSO 8: Modificar affiliate_link para aceitar NULL
ALTER TABLE propagandas_campanhas 
MODIFY COLUMN affiliate_link VARCHAR(500) NULL;

-- ✅ PASSO 8: Criar índices para melhor performance
ALTER TABLE propagandas_campanhas 
ADD INDEX idx_ad_type (ad_type);

ALTER TABLE propagandas_campanhas 
ADD INDEX idx_campaign_type (campaign_type);

ALTER TABLE propagandas_campanhas 
ADD INDEX idx_company_name (company_name);

ALTER TABLE propagandas_campanhas 
ADD INDEX idx_city (city);

-- ✅ PASSO 9: Criar tabela propagandas_analytics (se não existir)
CREATE TABLE IF NOT EXISTS propagandas_analytics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    user_id VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES propagandas_campanhas(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_event_type (event_type)
);

-- ========================================================
-- 🔍 MIGRAÇÃO DE DADOS - Atualizar ad_type baseado em dados existentes
-- ========================================================

-- Campanhas com phone OU whatsapp = LOCAL (tipo 2)
UPDATE propagandas_campanhas 
SET ad_type = 2 
WHERE (phone IS NOT NULL AND phone != '') 
   OR (whatsapp IS NOT NULL AND whatsapp != '');

-- Campanhas com affiliate_link OU html_code = WEB (tipo 1)
UPDATE propagandas_campanhas 
SET ad_type = 1 
WHERE (affiliate_link IS NOT NULL AND affiliate_link != '') 
   OR (html_code IS NOT NULL AND html_code != '');

-- ========================================================
-- 🔍 VERIFICAÇÃO - Execute para confirmar
-- ========================================================
DESCRIBE propagandas_campanhas;

-- Ver distribuição de tipos
SELECT ad_type, COUNT(*) as total FROM propagandas_campanhas GROUP BY ad_type;

-- Ver campanhas
SELECT id, title, ad_type, affiliate_link, phone, whatsapp FROM propagandas_campanhas LIMIT 10;
