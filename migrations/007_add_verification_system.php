<?php
// migrations/007_add_verification_system.php
// Adiciona sistema de badge "Verificado" para anúncios premium

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';

try {
    $pdo = db();
    
    // Verificar se as colunas já existem
    $columns = $pdo->query("SHOW COLUMNS FROM ads")->fetchAll(PDO::FETCH_COLUMN);
    
    // Adicionar coluna verified_badge se não existir
    if (!in_array('verified_badge', $columns, true)) {
        $pdo->exec('ALTER TABLE ads ADD COLUMN verified_badge TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
        echo "[OK] Coluna 'verified_badge' adicionada à tabela 'ads'.\n";
    } else {
        echo "[SKIP] Coluna 'verified_badge' já existe em 'ads'.\n";
    }
    
    // Adicionar coluna verification_document_path se não existir
    if (!in_array('verification_document_path', $columns, true)) {
        $pdo->exec('ALTER TABLE ads ADD COLUMN verification_document_path VARCHAR(500) NULL AFTER verified_badge');
        echo "[OK] Coluna 'verification_document_path' adicionada à tabela 'ads'.\n";
    } else {
        echo "[SKIP] Coluna 'verification_document_path' já existe em 'ads'.\n";
    }
    
    // Adicionar coluna verification_reviewed_at se não existir
    if (!in_array('verification_reviewed_at', $columns, true)) {
        $pdo->exec('ALTER TABLE ads ADD COLUMN verification_reviewed_at DATETIME NULL AFTER verification_document_path');
        echo "[OK] Coluna 'verification_reviewed_at' adicionada à tabela 'ads'.\n";
    } else {
        echo "[SKIP] Coluna 'verification_reviewed_at' já existe em 'ads'.\n";
    }
    
    // Adicionar coluna verification_reviewed_by se não existir
    if (!in_array('verification_reviewed_by', $columns, true)) {
        $pdo->exec('ALTER TABLE ads ADD COLUMN verification_reviewed_by INT UNSIGNED NULL AFTER verification_reviewed_at');
        echo "[OK] Coluna 'verification_reviewed_by' adicionada à tabela 'ads'.\n";
    } else {
        echo "[SKIP] Coluna 'verification_reviewed_by' já existe em 'ads'.\n";
    }
    
    // Adicionar índice para verificação rápida
    $indexes = $pdo->query("SHOW INDEX FROM ads WHERE Key_name = 'idx_ads_verified_badge'")->fetchAll();
    if (empty($indexes)) {
        $pdo->exec('ALTER TABLE ads ADD INDEX idx_ads_verified_badge (verified_badge)');
        echo "[OK] Índice 'idx_ads_verified_badge' adicionado à tabela 'ads'.\n";
    } else {
        echo "[SKIP] Índice 'idx_ads_verified_badge' já existe em 'ads'.\n";
    }
    
    echo "\n✅ Migração 007 completada com sucesso!\n";
    
} catch (Exception $e) {
    error_log('[MIGRATION 007] Erro: ' . $e->getMessage());
    echo "❌ Erro durante migração: " . $e->getMessage() . "\n";
    exit(1);
}
