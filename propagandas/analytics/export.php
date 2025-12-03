<?php
/**
 * Exportar Analytics como CSV
 */

session_start();
require_once __DIR__ . '/../../login_system/middleware.php';

if (!isset($_SESSION['user']) || !current_user()['is_admin']) {
    http_response_code(403);
    die('Acesso negado');
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

$logsDir = __DIR__ . '/logs';
$analyticsFile = $logsDir . "/analytics_{$selectedDate}.json";

if (!file_exists($analyticsFile)) {
    die('Nenhum dado disponível para esta data');
}

$analytics = json_decode(file_get_contents($analyticsFile), true);

// Preparar CSV
$csv = "Data,Usuário,ID,Email,Total de Interações,Cliques Anúncios,WhatsApp,Visitas Loja,Serviços\n";

$users = $analytics['users'];
usort($users, function($a, $b) {
    return $b['total_interactions'] <=> $a['total_interactions'];
});

foreach ($users as $user) {
    $adClicks = array_sum(array_column($user['ads'], 'clicks'));
    $whatsapp = array_sum(array_column($user['ads'], 'whatsapp_clicks'));
    
    $csv .= sprintf(
        '"%s","%s","%d","%s","%d","%d","%d","%d","%d"' . "\n",
        $selectedDate,
        str_replace('"', '""', $user['name']),
        $user['user_id'],
        str_replace('"', '""', $user['email']),
        $user['total_interactions'],
        $adClicks,
        $whatsapp,
        $user['store_visits'],
        count($user['services'])
    );
}

// Enviar como download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="analytics_' . $selectedDate . '.csv"');
echo $csv;
