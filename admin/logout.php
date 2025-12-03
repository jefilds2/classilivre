<?php
// admin/logout.php — Finaliza sessão administrativa.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

if (current_user()) {
    record_audit((int)current_user()['id'], 'admin_logout', 'users', (int)current_user()['id']);
}

destroy_auth_session();
flash_add('success', 'Sessão administrativa encerrada.');
redirect('/admin/login.php');
