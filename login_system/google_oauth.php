<?php
// login_system/google_oauth.php - Funções utilitárias para autenticação com Google OAuth.

declare(strict_types=1);

require_once __DIR__ . '/middleware.php';

function google_oauth_default_redirect_uri(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim($scheme . '://' . $host, '/');
    return $base . '/login_system/google_callback.php';
}

/**
 * Obtém as credenciais de OAuth a partir de env, config ou settings.
 *
 * Ordem de busca:
 * 1. Variáveis de ambiente (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI)
 * 2. Arquivo config/google_credentials.php (retorna array associativo)
 * 3. Tabela settings (chaves: google_client_id, google_client_secret, google_redirect_uri)
 */
function google_oauth_credentials(): array
{
    $credentials = [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: '',
    ];

    $configPath = __DIR__ . '/../config/google_credentials.php';
    if (file_exists($configPath)) {
        $fileConfig = require $configPath;
        if (is_array($fileConfig)) {
            $credentials = array_merge($credentials, array_intersect_key($fileConfig, $credentials));
        }
    }

    if (!$credentials['client_id']) {
        $credentials['client_id'] = (string)get_setting('google_client_id', '');
    }
    if (!$credentials['client_secret']) {
        $credentials['client_secret'] = (string)get_setting('google_client_secret', '');
    }
    if (!$credentials['redirect_uri']) {
        $credentials['redirect_uri'] = (string)get_setting('google_redirect_uri', '');
    }

    foreach (['client_id', 'client_secret', 'redirect_uri'] as $key) {
        $credentials[$key] = trim((string)$credentials[$key]);
        if ($credentials[$key] && stripos($credentials[$key], 'COLOQUE_SEU') !== false) {
            $credentials[$key] = '';
        }
    }

    if (!$credentials['redirect_uri']) {
        $credentials['redirect_uri'] = google_oauth_default_redirect_uri();
    }

    return $credentials;
}

function google_oauth_configured(): bool
{
    $credentials = google_oauth_credentials();
    return $credentials['client_id'] !== '' && $credentials['client_secret'] !== '' && $credentials['redirect_uri'] !== '';
}

function google_oauth_authorize_url(string $state): string
{
    $credentials = google_oauth_credentials();
    $params = [
        'client_id' => $credentials['client_id'],
        'redirect_uri' => $credentials['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'offline',
        'prompt' => 'select_account',
        'state' => $state,
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function google_oauth_exchange_code(string $code): array
{
    $credentials = google_oauth_credentials();
    $postData = [
        'code' => $code,
        'client_id' => $credentials['client_id'],
        'client_secret' => $credentials['client_secret'],
        'redirect_uri' => $credentials['redirect_uri'],
        'grant_type' => 'authorization_code',
    ];

    $response = http_post_json('https://oauth2.googleapis.com/token', $postData);
    if (!$response || empty($response['access_token'])) {
        throw new RuntimeException('Não foi possível obter o token de acesso do Google.');
    }

    return $response;
}

function google_oauth_fetch_userinfo(string $accessToken): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $accessToken,
            'timeout' => 10,
        ],
    ]);

    $response = @file_get_contents('https://www.googleapis.com/oauth2/v3/userinfo', false, $context);
    if ($response === false) {
        throw new RuntimeException('Não foi possível consultar os dados do usuário Google.');
    }

    $data = json_decode($response, true) ?: [];
    if (empty($data['sub'])) {
        throw new RuntimeException('Resposta inválida do Google.');
    }

    return $data;
}

/**
 * Realiza requisição POST (x-www-form-urlencoded) e retorna json decodificado.
 */
function http_post_json(string $url, array $data): array
{
    $postFields = http_build_query($data);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('Falha na comunicação com Google OAuth: ' . $error);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postFields,
                'timeout' => 10,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            throw new RuntimeException('Falha na comunicação com Google OAuth.');
        }
    }

    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Não foi possível interpretar a resposta do Google.');
    }
    return $decoded;
}
