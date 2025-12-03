<?php
/**
 * /api/cron/send_mail_worker.php
 * Worker que processa a fila de emails
 * 
 * Executar: A CADA MINUTO
 * 
 * Configuração Hostgator (cPanel):
 * Minuto: *
 * Hora: *
 * Dia: *
 * Mês: *
 * Dia da semana: *
 * Comando: /usr/local/bin/php /home/hg8cf561/public_html/api/cron/send_mail_worker.php
 */

require_once __DIR__ . '/../../sqlconnect.php';
require_once __DIR__ . '/../helpers_mail.php';

// 🔒 MUTEX LOCK - Impede execução simultânea
$lockFile = '/tmp/classilivre_cron_send_mail_worker.lock';
if (file_exists($lockFile)) {
    $fileAge = time() - filemtime($lockFile);
    if ($fileAge < 300) { // Se lock existe há menos de 5 minutos, sair
        error_log('[MAIL_WORKER] Lock ativo - CRON já está rodando (age: ' . $fileAge . 's)');
        exit(0);
    } else {
        @unlink($lockFile); // Lock expirou, limpar
    }
}
@file_put_contents($lockFile, time());

try {

// ✅ SINCRONIZAÇÃO: Garantir UTC em qualquer servidor/timezone
date_default_timezone_set('UTC');

$cfg = include __DIR__ . '/../../config/mail.php';
$pdo = function_exists('db') ? db() : (isset($pdo) ? $pdo : null);
if (!$pdo instanceof PDO) { exit; }

// ✅ SINCRONIZAÇÃO: Forçar MySQL usar UTC também
$pdo->exec("SET SESSION time_zone = '+00:00'");

// 🌙 COMPORTAMENTO INTELIGENTE POR HORÁRIO
// Processadores menos emails durante o dia (mais responsivo)
// Mais emails à noite (limpa fila)
$currentHour = (int)date('H');
$isMidnight = ($currentHour >= 0 && $currentHour < 5); // 0h-5h = madrugada
$emailLimit = $isMidnight ? 50 : 5; // À noite: 50, Dia: 5

$st = $pdo->prepare("
SELECT id, to_email, to_name, subject, body_html, body_text, tries
FROM email_queue
WHERE status='queued' AND (scheduled_at IS NULL OR scheduled_at <= NOW())
ORDER BY id ASC
LIMIT " . intval($emailLimit) . "
");
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
error_log('[MAIL_WORKER] Found ' . count($rows) . ' emails to process');

$maxTries = (int)($cfg['max_tries'] ?? 3);

foreach ($rows as $e) {
error_log('[MAIL_WORKER] Processing email ID: ' . $e['id'] . ' to: ' . $e['to_email']);
$ok = mail_send_now($cfg, $e['to_email'], $e['subject'], $e['body_html'], $e['body_text'], $e['to_name']);
if ($ok) {
error_log('[MAIL_WORKER] Email sent successfully ID: ' . $e['id']);
  $up = $pdo->prepare("UPDATE email_queue SET status='sent', sent_at=NOW(), tries=tries+1 WHERE id=?");
    $up->execute([$e['id']]);
  } else {
    error_log('[MAIL_WORKER] Email failed ID: ' . $e['id'] . ' - Error: ' . mail_last_error());
    $up = $pdo->prepare("
      UPDATE email_queue
      SET tries=tries+1,
          status = CASE WHEN tries+1 >= :max_status THEN 'error' ELSE 'queued' END,
          last_error = 'SMTP send failed',
          scheduled_at = CASE WHEN tries+1 >= :max_schedule THEN NULL ELSE DATE_ADD(NOW(), INTERVAL 10 MINUTE) END
      WHERE id=:id
    ");
    $up->execute([
      ':id'           => $e['id'],
      ':max_status'   => $maxTries,
      ':max_schedule' => $maxTries,
    ]);
    }
}
error_log('[MAIL_WORKER] Processing completed');

} catch (Throwable $e) {
    error_log('[MAIL_WORKER] Erro: ' . $e->getMessage());
} finally {
    // Sempre remover lock ao sair
    @unlink($lockFile);
}