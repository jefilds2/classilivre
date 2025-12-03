// assets/js/store-metrics.js - Módulo compartilhado para registrar métricas da loja.

(function () {
    if (typeof window === 'undefined') {
        return;
    }

    if (window.__storeMetrics && window.__storeMetrics.__version === '2') {
        return;
    }

    const endpoint = '/api/store_metrics_event.php';

    const allowedEvents = new Set([
        'store_visit',
        'ad_click',
        'favorite_added',
        'favorite_removed',
        'whatsapp_click',
        'phone_click',
        'route_google',
        'route_waze',
        'filter_interaction',
        'sort_interaction',
        'share_whatsapp',
        'share_instagram',
        'share_facebook',
        'share_email',
        'share_copy_link',
        'share_copy_message'
    ]);

    const parseStoreId = (value) => {
        const numeric = Number(value);
        if (!Number.isFinite(numeric) || numeric <= 0) {
            return null;
        }
        return numeric;
    };

    const sendPayload = (payload) => {
        const body = JSON.stringify(payload);

        try {
            if (navigator.sendBeacon) {
                const blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon(endpoint, blob)) {
                    return;
                }
            }
        } catch (_err) {
            // Ignorar falhas do sendBeacon; fallback com fetch abaixo.
        }

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body,
            keepalive: true,
        }).catch(() => {
            // Falha silenciosa; métricas não devem interromper a experiência.
        });
    };

    const resolveSourceElement = () => {
        const selectors = [
            '[data-store-grid]',
            '[data-store-owner]',
            '[data-store-metrics]'
        ];
        for (const selector of selectors) {
            const element = document.querySelector(selector);
            if (element) {
                return element;
            }
        }
        return null;
    };

    const toMetadataObject = (metadata) => {
        if (!metadata || typeof metadata !== 'object') {
            return {};
        }
        return { ...metadata };
    };

    const dispatchEvent = (storeIdCandidate, eventType, options = {}) => {
        if (!allowedEvents.has(eventType)) {
            return false;
        }

        const storeId = parseStoreId(storeIdCandidate);
        if (!storeId) {
            return false;
        }

        const payload = {
            store_id: storeId,
            event_type: eventType,
        };

        const { adId = null, metadata = null } = options;

        const resolvedAdId = Number(adId);
        if (Number.isFinite(resolvedAdId) && resolvedAdId > 0) {
            payload.ad_id = resolvedAdId;
        }

        const meta = toMetadataObject(metadata);
        if (!meta.page && typeof window !== 'undefined') {
            meta.page = window.location.pathname || '';
        }
        if (Object.keys(meta).length > 0) {
            payload.metadata = meta;
        }

        sendPayload(payload);
        return true;
    };

    const source = resolveSourceElement();
    const state = {
        storeId: null,
        storeName: null,
        isEnabled: false,
    };

    let autoVisit = false;

    if (source) {
        const dataset = source.dataset || {};
        const candidates = [
            dataset.storeOwner,
            dataset.storeOwnerId,
            dataset.storeId,
            dataset.store,
            source.getAttribute('data-store-owner'),
            source.getAttribute('data-store-id'),
        ];

        for (const candidate of candidates) {
            const parsed = parseStoreId(candidate);
            if (parsed) {
                state.storeId = parsed;
                state.isEnabled = true;
                state.storeName = dataset.storeName || null;
                break;
            }
        }

        if (state.isEnabled) {
            if (source.hasAttribute('data-store-track-visit')) {
                autoVisit = source.dataset.storeTrackVisit !== 'false';
            } else if (source.matches('[data-store-grid]')) {
                autoVisit = true;
            }
        }
    }

    const api = {
        track(eventType, options = {}) {
            if (!state.isEnabled || !state.storeId) {
                return false;
            }
            return dispatchEvent(state.storeId, eventType, options);
        },
        trackForStore(storeId, eventType, options = {}) {
            return dispatchEvent(storeId, eventType, options);
        },
        isEnabled: state.isEnabled,
        storeId: state.storeId,
        storeName: state.storeName,
        allowedEvents,
        __version: '2',
        __listenerAttached: false,
    };

    if (autoVisit && state.storeId) {
        api.track('store_visit');
    }

    const buildMetadata = (link, card) => {
        const metadata = {
            origin: link.dataset.trackOrigin || card.dataset.trackOrigin || (state.isEnabled ? 'store_context' : 'global'),
            page: window.location.pathname || '',
        };

        const positionCandidate = link.dataset.trackPosition || card.dataset.trackPosition;
        if (positionCandidate !== undefined) {
            const numericPosition = Number(positionCandidate);
            if (Number.isFinite(numericPosition)) {
                metadata.position = numericPosition;
            } else if (positionCandidate !== '') {
                metadata.position = positionCandidate;
            }
        }

        const section = link.dataset.trackSection || card.dataset.trackSection;
        if (section) {
            metadata.section = section;
        }

        const extra = link.dataset.trackExtra || card.dataset.trackExtra;
        if (extra) {
            metadata.extra = extra;
        }

        return metadata;
    };

    const handleGlobalAdClick = (event) => {
        const { target } = event;
        if (!(target instanceof Element)) {
            return;
        }

        const link = target.closest('[data-track-ad-click]');
        if (!link) {
            return;
        }

        const metrics = window.__storeMetrics;
        if (!metrics) {
            return;
        }

        const card = link.closest('[data-ad-id]');
        if (!card) {
            return;
        }

        const storeIdCandidate =
            link.dataset.storeOwner ||
            link.getAttribute('data-store-owner') ||
            card.dataset.storeOwner ||
            card.getAttribute('data-store-owner');

        const adIdCandidate = link.dataset.adId || card.getAttribute('data-ad-id');
        const metadata = buildMetadata(link, card);

        let handled = false;
        if (storeIdCandidate && typeof metrics.trackForStore === 'function') {
            handled = metrics.trackForStore(storeIdCandidate, 'ad_click', {
                adId: adIdCandidate,
                metadata,
            });
        }

        if (!handled && metrics.isEnabled && typeof metrics.track === 'function') {
            metrics.track('ad_click', {
                adId: adIdCandidate,
                metadata,
            });
        }
    };

    window.__storeMetrics = api;

    if (!api.__listenerAttached) {
        document.addEventListener('click', handleGlobalAdClick);
        api.__listenerAttached = true;
    }
})();
