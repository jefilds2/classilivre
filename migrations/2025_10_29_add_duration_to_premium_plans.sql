-- Adiciona coluna duration_days à tabela premium_plans
-- Este arquivo define quanto tempo cada plano premium dura em dias

ALTER TABLE premium_plans 
ADD COLUMN duration_days INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Duração do plano em dias' 
AFTER price_decimal;

-- Atualizar planos existentes com durações apropriadas
UPDATE premium_plans SET duration_days = 30 WHERE id = 1 AND name = 'Premium Ilimitado';
