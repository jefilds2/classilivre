<?php
/**
 * Migração: Adicionar suporte a múltiplas sessões simultâneas
 * Propósito: Coluna max_concurrent_sessions na tabela users
 * Data: 2025-11-13
 */

declare(strict_types=1);

require_once __DIR__ . '/../sqlconnect.php';

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        throw new Exception('Falha ao conectar ao banco de dados');
    }
    
    // Verificar se a coluna já existe
    $checkColumn = $pdo->query('SHOW COLUMNS FROM users WHERE Field = "max_concurrent_sessions"');
    if ($checkColumn->fetch()) {
        echo "✅ Coluna 'max_concurrent_sessions' já existe em users\n";
        exit(0);
    }
    
    // Adicionar coluna
    $pdo->exec('ALTER TABLE users ADD COLUMN max_concurrent_sessions INT UNSIGNED DEFAULT 1 AFTER last_session_token');
    
    echo "✅ Coluna 'max_concurrent_sessions' adicionada com sucesso à tabela users\n";
    
    // Definir Premium com 1 sessão
    $pdo->exec('UPDATE users SET max_concurrent_sessions = 1 WHERE is_premium = 1');
    echo "✅ Usuários premium atualizados para 1 sessão simultânea\n";
    
} catch (Throwable $e) {
    error_log('[MIGRAÇÃO] Erro ao adicionar coluna max_concurrent_sessions: ' . $e->getMessage());
    die("❌ Erro: " . $e->getMessage() . "\n");
}
?>
