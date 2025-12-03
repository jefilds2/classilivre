-- Stored Procedure para expiração automática de planos premium
-- Esta procedure marca usuários como não-premium quando assinatura expira
-- IMPORTANTE: NÃO afeta usuários bloqueados (status = 'blocked')

DELIMITER $$

DROP PROCEDURE IF EXISTS `check_premium_expiration` $$

CREATE PROCEDURE `check_premium_expiration`()
BEGIN
    -- Retirar flag is_premium de usuários com assinatura expirada
    -- CONDIÇÃO IMPORTANTE: Respeita bloqueio de usuários (não alterar bloqueados)
    UPDATE users 
    SET is_premium = 0, updated_at = NOW()
    WHERE status != 'blocked'  -- <-- Usuários bloqueados NÃO são alterados
    AND id IN (
        SELECT DISTINCT user_id FROM premium_subscriptions 
        WHERE expires_at IS NOT NULL 
        AND expires_at < NOW()
    );
END$$

DELIMITER ;

-- Comentário: Usuários com status = 'blocked' mantêm seu status até o admin desbloqueá-los manualmente
