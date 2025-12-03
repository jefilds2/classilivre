<?php
// partials/nav_user.php — Navegação principal para usuários autenticados.

// NOVO - Carregar saldo de moedas para display (Fase 3)
$user_coin_balance = 0;
try {
    $user_coin_balance = get_user_coin_balance((int)current_user()['id']);
} catch (Exception $e) {
    // Silent fail - se não conseguir, apenas não mostra o saldo
}
?>
<nav class="nav-links">
    <a href="/anuncios/listar.php">Buscar</a>
    <a href="/anuncios/criar.php">Novo anúncio</a>
    <a href="/anuncios/meus.php">Meus anúncios</a>
    <a href="/premium/status.php">
        💰 Minha Conta
        <span style="display:inline-block; background:#667eea; color:white; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.85rem; margin-left:0.3rem;">
            <?= (int)$user_coin_balance; ?>
        </span>
    </a>
</nav>
<div class="user-links">
    <span>Olá, <?= e(current_user()['name']); ?><?php if (user_is_premium(current_user())): ?> <span class="badge-premium">Premium</span><?php endif; ?></span>
    <a class="button secondary" href="/login_system/logout.php">Sair</a>
</div>
