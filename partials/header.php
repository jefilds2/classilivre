<?php
// partials/header.php - Cabeçalho unificado com o mesmo layout do index.php.

declare(strict_types=1);

if (!function_exists('current_user')) {
    require_once __DIR__ . '/../login_system/middleware.php';
}

$user = current_user();
$pageTitle = $pageTitle ?? 'Classi Livre Autos';

if (!function_exists('flash_css_class')) {
    function flash_css_class(string $type): string
    {
        switch ($type) {
            case 'success':
                return 'bg-green-100 text-green-800 border border-green-200';
            case 'error':
                return 'bg-red-100 text-red-800 border border-red-200';
            case 'warning':
                return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
            default:
                return 'bg-blue-100 text-blue-800 border border-blue-200';
        }
    }
}

$homeUrl = '/';
$searchPageUrl = '/anuncios/listar.php';
$servicesPageUrl = file_exists(__DIR__ . '/../servicos/index.php') ? '/servicos/index.php' : '#';
$purchaseRequestPageUrl = file_exists(__DIR__ . '/../solicitacoes-compra/index.php') ? '/solicitacoes-compra/' : '#';
$advertisePageUrl = $user ? '/anuncios/criar.php' : '/login_system/login.php';
$accountPageUrl = $user ? '/anuncios/meus.php' : '/login_system/login.php';
$logoPath = '/assets/img/classilivre-logo.gif';
$storeMetricsScriptPath = __DIR__ . '/../assets/js/store-metrics.js';
$storeMetricsScriptVersion = file_exists($storeMetricsScriptPath)
    ? (string) filemtime($storeMetricsScriptPath)
    : (string) time();

// Calcular saldo de moedas
$userCoinBalance = 0;
if ($user) {
    try {
        $userCoinBalance = function_exists('get_user_coin_balance') ? get_user_coin_balance((int) $user['id']) : 0;
    } catch (Throwable $e) {
        $userCoinBalance = 0;
    }
}

$flashMessages = flash_get();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description"
        content="<?= e($pageDescription ?? 'Classi Livre Autos - Portal para compra e venda de veículos.'); ?>">
    <title><?= e($pageTitle); ?></title>

    <!-- Meta Tags para Compartilhamento Social (Open Graph) -->
    <?php if (!empty($pageMetaTags) && is_array($pageMetaTags)): ?>
        <?php foreach ($pageMetaTags as $property => $content): ?>
            <meta property="<?= e($property); ?>" content="<?= e($content); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <link rel="icon" type="image/gif" href="/assets/img/classilivre-logo.png">

    <script src="https://cdn.botpress.cloud/webchat/v3.3/inject.js"></script>
    <script src="https://files.bpcontent.cloud/2025/10/24/22/20251024225643-1H5RMKJ2.js" defer></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet"
        href="/assets/css/main.css?v=<?= urlencode((string) filemtime(__DIR__ . '/../assets/css/main.css')); ?>">
    <script defer
        src="/assets/js/main.js?v=<?= urlencode((string) filemtime(__DIR__ . '/../assets/js/main.js')); ?>"></script>
    <script defer src="/assets/js/store-metrics.js?v=<?= urlencode($storeMetricsScriptVersion); ?>"></script>
    <script defer
        src="/assets/js/compare-multiselect.js?v=<?= urlencode((string) filemtime(__DIR__ . '/../assets/js/compare-multiselect.js')); ?>"></script>
    <?php if ($user): ?>
        <script defer
            src="/assets/js/session-timeout.js?v=<?= urlencode((string) filemtime(__DIR__ . '/../assets/js/session-timeout.js')); ?>"></script>
    <?php endif; ?>
    <style>
        .account-wrapper {
            display: inline-block;
            position: relative;
        }

        .account-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 999999 !important;
            display: none;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            min-width: 280px;
            max-height: 500px;
            overflow-y: auto;
            margin-top: 0.5rem;
        }

        .account-dropdown.show {
            display: block;
        }

        /* Responsive para celulares */
        @media (max-width: 640px) {
            .account-wrapper {
                position: fixed;
                display: block;
            }

            .account-dropdown {
                position: fixed;
                top: auto;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                min-width: 100%;
                max-width: 100%;
                border-radius: 1rem 1rem 0 0;
                max-height: calc(80vh);
                margin-top: 0;
            }
        }
    </style>
    <script>
        // Função global para toggle do dropdown da conta - DEVE estar no HEAD para inline onclick funcionar
        window.toggleAccountDropdown = function (event) {
            event.stopPropagation();
            const dropdown = document.querySelector('[data-account-dropdown]');
            if (!dropdown) return;

            dropdown.classList.toggle('show');
        };

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function (event) {
            const dropdown = document.querySelector('[data-account-dropdown]');
            const button = document.querySelector('[data-account-button]');
            if (dropdown && !dropdown.contains(event.target) && event.target !== button && !button.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Fechar com ESC
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const dropdown = document.querySelector('[data-account-dropdown]');
                if (dropdown) dropdown.classList.remove('show');
            }
        });
    </script>

    <!-- Proteção da Logo: Bloqueia Right-Click e Download -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Selecionar todas as imagens de logo
            const logos = document.querySelectorAll('img[src*="classilivre-logo"]');

            logos.forEach(logo => {
                // Bloquear right-click
                logo.addEventListener('contextmenu', function (e) {
                    e.preventDefault();
                    return false;
                });

                // Bloquear drag-and-drop
                logo.addEventListener('dragstart', function (e) {
                    e.preventDefault();
                    return false;
                });

                // Adicionar classe CSS para impedir seleção
                logo.style.userSelect = 'none';
                logo.style.webkitUserSelect = 'none';
                logo.style.msUserSelect = 'none';
                logo.style.mozUserSelect = 'none';

                // Impedir que seja arrastável
                logo.draggable = false;
            });
        });
    </script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-7862106661041475"
        crossorigin="anonymous"></script>
    <meta name="google-adsense-account" content="ca-pub-7862106661041475">

    <!-- Event snippet for Contato conversion page -->
    <script>
        gtag('event', 'conversion', { 'send_to': 'AW-10994153144/0o4cCKSBw-IDELjttfoo' });
    </script>

    <!-- Event snippet for Contato conversion page
