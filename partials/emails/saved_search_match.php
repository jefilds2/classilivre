<?php /** @var string $nome */ /** @var string $titulo */ /** @var string $preco */ /** @var string $url */ ?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">
  <h2 style="color:#1a7f4e">🎯 Novo anúncio encontrado!</h2>
  <p>Oi <?= htmlspecialchars($nome) ?>,</p>
  <p>Uma nova publicação foi encontrada que corresponde a uma de suas buscas salvas:</p>
  
  <div style="background-color:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #1a7f4e">
    <h3 style="margin-top:0"><?= htmlspecialchars($titulo) ?></h3>
    <p style="font-size:18px;color:#2d5f3f"><strong><?= htmlspecialchars($preco) ?></strong></p>
    <p style="margin-bottom:0">
      <a href="<?= htmlspecialchars($url) ?>" style="background-color:#1a7f4e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block">Ver anúncio completo</a>
    </p>
  </div>
  
  <p>Acesse sua conta para gerenciar suas buscas salvas e notificações.</p>
  
  <?php include __DIR__ . '/_email_signature.php'; ?>
</div>
