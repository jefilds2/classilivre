<?php /** @var string $nome */ ?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">
  <h3 style="color:#2e7d32;">Sua Conta Foi Reativada!</h3>
  <p>Olá <?= htmlspecialchars($nome) ?>,</p>
  <p>Ficamos felizes em informar que sua suspensão foi removida e sua conta foi <strong>reativada</strong> com sucesso!</p>
  
  <div style="background-color:#e8f5e9;border:1px solid #4caf50;border-radius:8px;padding:15px;margin:20px 0;">
    <h4 style="margin:0 0 10px 0;color:#2e7d32;">Você pode novamente:</h4>
    <ul style="margin:0;color:#333;padding-left:20px;">
      <li>Acessar sua conta normalmente</li>
      <li>Criar novos anúncios</li>
      <li>Ativar seus serviços</li>
      <li>Gerenciar seus dados de perfil</li>
    </ul>
  </div>

  <p>Agradecemos sua compreensão e esperamos contar com você novamente na Classi Livre Autos.</p>
  <p>Qualquer dúvida, estamos à disposição!</p>
  
  <?php include __DIR__ . '/_email_signature.php'; ?>
</div>
