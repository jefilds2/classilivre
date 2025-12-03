<?php
// login_system/logout.php — Encerra a sessão do usuário autenticado.

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/session_manager.php';

$user = current_user();
if ($user) {
    // ✨ NOVO: Invalidar o token de sessão
    if (!empty($_SESSION['session_token'])) {
        invalidate_session(db(), (int)$user['id'], $_SESSION['session_token']);
    }
    record_audit((int)$user['id'], 'user_logout', 'users', (int)$user['id']);
}

destroy_auth_session();
flash_add('success', 'Sessão encerrada com sucesso. Volte sempre!');
redirect('/');
