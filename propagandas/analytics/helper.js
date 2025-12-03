/**
 * Analytics Tracker Helper
 * Facilita o rastreamento de eventos via JavaScript
 */

const AnalyticsHelper = {
    apiEndpoint: '/propagandas/analytics/api.php',

    /**
     * Registra um evento genérico
     */
    async trackEvent(type, data = {}) {
        try {
            await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ type, ...data }),
            });
        } catch (error) {
            console.warn('[Analytics] Erro ao rastrear evento:', error);
        }
    },

    /**
     * Registra clique em anúncio
     */
    trackAdClick(adId, ownerId) {
        this.trackEvent('ad_click', {
            ad_id: parseInt(adId),
            owner_user: parseInt(ownerId),
        });
    },

    /**
     * Registra visualização de detalhes do anúncio
     */
    trackAdView(adId, ownerId) {
        this.trackEvent('ad_detail_view', {
            ad_id: parseInt(adId),
            owner_user: parseInt(ownerId),
        });
    },

    /**
     * Registra clique em WhatsApp
     */
    trackWhatsApp(adId, ownerId) {
        this.trackEvent('whatsapp_click', {
            ad_id: parseInt(adId),
            owner_user: parseInt(ownerId),
        });
    },

    /**
     * Registra visita à vitrine
     */
    trackStoreVisit(storeOwnerId) {
        this.trackEvent('store_visit', {
            store_owner: parseInt(storeOwnerId),
        });
    },

    /**
     * Registra visualização de serviço
     */
    trackServiceView(serviceId, ownerId) {
        this.trackEvent('service_view', {
            service_id: parseInt(serviceId),
            owner_user: parseInt(ownerId),
        });
    },

    /**
     * Registra solicitação de contato de serviço
     */
    trackServiceContact(serviceId, ownerId) {
        this.trackEvent('service_contact', {
            service_id: parseInt(serviceId),
            owner_user: parseInt(ownerId),
        });
    },
};

// Inicializar listeners de clique em anúncios (auto)
document.addEventListener('DOMContentLoaded', () => {
    // Listener para cliques em cards de anúncios
    document.addEventListener('click', (e) => {
        const card = e.target.closest('[data-ad-id][data-owner-id]');
        if (!card) return;

        const adId = card.dataset.adId;
        const ownerId = card.dataset.ownerId;

        // Se foi um clique no WhatsApp, rastrear separadamente
        if (e.target.closest('[data-whatsapp-link]')) {
            AnalyticsHelper.trackWhatsApp(adId, ownerId);
        } else if (e.target.closest('a')) {
            // Se foi link para detalhes, rastrear view
            const href = e.target.closest('a')?.href || '';
            if (href.includes('/anuncios/ver.php')) {
                AnalyticsHelper.trackAdView(adId, ownerId);
            } else {
                AnalyticsHelper.trackAdClick(adId, ownerId);
            }
        }
    });

    // Listener para cliques em serviços
    document.addEventListener('click', (e) => {
        const serviceCard = e.target.closest('[data-service-id][data-owner-id]');
        if (!serviceCard) return;

        const serviceId = serviceCard.dataset.serviceId;
        const ownerId = serviceCard.dataset.ownerId;

        if (e.target.closest('[data-service-contact]')) {
            AnalyticsHelper.trackServiceContact(serviceId, ownerId);
        } else {
            AnalyticsHelper.trackServiceView(serviceId, ownerId);
        }
    });

    // Listener para cliques em vitrines
    document.addEventListener('click', (e) => {
        const storeLink = e.target.closest('[data-store-owner]');
        if (storeLink && !e.target.closest('[data-whatsapp-link]')) {
            const storeOwnerId = storeLink.dataset.storeOwner;
            AnalyticsHelper.trackStoreVisit(storeOwnerId);
        }
    });
});
