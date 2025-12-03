<?php /** @var string $nome */ /** @var string $motivo */ /** @var string $tipo */ /** @var string|null $dataReativacao */ ?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">
  <h3 style="color:#c62828;">Notificação de Suspensão de Conta</h3>
  <p>Olá <?= htmlspecialchars($nome) ?>,</p>
  <p>Informamos que sua conta foi <strong>suspensa</strong> temporariamente por um período.</p>
  
  <div style="background-color:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:15px;margin:20px 0;">
    <p style="margin:0 0 10px 0;"><strong>Motivo da suspensão:</strong></p>
    <p style="margin:0;color:#333;"><?= nl2br(htmlspecialchars($motivo)) ?></p>
  </div>

  <h4>O que acontece agora:</h4>
  <ul style="color:#555;">
    <li>Sua conta está inacessível temporariamente</li>
    <li>Todos os seus anúncios foram suspensos</li>
    <li>Seus serviços foram desativados</li>
    <li>Você não conseguirá fazer novas publicações</li>
  </ul>

  <?php if(!empty($dataReativacao)): ?>
  <p style="background-color:#e8f5e9;border-left:4px solid #4caf50;padding:12px;margin:20px 0;">
    <strong>Previsão de reativação:</strong> <?= date('d/m/Y H:i', strtotime($dataReativacao)) ?>
  </p>
  <?php endif; ?>

  <p style="margin-top:20px;">Se você acredita que é um erro, entre em contato conosco através do e-mail de suporte.</p>
  
  <?php include __DIR__ . '/_email_signature.php'; ?>
</div>
