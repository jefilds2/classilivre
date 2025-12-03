<?php
/**
 * config/mercadopago.php - Configurações de Mercado Pago
 * 
 * Armazena credenciais para ambientes test e production
 * Mude 'mode' para trocar entre test e production
 */

return [
    // Modo atual: 'test' ou 'production'
    'mode' => 'production',

    // Credenciais para ambiente de TESTE
    'test' => [
        'public_key' => 'APP_USR-9c463263-4af7-41f8-8317-fc8ddb576778',
        'access_token' => 'APP_USR-613745341190513-101922-eb938c5fdef7b689b012cb69a9237909-2935832386',
    ],

    // Credenciais para ambiente de PRODUÇÃO (Credenciais Oficiais)
    'production' => [
        'public_key' => 'APP_USR-d3c0c27d-1922-40c2-97cc-a7c1e3fd3434',
        'access_token' => 'APP_USR-334329186093935-101922-37b19b044f2a7bd62dda3917f5643783-443993878',
        'client_id' => '334329186093935',
        'client_secret' => 'xSn8X3tYA6QR2k3rlZED7g37XfpysCVE',
    ],

    // Configurações adicionais
    'webhook_url' => '/api/webhook_mercadopago.php',
    'success_url' => '/anuncios/criar.php?payment=success',
    'failure_url' => '/anuncios/criar.php?payment=failure',
];