In your html page, add the snippet and call gtag_report_conversion when someone clicks on the chosen link or button. -->
    <script>
        function gtag_report_conversion(url) {
            var callback = function () {
                if (typeof (url) != 'undefined') {
                    window.location = url;
                }
            };
            gtag('event', 'conversion', {
                'send_to': 'AW-10994153144/0o4cCKSBw-IDELjttfoo',
                'event_callback': callback
            });
            return false;
        }
    </script>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-10994153144">
    </script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'AW-10994153144');
    </script>

</head>

<body class="bg-gray-50 font-['Inter',sans-serif]">
    <!-- Facebook SDK for JavaScript -->
    <script>
        window.fbAsyncInit = function () {
            FB.init({
                appId: '1234567890', // Será substituído pelo app_id real
                xfbml: true,
                version: 'v21.0'
            });
        };
    </script>
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/pt_BR/sdk.js#xfbml=1&version=v21.0"
        nonce="AbCdEfGhIjKlMnOpQrStUvWxYz">
        </script>

    <header class="bg-white shadow-sm sticky top-0" style="z-index: 1000;">
        <div class="max-w-screen-2xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="<?= e($homeUrl); ?>" class="flex items-center space-x-3">
                <img src="<?= e($logoPath); ?>" alt="Logo Classi Livre Autos" class="h-12 md:h-16">
                <span class="text-sm md:text-xl font-semibold text-green-800">Classi Livre Autos</span>
            </a>
            <nav class="hidden md:flex space-x-2 items-center">
                <a href="<?= e($homeUrl); ?>"
                    class="nav-link bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center">
                    <i class="fas fa-home mr-2"></i> Início
                </a>
                <a href="<?= e($searchPageUrl); ?>"
                    class="nav-link bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center">
                    <i class="fas fa-search mr-2"></i> Buscar Veículos
                </a>
                <a href="<?= e($servicesPageUrl); ?>"
                    class="nav-link bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center">
                    <i class="fas fa-tools mr-2"></i> Catálogo de Empresas
                </a>
                <a href="<?= e($purchaseRequestPageUrl); ?>"
                    class="nav-link bg-gradient-to-r from-red-600 to-orange-500 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center">
                    <i class="fas fa-bullseye mr-2"></i> Solicitação de compra
                </a>
                <a href="<?= e($advertisePageUrl); ?>"
                    class="nav-link bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i> Anunciar
                </a>
                <?php if ($user): ?>
                    <div class="account-wrapper ml-2">
                        <button type="button"
                            class="nav-link bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center group"
                            data-account-button title="Sessão: <?= e($user['name']); ?>"
                            onclick="toggleAccountDropdown(event)">
                            <i class="fas fa-user-circle mr-2"></i>
                            <span class="hidden sm:inline">Conta</span>
                            <span class="hidden group-hover:inline text-xs ml-1 text-green-700"
                                title="<?= e($user['email']); ?>">
                                👤<?= mb_substr(e($user['name']), 0, 8); ?>
                            </span>
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        <div class="account-dropdown bg-white rounded-lg shadow-lg overflow-hidden" data-account-dropdown>
                            <div
                                class="bg-gradient-to-r from-green-700 to-green-800 px-4 py-3 text-white border-b border-green-600">
                                <div class="flex items-center">
                                    <i class="fas fa-circle text-green-300 text-xs mr-2"></i>
                                    <span class="text-xs font-semibold">LOGADO COMO</span>
                                </div>
                                <div class="mt-2 font-semibold text-sm">👤 <?= e($user['name']); ?></div>
                                <div class="text-xs text-green-200 mt-2 flex items-center gap-2 flex-wrap">
                                    <span class="flex items-center gap-1">📧 <?= e($user['email']); ?></span>
                                    <span
                                        class="inline-flex items-center gap-1 bg-white bg-opacity-40 rounded px-3 py-1 font-semibold text-green-900">💰
                                        <?= (int) $userCoinBalance; ?> moedas</span>
                                </div>
                                <?php if (!empty($user['is_admin'])): ?>
                                    <div
                                        class="mt-1.5 inline-block bg-red-500 text-white px-1.5 py-0.5 rounded text-xs font-bold">
                                        🔑 ADMINISTRADOR
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($user['is_premium'])): ?>
                                    <div
                                        class="mt-1.5 inline-block bg-gradient-to-r from-yellow-500 to-yellow-600 text-white px-2 py-0.5 rounded text-xs font-bold ml-1 shadow-md">
                                        <span
                                            style="text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000, 0 0 0 1px #000;">⭐</span>
                                        PREMIUM
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="<?= e($accountPageUrl); ?>"
                                class="flex items-center text-gray-700 px-4 py-3 hover:bg-gray-50 text-sm border-b">
                                <i class="fas fa-user mr-2 text-amber-500"></i> Minha Conta
                            </a>
                            <a href="/favoritos/meus_favoritos.php"
                                class="flex items-center text-gray-700 px-4 py-3 hover:bg-gray-50 text-sm border-b">
                                <i class="fas fa-heart mr-2 text-red-500"></i> Meus Favoritos
                            </a>
                            <a href="/premium/minhas_buscas_salvas.php"
                                class="flex items-center text-gray-700 px-4 py-3 hover:bg-gray-50 text-sm border-b">
                                <i class="fas fa-bookmark mr-2 text-amber-500"></i> Minhas Buscas Salvas
                            </a>
                            <a href="/solicitacoes-compra/minhas.php"
                                class="flex items-center text-gray-700 px-4 py-3 hover:bg-gray-50 text-sm border-b">
                                <i class="fas fa-bullseye mr-2 text-red-500"></i> Minhas Solicitações de Compra
                            </a>
                            <?php if (!empty($user['is_admin'])): ?>
                                <a href="/admin/index.php"
                                    class="flex items-center text-gray-700 px-4 py-3 hover:bg-gray-50 text-sm border-b">
                                    <i class="fas fa-gauge-high mr-2 text-amber-500"></i> Painel administrativo
                                </a>
                            <?php endif; ?>
                            <a href="/login_system/manage_sessions.php"
                                class="flex items-center text-gray-700 px-4 py-3 hover:bg-gray-50 text-sm border-b">
                                <i class="fas fa-devices mr-2 text-amber-500"></i> Minhas Sessões
                            </a>
                            <a href="/premium/status.php"
                                class="flex items-center text-gray-700 px-4 py-3 hover:bg-gray-50 text-sm border-b">
                                <i class="fas fa-shield-check mr-2 text-amber-500"></i> Status Premium
                            </a>
                            <a href="/login_system/logout.php"
                                class="flex items-center text-gray-700 px-4 py-3 hover:bg-red-50 text-sm font-semibold text-red-600">
                                <i class="fas fa-sign-out-alt mr-2"></i> Sair da Sessão
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login_system/login.php"
                        class="nav-link bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm flex items-center">
                        <i class="fas fa-user mr-2"></i> Entrar
                    </a>
                <?php endif; ?>
            </nav>
            <button
                class="md:hidden flex items-center gap-2 bg-amber-500 text-green-900 px-4 py-2 rounded-full font-semibold shadow-md border border-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2"
                type="button" data-mobile-menu-button aria-label="Abrir menu" aria-expanded="false"
                aria-controls="mobile-menu-panel">
                <i class="fas fa-bars text-xl"></i>
                <span class="text-sm tracking-wide uppercase">Menu</span>
            </button>
        </div>
        <div id="mobile-menu-panel" class="md:hidden hidden border-t border-gray-100 bg-white" data-mobile-menu-panel>
            <div class="px-4 py-3 space-y-2">
                <a href="<?= e($homeUrl); ?>"
                    class="block bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center">
                    <i class="fas fa-home mr-2"></i> Início
                </a>
                <a href="<?= e($searchPageUrl); ?>"
                    class="block bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center">
                    <i class="fas fa-search mr-2"></i> Buscar
                </a>
                <a href="<?= e($servicesPageUrl); ?>"
                    class="block bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center">
                    <i class="fas fa-tools mr-2"></i> Serviços Automotivos
                </a>
                <a href="<?= e($purchaseRequestPageUrl); ?>"
                    class="block bg-gradient-to-r from-red-600 to-orange-500 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center">
                    <i class="fas fa-bullseye mr-2"></i> Solicitação de compra
                </a>
                <a href="<?= e($advertisePageUrl); ?>"
                    class="block bg-amber-500 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center">
                    <i class="fas fa-plus-circle mr-2"></i> Anunciar
                </a>
                <?php if ($user): ?>
                    <div class="bg-gradient-to-r from-green-700 to-green-800 px-4 py-3 text-white mb-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-circle text-green-300 text-xs mr-2"></i>
                            <span class="text-xs font-semibold">LOGADO COMO</span>
                        </div>
                        <div class="mt-2 font-semibold text-sm">👤 <?= e($user['name']); ?></div>
                        <div class="text-xs text-green-200 mt-1 flex items-center gap-2 flex-wrap">
                            <span class="flex items-center gap-1">📧 <?= e($user['email']); ?></span>
                            <span
                                class="inline-flex items-center gap-1 bg-white bg-opacity-20 rounded px-2 py-0.5 font-semibold text-green-50">💰
                                <?= (int) $userCoinBalance; ?> moedas</span>
                        </div>
                        <?php if (!empty($user['is_admin'])): ?>
                            <div class="mt-1.5 inline-block bg-red-500 text-white px-1.5 py-0.5 rounded text-xs font-bold">
                                🔑 ADMINISTRADOR
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($user['is_premium'])): ?>
                            <div
                                class="mt-1.5 inline-block bg-yellow-400 text-gray-900 px-2 py-0.5 rounded text-xs font-bold ml-1">
                                ⭐ PREMIUM
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= e($accountPageUrl); ?>"
                        class="block bg-white border border-amber-200 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center mb-2">
                        <i class="fas fa-user mr-2"></i> Minha Conta
                    </a>
                    <a href="/favoritos/meus_favoritos.php"
                        class="block bg-white border border-amber-200 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center mb-2">
                        <i class="fas fa-heart mr-2 text-red-500"></i> Meus Favoritos
                    </a>
                    <a href="/premium/minhas_buscas_salvas.php"
                        class="block bg-white border border-amber-200 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center mb-2">
                        <i class="fas fa-bookmark mr-2 text-amber-500"></i> Minhas Buscas Salvas
                    </a>
                    <a href="/solicitacoes-compra/minhas.php"
                        class="block bg-white border border-amber-200 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center mb-2">
                        <i class="fas fa-bullseye mr-2 text-red-500"></i> Minhas Solicitações de Compra
                    </a>
                    <?php if (!empty($user['is_admin'])): ?>
                        <a href="/admin/index.php"
                            class="block bg-white border border-amber-200 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center mb-2">
                            <i class="fas fa-gauge-high mr-2"></i> Painel administrativo
                        </a>
                    <?php endif; ?>
                    <a href="/premium/status.php"
                        class="block bg-white border border-amber-200 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center mb-2">
                        <i class="fas fa-shield-check mr-2"></i> Status Premium
                    </a>
                    <a href="/login_system/logout.php"
                        class="block bg-red-500 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sair da Sessão
                    </a>
                <?php else: ?>
                    <a href="/login_system/login.php"
                        class="block bg-white border border-amber-200 text-green-900 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm text-center">
                        <i class="fas fa-user mr-2"></i> Entrar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if ($flashMessages): ?>
        <div class="max-w-screen-2xl mx-auto px-4 mt-4 space-y-3">
            <?php foreach ($flashMessages as $flash): ?>
                <div class="<?= e(flash_css_class($flash['type'] ?? 'info')); ?> px-4 py-3 rounded-lg text-sm">
                    <?= e($flash['message'] ?? ''); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
