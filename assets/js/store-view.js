// assets/js/store-view.js - Interações da loja de vendedores premium.

const storeMetrics = (typeof window !== 'undefined' && window.__storeMetrics)
    ? window.__storeMetrics
    : {
        track: () => false,
        isEnabled: false,
        storeId: null,
    };

const trackStoreEvent = (eventType, options = {}) => {
    if (!storeMetrics || !storeMetrics.isEnabled) {
        return false;
    }
    return storeMetrics.track(eventType, options);
};

(function () {
    const searchInput = document.querySelector('[data-store-search]');
    const suggestionBox = document.querySelector('[data-store-suggestions]');
    const cards = Array.from(document.querySelectorAll('[data-store-card]'));
    const grid = document.querySelector('[data-store-grid]');
    const emptyState = document.querySelector('[data-store-empty]');
    const countOutput = document.querySelector('[data-store-count]');
    const resetButton = document.querySelector('[data-store-reset]');
    const sortSelect = document.querySelector('[data-store-sort]');
    const toggleButtons = Array.from(document.querySelectorAll('[data-store-toggle]'));

    if (!grid || cards.length === 0) {
        if (emptyState) {
            emptyState.classList.toggle('hidden', cards.length > 0);
        }
        return;
    }

    const normalize = (value) => {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    };

    const suggestionsSource = (() => {
        const element = document.getElementById('store-suggestions');
        if (!element) {
            return [];
        }
        try {
            const parsed = JSON.parse(element.textContent || '[]');
            if (Array.isArray(parsed)) {
                return parsed.filter((item) => typeof item === 'string' && item.trim() !== '');
            }
        } catch (_error) {
            // ignora falhas de parse
        }
        return [];
    })();

    const fallbackSuggestions = Array.from(new Set(cards.flatMap((card) => {
        const { brand = '', model = '' } = card.dataset;
        const items = [];
        if (brand) items.push(brand);
        if (model) items.push(model);
        if (brand && model) items.push(`${brand} ${model}`);
        return items;
    })));

    const suggestionPool = suggestionsSource.length > 0 ? suggestionsSource : fallbackSuggestions;
    const maxSuggestions = 8;

    const defaultState = {
        search: searchInput ? (searchInput.value || '') : '',
        sort: sortSelect ? (sortSelect.value || 'recent') : 'recent',
        toggles: {
            new: false,
            premium: false,
        },
    };

    const state = {
        search: defaultState.search,
        sort: defaultState.sort,
        toggles: { ...defaultState.toggles },
    };

    const cardData = new Map();

    cards.forEach((card) => {
        const { brand = '', model = '', title = '' } = card.dataset;
        const keywords = [brand, model, title].filter(Boolean).join(' ');
        card.dataset.keywords = normalize(keywords);

        const getNumber = (value) => {
            const parsed = Number(value);
            return Number.isFinite(parsed) ? parsed : NaN;
        };

        cardData.set(card, {
            id: getNumber(card.dataset.adId),
            keywords: card.dataset.keywords || '',
            price: getNumber(card.dataset.price),
            mileage: getNumber(card.dataset.mileage),
            updated: getNumber(card.dataset.updated),
            year: getNumber(card.dataset.year),
            isNew: card.dataset.isNew === 'true',
            isVerified: card.dataset.isVerified === 'true',
        });
    });

    const sortAscBy = (key) => {
        return (cardA, cardB) => {
            const dataA = cardData.get(cardA);
            const dataB = cardData.get(cardB);
            const valueA = dataA ? Number(dataA[key]) : NaN;
            const valueB = dataB ? Number(dataB[key]) : NaN;
            const safeA = Number.isFinite(valueA) ? valueA : Number.POSITIVE_INFINITY;
            const safeB = Number.isFinite(valueB) ? valueB : Number.POSITIVE_INFINITY;
            return safeA - safeB;
        };
    };

    const sortDescBy = (key) => {
        return (cardA, cardB) => {
            const dataA = cardData.get(cardA);
            const dataB = cardData.get(cardB);
            const valueA = dataA ? Number(dataA[key]) : NaN;
            const valueB = dataB ? Number(dataB[key]) : NaN;
            const safeA = Number.isFinite(valueA) ? valueA : Number.NEGATIVE_INFINITY;
            const safeB = Number.isFinite(valueB) ? valueB : Number.NEGATIVE_INFINITY;
            return safeB - safeA;
        };
    };

    const sortComparators = {
        recent: (cardA, cardB) => {
            const dataA = cardData.get(cardA);
            const dataB = cardData.get(cardB);
            const updatedA = dataA && Number.isFinite(dataA.updated) ? dataA.updated : 0;
            const updatedB = dataB && Number.isFinite(dataB.updated) ? dataB.updated : 0;
            if (updatedB !== updatedA) {
                return updatedB - updatedA;
            }
            const idA = dataA && Number.isFinite(dataA.id) ? dataA.id : 0;
            const idB = dataB && Number.isFinite(dataB.id) ? dataB.id : 0;
            return idB - idA;
        },
        price_asc: sortAscBy('price'),
        price_desc: sortDescBy('price'),
        mileage_asc: sortAscBy('mileage'),
        mileage_desc: sortDescBy('mileage'),
        year_desc: sortDescBy('year'),
        year_asc: sortAscBy('year'),
    };

    const syncToggleButtons = () => {
        toggleButtons.forEach((button) => {
            const key = button.dataset.storeToggle;
            if (!key) return;
            const isActive = Boolean(state.toggles[key]);
            button.classList.toggle('is-active', isActive);
        });
    };

    function updateCount(visible) {
        if (countOutput) {
            countOutput.textContent = String(visible);
        }
    }

    function updateResetVisibility(normalizedTerm) {
        if (!resetButton) return;
        const hasSearch = normalizedTerm !== '';
        const hasToggles = Object.values(state.toggles).some(Boolean);
        const hasCustomSort = state.sort !== defaultState.sort;
        const shouldShow = hasSearch || hasToggles || hasCustomSort;
        resetButton.classList.toggle('hidden', !shouldShow);
    }

    function refreshListing() {
        const normalizedTerm = normalize(state.search);
        let visibleCount = 0;
        const visibleCards = [];

        cards.forEach((card) => {
            const data = cardData.get(card);
            const matchesSearch = normalizedTerm === '' || (data && data.keywords.includes(normalizedTerm));
            const matchesNew = !state.toggles.new || (data ? data.isNew : false);
            const matchesVerified = !state.toggles.premium || (data ? data.isVerified : false);

            const shouldShow = Boolean(matchesSearch && matchesNew && matchesVerified);
            card.classList.toggle('store-card--hidden', !shouldShow);

            if (shouldShow) {
                visibleCount += 1;
                visibleCards.push(card);
            }
        });

        const comparator = sortComparators[state.sort] || sortComparators.recent;
        visibleCards.sort(comparator);
        visibleCards.forEach((card) => {
            grid.appendChild(card);
        });

        updateCount(visibleCount);
        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }

        updateResetVisibility(normalizedTerm);
    }

    function buildSuggestions(term) {
        if (!suggestionBox) return;
        suggestionBox.innerHTML = '';

        if (suggestionPool.length === 0) {
            suggestionBox.classList.add('hidden');
            return;
        }

        const normalizedTerm = normalize(term);
        if (normalizedTerm === '') {
            suggestionBox.classList.add('hidden');
            return;
        }
        const items = suggestionPool
            .filter((item) => normalizedTerm === '' || normalize(item).includes(normalizedTerm))
            .slice(0, maxSuggestions);

        if (items.length === 0) {
            suggestionBox.classList.add('hidden');
            return;
        }

        items.forEach((item) => {
            const option = document.createElement('div');
            option.className = 'autocomplete-option';
            option.dataset.value = item;
            option.textContent = item;
            suggestionBox.appendChild(option);
        });

        suggestionBox.classList.remove('hidden');
    }

    function hideSuggestions() {
        if (suggestionBox) {
            suggestionBox.classList.add('hidden');
        }
    }

    function setSearchTerm(value) {
        state.search = value || '';
        refreshListing();
    }

    function setSort(value) {
        state.sort = value || defaultState.sort;
        refreshListing();
    }

    function toggleFilter(key) {
        if (!(key in state.toggles)) {
            state.toggles[key] = false;
        }
        state.toggles[key] = !state.toggles[key];
        syncToggleButtons();
        refreshListing();

        trackStoreEvent('filter_interaction', {
            metadata: {
                filter: key,
                active: state.toggles[key],
                origin: 'store_toolbar',
            },
        });
    }

    function resetFilters() {
        state.search = '';
        state.sort = defaultState.sort;
        Object.keys(state.toggles).forEach((key) => {
            state.toggles[key] = false;
        });

        if (searchInput) {
            searchInput.value = '';
        }
        if (sortSelect) {
            sortSelect.value = state.sort;
        }

        syncToggleButtons();
        refreshListing();
        hideSuggestions();
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const value = searchInput.value || '';
            setSearchTerm(value);
            buildSuggestions(value);
        });

        searchInput.addEventListener('focus', () => {
            const value = searchInput.value || '';
            if (value.trim() === '') {
                hideSuggestions();
            } else {
                buildSuggestions(value);
            }
        });
    }

    if (suggestionBox) {
        suggestionBox.addEventListener('mousedown', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            const value = target.dataset.value;
            if (!value) return;
            if (searchInput) {
                searchInput.value = value;
            }
            setSearchTerm(value);
            hideSuggestions();
        });
    }

    document.addEventListener('click', (event) => {
        if (searchInput && event.target === searchInput) {
            return;
        }
        if (suggestionBox && suggestionBox.contains(event.target)) {
            return;
        }
        hideSuggestions();
    });

    if (toggleButtons.length > 0) {
        toggleButtons.forEach((button) => {
            const key = button.dataset.storeToggle;
            if (!key) {
                return;
            }
            if (!(key in state.toggles)) {
                state.toggles[key] = false;
            }
            button.addEventListener('click', () => {
                toggleFilter(key);
            });
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            setSort(sortSelect.value || defaultState.sort);
            trackStoreEvent('sort_interaction', {
                metadata: {
                    value: state.sort,
                    origin: 'store_toolbar',
                },
            });
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            resetFilters();
        });
    }

    if (grid) {
        grid.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }
            const link = target.closest('[data-store-ad-link]');
            if (!link) {
                return;
            }
            const card = link.closest('[data-store-card]');
            const adId = card ? card.getAttribute('data-ad-id') : null;
            trackStoreEvent('ad_click', {
                adId,
                metadata: {
                    origin: 'store_grid',
                },
            });
        });
    }

    syncToggleButtons();
    refreshListing();
    hideSuggestions();
})();

