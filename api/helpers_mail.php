<?php
// /api/helpers_mail.php — versão estável (sem Composer)

if (!function_exists('mail_enqueue')) {
  /**
   * Enfileira e-mail na tabela email_queue. Retorna id da fila ou null em caso de falha.
   */
  function mail_enqueue($pdo, string $toEmail, string $subject, string $template, array $data = [], ?string $toName = null): ?int {
    try {
      if (!$pdo instanceof PDO) { return null; }
      [$html, $text] = mail_render_template($template, $data);
      $sql = "INSERT INTO email_queue (to_email,to_name,subject,body_html,body_text,status,tries,created_at)
              VALUES (?,?,?,?,?,'queued',0,NOW())";
      $st  = $pdo->prepare($sql);
      $st->execute([$toEmail, $toName, $subject, $html, $text]);
      return (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
      error_log('[MAIL][enqueue] '.$e->getMessage());
      return null;
    }
  }
}

if (!function_exists('mail_last_error')) {
  /**
   * Getter/setter simples para armazenar o último erro do envio SMTP.
   */
  function mail_last_error(?string $value = null): ?string {
    static $last = null;
    if (func_num_args() > 0) {
      $last = $value;
    }
    return $last;
  }
}

if (!function_exists('mail_send_now')) {
  /**
   * Envia e-mail via SMTP (PHPMailer). Retorna true/false.
   * Aceita config de /config/mail.php com chaves: host, port, secure, username, password, from[email|name], reply_to[email|name].
   */
  function mail_send_now(array $cfg, string $toEmail, string $subject, string $bodyHtml, ?string $bodyText = null, ?string $toName = null): bool {
    mail_last_error(null); // limpa erro anterior
    try {
      // Carrega PHPMailer manualmente
      $paths = [
        __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php',
        __DIR__ . '/../vendor/PHPMailer/src/SMTP.php',
        __DIR__ . '/../vendor/PHPMailer/src/Exception.php',
        __DIR__ . '/../../vendor/PHPMailer/src/PHPMailer.php',
        __DIR__ . '/../../vendor/PHPMailer/src/SMTP.php',
        __DIR__ . '/../../vendor/PHPMailer/src/Exception.php',
      ];
      foreach ($paths as $p) { if (file_exists($p)) require_once $p; }
      if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $msg = '[MAIL][send_now] PHPMailer não encontrado em /vendor/PHPMailer/src';
        mail_last_error($msg);
        error_log($msg);
        return false;
      }

      $mail = new PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();

      $smtpCfg = $cfg['smtp'] ?? $cfg;

      // Nível de debug opcional (0=off). Pode ser definido em config/mail.php => 'debug' => 2, etc.
      $debugLevel = (int)($cfg['debug'] ?? ($smtpCfg['debug'] ?? 0));
      if ($debugLevel > 0) {
        $mail->SMTPDebug  = $debugLevel;
        $mail->Debugoutput = $cfg['debug_output'] ?? ($smtpCfg['debug_output'] ?? 'error_log');
      }

      $smtpHost = $smtpCfg['host'] ?? ($cfg['host'] ?? ($cfg['smtp_host'] ?? ''));
      $smtpPort = (int)($smtpCfg['port'] ?? ($cfg['port'] ?? ($cfg['smtp_port'] ?? 465)));
      $mail->Host       = $smtpHost;
      $mail->Port       = $smtpPort;
      // Modo de criptografia compatível (Titan: 465=SMTPS, 587=STARTTLS)
      $enc = strtolower((string)($smtpCfg['secure'] ?? ($cfg['secure'] ?? ($cfg['smtp_secure'] ?? 'ssl'))));
      $mail->SMTPSecure = ($enc === 'ssl' || $enc === 'smtps')
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

      $mail->Username   = $smtpCfg['username'] ?? ($cfg['username'] ?? ($cfg['smtp_user']  ?? ''));
      $mail->Password   = $smtpCfg['password'] ?? ($cfg['password'] ?? ($cfg['smtp_pass']  ?? ''));
      if (!empty($smtpCfg['auth_type'])) {
        $mail->AuthType = $smtpCfg['auth_type']; // ex: 'LOGIN'
      } elseif (!empty($cfg['auth_type'])) {
        $mail->AuthType = $cfg['auth_type'];
      }
      $mail->SMTPAuth = !empty($mail->Username) || !empty($mail->Password);
      $mail->Timeout  = (int)($smtpCfg['timeout'] ?? $cfg['timeout'] ?? 15);
      
      // Remetente / Reply-To
      $identityEmail = $cfg['identity']['email'] ?? null;
      $identityName  = $cfg['identity']['name'] ?? null;

      $fromEmail = $cfg['from']['email'] ?? ($cfg['from_email'] ?? $identityEmail ?? 'no-reply@localhost');
      $fromName  = $cfg['from']['name']  ?? ($cfg['from_name']  ?? $identityName ?? 'No Reply');
      $mail->setFrom($fromEmail, $fromName);

      // Garante que o envelope (Return-Path) seja o mesmo do remetente autenticado
      $mail->Sender = $cfg['sender']['email'] ?? $fromEmail;

      // Ajustes recomendados p/ Titan: HELO/Hostname e TLS >=1.2
      if (strpos($fromEmail, '@') !== false) {
        $helo = substr(strrchr($fromEmail, '@'), 1);
        if ($helo) { $mail->Hostname = $helo; }
      }
      $mail->SMTPOptions = [
        'ssl' => [
          'verify_peer' => true,
          'verify_peer_name' => true,
          'allow_self_signed' => false,
          'crypto_method' => (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0)
                           | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        ],
      ];

      // Qualidade/compatibilidade do conteúdo
      $mail->CharSet   = 'UTF-8';
      $mail->Encoding  = 'base64';
      $mail->SMTPAutoTLS = true;   // oportunístico, se o servidor exigir TLS
      $mail->SMTPKeepAlive = true; // opcional: melhor performance em lotes
      
      if (!empty($cfg['reply_to']['email'])) {
        $mail->addReplyTo($cfg['reply_to']['email'], $cfg['reply_to']['name'] ?? $fromName);
      } elseif (!empty($cfg['reply_to'])) {
        $mail->addReplyTo($cfg['reply_to'], $fromName);
      }

      // Destinatário
      $mail->addAddress($toEmail, $toName ?: '');

      // Conteúdo
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $bodyHtml;
      $mail->AltBody = $bodyText ?: strip_tags($bodyHtml);

      // Logo será renderizada via HTML/CSS na assinatura, não como imagem

       $sent = $mail->send();
      if (!$sent) {
        $errorInfo = trim((string)$mail->ErrorInfo);
        $message = $errorInfo !== '' ? $errorInfo : 'SMTP send failed';
        mail_last_error($message);
      } else {
        mail_last_error(null);
      }
      return $sent;
    } catch (Throwable $e) {
      $err = '[MAIL][send_now] '.$e->getMessage();
      if (isset($mail) && $mail instanceof PHPMailer\PHPMailer\PHPMailer) {
        if (!empty($mail->ErrorInfo)) {
          $err .= ' | ErrorInfo: ' . $mail->ErrorInfo;
        }
      }
      mail_last_error($err);
      error_log($err);
      return false;
    }
  }
}

