<?php
// migrations/010_add_cnpj_verification.php
// Adiciona suporte a verificação assíncrona de CNPJ

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';

try {
    $pdo = db();
    
    // Adicionar coluna de verificação de CNPJ
    $pdo->exec('
        ALTER TABLE users ADD COLUMN cnpj_verified_at TIMESTAMP NULL DEFAULT NULL COMMENT "Timestamp quando CNPJ foi verificado (NULL = não verificado, invalid = inválido)"
    ');
    
    echo "✓ Migration 010 completed: Added cnpj_verified_at column\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "⚠ Column cnpj_verified_at already exists\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
