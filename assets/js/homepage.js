(() => {
    const FEATURED_REFRESH_INTERVAL = 600000; // 10 minutos
    const CAROUSEL_INTERVAL = 5000; // 5 segundos por foto
    const carouselTimers = new WeakMap();

    function initTypeSelector() {
        const selector = document.querySelector('[data-type-selector]');
        if (!selector) return;
        const buttons = selector.querySelectorAll('button[data-type]');
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                buttons.forEach((btn) => btn.classList.remove('ativo'));
                button.classList.add('ativo');
                selector.dataset.selected = button.dataset.type ?? '';
            });
        });
    }

    function buildSearchParams(baseUrl, selectedType, cityValue) {
        const params = new URLSearchParams();
        if (selectedType) {
            params.set('tipo', selectedType);
        }
        if (cityValue) {
            params.set('city', cityValue);
        }
        const query = params.toString();
        return query ? `${baseUrl}?${query}` : baseUrl;
    }

    function initSearch() {
        const wrapper = document.querySelector('[data-search-wrapper]');
        const selector = document.querySelector('[data-type-selector]');
        if (!wrapper || !selector) return;

        const baseUrl = wrapper.dataset.searchBase || '/anuncios/listar.php';
        const input = wrapper.querySelector('[data-city-input]');
        const button = wrapper.querySelector('[data-search-button]');

        if (!input || !button) return;

        const runSearch = () => {
            const selectedType = selector.dataset.selected || '';
            const cityValue = input.value.trim();
            window.location.href = buildSearchParams(baseUrl, selectedType, cityValue);
        };

        button.addEventListener('click', runSearch);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                runSearch();
            }
        });
    }

    function initAutocomplete() {
        const wrapper = document.querySelector('[data-search-wrapper]');
        if (!wrapper) return;
        const input = wrapper.querySelector('[data-city-input]');
        const list = wrapper.querySelector('[data-suggestions]');
        if (!input || !list) return;

        // Se cidadesProximas não foi definido, tenta carregar agora
        if (typeof cidadesProximas === 'undefined' || !Array.isArray(cidadesProximas)) {
            if (typeof window.preloadCidades === 'function') {
                window.preloadCidades().then((cities) => {
                    window.cidadesProximas = cities;
                    // Re-inicializa autocomplete com as cidades carregadas
                    initAutocomplete();
                }).catch((err) => {
                    console.error('[HOMEPAGE] Erro ao carregar cidades:', err);
                });
                return;
            }
        }

        const availableCities = typeof cidadesProximas !== 'undefined' && Array.isArray(cidadesProximas)
            ? cidadesProximas
            : [];

        const maxSuggestions = 6;

        const hideSuggestions = () => {
            list.classList.add('hidden');
            list.innerHTML = '';
        };

        const renderSuggestions = (items) => {
            if (!items.length) {
                hideSuggestions();
                return;
            }
            list.innerHTML = items
                .map((city) => `<div data-value="${city}">${city}</div>`)
                .join('');
            list.classList.remove('hidden');
        };

        input.addEventListener('input', () => {
            const value = input.value.trim().toLowerCase();
            if (!value) {
                hideSuggestions();
                return;
            }
            const filtered = availableCities
                .filter((city) => city.toLowerCase().startsWith(value))
                .slice(0, maxSuggestions);
            renderSuggestions(filtered);
        });

        list.addEventListener('mousedown', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            const value = target.dataset.value;
            if (!value) return;
            input.value = value;
            hideSuggestions();
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                hideSuggestions();
            }
        });
    }

    function initFeaturedCarousels(root = document) {
        const cards = root.querySelectorAll('[data-images]');
        cards.forEach((card) => {
            let images = [];
            try {
                const raw = card.getAttribute('data-images') || '[]';
                images = JSON.parse(raw);
            } catch (error) {
                images = [];
            }

            if (!Array.isArray(images) || images.length <= 1) {
                if (carouselTimers.has(card)) {
                    clearInterval(carouselTimers.get(card));
                    carouselTimers.delete(card);
                }
                return;
            }

            const imgElement = card.querySelector('[data-primary-image]');
            if (!imgElement) return;

            if (carouselTimers.has(card)) {
                clearInterval(carouselTimers.get(card));
            }

            let current = 0;
            const intervalId = window.setInterval(() => {
                current = (current + 1) % images.length;
                imgElement.src = images[current];
            }, CAROUSEL_INTERVAL);

            carouselTimers.set(card, intervalId);
        });
    }

    function escapeHtmlAttribute(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function formatMileage(value) {
        const numeric = Number(value);
        if (!Number.isFinite(numeric) || numeric <= 0) {
            return '0';
        }
        return numeric.toLocaleString('pt-BR');
    }

    function formatColor(value) {
        if (!value) return '';
        const lower = value.toString().toLowerCase();
        return lower.charAt(0).toUpperCase() + lower.slice(1);
    }

    function renderFeaturedEmpty(container) {
        container.innerHTML = `
            <div class="col-span-full">
                <div class="bg-white border border-dashed border-green-300 rounded-2xl p-10 text-center text-green-800">
                    <p class="font-semibold">Nenhum anúncio premium disponível no momento.</p>
                    <p class="text-sm text-green-700 mt-2">Torne-se um anunciante verificado para aparecer aqui.</p>
                </div>
            </div>
        `;
    }

    function renderFeaturedSkeleton(container) {
        container.innerHTML = `
            <div class="col-span-full flex justify-center">
                <div class="loading-spinner"></div>
            </div>
        `;
    }

    function renderFeaturedAds(ads) {
        const container = document.getElementById('featuredAdsContainer');
        if (!container) return;

        if (!Array.isArray(ads) || ads.length === 0) {
            renderFeaturedEmpty(container);
            return;
        }

        const verifiedIcon = container.dataset.verifiedIcon || '';
        const placeholder = container.dataset.placeholderImage || '';

        const cardsHtml = ads.map((ad, index) => {
            const rawImages = Array.isArray(ad.images) && ad.images.length ? ad.images : [placeholder];
            const imagesJson = escapeHtmlAttribute(JSON.stringify(rawImages));
            const mileage = formatMileage(ad.mileage);
            const color = formatColor(ad.color);
            const modelYear = ad.model_year ?? '';
            const transmission = ad.transmission ?? '';
            const fuelType = ad.fuel_type ?? '';
            const isVerified = Boolean(ad.is_verified);
            const priceLabel = ad.price_formatted || '';
            const title = ad.title || '';
            const city = ad.city || '';
            const state = ad.state || '';
            const url = ad.url || '#';
            const firstImage = escapeHtmlAttribute(rawImages[0] || placeholder);
            const storeId = Number(ad.store_id || ad.storeId || 0);
            const storeName = ad.owner_name || ad.store_name || '';
            const trackPosition = index + 1;

            return `
                <article class="vehicle-card bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col relative" data-ad-id="${escapeHtmlAttribute(ad.id)}" data-images="${imagesJson}"${storeId > 0 ? ` data-store-owner="${escapeHtmlAttribute(storeId)}"` : ''}${storeName ? ` data-store-owner-name="${escapeHtmlAttribute(storeName)}"` : ''} data-track-origin="homepage_featured" data-track-position="${trackPosition}">
                    <div class="relative">
                        <img src="${firstImage}" alt="${escapeHtmlAttribute(title)}" class="w-full h-56 object-cover" data-primary-image>
                        <span class="price-badge">${escapeHtmlAttribute(priceLabel)}</span>
                        ${isVerified && verifiedIcon ? `
                            <span class="verified-badge">
                                <img src="${escapeHtmlAttribute(verifiedIcon)}" alt="Anunciante verificado" class="w-4 h-4">
                                Verificado
                            </span>
                        ` : ''}
                    </div>
                    <div class="p-5 flex flex-col flex-1">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">${escapeHtmlAttribute(title)}</h3>
                        <div class="text-sm text-gray-600 space-y-1 mb-4">
                            <p class="flex items-center gap-2">
                                <i class="fa-regular fa-calendar text-amber-500"></i>
                                <span>${escapeHtmlAttribute(String(modelYear))} | ${escapeHtmlAttribute(color)} | ${mileage} km</span>
                            </p>
                            <p class="flex items-center gap-2">
                                <i class="fa-solid fa-gear text-amber-500"></i>
                                <span>${escapeHtmlAttribute(transmission)} | ${escapeHtmlAttribute(fuelType)}</span>
                            </p>
                        </div>
                        <p class="text-sm text-gray-600 flex items-center mb-5">
                            <i class="fa-solid fa-location-dot text-amber-500 mr-2"></i>
                            ${escapeHtmlAttribute(city)}, ${escapeHtmlAttribute(state)}
                        </p>
                        <a href="${escapeHtmlAttribute(url)}" class="inline-flex items-center justify-center w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg transition-colors" data-track-ad-click>
                            <i class="fa-regular fa-eye mr-2"></i> Ver detalhes
                        </a>
                    </div>
                </article>
            `;
        }).join('');

        container.innerHTML = cardsHtml;
        initFeaturedCarousels(container);
    }

    function handleFeaturedError(container) {
        container.innerHTML = `
            <div class="col-span-full">
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-6 text-center">
                    Não foi possível atualizar os anúncios em destaque agora.
                </div>
            </div>
        `;
    }

    function initFeaturedRefresh() {
        const container = document.getElementById('featuredAdsContainer');
        if (!container) return;

        const fetchAds = async () => {
            renderFeaturedSkeleton(container);
            try {
                const response = await fetch('/api/featured_ads.php', {
                    headers: {
                        'Accept': 'application/json',
                    },
                    cache: 'no-store',
                });
                if (!response.ok) {
                    throw new Error(`Status ${response.status}`);
                }
                const payload = await response.json();
                renderFeaturedAds(Array.isArray(payload?.data) ? payload.data : []);
            } catch (error) {
                console.error('Erro ao carregar anúncios em destaque', error);
                handleFeaturedError(container);
            }
        };

        window.setInterval(fetchAds, FEATURED_REFRESH_INTERVAL);
    }

    function init() {
        initTypeSelector();
        initSearch();
        initAutocomplete();
        initFeaturedCarousels();
        initFeaturedRefresh();
    }

    document.addEventListener('DOMContentLoaded', init);
})();
