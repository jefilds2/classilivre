<?php /** @var string $nome */ /** @var string $titulo */ /** @var string $status_label */ /** @var string|null $motivo */ /** @var string $dashboard_url */ ?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">
  <h3>Atualização do seu serviço: <em><?= htmlspecialchars($titulo); ?></em></h3>
  <p>Status: <strong><?= htmlspecialchars($status_label); ?></strong></p>
  <?php if (!empty($motivo)): ?>
    <p><strong>Motivo informado pela equipe:</strong><br><?= nl2br(htmlspecialchars($motivo)); ?></p>
  <?php endif; ?>
  <p>Você pode acompanhar e gerenciar seus serviços acessando o painel do vendedor:</p>
  <p><a href="<?= htmlspecialchars($dashboard_url); ?>">Ir para Minha Conta</a></p>
  <?php include __DIR__ . '/_email_signature.php'; ?>
</div>
