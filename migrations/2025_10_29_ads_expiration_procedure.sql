-- ========================================
-- PROCEDURE: Marcar Anúncios Expirados
-- ========================================
-- Data: 29/10/2025
-- Executa: A cada minuto via cron
-- O que faz: Marca anúncios como 'expired' quando passa a data expires_at
-- ========================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `check_ads_expiration` $$

CREATE DEFINER=`cpses_hgru5lenmw`@`localhost` PROCEDURE `check_ads_expiration` ()
BEGIN
    -- Atualizar status de anúncios para 'expired' quando expired_at < NOW()
    UPDATE ads 
    SET 
        status = 'expired',
        updated_at = NOW()
    WHERE 
        status NOT IN ('expired', 'rejected', 'blocked', 'sold')
        AND expires_at IS NOT NULL 
        AND expires_at < NOW();
END $$

DELIMITER ;