if (!function_exists('mail_render_template')) {
  /**
   * Renderiza template HTML e texto. Usa /config/mail.php para base de templates se existir.
   */
  function mail_render_template(string $template, array $data = []): array {
    $cfgFile = __DIR__ . '/../config/mail.php';
    $base = __DIR__ . '/../partials/emails';
    if (file_exists($cfgFile)) {
      $cfg = include $cfgFile;
      if (isset($cfg['templates_path'])) {
        $base = $cfg['templates_path'];
      }
    }
    $htmlFile = rtrim($base, '/').'/'.$template.'.php';
    if (!file_exists($htmlFile)) {
      $safe = htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE));
      $html = "<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:auto\"><h3>{$template}</h3><pre>{$safe}</pre></div>";
      return [$html, strip_tags($html)];
    }

    extract($data, EXTR_SKIP);
    ob_start();
    include $htmlFile;
    $html = ob_get_clean();
    $text = preg_replace('/\s+/', ' ', strip_tags($html));
    return [$html, $text];
  }
}

if (!function_exists('mail_send_immediate')) {
  /**
   * Envia e-mail IMEDIATAMENTE via SMTP (sem fila).
   * Retorna true se sucesso, false se falha.
   * Usa as mesmas configurações de /config/mail.php
   */
  function mail_send_immediate(string $toEmail, string $subject, string $template, array $data = [], ?string $toName = null): bool {
    try {
      $cfg = require(__DIR__ . '/../config/mail.php');
      if (!is_array($cfg)) {
        error_log('[MAIL][send_immediate] Configuração de mail inválida');
        return false;
      }
      
      [$html, $text] = mail_render_template($template, $data);
      return mail_send_now($cfg, $toEmail, $subject, $html, $text, $toName);
    } catch (Throwable $e) {
      error_log('[MAIL][send_immediate] ' . $e->getMessage());
      return false;
    }
  }
}

