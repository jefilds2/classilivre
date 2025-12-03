<?php
// /api/cron/send_mail_worker_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>DEBUG worker start\n";

$paths = [
  __DIR__.'/../../sqlconnect.php',
  __DIR__.'/../helpers_mail.php',
];
foreach ($paths as $p) {
  if (!file_exists($p)) { die("Arquivo não encontrado: $p\n"); }
}
require_once __DIR__.'/../../sqlconnect.php';
require_once __DIR__.'/../helpers_mail.php';

$cfgPath = __DIR__.'/../../config/mail.php';
if (!file_exists($cfgPath)) { die("Config mail.php não encontrada\n"); }
$cfg = include $cfgPath;
$maxTries = (int)($cfg['max_tries'] ?? 3);

// Obter conexão
$pdo = function_exists('db') ? db() : (isset($pdo) ? $pdo : null);
if (!$pdo instanceof PDO) { die("Sem conexão PDO (db() / $pdo).\n"); }

$st = $pdo->prepare("
  SELECT id, to_email, to_name, subject, body_html, body_text, tries
  FROM email_queue
  WHERE status='queued' AND (scheduled_at IS NULL OR scheduled_at <= NOW())
  ORDER BY id ASC
  LIMIT 10
");
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
echo "Pendentes: ".count($rows)."\n";

foreach ($rows as $e) {
  echo "Enviando id={$e['id']} para {$e['to_email']} ... ";
  $ok = mail_send_now($cfg, $e['to_email'], $e['subject'], $e['body_html'], $e['body_text'], $e['to_name']);
  if ($ok) {
    echo "OK\n";
    $up = $pdo->prepare("UPDATE email_queue SET status='sent', sent_at=NOW(), tries=tries+1 WHERE id=?");
    $up->execute([$e['id']]);
  } else {
    $lastError = mail_last_error();
    $errorMsg = $lastError ?: 'SMTP send failed';
    echo "FALHOU (" . $errorMsg . ")\n";
    $up = $pdo->prepare("
      UPDATE email_queue 
      SET tries=tries+1,
          last_error=:err,
          status = CASE WHEN tries+1 >= :max_status THEN 'error' ELSE 'queued' END,
          scheduled_at = CASE WHEN tries+1 >= :max_schedule THEN NULL ELSE DATE_ADD(NOW(), INTERVAL 10 MINUTE) END
      WHERE id=:id
    ");
    $up->execute([
      ':id'           => $e['id'],
      ':err'          => $errorMsg,
      ':max_status'   => $maxTries,
      ':max_schedule' => $maxTries,
    ]);
  }
}

echo "DEBUG worker fim\n</pre>";
