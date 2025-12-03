<?php
// premium/index.php — Página com benefícios do plano premium e chamada para ação.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

$user = current_user();
$pageTitle = 'Premium — Site Carros';
$isPremium = $user ? user_is_premium($user) : false;

$features = [
    'Publicação ilimitada de anúncios',
    'Aprovação prioritária na moderação',
    'Destaque visual nas listagens',
    'Envio de vídeo MP4 do veículo',
    'Suporte especializado para destacar seu anúncio',
];

include __DIR__ . '/../partials/header.php';
?>
<section style="margin:3rem 0;">
    <div class="form-card" style="background:linear-gradient(120deg,#1d3557,#457b9d);color:#fff;">
        <h1>Plano Premium SiteCarros</h1>
        <p style="margin-top:1rem; max-width:600px;">Enquanto testamos o sistema de pagamentos, o plano <strong>Premium Ilimitado</strong> está liberado gratuitamente. Aproveite para turbinar seus anúncios e alcançar mais compradores.</p>
        <div style="margin-top:1.5rem; display:flex; gap:1rem; flex-wrap:wrap;">
            <?php if ($isPremium): ?>
                <span class="button secondary" style="background:#fff;color:#1d3557;">Você já é Premium</span>
                <a class="button" href="/premium/status.php">Ver status</a>
            <?php elseif ($user): ?>
                <a class="button" href="/premium/status.php">Assinar agora</a>
                <a class="button secondary" href="/premium/status.php">Ver planos</a>
            <?php else: ?>
                <a class="button" href="/login_system/login.php?redirect=/premium/index.php">Entrar para ativar</a>
                <a class="button secondary" href="/login_system/register.php">Criar conta</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-card" style="margin-top:2rem;">
        <h2>Por que virar premium?</h2>
        <ul style="margin-top:1rem; display:grid; gap:0.8rem;">
            <?php foreach ($features as $feature): ?>
                <li style="display:flex;align-items:center;gap:0.6rem;">✅ <?= e($feature); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
