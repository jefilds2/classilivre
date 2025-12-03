<?php
// login_system/session_manager.php - Gerenciador de sessões únicas

declare(strict_types=1);

// Configurações de sessão
const SESSION_MAX_LIFETIME = 30 * 24 * 60 * 60;        // 30 dias - duração máxima
const SESSION_INACTIVITY_TIMEOUT = 30 * 60;             // 30 minutos - inatividade

/**
 * Cria e ativa uma nova sessão, respeitando o limite máximo de sessões simultâneas
 * 
 * @param PDO $pdo Conexão banco de dados
 * @param int $userId ID do usuário
 * @param array $sessionData Dados da sessão (ip, user_agent, etc)
 * @return string|null Token de sessão gerado ou null se falha
 */
function create_single_session(PDO $pdo, int $userId, array $sessionData = []): ?string {
    try {
        $ipAddress = $sessionData['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $userAgent = $sessionData['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
        
        // Gerar token único
        $sessionToken = bin2hex(random_bytes(32));
        // Expiração total (30 dias)
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_MAX_LIFETIME);
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        try {
            // 1. Obter limite de sessões do usuário
            $userStmt = $pdo->prepare('SELECT max_concurrent_sessions FROM users WHERE id = :user_id LIMIT 1');
            $userStmt->execute([':user_id' => $userId]);
            $userData = $userStmt->fetch();
            $maxSessions = (int)($userData['max_concurrent_sessions'] ?? 1);
            
            // 2. Contar sessões ativas do usuário
            $countStmt = $pdo->prepare('
                SELECT COUNT(*) as active_count 
                FROM user_sessions 
                WHERE user_id = :user_id AND is_active = 1
            ');
            $countStmt->execute([':user_id' => $userId]);
            $countData = $countStmt->fetch();
            $activeSessions = (int)($countData['active_count'] ?? 0);
            
            // 3. Se atingiu limite, invalidar a sessão mais antiga
            if ($activeSessions >= $maxSessions) {
                $oldestStmt = $pdo->prepare('
                    SELECT id FROM user_sessions 
                    WHERE user_id = :user_id AND is_active = 1
                    ORDER BY created_at ASC LIMIT 1
                ');
                $oldestStmt->execute([':user_id' => $userId]);
                if ($oldest = $oldestStmt->fetch()) {
                    $invalidateOld = $pdo->prepare('UPDATE user_sessions SET is_active = 0 WHERE id = :id');
                    $invalidateOld->execute([':id' => (int)$oldest['id']]);
                }
            }
            
            // 4. Criar nova sessão
            $insert = $pdo->prepare('
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at, is_active, created_at)
                VALUES (:user_id, :token, :ip, :ua, :expires, 1, NOW())
            ');
            $insert->execute([
                ':user_id' => $userId,
                ':token' => $sessionToken,
                ':ip' => $ipAddress,
                ':ua' => $userAgent,
                ':expires' => $expiresAt,
            ]);
            
            // 5. Atualizar último token na tabela users (para rápido lookup)
            $updateUser = $pdo->prepare('
                UPDATE users 
                SET last_session_token = :token, updated_at = NOW()
                WHERE id = :user_id
            ');
            $updateUser->execute([
                ':token' => $sessionToken,
                ':user_id' => $userId,
            ]);
            
            $pdo->commit();
            
            // Salvar token na sessão PHP
            $_SESSION['session_token'] = $sessionToken;
            
            return $sessionToken;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Throwable $e) {
        error_log('[SESSION] Erro ao criar sessão: ' . $e->getMessage());
        return null;
    }
}

/**
 * Valida se o token da sessão atual é válido e está ativo
 * 
 * @param PDO $pdo Conexão banco de dados
 * @param int $userId ID do usuário
 * @param string $sessionToken Token a validar
 * @return bool true se válido e ativo, false caso contrário
 */
function is_session_valid(PDO $pdo, int $userId, string $sessionToken): bool {
    try {
        $stmt = $pdo->prepare('
            SELECT id, is_active, expires_at 
            FROM user_sessions 
            WHERE user_id = :user_id 
                AND session_token = :token 
            LIMIT 1
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':token' => $sessionToken,
        ]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            return false; // Sessão não encontrada
        }
        
        if (!$session['is_active']) {
            return false; // Sessão foi invalidada
        }
        
        // Verificar expiração total (30 dias)
        if ($session['expires_at'] && strtotime($session['expires_at']) < time()) {
            return false; // Sessão expirou
        }
        
        // ✨ NOVO: Verificar inatividade (30 minutos)
        $stmt2 = $pdo->prepare('
            SELECT TIMESTAMPDIFF(SECOND, last_activity_at, NOW()) AS inactivity_seconds
            FROM user_sessions
            WHERE id = :id
            LIMIT 1
        ');
        $stmt2->execute([':id' => $session['id']]);
        $inactivity = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($inactivity && (int)$inactivity['inactivity_seconds'] > SESSION_INACTIVITY_TIMEOUT) {
            // Sessão expirou por inatividade (> 30 min)
            return false;
        }
        
        // Atualizar last_activity_at para resetar contador de inatividade
        $update = $pdo->prepare('
            UPDATE user_sessions 
            SET last_activity_at = NOW() 
            WHERE id = :id
        ');
        $update->execute([':id' => $session['id']]);
        
        return true;
    } catch (Throwable $e) {
        error_log('[SESSION] Erro ao validar sessão: ' . $e->getMessage());
        return false;
    }
}

/**
 * Invalida (logout) a sessão atual do usuário
 * 
 * @param PDO $pdo Conexão banco de dados
 * @param int $userId ID do usuário
 * @param string $sessionToken Token a invalidar
 * @return bool true se sucesso, false se falha
 */
function invalidate_session(PDO $pdo, int $userId, string $sessionToken): bool {
    try {
        $update = $pdo->prepare('
            UPDATE user_sessions 
            SET is_active = 0 
            WHERE user_id = :user_id AND session_token = :token
        ');
        return $update->execute([
            ':user_id' => $userId,
            ':token' => $sessionToken,
        ]);
    } catch (Throwable $e) {
        error_log('[SESSION] Erro ao invalidar sessão: ' . $e->getMessage());
        return false;
    }
}

/**
 * Invalida TODAS as sessões de um usuário (força logout em tudo)
 * 
 * @param PDO $pdo Conexão banco de dados
 * @param int $userId ID do usuário
 * @return bool true se sucesso, false se falha
 */
function invalidate_all_sessions(PDO $pdo, int $userId): bool {
    try {
        $update = $pdo->prepare('
            UPDATE user_sessions 
            SET is_active = 0 
            WHERE user_id = :user_id
        ');
        $clearUser = $pdo->prepare('
            UPDATE users 
            SET last_session_token = NULL 
            WHERE id = :user_id
        ');
        
        $update->execute([':user_id' => $userId]);
        $clearUser->execute([':user_id' => $userId]);
        
        return true;
    } catch (Throwable $e) {
        error_log('[SESSION] Erro ao invalidar todas as sessões: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtém informações de todas as sessões ativas de um usuário
 * 
 * @param PDO $pdo Conexão banco de dados
 * @param int $userId ID do usuário
 * @return array Lista de sessões ativas
 */
function get_user_sessions(PDO $pdo, int $userId): array {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                id, 
                session_token, 
                ip_address, 
                user_agent, 
                created_at, 
                last_activity_at,
                expires_at,
                is_active
            FROM user_sessions 
            WHERE user_id = :user_id AND is_active = 1
            ORDER BY last_activity_at DESC
        ');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('[SESSION] Erro ao buscar sessões: ' . $e->getMessage());
        return [];
    }
}

/**
 * Retorna tempo (em segundos) até a sessão expirar por inatividade
 * 
 * @param PDO $pdo Conexão banco de dados
 * @param int $userId ID do usuário
 * @param string $sessionToken Token da sessão
 * @return int|null Segundos até expiração ou null se sessão não existe
 */
function get_session_inactivity_remaining(PDO $pdo, int $userId, string $sessionToken): ?int {
    try {
        $stmt = $pdo->prepare('
            SELECT 
                TIMESTAMPDIFF(SECOND, last_activity_at, NOW()) AS inactivity_seconds,
                is_active
            FROM user_sessions 
            WHERE user_id = :user_id AND session_token = :token
            LIMIT 1
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':token' => $sessionToken,
        ]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session || !$session['is_active']) {
            return null;
        }
        
        $inactivitySeconds = (int)$session['inactivity_seconds'];
        $remaining = SESSION_INACTIVITY_TIMEOUT - $inactivitySeconds;
        
        return max(0, $remaining);
    } catch (Throwable $e) {
        error_log('[SESSION] Erro ao buscar inatividade: ' . $e->getMessage());
        return null;
    }
}

/**
 * Limpa sessões expiradas do banco de dados
 * (Chamar via cron periodicamente)
 * 
 * @param PDO $pdo Conexão banco de dados
 * @return int Número de sessões deletadas
 */
function cleanup_expired_sessions(PDO $pdo): int {
    try {
        $delete = $pdo->prepare('
            DELETE FROM user_sessions 
            WHERE (expires_at IS NOT NULL AND expires_at < NOW())
                OR (is_active = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
        ');
        $delete->execute();
        return $delete->rowCount();
    } catch (Throwable $e) {
        error_log('[SESSION] Erro ao limpar sessões: ' . $e->getMessage());
        return 0;
    }
}
