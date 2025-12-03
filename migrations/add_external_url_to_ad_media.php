<?php
/**
 * Migração: Adicionar coluna external_url à tabela ad_media
 * Propósito: Armazenar URLs de vídeos do Instagram/YouTube
 * Data: 2025-11-13
 */

declare(strict_types=1);

try {
    $pdo = db();
    
    // Verificar se a coluna já existe
    $checkColumn = $pdo->query('SHOW COLUMNS FROM ad_media WHERE Field = "external_url"');
    if ($checkColumn->fetch()) {
        echo "✅ Coluna 'external_url' já existe em ad_media\n";
        exit(0);
    }
    
    // Adicionar coluna
    $pdo->exec('ALTER TABLE ad_media ADD COLUMN external_url VARCHAR(500) NULL AFTER filepath');
    
    echo "✅ Coluna 'external_url' adicionada com sucesso à tabela ad_media\n";
    
    // Adicionar índice para melhor performance
    $checkIndex = $pdo->query('SHOW INDEX FROM ad_media WHERE Key_name = "idx_ad_media_type_external"');
    if (!$checkIndex->fetch()) {
        $pdo->exec('CREATE INDEX idx_ad_media_type_external ON ad_media(ad_id, type, external_url(255))');
        echo "✅ Índice criado com sucesso\n";
    }
    
} catch (Throwable $e) {
    error_log('[MIGRAÇÃO] Erro ao adicionar coluna external_url: ' . $e->getMessage());
    die("❌ Erro: " . $e->getMessage() . "\n");
}
?>