// Favorite toggle for store listings
(function () {
    const FAVORITES_KEY = 'clautos:favorites';

    function readFavorites() {
        try {
            const stored = localStorage.getItem(FAVORITES_KEY);
            if (!stored) {
                return [];
            }
            const parsed = JSON.parse(stored);
            return Array.isArray(parsed) ? parsed : [];
        } catch (_error) {
            return [];
        }
    }

    function writeFavorites(list) {
        try {
            localStorage.setItem(FAVORITES_KEY, JSON.stringify(list));
        } catch (_error) {
            // Falha silenciosa em ambientes com bloqueio de storage
        }
    }

    function parsePayload(button) {
        if (button.__favoritePayload) {
            return button.__favoritePayload;
        }
        const raw = button.getAttribute('data-favorite-payload');
        if (!raw) {
            return null;
        }
        try {
            const payload = JSON.parse(raw);
            button.__favoritePayload = payload;
            return payload;
        } catch (_error) {
            return null;
        }
    }

    function updateFavoriteVisual(button, isActive) {
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        button.classList.toggle('is-active', isActive);
        const icon = button.querySelector('i');
        if (icon) {
            if (isActive) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
            }
        }
    }

    function initializeFavoriteButtons(root, favoritesList) {
        const scope = root || document;
        const favorites = Array.isArray(favoritesList) ? favoritesList : readFavorites();
        const ids = new Set(favorites.map((item) => Number(item && item.id)));

        scope.querySelectorAll('[data-favorite-toggle]').forEach((button) => {
            const payload = parsePayload(button);
            if (!payload || typeof payload.id === 'undefined') {
                return;
            }

            const adId = Number(payload.id);
            if (!Number.isFinite(adId)) {
                return;
            }

            button.dataset.favoriteId = button.dataset.favoriteId || String(adId);
            button.__favoritePayload = payload;
            updateFavoriteVisual(button, ids.has(adId));

            if (button.dataset.favoriteBound === 'true') {
                return;
            }

            button.dataset.favoriteBound = 'true';
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const currentPayload = parsePayload(button);
                if (!currentPayload || typeof currentPayload.id === 'undefined') {
                    return;
                }

                const targetId = Number(currentPayload.id);
                const list = readFavorites();
                const index = list.findIndex((item) => Number(item && item.id) === targetId);

                let metricEvent = null;
                if (index !== -1) {
                    list.splice(index, 1);
                    metricEvent = 'favorite_removed';
                } else {
                    list.push(currentPayload);
                    metricEvent = 'favorite_added';
                }

                writeFavorites(list);
                initializeFavoriteButtons(document, list);

                if (metricEvent) {
                    trackStoreEvent(metricEvent, {
                        adId: targetId,
                        metadata: {
                            origin: 'store_listing',
                        },
                    });
                }
            });
        });
    }

    if (typeof window !== 'undefined') {
        window.addEventListener('storage', (event) => {
            if (event.key === FAVORITES_KEY) {
                initializeFavoriteButtons();
            }
        });

        document.addEventListener('favorites:refresh', () => {
            initializeFavoriteButtons();
        });
    }

    initializeFavoriteButtons();
})();

