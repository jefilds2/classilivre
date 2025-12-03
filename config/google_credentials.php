<?php
// config/google_credentials.php - Configure as credenciais OAuth do Google.

return [
    // Preencha com os valores gerados no Google Cloud Console.
    'client_id' => '',
    'client_secret' => '',
    // Opcional: deixe em branco para usar automaticamente o domínio atual + /login_system/google_callback.php
    // Definido para o novo domínio HostGator fornecido pelo usuário:
    'redirect_uri' => 'https://classilivreautos.com/login_system/google_callback.php',
];
