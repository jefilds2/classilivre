<?php /** @var string $nome */ /** @var string $titulo */ /** @var string $status */ /** @var string|null $motivo */ /** @var string $url */ ?>
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">
  <h3>Atualização do seu anúncio: <em><?= htmlspecialchars($titulo) ?></em></h3>
  <p>Status: <strong><?= htmlspecialchars(ucfirst($status)) ?></strong></p>
  <?php if(!empty($motivo)): ?>
  <p><strong>Motivo:</strong> <?= nl2br(htmlspecialchars($motivo)) ?></p>
  <?php endif; ?>
  <p><a href="<?= htmlspecialchars($url) ?>">Ver anúncio</a></p>
  <?php include __DIR__ . '/_email_signature.php'; ?>
 </div>
