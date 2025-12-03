<?php /** @var string $nome */ /** @var string $verifyUrl */ ?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">
  <h2>Bem-vindo(a) ao Classi Livre Autos, <?= htmlspecialchars($nome) ?>!</h2>
  <p>Confirme seu e-mail para ativar sua conta:</p>
  <p><a href="<?= htmlspecialchars($verifyUrl) ?>" style="background:#0ea5e9;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none">Confirmar e-mail</a></p>
  <p>Se não foi você, ignore este e-mail.</p>
  <?php include __DIR__ . '/_email_signature.php'; ?>
 </div>
