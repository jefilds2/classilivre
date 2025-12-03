<?php
// login_system/google_login.php - Inicia o fluxo OAuth com o Google.

declare(strict_types=1);

require_once __DIR__ . '/google_oauth.php';

if (!google_oauth_configured()) {
    flash_add('error', 'A autenticação com o Google ainda não foi configurada. Informe as credenciais no painel.');
    redirect('/login_system/login.php');
}

try {
    $state = bin2hex(random_bytes(24));
    $_SESSION['google_oauth_state'] = $state;

    $redirect = input_get('redirect');
    if ($redirect && str_starts_with($redirect, '/')) {
        $_SESSION['google_post_login_redirect'] = $redirect;
    }

    $authorizeUrl = google_oauth_authorize_url($state);
    header('Location: ' . $authorizeUrl);
    exit;
} catch (Throwable $exception) {
    record_audit(current_user()['id'] ?? null, 'google_login_error', null, null, [
        'message' => $exception->getMessage(),
    ]);
    flash_add('error', 'Não foi possível iniciar o login com o Google. Tente novamente em alguns instantes.');
    redirect('/login_system/login.php');
}
