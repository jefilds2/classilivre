<?php
return [
    // Configuração SMTP principal recomendada pelo Titan
    'smtp' => [
        'host'      => 'smtp.titan.email',
        'port'      => 465,
        'secure'    => 'ssl',           // SSL/TLS (porta 465)
        'auth_type' => 'LOGIN',
        'username'  => 'contato@classilivreautos.com',
        'password'  => '@38mgzykH',     // senha real da conta Titan
        'timeout'   => 20,
        'debug'     => 0,
    ],

    // Identidade padrão do remetente
    'identity' => [
        'email' => 'contato@classilivreautos.com',
        'name'  => 'Classi Livre',
    ],

    // Cabeçalhos From / Reply-To (podem ser sobrescritos por chamada)
    'from' => [
        'email' => 'contato@classilivreautos.com',
        'name'  => 'Classi Livre',
    ],
    'reply_to' => [
        'email' => 'contato@classilivreautos.com',
        'name'  => 'Suporte Classi Livre',
    ],

    // Número máximo de tentativas do worker antes de marcar erro definitivo
    'max_tries' => 3,
];
