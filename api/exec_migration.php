<?php
// DESCONTINUADO: Este arquivo não deve mais ser usado
// Sistema de migrations obsoleto removido em 28/10/2025
// As migrations agora são executadas automaticamente em sqlconnect.php

header('Content-Type: application/json');

http_response_code(410); // Gone
echo json_encode([
    'success' => false,
    'message' => 'Este endpoint foi descontinuado',
    'note' => 'Migrations são executadas automaticamente no arquivo sqlconnect.php'
]);
exit;
?>
