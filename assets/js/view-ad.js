// assets/js/view-ad.js - Interações da página de visualização de anúncio.

(function () {
    const state = window.AD_VIEW;
    if (!state) {
        return;
    }

    const config = window.AD_VIEW_CONFIG || {};
    const storeMetrics = (typeof window !== 'undefined') ? window.__storeMetrics : null;

    const trackAdEvent = (eventType, { adId = null, metadata = null } = {}) => {
        if (!storeMetrics || !storeMetrics.isEnabled) {
            return false;
        }

        const payload = {};
        const resolvedAdId = Number.isFinite(Number(adId)) ? Number(adId) : Number(state.id);
        if (Number.isFinite(resolvedAdId) && resolvedAdId > 0) {
            payload.adId = resolvedAdId;
        }

        const baseMetadata = { origin: 'ad_detail' };
        if (metadata && typeof metadata === 'object') {
            payload.metadata = { ...baseMetadata, ...metadata };
        } else {
            payload.metadata = baseMetadata;
        }

        return storeMetrics.track(eventType, payload);
    };
    const csrfToken = window.AD_VIEW_CSRF || '';
    const formatNumber = (value) => Number.isFinite(value) ? value.toLocaleString('pt-BR') : '0';
    const formatCurrency = (value) => {
        if (typeof value === 'string') return value;
        if (!Number.isFinite(value)) return 'R$ 0,00';
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    const body = document.body;
    const mediaGallery = document.querySelector('[data-gallery]');
    const galleryMainImage = document.querySelector('[data-gallery-main]');
    const galleryThumbs = Array.from(document.querySelectorAll('[data-gallery-thumb]'));
    const galleryPrevBtn = document.querySelector('[data-gallery-prev]');
    const galleryNextBtn = document.querySelector('[data-gallery-next]');
    const galleryOpenBtn = document.querySelector('[data-gallery-open]');
    const lightboxOverlay = document.querySelector('[data-modal="lightbox"]');
    const lightboxMainImage = lightboxOverlay?.querySelector('[data-lightbox-main]');
    const lightboxThumbs = lightboxOverlay ? Array.from(lightboxOverlay.querySelectorAll('[data-lightbox-thumb]')) : [];
    const lightboxPrevBtn = lightboxOverlay?.querySelector('[data-lightbox-prev]');
    const lightboxNextBtn = lightboxOverlay?.querySelector('[data-lightbox-next]');
    const compareOverlay = document.querySelector('[data-modal="compare"]');
    const shareOverlay = document.querySelector('[data-modal="share"]');
    const reportOverlay = document.querySelector('[data-modal="report"]');
    const compareContent = compareOverlay?.querySelector('[data-compare-content]');
    const reportForm = reportOverlay?.querySelector('[data-report-form]');
    const reportFeedback = reportOverlay?.querySelector('[data-report-feedback]');
    const shareOptions = Array.from(shareOverlay?.querySelectorAll('[data-share-target]') ?? []);
    const shareCopyLinkBtn = shareOverlay?.querySelector('[data-share-copy-link]');
    const shareCopyMessageBtn = shareOverlay?.querySelector('[data-share-copy-message]');
    const shareMessageElement = shareOverlay?.querySelector('[data-share-message]');
    const shareFeedback = shareOverlay?.querySelector('[data-share-feedback]');
    const shareEventMap = {
        whatsapp: 'share_whatsapp',
        instagram: 'share_instagram',
        facebook: 'share_facebook',
        email: 'share_email',
    };
    const CARD_CAROUSEL_INTERVAL = 5000;
    const cardCarouselTimers = new WeakMap();
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    let currentImageIndex = 0;
    let activeModal = null;

    const MODAL_SELECTORS = {
        lightbox: lightboxOverlay,
        compare: compareOverlay,
        share: shareOverlay,
        report: reportOverlay,
    };

    function openModal(name) {
        const modal = MODAL_SELECTORS[name];
        if (!modal) return;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        activeModal = name;
        body.classList.add('modal-open');
        const focusTarget = modal.querySelector('.modal__close, [data-modal-close], button, a');
        if (focusTarget instanceof HTMLElement) {
            focusTarget.focus();
        }
        document.addEventListener('keydown', handleModalKeydown);
        
        // Disparar evento customizado quando o modal abrir
        const event = new CustomEvent('data-modal-open', { detail: { modal: name } });
        document.dispatchEvent(event);
    }

    function closeModal(name) {
        const modal = MODAL_SELECTORS[name];
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        activeModal = null;
        body.classList.remove('modal-open');
        document.removeEventListener('keydown', handleModalKeydown);
    }

    function handleModalKeydown(event) {
        if (!activeModal) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal(activeModal);
        }
        if (activeModal === 'lightbox') {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                showPreviousImage();
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                showNextImage();
            }
        }
    }

    document.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('[data-modal]');
            if (!modal) return;
            const name = Object.keys(MODAL_SELECTORS).find((key) => MODAL_SELECTORS[key] === modal);
            if (name) closeModal(name);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                const name = Object.keys(MODAL_SELECTORS).find((key) => MODAL_SELECTORS[key] === overlay);
                if (name) closeModal(name);
            }
        });
    });

    function setGalleryImage(index) {
        if (!Array.isArray(state.images) || state.images.length === 0) {
            return;
        }

        const total = state.images.length;
        const normalizedIndex = ((index % total) + total) % total;
        const picture = state.images[normalizedIndex];
        if (!picture) return;

        currentImageIndex = normalizedIndex;

        if (galleryMainImage) {
            galleryMainImage.src = picture.src;
            galleryMainImage.alt = picture.alt;
        }

        if (lightboxMainImage) {
            lightboxMainImage.src = picture.src;
            lightboxMainImage.alt = picture.alt;
        }

        const updateThumbState = (thumbs, key) => {
            thumbs.forEach((thumb) => {
                const value = Number(thumb.dataset[key]);
                const isActive = value === currentImageIndex;
                thumb.classList.toggle('is-active', isActive);
            });
        };

        updateThumbState(galleryThumbs, 'galleryThumb');
        updateThumbState(lightboxThumbs, 'lightboxThumb');
    }

    function showNextImage() {
        if (!Array.isArray(state.images) || state.images.length === 0) return;
        setGalleryImage(currentImageIndex + 1);
    }

    function showPreviousImage() {
        if (!Array.isArray(state.images) || state.images.length === 0) return;
        setGalleryImage(currentImageIndex - 1);
    }

    const bindThumbNavigation = (thumbs, key) => {
        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const value = Number(thumb.dataset[key]);
                if (!Number.isFinite(value)) return;
                setGalleryImage(value);
                if (key === 'lightboxThumb') {
                    thumb.blur();
                }
            });
        });
    };

    bindThumbNavigation(galleryThumbs, 'galleryThumb');
    bindThumbNavigation(lightboxThumbs, 'lightboxThumb');

    galleryPrevBtn?.addEventListener('click', () => {
        showPreviousImage();
    });

    galleryNextBtn?.addEventListener('click', () => {
        showNextImage();
    });

    lightboxPrevBtn?.addEventListener('click', () => {
        showPreviousImage();
    });

    lightboxNextBtn?.addEventListener('click', () => {
        showNextImage();
    });

    const openLightbox = () => {
        if (!lightboxOverlay) return;
        setGalleryImage(currentImageIndex);
        openModal('lightbox');
    };

    galleryOpenBtn?.addEventListener('click', openLightbox);
    galleryMainImage?.addEventListener('click', openLightbox);

    if (mediaGallery) {
        mediaGallery.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                showPreviousImage();
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                showNextImage();
            }
        });
    }

    function initSuggestionCarousels(root = document) {
        if (!root) return;
        const cards = root.querySelectorAll('[data-images]');
        cards.forEach((card) => {
            let images = [];
            try {
                const raw = card.getAttribute('data-images') || '[]';
                images = JSON.parse(raw);
            } catch (_error) {
                images = [];
            }

            if (!Array.isArray(images) || images.length <= 1) {
                if (cardCarouselTimers.has(card)) {
                    window.clearInterval(cardCarouselTimers.get(card));
                    cardCarouselTimers.delete(card);
                }
                return;
            }

            const imgElement = card.querySelector('[data-primary-image]');
            if (!imgElement) return;

            if (cardCarouselTimers.has(card)) {
                window.clearInterval(cardCarouselTimers.get(card));
            }

            let current = 0;
            const intervalId = window.setInterval(() => {
                current = (current + 1) % images.length;
                imgElement.src = images[current];
            }, CARD_CAROUSEL_INTERVAL);

            cardCarouselTimers.set(card, intervalId);
        });
    }

    const compareBtn = document.querySelector('[data-action="compare"]');
    const favoriteCurrentBtn = document.querySelector('[data-favorite-current]');
    const favoriteCardButtons = Array.from(document.querySelectorAll('[data-favorite-toggle]'));
    const FAVORITES_KEY = 'clautos:favorites';
    const MAX_FAVORITES = 10;

    function normalizeFavoritePayload(raw) {
        if (!raw) return null;
        const id = Number(raw.id);
        if (!Number.isFinite(id)) return null;
        const priceNumber = Number(raw.price);
        const mileageNumber = Number(raw.mileage);
        const manufactureYear = raw.manufacture_year !== undefined ? Number(raw.manufacture_year) : null;
        const modelYear = raw.model_year !== undefined ? Number(raw.model_year) : null;
        
        // Converter is_verified corretamente (pode ser boolean, string "true"/"1", ou number)
        let isVerified = false;
        if (raw.is_verified !== undefined && raw.is_verified !== null) {
            if (typeof raw.is_verified === 'boolean') {
                isVerified = raw.is_verified;
            } else if (typeof raw.is_verified === 'string') {
                isVerified = raw.is_verified === 'true' || raw.is_verified === '1';
            } else if (typeof raw.is_verified === 'number') {
                isVerified = raw.is_verified === 1;
            }
        } else if (raw.isVerified !== undefined && raw.isVerified !== null) {
            if (typeof raw.isVerified === 'boolean') {
                isVerified = raw.isVerified;
            } else if (typeof raw.isVerified === 'string') {
                isVerified = raw.isVerified === 'true' || raw.isVerified === '1';
            } else if (typeof raw.isVerified === 'number') {
                isVerified = raw.isVerified === 1;
            }
        }
        
        // Converter is_premium corretamente (pode ser boolean, string "true"/"1", ou number)
        let isPremium = false;
        if (raw.is_premium !== undefined && raw.is_premium !== null) {
            if (typeof raw.is_premium === 'boolean') {
                isPremium = raw.is_premium;
            } else if (typeof raw.is_premium === 'string') {
                isPremium = raw.is_premium === 'true' || raw.is_premium === '1';
            } else if (typeof raw.is_premium === 'number') {
                isPremium = raw.is_premium === 1;
            }
        } else if (raw.isPremium !== undefined && raw.isPremium !== null) {
            if (typeof raw.isPremium === 'boolean') {
                isPremium = raw.isPremium;
            } else if (typeof raw.isPremium === 'string') {
                isPremium = raw.isPremium === 'true' || raw.isPremium === '1';
            } else if (typeof raw.isPremium === 'number') {
                isPremium = raw.isPremium === 1;
            }
        }
        
        const storeIdCandidates = [
            raw.store_id,
            raw.storeId,
            raw.store,
            raw.storeOwner,
            raw.storeOwnerId,
            raw.owner_id,
            raw.ownerId,
            raw.user_id,
            raw.userId,
        ];
        const resolvedStoreId = (() => {
            for (const candidate of storeIdCandidates) {
                const numeric = Number(candidate);
                if (Number.isFinite(numeric) && numeric > 0) {
                    return numeric;
                }
            }
            return null;
        })();
        const normalized = {
            id,
            title: raw.title || state.title || '',
            price: Number.isFinite(priceNumber) ? priceNumber : 0,
            price_formatted: raw.price_formatted || formatCurrency(Number.isFinite(priceNumber) ? priceNumber : Number(state.price)),
            manufacture_year: Number.isFinite(manufactureYear) ? manufactureYear : null,
            model_year: Number.isFinite(modelYear) ? modelYear : null,
            mileage: Number.isFinite(mileageNumber) ? mileageNumber : null,
            transmission: raw.transmission || '',
            fuel_type: raw.fuel_type || '',
            image: raw.image || state.images?.[0]?.src || '',
            url: raw.url || state.url || window.location.href,
            city: raw.city || state.city || '',
            state: raw.state || state.state || '',
            is_verified: isVerified,
            is_premium: isPremium,
            store_id: resolvedStoreId,
        };
        console.log('[DEBUG Normalize] Input raw.is_verified:', raw.is_verified, 'Input raw.is_premium:', raw.is_premium, 'Output is_verified:', normalized.is_verified, 'Output is_premium:', normalized.is_premium);
        return normalized;
    }

    function buildCurrentFavoritePayload() {
        return normalizeFavoritePayload({
            id: state.id,
            title: state.title,
            price: state.price,
            price_formatted: state.price_formatted,
            manufacture_year: state.manufacture_year,
            model_year: state.model_year,
            mileage: state.mileage,
            transmission: state.transmission,
            fuel_type: state.fuel_type,
            image: state.images?.[0]?.src || '',
            url: state.url,
            city: state.city,
            state: state.state,
            is_verified: Boolean(
                state.is_verified !== undefined
                    ? state.is_verified
                    : state.seller?.isVerified
            ),
            is_premium: Boolean(
                state.is_premium !== undefined
                    ? state.is_premium
                    : state.seller?.isPremium
            ),
            store_id: Number.isFinite(Number(state.seller?.id)) && Number(state.seller?.id) > 0
                ? Number(state.seller.id)
                : null,
        });
    }

    function loadFavorites() {
        try {
            const data = localStorage.getItem(FAVORITES_KEY);
            if (!data) return [];
            const parsed = JSON.parse(data);
            if (!Array.isArray(parsed)) return [];
            return parsed.map(normalizeFavoritePayload).filter(Boolean).slice(-MAX_FAVORITES);
        } catch (_error) {
            return [];
        }
    }

    function saveFavorites(list) {
        const trimmed = list.slice(-MAX_FAVORITES);
        localStorage.setItem(FAVORITES_KEY, JSON.stringify(trimmed));
    }

    function notifyFavoriteLimit() {
        window.alert('Você atingiu o limite de 10 veículos favoritados. Remova um para adicionar outro.');
    }

    function parseFavoriteButtonPayload(button) {
        if (!button) return null;
        if (button._favoritePayload) {
            return button._favoritePayload;
        }
        const raw = button.dataset.favoritePayload;
        if (!raw) return null;
        try {
            const parsed = JSON.parse(raw);
            const normalized = normalizeFavoritePayload(parsed);
            button._favoritePayload = normalized;
            return normalized;
        } catch (_error) {
            return null;
        }
    }

    function toggleFavoritePayload(payload) {
        const favorites = loadFavorites();
        const index = favorites.findIndex((item) => item.id === payload.id);
        if (index !== -1) {
            const updated = [...favorites.slice(0, index), ...favorites.slice(index + 1)];
            saveFavorites(updated);
            return updated;
        }
        if (favorites.length >= MAX_FAVORITES) {
            notifyFavoriteLimit();
            return favorites;
        }
        const updated = [...favorites, payload];
        saveFavorites(updated);
        return updated;
    }

    function syncFavoriteButtons(favorites) {
        const ids = new Set(favorites.map((item) => item.id));
        if (favoriteCurrentBtn) {
            const isActive = ids.has(Number(state.id));
            favoriteCurrentBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            favoriteCurrentBtn.classList.toggle('is-active', isActive);
            const label = favoriteCurrentBtn.querySelector('span');
            if (label) {
                label.textContent = isActive ? 'Preferido' : 'Favoritar';
            }
        }
        favoriteCardButtons.forEach((button) => {
            const payload = parseFavoriteButtonPayload(button);
            const isActive = payload ? ids.has(payload.id) : false;
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            button.classList.toggle('is-active', isActive);
        });
    }

    async function enrichFavoritesWithSuggestionData(favorites) {
        // Tenta enriquecer os favoritos com dados das sugestões visíveis na página
        const suggestionButtons = Array.from(document.querySelectorAll('[data-favorite-toggle]'));
        const suggestionMap = new Map();
        
        suggestionButtons.forEach((button) => {
            const payload = parseFavoriteButtonPayload(button);
            if (payload) {
                suggestionMap.set(payload.id, payload);
            }
        });
        
        console.log('[DEBUG Enrich] Available suggestion data:', Array.from(suggestionMap.entries()).map(([id, data]) => ({ id, is_premium: data.is_premium, is_verified: data.is_verified })));
        
        // Se houver dados das sugestões, use-os
        if (suggestionMap.size > 0) {
            return favorites.map((favorite) => {
                const suggestionData = suggestionMap.get(favorite.id);
                if (suggestionData && suggestionData.is_premium !== undefined) {
                    console.log('[DEBUG Enrich] Enriching favorite', favorite.id, 'with is_premium:', suggestionData.is_premium, 'is_verified:', suggestionData.is_verified);
                    return {
                        ...favorite,
                        is_premium: suggestionData.is_premium,
                        is_verified: suggestionData.is_verified,
                    };
                }
                console.log('[DEBUG Enrich] No suggestion data for favorite', favorite.id, 'keeping is_premium:', favorite.is_premium, 'is_verified:', favorite.is_verified);
                return favorite;
            });
        }
        
        // Caso não haja sugestões, faça uma consulta AJAX para enriquecer
        const idsToEnrich = favorites.filter(f => !f.is_premium).map(f => f.id);
        if (idsToEnrich.length === 0) {
            return favorites; // Já tem is_premium
        }
        
        console.log('[DEBUG Enrich] Fetching premium data from API for IDs:', idsToEnrich);
        
        try {
            const response = await fetch('/api/enrich_favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ad_ids: idsToEnrich })
            });
            
            if (!response.ok) {
                throw new Error(`API error: ${response.status}`);
            }
            
            const enrichData = await response.json();
            console.log('[DEBUG Enrich] API response:', enrichData);
            
            return favorites.map((favorite) => {
                if (enrichData[favorite.id]) {
                    const apiData = enrichData[favorite.id];
                    console.log('[DEBUG Enrich] Enriching favorite', favorite.id, 'with is_premium:', apiData.is_premium, 'is_verified:', apiData.is_verified);
                    return {
                        ...favorite,
                        is_premium: apiData.is_premium,
                        is_verified: apiData.is_verified,
                    };
                }
                return favorite;
            });
        } catch (error) {
            console.error('[DEBUG Enrich] Error enriching favorites:', error);
            return favorites; // Retorna sem enriquecimento se falhar
        }
    }

    async function renderCompareModal(list) {
        if (!compareContent) return;
        if (!list.length) {
            compareContent.innerHTML = '<div class="compare-empty">Marque an\\u00FAncios como preferidos clicando no cora\\u00E7\\u00E3o para montar sua compara\\u00E7\\u00E3o.</div>';
            return;
        }
        
        // Enriquecer favoritos com dados das sugestões ou API
        const enrichedList = await enrichFavoritesWithSuggestionData(list);
        const limited = enrichedList.slice(0, MAX_FAVORITES);
        const cards = limited.map((item) => {
            const manufactureYear = Number.isFinite(item.manufacture_year) ? item.manufacture_year : null;
            const modelYear = Number.isFinite(item.model_year) ? item.model_year : null;
            const yearText = modelYear && manufactureYear
                ? `${manufactureYear}/${modelYear}`
                : (modelYear ?? manufactureYear ?? '');
            const mileageText = Number.isFinite(item.mileage) ? `${formatNumber(item.mileage)} km` : '';
            const primaryDetails = [yearText, mileageText].filter(Boolean);
            const secondaryDetails = [item.transmission, item.fuel_type].filter(Boolean);
            const location = [item.city, item.state].filter(Boolean).join(' / ');
            const fallbackImage = '/assets/uploads/images/sample-car-1.jpg';
            const primaryImage = (() => {
                if (Array.isArray(item.images) && item.images.length) {
                    return item.images[0];
                }
                if (typeof item.image === 'string' && item.image.trim() !== '') {
                    const sanitized = item.image.trim();
                    if (sanitized.startsWith('http') || sanitized.startsWith('//')) {
                        return sanitized;
                    }
                    return sanitized.startsWith('/') ? sanitized : `/${sanitized.replace(/^\/+/, '')}`;
                }
                return null;
            })();
            const image = primaryImage || fallbackImage;
            const priceText = item.price_formatted || formatCurrency(item.price);
            const verifiedBadge = (item.is_verified)
                ? `<span class="verified-badge compare-card__verified"><img src="/assets/img/verificado.png" alt="Anúncio verificado" class="w-4 h-4" aria-hidden="true"><span>Verificado</span></span>`
                : '';
            console.log('[DEBUG Compare] Ad:', { id: item.id, title: item.title, is_verified: item.is_verified, is_premium: item.is_premium, shouldShowBadge: item.is_verified });
            const primaryHtml = primaryDetails.length
                ? `<p class="compare-card__row"><i class="fa-regular fa-calendar"></i><span>${escapeHtml(primaryDetails.join(' | '))}</span></p>`
                : '';
            const secondaryHtml = secondaryDetails.length
                ? `<p class="compare-card__row"><i class="fa-solid fa-gear"></i><span>${escapeHtml(secondaryDetails.join(' | '))}</span></p>`
                : '';
            const locationHtml = location
                ? `<p class="compare-card__row compare-card__row--location"><i class="fa-solid fa-location-dot"></i><span>${escapeHtml(location)}</span></p>`
                : '';
            return `
                <article class="compare-card" role="listitem">
                    <div class="compare-card__media">
                        <img src="${image}" alt="${escapeHtml(item.title || '')}" class="compare-card__image" data-ad-image>
                        <span class="price-badge compare-card__price">${escapeHtml(priceText)}</span>
                        ${verifiedBadge}
                    </div>
                    <div class="compare-card__body">
                        <h3 class="compare-card__title">${escapeHtml(item.title || '')}</h3>
                        <div class="compare-card__meta">
                            ${primaryHtml}
                            ${secondaryHtml}
                            ${locationHtml}
                        </div>
                        <a class="compare-card__cta" href="${item.url}">
                            <i class="fa-regular fa-eye"></i>
                            <span>Ver detalhes</span>
                        </a>
                    </div>
                </article>
            `;
        }).join('');
        const needsMore = limited.length < 2;
        const helper = needsMore
            ? '<div class="compare-empty">Escolha pelo menos mais um preferido para comparar lado a lado.</div>'
            : '';
        compareContent.innerHTML = `<div class="compare-grid" role="list">${cards}</div>${helper}`;
        compareContent.scrollTop = 0;
    }

    const initialFavorites = loadFavorites();
    syncFavoriteButtons(initialFavorites);

    favoriteCurrentBtn?.addEventListener('click', () => {
        if (!config.authenticated) {
            window.location.href = config.loginUrl || '/login_system/login.php';
            return;
        }
        const payload = buildCurrentFavoritePayload();
        if (!payload) return;
        const favorites = toggleFavoritePayload(payload);
        syncFavoriteButtons(favorites);
        const isActive = favorites.some((item) => item.id === payload.id);
        trackAdEvent(isActive ? 'favorite_added' : 'favorite_removed', {
            adId: payload.id,
            metadata: {
                origin: 'favorite_control',
            },
        });
        if (activeModal === 'compare') {
            void renderCompareModal(favorites);
        }
    });

    favoriteCardButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!config.authenticated) {
                window.location.href = config.loginUrl || '/login_system/login.php';
                return;
            }
            const payload = parseFavoriteButtonPayload(button);
            if (!payload) return;
            const favorites = toggleFavoritePayload(payload);
            syncFavoriteButtons(favorites);
            if (Number(payload.id) === Number(state.id)) {
                const isActive = favorites.some((item) => item.id === payload.id);
                trackAdEvent(isActive ? 'favorite_added' : 'favorite_removed', {
                    adId: payload.id,
                    metadata: {
                        origin: 'favorite_card',
                    },
                });
            }
            if (activeModal === 'compare') {
                void renderCompareModal(favorites);
            }
        });
    });

    compareOverlay?.querySelector('[data-compare-clear]')?.addEventListener('click', () => {
        saveFavorites([]);
        syncFavoriteButtons([]);
        void renderCompareModal([]);
    });

    compareBtn?.addEventListener('click', () => {
        const favorites = loadFavorites();
        if (!favorites.length) {
            void renderCompareModal([]);
        } else {
            void renderCompareModal(favorites);
        }
        openModal('compare');
    });

    const shareBtn = document.querySelector('[data-action="share"]');

    function getShareUrl() {
        // Usar window.location.href para pegar a URL atual
        const currentUrl = window.location.href;
        
        // Remover parâmetros de cache (nocache) e photo existentes
        let baseUrl = currentUrl.split('?')[0];
        
        // Adicionar parâmetro de foto selecionada
        const shareUrl = `${baseUrl}?photo=${selectedPhotoIndex + 1}`;
        console.log('getShareUrl Debug:', { currentUrl, baseUrl, selectedPhotoIndex, shareUrl });
        return shareUrl;
    }

    function getShareMessage() {
        const base = (state.share_text || '').trim();
        if (base) return base;
        return  `Olha este veiculo no Classi Livre Autos!`; 
    }

    function resetShareFeedback() {
        if (!shareFeedback) return;
        shareFeedback.hidden = true;
        shareFeedback.classList.remove('error');
        shareFeedback.textContent = '';
    }

    function showShareFeedback(message, isError = false) {
        if (!shareFeedback) return;
        shareFeedback.hidden = false;
        shareFeedback.textContent = message;
        shareFeedback.classList.toggle('error', isError);
        window.setTimeout(() => {
            if (!shareFeedback) return;
            shareFeedback.hidden = true;
            shareFeedback.classList.remove('error');
            shareFeedback.textContent = '';
        }, 2400);
    }

    async function copyText(button, text, successLabel) {
        if (!text) return;
        const original = button ? button.innerHTML : '';
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(text);
            } else {
                throw new Error('clipboard unsupported');
            }
            if (button) {
                button.innerHTML = `<i class="fas fa-check"></i> ${successLabel}`;
                setTimeout(() => {
                    button.innerHTML = original;
                }, 2200);
            }
            showShareFeedback(successLabel);
        } catch (_error) {
            window.prompt('Copie a mensagem para compartilhar:', text);
        }
    }

    async function fetchImageFile(src) {
        const response = await fetch(src, { mode: 'cors' });
        if (!response.ok) {
            throw new Error('Nao foi possivel carregar a imagem do anuncio.');
        }
        const blob = await response.blob();
        const type = blob.type && blob.type.startsWith('image/') ? blob.type : 'image/jpeg';
        const extension = type.split('/')[1] || 'jpg';
        const filename = `${state.slug || 'anuncio'}.${extension}`;
        return new File([blob], filename, { type });
    }

    function triggerTemporaryDownload(url, filename) {
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1500);
    }

    async function shareToInstagram(message) {
        const firstImage = state.images?.[0]?.src;
        if (!firstImage) {
            showShareFeedback('Adicione ao menos uma imagem para compartilhar no Instagram.', true);
            return;
        }
        try {
            const file = await fetchImageFile(firstImage);
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({
                    files: [file],
                    title: state.title || 'Classi Livre Autos',
                    text: message,
                });
                showShareFeedback('Abra o Instagram para concluir a postagem.');
                return;
            }
            const objectUrl = URL.createObjectURL(file);
            triggerTemporaryDownload(objectUrl, file.name);
            showShareFeedback('Imagem baixada. Publique manualmente no Instagram.');
            window.open('https://www.instagram.com/', '_blank', 'noopener');
        } catch (error) {
            console.error(error);
            showShareFeedback('Nao foi possivel preparar o arquivo para o Instagram.', true);
            window.open('https://www.instagram.com/', '_blank', 'noopener');
        }
    }

    // Seletor de fotos para compartilhamento social
    const photoSelector = shareOverlay?.querySelector('[data-share-photo-selector]');
    const photoPreview = shareOverlay?.querySelector('[data-share-selected-photo]');
    const photoGallery = shareOverlay?.querySelector('[data-share-photo-gallery]');
    const photoConfirm = shareOverlay?.querySelector('[data-share-photo-confirm]');
    const photoCancel = shareOverlay?.querySelector('[data-share-photo-cancel]');
    const optionsGrid = shareOverlay?.querySelector('[data-share-options-grid]');
    let selectedPhotoIndex = 0;
    let pendingShareTarget = null;

    // Inicializar galeria de fotos no seletor
    function initPhotoSelector() {
        if (!photoGallery || !state.images || state.images.length === 0) return;
        
        // Remover botões existentes (mantém apenas o primeiro como template)
        while (photoGallery.children.length > 0) {
            photoGallery.removeChild(photoGallery.lastChild);
        }
        
        state.images.forEach((image, index) => {
            const thumbBtn = document.createElement('button');
            thumbBtn.type = 'button';
            thumbBtn.className = 'share-modal__photo-thumb';
            thumbBtn.dataset.sharePhotoIndex = index;
            thumbBtn.dataset.sharePhotoSelect = '';
            if (index === 0) {
                thumbBtn.classList.add('is-active');
            }
            
            const img = document.createElement('img');
            img.src = image.src;
            img.alt = `Foto ${index + 1}`;
            
            thumbBtn.appendChild(img);
            thumbBtn.addEventListener('click', () => selectPhoto(index));
            
            photoGallery.appendChild(thumbBtn);
        });
    }

    function selectPhoto(index) {
        if (!Array.isArray(state.images) || !state.images[index]) return;
        
        selectedPhotoIndex = index;
        console.log('selectPhoto called with index:', index, 'selectedPhotoIndex now:', selectedPhotoIndex);
        const image = state.images[index];
        
        // Atualizar preview
        if (photoPreview) {
            photoPreview.src = image.src;
            photoPreview.alt = image.alt;
        }
        
        // Atualizar estado visual dos thumbnails
        document.querySelectorAll('[data-share-photo-select]').forEach((thumb, i) => {
            thumb.classList.toggle('is-active', i === index);
        });
    }

    function showPhotoSelector(target) {
        pendingShareTarget = target;
        if (optionsGrid) optionsGrid.hidden = true;
        if (photoSelector) photoSelector.hidden = false;
        if (photoConfirm) photoConfirm.hidden = false;
        if (photoCancel) photoCancel.hidden = false;
        
        initPhotoSelector();
        selectPhoto(0);
    }

    function hidePhotoSelector() {
        pendingShareTarget = null;
        if (photoSelector) photoSelector.hidden = true;
        if (photoConfirm) photoConfirm.hidden = true;
        if (photoCancel) photoCancel.hidden = true;
        if (optionsGrid) optionsGrid.hidden = false;
    }

    if (photoCancel) {
        photoCancel.addEventListener('click', hidePhotoSelector);
    }

    if (photoConfirm) {
        photoConfirm.addEventListener('click', async () => {
            if (pendingShareTarget === 'copylink') {
                // Para copiar link, usar o botão "Copiar link"
                await copyText(shareCopyLinkBtn, getShareUrl(), 'Link copiado!');
                trackAdEvent('share_copy_link', {
                    metadata: {
                        channel: 'copy_link',
                        via: 'photo_selector',
                    },
                });
            } else if (pendingShareTarget) {
                await openShareTarget(pendingShareTarget);
            }
            hidePhotoSelector();
        });
    }

    async function openShareTarget(target) {
        console.log('openShareTarget called with target:', target, 'selectedPhotoIndex:', selectedPhotoIndex);
        const message = getShareMessage();
        const url = getShareUrl();
        console.log('After getShareUrl, url is:', url);
        const encodedMessage = encodeURIComponent(message);
        const encodedUrl = encodeURIComponent(url);
        let shareUrl = '';
        const eventType = shareEventMap[target] || null;

        switch (target) {
            case 'whatsapp': {
                // Compartilhar para WhatsApp com a foto selecionada
                // Incluir a URL com o parâmetro ?photo=X
                const whatsappText = `${message}\n\n${url}`;
                shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(whatsappText)}`;
                window.open(shareUrl, '_blank', 'noopener,noreferrer');
                break;
            }
            case 'instagram': {
                // Compartilhar para Instagram
                // Opção 1: Usar Web Share API (disponível em navegadores modernos)
                const shareData = {
                    title: state.title || 'Classi Livre Autos',
                    text: message,
                    url: url
                };
                
                if (navigator.canShare && navigator.canShare(shareData)) {
                    // Usar Web Share API nativa do navegador
                    navigator.share(shareData).catch(err => {
                        console.log('Web Share API rejected or cancelled:', err);
                        // Fallback automático abaixo
                    });
                } else if (window.FB && typeof FB !== 'undefined' && FB.ui) {
                    // Opção 2: Usar Facebook SDK que funciona para Instagram
                    FB.ui({
                        method: 'share',
                        href: url,
                        hashtag: '#ClassiLivreAutos',
                        display: 'popup',
                    }, function(response){
                        console.log('Facebook share response:', response);
                    });
                } else {
                    // Opção 3: Fallback - copiar para clipboard e abrir Instagram
                    try {
                        navigator.clipboard.writeText(`${message}\n\n${url}`).then(() => {
                            console.log('Mensagem e link copiados para Instagram');
                            showShareFeedback('Link copiado! Abra Instagram para compartilhar.');
                            // Abrir Instagram web para o usuário colar
                            window.open('https://www.instagram.com/', '_blank', 'noopener,noreferrer');
                        });
                    } catch (err) {
                        console.error('Erro ao copiar para clipboard:', err);
                        window.open('https://www.instagram.com/', '_blank', 'noopener,noreferrer');
                    }
                }
                break;
            }
            case 'facebook': {
                // Compartilhar para Facebook Feed com imagem via Open Graph
                // Usar o Facebook Share Dialog para melhor UX
                if (window.FB && typeof FB !== 'undefined' && FB.ui) {
                    FB.ui({
                        method: 'share',
                        href: url,
                        hashtag: '#ClassiLivreAutos',
                        display: 'popup',
                    }, function(response){});
                } else {
                    // Fallback: usar o compartilhador web do Facebook
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}&quote=${encodedMessage}`;
                    window.open(shareUrl, '_blank', 'noopener,noreferrer');
                }
                break;
            }
            case 'email': {
                const subject = encodeURIComponent('Veja este veículo no Classi Livre Autos');
                shareUrl = `mailto:?subject=${subject}&body=${encodedMessage}`;
                window.location.href = shareUrl;
                break;
            }
            default:
                return;
        }

        if (eventType) {
            trackAdEvent(eventType, {
                metadata: {
                    channel: target,
                },
            });
        }
    }

    shareOptions.forEach((button) => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const target = button.dataset.shareTarget;
            if (!target) return;
            
            // Para Instagram, Facebook e WhatsApp, mostrar seletor de foto
            if ((target === 'instagram' || target === 'facebook' || target === 'whatsapp') && state.images && state.images.length > 0) {
                showPhotoSelector(target);
            } else {
                await openShareTarget(target);
            }
        });
    });

    shareCopyLinkBtn?.addEventListener('click', async () => {
        // Se há imagens, mostrar o seletor de fotos
        if (state.images && state.images.length > 0) {
            showPhotoSelector('copylink');
        } else {
            await copyText(shareCopyLinkBtn, getShareUrl(), 'Link copiado!');
            trackAdEvent('share_copy_link', {
                metadata: {
                    channel: 'copy_link',
                },
            });
        }
    });

    shareCopyMessageBtn?.addEventListener('click', async () => {
        await copyText(shareCopyMessageBtn, getShareMessage(), 'Mensagem copiada!');
        trackAdEvent('share_copy_message', {
            metadata: {
                channel: 'copy_message',
            },
        });
    });

    shareBtn?.addEventListener('click', () => {
        if (shareMessageElement) {
            shareMessageElement.textContent = getShareMessage();
        }
        resetShareFeedback();
        openModal('share');
    });

    const whatsappContactBtn = document.querySelector('.seller-card__contact-btn--whatsapp');
    if (whatsappContactBtn) {
        whatsappContactBtn.addEventListener('click', () => {
            trackAdEvent('whatsapp_click', {
                metadata: {
                    origin: 'contact_whatsapp',
                },
            });
        });
    }

    const phoneContactBtn = document.querySelector('.seller-card__contact-btn--phone');
    if (phoneContactBtn) {
        phoneContactBtn.addEventListener('click', () => {
            trackAdEvent('phone_click', {
                metadata: {
                    origin: 'contact_phone',
                },
            });
        });
    }

    const reportBtn = document.querySelector('[data-action="report"]');

    reportBtn?.addEventListener('click', () => {
        if (!config.authenticated) {
            window.location.href = config.loginUrl || '/login_system/login.php';
            return;
        }
        openModal('report');
    });

    reportForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!config.authenticated) {
            window.location.href = config.loginUrl || '/login_system/login.php';
            return;
        }
        const formData = new FormData(reportForm);
        const payload = {
            reason: formData.get('reason'),
            details: formData.get('details'),
            evidence: formData.get('evidence'),
            csrf_token: csrfToken,
        };
        try {
            const response = await fetch(`/api/anuncios/${state.id}/denuncia`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            if (response.status === 401) {
                window.location.href = config.loginUrl || '/login_system/login.php';
                return;
            }
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Não foi possível registrar sua denúncia.');
            }
            if (reportFeedback) {
                reportFeedback.hidden = false;
                reportFeedback.classList.remove('error');
                reportFeedback.textContent = data.message || 'Recebemos sua denúncia. Obrigado por colaborar!';
            }
            reportForm.reset();
        } catch (error) {
            if (reportFeedback) {
                reportFeedback.hidden = false;
                reportFeedback.textContent = error.message;
                reportFeedback.classList.add('error');
            }
        }
    });

    // Inicialização do carrossel de comparação
    function initCompareCarousel() {
        const compareButtons = document.querySelectorAll('[data-compare-nav]');
        if (compareButtons.length === 0) return;

        const compareGrid = document.querySelector('[data-modal="compare"] .compare-grid');
        if (!compareGrid) return;

        const scrollDistance = 360; // Card width + gap
        
        // Função para atualizar estado dos botões
        function updateButtonStates() {
            const leftBtn = document.querySelector('[data-compare-nav="left"]');
            const rightBtn = document.querySelector('[data-compare-nav="right"]');
            
            if (!leftBtn || !rightBtn) return;

            // Botão esquerdo: desabilitado se no início
            const isAtStart = compareGrid.scrollLeft <= 10;
            leftBtn.disabled = isAtStart;
            leftBtn.classList.toggle('disabled', isAtStart);

            // Botão direito: desabilitado se no final
            const isAtEnd = compareGrid.scrollLeft >= compareGrid.scrollWidth - compareGrid.clientWidth - 10;
            rightBtn.disabled = isAtEnd;
            rightBtn.classList.toggle('disabled', isAtEnd);
        }

        // Event listeners nos botões
        compareButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.disabled) return;

                const direction = this.dataset.compareNav;
                const scrollAmount = direction === 'left' ? -scrollDistance : scrollDistance;
                
                compareGrid.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });

                // Atualizar estados após scroll
                setTimeout(updateButtonStates, 400);
            });
        });

        // Event listener no scroll
        compareGrid.addEventListener('scroll', updateButtonStates);

        // Atualizar estados iniciais
        updateButtonStates();
    }

    // Adicionar listener ao modal de comparação
    document.addEventListener('data-modal-open', function(e) {
        if (e.detail?.modal === 'compare') {
            setTimeout(initCompareCarousel, 100);
        }
    });

    // Também inicializar se modal já estiver aberto
    if (document.querySelector('[data-modal="compare"].active')) {
        initCompareCarousel();
    }

    // Inicialização
    setGalleryImage(0);
    initSuggestionCarousels(document.querySelector('.ad-suggestions__list'));
})();















