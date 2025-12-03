-- Criar tabela propaganda_metrics
-- Rastreia visualizações e cliques de campanhas

CREATE TABLE IF NOT EXISTS `propaganda_metrics` (
  `campaign_id` int unsigned NOT NULL,
  `views_count` int unsigned DEFAULT 0 COMMENT 'Total de visualizações',
  `clicks_count` int unsigned DEFAULT 0 COMMENT 'Total de cliques',
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`campaign_id`),
  CONSTRAINT `fk_propaganda_metrics_campaign` 
    FOREIGN KEY (`campaign_id`) 
    REFERENCES `propagandas_campanhas` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Métricas de visualizações e cliques de campanhas de propaganda';
