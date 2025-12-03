<?php

declare(strict_types=1);

/**
 * Migration: Criar tabela propaganda_metrics
 * Tabela para rastrear visualizações e cliques de campanhas
 * 
 * Schema:
 * - campaign_id (PK): ID da campanha
 * - views_count: Total de visualizações
 * - clicks_count: Total de cliques
 * - updated_at: Última atualização
 */

require_once __DIR__ . '/../../sqlconnect.php';
require_once __DIR__ . '/../config.php';

try {
    $pdo = db();
    
    // Verificar se tabela já existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'propaganda_metrics'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ Tabela 'propaganda_metrics' já existe. Nenhuma ação necessária.\n";
        exit(0);
    }
    
    // Criar tabela
    $sql = <<<SQL
        CREATE TABLE propaganda_metrics (
            campaign_id INT UNSIGNED NOT NULL PRIMARY KEY,
            views_count INT UNSIGNED DEFAULT 0 COMMENT 'Total de visualizações',
            clicks_count INT UNSIGNED DEFAULT 0 COMMENT 'Total de cliques',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_propaganda_metrics_campaign 
                FOREIGN KEY (campaign_id) 
                REFERENCES propagandas_campanhas(id) 
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Métricas de visualizações e cliques de campanhas de propaganda';
    SQL;
    
    $pdo->exec($sql);
    
    echo "✓ Tabela 'propaganda_metrics' criada com sucesso!\n";
    echo "✓ Schema:\n";
    echo "  - campaign_id (PK, INT): ID da campanha\n";
    echo "  - views_count (INT): Total de visualizações\n";
    echo "  - clicks_count (INT): Total de cliques\n";
    echo "  - updated_at (TIMESTAMP): Última atualização\n";
    
} catch (Exception $e) {
    echo "✗ Erro ao criar tabela: " . $e->getMessage() . "\n";
    exit(1);
}

?>