(function () {
    if (!storeMetrics || !storeMetrics.isEnabled) {
        return;
    }

    const contactMap = {
        whatsapp: 'whatsapp_click',
        phone: 'phone_click',
    };

    document.querySelectorAll('[data-store-contact]').forEach((element) => {
        element.addEventListener('click', () => {
            const key = element.dataset.storeContact;
            const eventType = contactMap[key];
            if (eventType) {
                trackStoreEvent(eventType, {
                    metadata: {
                        origin: 'store_hero',
                    },
                });
            }
        });
    });

    const routeMap = {
        google: 'route_google',
        waze: 'route_waze',
    };

    document.querySelectorAll('[data-store-route]').forEach((element) => {
        element.addEventListener('click', () => {
            const key = element.dataset.storeRoute;
            const eventType = routeMap[key];
            if (eventType) {
                trackStoreEvent(eventType, {
                    metadata: {
                        origin: 'store_hero',
                    },
                });
            }
        });
    });

    // Compartilhamento de loja
    const shareBtn = document.getElementById('storeShareBtn');
    if (shareBtn) {
        const storeUrl = shareBtn.dataset.storeShare || window.location.href;
        const storeName = shareBtn.dataset.storeName || 'Loja Premium';

        shareBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            trackStoreEvent('share_store', {
                metadata: {
                    origin: 'store_hero',
                },
            });

            // Web Share API - Suportado por navegadores modernos
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: storeName,
                        text: `Confira a loja de ${storeName} no Classi Livre Autos!`,
                        url: storeUrl,
                    });
                    return;
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Erro ao compartilhar:', err);
                    }
                }
            }

            // Fallback: Modal de compartilhamento
            openShareModal(storeUrl, storeName);
        });

        function openShareModal(url, name) {
            // Remover modal anterior se existir
            const existing = document.getElementById('share-modal');
            if (existing) {
                existing.remove();
            }

            const modal = document.createElement('div');
            modal.id = 'share-modal';
            modal.className = 'share-modal';
            modal.innerHTML = `
                <div class="share-modal__overlay" id="shareOverlay"></div>
                <div class="share-modal__content">
                    <div class="share-modal__header">
                        <h3 class="share-modal__title">Compartilhar Loja</h3>
                        <button type="button" class="share-modal__close" id="shareClose">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="share-modal__body">
                        <div class="share-modal__options">
                            <a href="https://wa.me/?text=${encodeURIComponent(`Confira a loja de ${name}! ${url}`)}" 
                               target="_blank" rel="noopener" class="share-option share-option--whatsapp">
                                <i class="fab fa-whatsapp"></i>
                                <span>WhatsApp</span>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}" 
                               target="_blank" rel="noopener" class="share-option share-option--facebook">
                                <i class="fab fa-facebook"></i>
                                <span>Facebook</span>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(`Confira a loja de ${name}!`)}" 
                               target="_blank" rel="noopener" class="share-option share-option--twitter">
                                <i class="fab fa-twitter"></i>
                                <span>Twitter</span>
                            </a>
                            <button type="button" class="share-option share-option--copy" id="shareCopyBtn">
                                <i class="fas fa-link"></i>
                                <span id="copyText">Copiar Link</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Fechar modal
            const closeBtn = modal.querySelector('#shareClose');
            const overlay = modal.querySelector('#shareOverlay');
            
            [closeBtn, overlay].forEach(el => {
                el?.addEventListener('click', () => modal.remove());
            });

            // Fechar com ESC
            const handleEsc = (e) => {
                if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', handleEsc);
                }
            };
            document.addEventListener('keydown', handleEsc);

            // Copiar link
            const copyBtn = modal.querySelector('#shareCopyBtn');
            const copyText = modal.querySelector('#copyText');
            if (copyBtn && copyText) {
                copyBtn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(url);
                        copyText.textContent = 'Copiado!';
                        setTimeout(() => {
                            copyText.textContent = 'Copiar Link';
                        }, 2000);
                    } catch (err) {
                        console.error('Erro ao copiar:', err);
                        copyText.textContent = 'Erro ao copiar';
                    }
                });
            }
        }
    }
})();
