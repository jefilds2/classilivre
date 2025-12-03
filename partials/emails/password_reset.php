<?php /** @var string $nome */ /** @var string $resetUrl */ ?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">
  <h3>Recuperação de senha</h3>
  <p>Olá, <?= htmlspecialchars($nome) ?>. Recebemos um pedido para redefinir sua senha.</p>
  <p><a href="<?= htmlspecialchars($resetUrl) ?>" style="background:#16a34a;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none">Redefinir senha</a></p>
    <p>Este link expira em 60 minutos. Se não foi você, ignore.</p>
  <?php include __DIR__ . '/_email_signature.php'; ?>
</div>
