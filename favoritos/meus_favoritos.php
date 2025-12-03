<?php
/**
 * favoritos/meus_favoritos.php
 * Página para gerenciar favoritos do usuário
 * Os favoritos são armazenados em localStorage no cliente
 */

require_once __DIR__ . '/../login_system/middleware.php';

if (!current_user()) {
    redirect('/login_system/login.php');
}

$pageTitle = 'Meus Favoritos';
require_once __DIR__ . '/../partials/header.php';
?>

    <main class="container" style="padding: 3rem 1.5rem; max-width: 1200px; margin: 0 auto;">
        <div style="display: grid; gap: 1.5rem; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem; font-weight: 700; color: #1f2937; margin: 0;">Meus Favoritos</h1>
                <p style="color: #6b7280; margin: 0.5rem 0 0; font-size: 1rem;">
                    Você tem <strong id="favCount">0</strong> de <strong>10</strong> favoritos salvos.
                </p>
            </div>

            <!-- Barra de ações -->
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="/anuncios/listar.php" class="button secondary">
                    <i class="fas fa-arrow-left"></i> Voltar à Busca
                </a>
                <button type="button" class="button secondary" onclick="clearAllFavorites()" style="background: #dc2626; color: white; border-color: #dc2626; display: none;" id="clearBtn">
                    <i class="fas fa-trash"></i> Limpar Todos
                </button>
            </div>
        </div>

        <!-- Loader -->
        <div id="loader" style="text-align: center; padding: 3rem; color: #6b7280;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-right: 1rem;"></i>
            Carregando favoritos...
        </div>

        <!-- Lista de Favoritos -->
        <div id="favoritesContainer"></div>

        <!-- Botão Comparar -->
        <div id="compareButtonContainer" style="display: none; margin-top: 2rem; text-align: center;">
            <button 
                type="button"
                class="button"
                onclick="showCompareSelectionModal()"
                style="background: #1d4ed8; color: white; border-color: #1d4ed8; font-size: 1.05rem; padding: 0.8rem 2rem;"
            >
                <i class="fas fa-layer-group"></i> Comparar Selecionados (<span id="compareCount">0</span>)
            </button>
        </div>
    </main>

    <?php require_once __DIR__ . '/../partials/footer.php'; ?>

    <script defer src="/assets/js/compare-multiselect.js"></script>
    <script>
        // Carregar favoritos do localStorage
        async function loadFavorites() {
            const key = 'clautos:favorites';
            let favorites = JSON.parse(localStorage.getItem(key) || '[]');
            
            document.getElementById('favCount').textContent = favorites.length;
            document.getElementById('compareCount').textContent = favorites.length;
            
            if (favorites.length === 0) {
                renderEmptyState();
                document.getElementById('loader').style.display = 'none';
                return;
            }

            document.getElementById('clearBtn').style.display = 'inline-flex';
            document.getElementById('compareButtonContainer').style.display = favorites.length > 1 ? 'block' : 'none';

            try {
                // Buscar detalhes dos anúncios
                const idsToFetch = favorites.map(f => f.id);
                
                const response = await fetch('/api/get_ads_by_ids.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: idsToFetch })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                
                const ads = await response.json();
                
                // Se API retornou vazio, usar dados do localStorage
                if (!ads || ads.length === 0) {
                    renderFavorites(favorites);
                } else {
                    renderFavorites(ads);
                }
            } catch (error) {
                // Fallback: renderizar com dados do localStorage
                renderFavorites(favorites);
            } finally {
                document.getElementById('loader').style.display = 'none';
            }
        }

        function renderEmptyState() {
            document.getElementById('favoritesContainer').innerHTML = `
                <div style="
                    background: rgba(15, 23, 42, 0.04);
                    border: 2px dashed rgba(15, 23, 42, 0.18);
                    border-radius: 16px;
                    padding: 3rem;
                    text-align: center;
                    color: #6b7280;
                ">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">
                        <i class="fas fa-heart-broken"></i>
                    </div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; color: #1f2937; margin: 0;">Nenhum favorito ainda</h3>
                    <p style="margin: 0.5rem 0 0; line-height: 1.6;">
                        Quando você favoritar um anúncio, ele aparecerá aqui. 
                        <a href="/anuncios/listar.php" style="color: #136f33; text-decoration: none; font-weight: 600;">
                            Explore veículos →
                        </a>
                    </p>
                </div>
            `;
        }

        function resolveAdImages(ad) {
            const ensureAbsolutePath = (path) => {
                if (typeof path !== 'string') {
                    return '';
                }

                const trimmed = path.trim();
                if (!trimmed) {
                    return '';
                }

                if (/^https?:\/\//i.test(trimmed) || trimmed.startsWith('//')) {
                    return trimmed;
                }

                return trimmed.startsWith('/') ? trimmed : `/${trimmed.replace(/^\/+/, '')}`;
            };

            const rawImages = [];

            if (ad && Array.isArray(ad.images)) {
                rawImages.push(...ad.images);
            } else if (ad && typeof ad.images === 'string') {
                try {
                    const parsed = JSON.parse(ad.images);
                    if (Array.isArray(parsed)) {
                        rawImages.push(...parsed);
                    } else {
                        rawImages.push(ad.images);
                    }
                } catch (error) {
                    rawImages.push(ad.images);
                }
            }

            ['image', 'cover_image', 'featured_image', 'thumbnail'].forEach((key) => {
                if (ad && typeof ad[key] === 'string') {
                    rawImages.push(ad[key]);
                }
            });

            const normalized = Array.from(new Set(rawImages
                .map(ensureAbsolutePath)
                .filter(Boolean)));

            return normalized.length ? normalized : ['/assets/uploads/images/sample-car-1.jpg'];
        }

        function resolveStoreId(ad) {
            if (!ad || typeof ad !== 'object') {
                return null;
            }

            const candidates = [
                ad.store_id,
                ad.storeId,
                ad.store,
                ad.storeOwner,
                ad.storeOwnerId,
                ad.owner_id,
                ad.ownerId,
                ad.user_id,
                ad.userId,
            ];

            for (const candidate of candidates) {
                const numeric = Number(candidate);
                if (Number.isFinite(numeric) && numeric > 0) {
                    return numeric;
                }
            }

            return null;
        }

        function renderFavorites(ads) {
            if (!ads || ads.length === 0) {
                renderEmptyState();
                return;
            }

            let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">';
            
            ads.forEach((ad, index) => {
                const priceFormatted = ad.price ? parseFloat(ad.price).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : 'N/A';

                const images = resolveAdImages(ad);
                const imagesJson = JSON.stringify(images);
                const imageUrl = images[0];
                const storeId = resolveStoreId(ad);
                const trackPosition = index + 1;
                const storeName = ad.owner_name || ad.ownerName || '';
                
                // Montar detalhes primários (ano | cor | km)
                const primaryDetails = [];
                if (ad.model_year) primaryDetails.push(ad.model_year);
                if (ad.color) primaryDetails.push(ad.color.charAt(0).toUpperCase() + ad.color.slice(1).toLowerCase());
                const mileage = ad.mileage > 0 ? ad.mileage.toLocaleString('pt-BR') : '0';
                if (mileage !== '0') primaryDetails.push(mileage + ' km');
                const primaryText = primaryDetails.join(' | ');
                
                // Montar detalhes secundários (câmbio | combustível)
                const secondaryDetails = [];
                if (ad.transmission) secondaryDetails.push(ad.transmission);
                if (ad.fuel_type) secondaryDetails.push(ad.fuel_type);
                const secondaryText = secondaryDetails.join(' | ');
                
                const location = [ad.city, ad.state].filter(x => x).join(' / ');
                const verifiedBadge = ad.is_verified ? `
                    <span style="
                        position: absolute;
                        top: 12px;
                        right: 12px;
                        background: white;
                        border: none;
                        color: #166534;
                        padding: 0.35rem 0.75rem;
                        border-radius: 2rem;
                        font-size: 0.75rem;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 0.35rem;
                    ">
                        <img src="/assets/img/verificado.png" alt="Verificado" style="width: 14px; height: 14px;">
                        <span>Verificado</span>
                    </span>
                ` : '';

                html += `
                    <article style="
                        background: white;
                        border-radius: 1rem;
                        box-shadow: 0 10px 15px rgba(15, 23, 42, 0.12);
                        overflow: hidden;
                        display: flex;
                        flex-direction: column;
                        position: relative;
                    " data-ad-id="${ad.id}" data-images="${imagesJson.replace(/"/g, '&quot;')}"${storeId ? ` data-store-owner="${storeId}"` : ''}${storeName ? ` data-store-owner-name="${storeName.replace(/"/g, '&quot;')}"` : ''} data-track-origin="favorites_list" data-track-position="${trackPosition}">
                        <div style="position: relative;">
                            <a href="/anuncio/${ad.id}/${ad.slug || 'veiculo'}" style="display: block;" data-track-ad-click>
                                <img src="${imageUrl}" alt="${ad.title || 'Veículo'}" style="width: 100%; height: 224px; object-fit: cover; display: block;" onerror="this.src='/assets/placeholder.jpg'" data-primary-image data-ad-image>
                            </a>
                            <span style="
                                position: absolute;
                                top: 12px;
                                left: 12px;
                                background: #166534;
                                color: white;
                                padding: 0.35rem 0.75rem;
                                border-radius: 2rem;
                                font-weight: 700;
                                font-size: 0.85rem;
                            ">${priceFormatted}</span>
                            ${verifiedBadge}
                        </div>
                        <div style="padding: 1.25rem; display: flex; flex-direction: column; flex: 1;">
                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1f2937; line-height: 1.35; flex: 1;">
                                    ${ad.title || 'Veículo'}
                                </h3>
                                <button type="button" 
                                    class="vehicle-card__favorite vehicle-card__favorite--inline"
                                    data-favorite-toggle
                                    data-favorite-payload='${JSON.stringify({id: ad.id, title: ad.title, price: ad.price, price_formatted: priceFormatted, model_year: ad.model_year, mileage: ad.mileage, transmission: ad.transmission, fuel_type: ad.fuel_type, image: imageUrl, url: `/anuncio/${ad.id}/${ad.slug || "veiculo"}`, city: ad.city, state: ad.state, is_verified: ad.is_verified, store_id: storeId}).replace(/"/g, "&quot;")}'
                                    aria-pressed="true"
                                    title="Remover dos favoritos">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                            <div style="display: grid; gap: 0.5rem; font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem; flex: 1;">
                                ${primaryText ? `<p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fa-regular fa-calendar" style="color: #f59e0b;"></i>
                                    <span>${primaryText}</span>
                                </p>` : ''}
                                ${secondaryText ? `<p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fa-solid fa-gear" style="color: #f59e0b;"></i>
                                    <span>${secondaryText}</span>
                                </p>` : ''}
                            </div>
                            ${location ? `<p style="margin: 0 0 1.25rem; font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fa-solid fa-location-dot" style="color: #f59e0b;"></i>
                                <span>${location}</span>
                            </p>` : ''}
                            <a href="/anuncio/${ad.id}/${ad.slug || 'veiculo'}"
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 100%;
                                    background: #166534;
                                    color: white;
                                    padding: 0.625rem 1rem;
                                    border-radius: 0.5rem;
                                    text-decoration: none;
                                    font-weight: 600;
                                    transition: background 0.2s;
                                    gap: 0.5rem;
                                    font-size: 0.9rem;
                                "
                                onmouseover="this.style.background='#15803d'"
                                onmouseout="this.style.background='#166534'"
                                data-track-ad-click
                            >
                                <i class="fa-regular fa-eye"></i> Ver detalhes
                            </a>
                        </div>
                    </article>
                `;
            });

            html += '</div>';
            document.getElementById('favoritesContainer').innerHTML = html;
            
            // Inicializar botões de favorito após renderizar
            initializeFavoriteButtons();
        }

        // Constante para a chave do localStorage
        const FAVORITES_KEY = 'clautos:favorites';

        // Função para obter todos os favoritos
        function getFavorites() {
            try {
                const stored = localStorage.getItem(FAVORITES_KEY);
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                return [];
            }
        }

        // Função para salvar favoritos
        function setFavorites(favorites) {
            localStorage.setItem(FAVORITES_KEY, JSON.stringify(favorites));
        }

        // Função para verificar se um anúncio é favorito
        function isFavorited(adId) {
            return getFavorites().some(fav => fav.id === adId);
        }

        // Função para adicionar favorito
        function addFavorite(payload) {
            const favorites = getFavorites();
            if (!favorites.some(fav => fav.id === payload.id)) {
                favorites.push(payload);
                setFavorites(favorites);
            }
        }

        // Função para atualizar visual do botão
        function updateFavoriteButton(button, isFav) {
            button.setAttribute('aria-pressed', isFav ? 'true' : 'false');
            const icon = button.querySelector('i');
            if (icon) {
                if (isFav) {
                    icon.classList.remove('fa-regular');
                    icon.classList.add('fa-solid');
                } else {
                    icon.classList.remove('fa-solid');
                    icon.classList.add('fa-regular');
                }
            }
        }

        // Função para inicializar botões de favorito
        function initializeFavoriteButtons() {
            document.querySelectorAll('[data-favorite-toggle]').forEach(button => {
                const payload = button.getAttribute('data-favorite-payload');
                if (!payload) return;

                try {
                    const adData = JSON.parse(payload);
                    const isFav = isFavorited(adData.id);
                    updateFavoriteButton(button, isFav);

                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        const currentIsFav = isFavorited(adData.id);
                        
                        if (currentIsFav) {
                            removeFavorite(adData.id);
                            updateFavoriteButton(button, false);
                        } else {
                            addFavorite(adData);
                            updateFavoriteButton(button, true);
                        }
                    });
                } catch (e) {
                    // Erro ao processar favorito
                }
            });
        }

        // Remover um favorito específico
        function removeFavorite(adId) {
            const favorites = getFavorites();
            const updated = favorites.filter(fav => fav.id !== adId);
            setFavorites(updated);
            
            // Recarregar a lista de favoritos
            loadFavorites();
        }

        // Limpar todos os favoritos
        function clearAllFavorites() {
            if (!confirm('Tem certeza que quer remover TODOS os favoritos?')) return;
            
            localStorage.removeItem('clautos:favorites');
            loadFavorites();
        }

        // Carregar ao abrir a página
        document.addEventListener('DOMContentLoaded', () => {
            loadFavorites();
        });

        // Renderizar modal de comparação
        function renderCompareModal(favorites) {
            if (!favorites || favorites.length === 0) {
                alert('Nenhum favorito selecionado para comparar.');
                return;
            }

            // Criar HTML do modal de comparação
            const compareModalHtml = `
                <div class="compare-overlay" id="compareOverlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 99998;
                    padding: 1rem;
                " onclick="if(event.target.id === 'compareOverlay') document.getElementById('compareOverlay').remove()">
                    <div style="
                        background: white;
                        border-radius: 1rem;
                        width: 100%;
                        max-width: 1400px;
                        max-height: 90vh;
                        overflow: auto;
                        position: relative;
                    ">
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 1.5rem;
                            border-bottom: 1px solid #e5e7eb;
                            position: sticky;
                            top: 0;
                            background: white;
                        ">
                            <h2 style="
                                margin: 0; 
                                font-size: 1.75rem; 
                                font-weight: 700;
                                background: linear-gradient(to right, #166534 0%, #166534 6px, transparent 6px);
                                padding-left: 12px;
                                display: inline-block;
                                color: #166534;
                            ">Comparação de Veículos</h2>
                            <button onclick="document.getElementById('compareOverlay').remove()" style="
                                background: none;
                                border: none;
                                font-size: 1.5rem;
                                cursor: pointer;
                                color: #6b7280;
                            ">✕</button>
                        </div>
                        <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                            ${favorites.map((ad, idx) => {
                                const images = resolveAdImages(ad);
                                const imagesJson = JSON.stringify(images).replace(/"/g, '&quot;');
                                const mainImage = images[0];
                                const storeId = resolveStoreId(ad);
                                const storeName = ad.owner_name || ad.ownerName || '';
                                const trackPosition = idx + 1;
                                
                                const mileage = ad.mileage > 0 ? ad.mileage.toLocaleString('pt-BR') : '0';
                                const priceFormatted = ad.price_formatted || 'R$ ' + (ad.price || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                
                                // Montar detalhes primários (ano | cor | km)
                                const primaryDetails = [];
                                if (ad.model_year) primaryDetails.push(ad.model_year);
                                if (ad.color) primaryDetails.push(ad.color.charAt(0).toUpperCase() + ad.color.slice(1).toLowerCase());
                                if (mileage !== '0') primaryDetails.push(mileage + ' km');
                                const primaryText = primaryDetails.join(' | ');
                                
                                // Montar detalhes secundários (câmbio | combustível)
                                const secondaryDetails = [];
                                if (ad.transmission) secondaryDetails.push(ad.transmission);
                                if (ad.fuel_type) secondaryDetails.push(ad.fuel_type);
                                const secondaryText = secondaryDetails.join(' | ');
                                
                                const location = [ad.city, ad.state].filter(x => x).join(' / ');
                                const verifiedBadge = ad.is_verified ? `
                                    <span style="
                                        position: absolute;
                                        top: 12px;
                                        right: 12px;
                                        background: white;
                                        border: none;
                                        color: #166534;
                                        padding: 0.35rem 0.75rem;
                                        border-radius: 2rem;
                                        font-size: 0.75rem;
                                        font-weight: 600;
                                        display: flex;
                                        align-items: center;
                                        gap: 0.35rem;
                                    ">
                                        <img src="/assets/img/verificado.png" alt="Verificado" style="width: 14px; height: 14px;">
                                        <span>Verificado</span>
                                    </span>
                                ` : '';
                                
                                return `
                                    <article style="
                                        background: white;
                                        border-radius: 1rem;
                                        box-shadow: 0 10px 15px rgba(15, 23, 42, 0.12);
                                        overflow: hidden;
                                        display: flex;
                                        flex-direction: column;
                                        position: relative;
                                    " data-ad-id="${ad.id}" data-images="${imagesJson}"${storeId ? ` data-store-owner="${storeId}"` : ''}${storeName ? ` data-store-owner-name="${storeName.replace(/"/g, '&quot;')}"` : ''} data-track-origin="favorites_compare" data-track-position="${trackPosition}">
                                        <div style="position: relative;">
                                            <a href="/anuncio/${ad.id}/${ad.slug || 'veiculo'}" style="display: block;" data-track-ad-click>
                                                <img src="${mainImage}" 
                                                    alt="${ad.title}" 
                                                    style="width: 100%; height: 224px; object-fit: cover; display: block;"
                                                    data-primary-image
                                                    data-ad-image>
                                            </a>
                                            <span style="
                                                position: absolute;
                                                top: 12px;
                                                left: 12px;
                                                background: #166534;
                                                color: white;
                                                padding: 0.35rem 0.75rem;
                                                border-radius: 2rem;
                                                font-weight: 700;
                                                font-size: 0.85rem;
                                            ">${priceFormatted}</span>
                                            ${verifiedBadge}
                                        </div>
                                        <div style="padding: 1.25rem; display: flex; flex-direction: column; flex: 1;">
                                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem;">
                                                <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1f2937; line-height: 1.35; flex: 1;">
                                                    ${ad.title}
                                                </h3>
                                                <button type="button" 
                                                    class="vehicle-card__favorite vehicle-card__favorite--inline"
                                                    data-favorite-toggle
                                                    data-favorite-payload='${JSON.stringify({id: ad.id, title: ad.title, price: ad.price, price_formatted: priceFormatted, model_year: ad.model_year, mileage: ad.mileage, transmission: ad.transmission, fuel_type: ad.fuel_type, image: mainImage, url: `/anuncio/${ad.id}/${ad.slug || "veiculo"}`, city: ad.city, state: ad.state, is_verified: ad.is_verified, store_id: storeId}).replace(/"/g, "&quot;")}'
                                                    aria-pressed="true" 
                                                    title="Remover dos favoritos">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                            </div>
                                            <div style="display: grid; gap: 0.5rem; font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem; flex: 1;">
                                                ${primaryText ? `<p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fa-regular fa-calendar" style="color: #f59e0b;"></i>
                                                    <span>${primaryText}</span>
                                                </p>` : ''}
                                                ${secondaryText ? `<p style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fa-solid fa-gear" style="color: #f59e0b;"></i>
                                                    <span>${secondaryText}</span>
                                                </p>` : ''}
                                            </div>
                                            ${location ? `<p style="margin: 0 0 1.25rem; font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fa-solid fa-location-dot" style="color: #f59e0b;"></i>
                                                <span>${location}</span>
                                            </p>` : ''}
                                            <a href="/anuncio/${ad.id}/${ad.slug || 'veiculo'}" 
                                                style="
                                                    display: inline-flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    width: 100%;
                                                    background: #166534;
                                                    color: white;
                                                    padding: 0.625rem 1rem;
                                                    border-radius: 0.5rem;
                                                    text-decoration: none;
                                                    font-weight: 600;
                                                    transition: background 0.2s;
                                                    gap: 0.5rem;
                                                    font-size: 0.9rem;
                                                "
                                                onmouseover="this.style.background='#15803d'"
                                                onmouseout="this.style.background='#166534'"
                                                data-track-ad-click
                                            >
                                                <i class="fa-regular fa-eye"></i> Ver detalhes
                                            </a>
                                        </div>
                                    </article>
                                `;
                            }).join('')}
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', compareModalHtml);
            
            // Inicializar carrossel se a função estiver disponível (vindo de search-page.js)
            if (typeof window.initCarousels === 'function') {
                window.initCarousels();
            }
            
            // Inicializar botões de favorito no modal
            initializeFavoriteButtons();
        }

        // Abrir modal de comparação com favoritos selecionados
        function showCompareSelectionModal() {
            // Obter favoritos do localStorage
            const key = 'clautos:favorites';
            let favorites = JSON.parse(localStorage.getItem(key) || '[]');
            
            if (favorites.length < 2) {
                alert('Selecione pelo menos 2 veículos para comparar.');
                return;
            }
            
            // Buscar detalhes dos favoritos
            fetch('/api/get_ads_by_ids.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: favorites.map(f => f.id) })
            })
            .then(r => r.json())
            .then(data => {
                // API retorna diretamente um array de anúncios
                if (Array.isArray(data) && data.length > 0) {
                    renderCompareModal(data);
                } else {
                    alert('Erro ao carregar detalhes dos veículos.');
                }
            })
            .catch(err => {
                alert('Erro ao comparar favoritos.');
            });
        }
    </script>

    <style>
        .vehicle-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .vehicle-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.15);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.95rem;
            border: 2px solid #e5e7eb;
            background: white;
            color: #1f2937;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .button:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .button.secondary {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        @media (max-width: 768px) {
            main {
                padding: 1.5rem !important;
            }
        }
    </style>
