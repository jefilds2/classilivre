/**
 * Sistema de Pop-ups WEB
 * Arquivo: /propagandas/frontend/assets/js/propaganda_web.js
 */

class PropagandaWebPopupManager {
    constructor(campaigns = []) {
        this.campaigns = campaigns;
        this.currentIndex = 0;
        this.popup = null;
        this.timers = { initial: null, reopen: null };
        if (campaigns.length > 0) this.init();
    }

    init() {
        // Verificar frequência de exibição
        if (!this.shouldDisplay()) {
            console.log('📅 Pop-up já foi exibido hoje. Próxima exibição amanhã.');
            return;
        }

        this.createPopupElement();
        this.attachEventListeners();
        this.selectCampaign();
        this.showWithDelay();
    }

    shouldDisplay() {
        const campaign = this.campaigns[this.currentIndex];
        if (!campaign) return true;

        const frequency = campaign.display_frequency || 'por_sessao';
        const storageKey = `propaganda_shown_${campaign.id}`;
        
        // ===== Frequência: POR_DIA =====
        if (frequency === 'por_dia') {
            const lastShown = localStorage.getItem(storageKey);
            
            if (lastShown) {
                const lastDate = new Date(lastShown);
                const now = new Date();
                const diffHours = (now - lastDate) / (1000 * 60 * 60);
                
                if (diffHours < 24) {
                    console.log(`⏱️ Campanha ${campaign.id} será mostrada em ${(24 - diffHours).toFixed(1)}h`);
                    return false;
                }
            }
            
            // Passou 24h - registrar novo timestamp
            localStorage.setItem(storageKey, new Date().toISOString());
            return true;
        }
        
        // ===== Frequência: POR_SEMANA =====
        if (frequency === 'por_semana') {
            const lastShown = localStorage.getItem(storageKey);
            
            if (lastShown) {
                const lastDate = new Date(lastShown);
                const now = new Date();
                const diffDays = (now - lastDate) / (1000 * 60 * 60 * 24);
                
                if (diffDays < 7) {
                    console.log(`⏱️ Campanha ${campaign.id} será mostrada em ${(7 - diffDays).toFixed(1)} dias`);
                    return false;
                }
            }
            
            // Passou 7 dias - registrar novo timestamp
            localStorage.setItem(storageKey, new Date().toISOString());
            return true;
        }
        
        // ===== Frequência: POR_SESSAO =====
        // Apenas respeita uma vez por sessão (fecha browser = nova sessão)
        if (frequency === 'por_sessao') {
            // sessionStorage limpa quando browser fecha
            const sessionKey = `propaganda_session_${campaign.id}`;
            if (sessionStorage.getItem(sessionKey)) {
                return false; // Já foi exibido nesta sessão
            }
            sessionStorage.setItem(sessionKey, 'true');
            localStorage.setItem(storageKey, new Date().toISOString());
            return true;
        }
        
        // ===== Frequência: SEMPRE =====
        // Exibe toda vez (sempre recarrega)
        if (frequency === 'sempre') {
            localStorage.setItem(storageKey, new Date().toISOString());
            return true;
        }
        
        // Padrão: exibir
        return true;
    }

    createPopupElement() {
        const html = `
            <div id="propagandaPopupWeb" class="propaganda-popup--web propaganda-popup--hidden">
                <div class="propaganda-popup__overlay"></div>
                <div class="propaganda-popup__container">
                    <button class="propaganda-popup__close" aria-label="Fechar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <img id="propagandaImageWeb" class="propaganda-popup__image" src="" alt="Propaganda" />
                    <div class="propaganda-popup__content">
                        <h3 id="propagandaTitleWeb" class="propaganda-popup__title"></h3>
                        <p id="propagandaDescriptionWeb" class="propaganda-popup__description"></p>
                    </div>
                    <div id="propagandaControlsWeb" class="propaganda-popup__controls propaganda-popup__controls--hidden">
                        <button class="propaganda-popup__nav propaganda-popup__nav--prev" aria-label="Anterior">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <span class="propaganda-popup__counter"><span id="propagandaCounterWeb">1</span>/<span id="propagandaTotalWeb">1</span></span>
                        <button class="propaganda-popup__nav propaganda-popup__nav--next" aria-label="Próxima">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                    <a id="propagandaLinkWeb" class="propaganda-popup__button" href="" target="_blank" rel="noopener noreferrer">
                        <span id="propagandaCtaWeb"></span> <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                    </a>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);
        this.popup = document.getElementById('propagandaPopupWeb');
    }

    attachEventListeners() {
        const overlay = this.popup.querySelector('.propaganda-popup__overlay');
        const closeBtn = this.popup.querySelector('.propaganda-popup__close');
        const prevBtn = this.popup.querySelector('.propaganda-popup__nav--prev');
        const nextBtn = this.popup.querySelector('.propaganda-popup__nav--next');
        const link = this.popup.querySelector('#propagandaLinkWeb');

        if (overlay) overlay.addEventListener('click', () => this.close());
        if (closeBtn) closeBtn.addEventListener('click', () => this.close());
        if (prevBtn) prevBtn.addEventListener('click', () => this.previousCampaign());
        if (nextBtn) nextBtn.addEventListener('click', () => this.nextCampaign());
        if (link) link.addEventListener('click', () => this.recordClick());
    }

    selectCampaign() {
        if (this.campaigns.length === 0) return;
        this.currentIndex = Math.floor(Math.random() * this.campaigns.length);
        this.displayCampaign();
    }

    displayCampaign() {
        const campaign = this.campaigns[this.currentIndex];
        if (!campaign) return;

        document.getElementById('propagandaImageWeb').src = campaign.image_url || '';
        document.getElementById('propagandaTitleWeb').textContent = campaign.title;
        document.getElementById('propagandaDescriptionWeb').textContent = campaign.description;
        document.getElementById('propagandaCtaWeb').textContent = campaign.cta_text || 'Saiba Mais';
        document.getElementById('propagandaLinkWeb').href = campaign.affiliate_link || '#';

        const controls = document.getElementById('propagandaControlsWeb');
        if (this.campaigns.length > 1) {
            controls.classList.remove('propaganda-popup__controls--hidden');
            document.getElementById('propagandaCounterWeb').textContent = this.currentIndex + 1;
            document.getElementById('propagandaTotalWeb').textContent = this.campaigns.length;
        } else {
            controls.classList.add('propaganda-popup__controls--hidden');
        }
    }

    nextCampaign() {
        this.currentIndex = (this.currentIndex + 1) % this.campaigns.length;
        this.displayCampaign();
    }

    previousCampaign() {
        this.currentIndex = (this.currentIndex - 1 + this.campaigns.length) % this.campaigns.length;
        this.displayCampaign();
    }

    showWithDelay() {
        const campaign = this.campaigns[this.currentIndex];
        const delay = campaign.initial_delay_ms || 3000;
        this.timers.initial = setTimeout(() => this.show(), delay);
    }

    show() {
        if (this.popup) this.popup.classList.remove('propaganda-popup--hidden');
    }

    close() {
        if (this.popup) this.popup.classList.add('propaganda-popup--hidden');
        
        // Registrar visualização quando fecha o popup
        const campaign = this.campaigns[this.currentIndex];
        this.trackMetric(campaign.id, 'view');
        
        const reopenDelay = campaign.reopen_delay_ms || 300000;
        this.timers.reopen = setTimeout(() => {
            // Verificar frequência antes de reabrir
            if (!this.shouldDisplay()) {
                console.log('📅 Frequência 24h ainda não expirou - aguardando próxima oportunidade');
                return;
            }
            this.show();
        }, reopenDelay);
    }

    trackMetric(campaignId, eventType) {
        const data = { campaign_id: campaignId, event_type: eventType };
        console.log('📊 Rastreando métrica:', data);
        
        fetch('/propagandas/admin/api/track-metric-v2.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(data => console.log('✓ Resposta da API:', data))
        .catch(e => console.error('❌ Erro ao rastrear métrica:', e));
    }

    recordClick() {
        const campaign = this.campaigns[this.currentIndex];
        this.trackMetric(campaign.id, 'click');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const scriptElement = document.getElementById('propagandas-web');
    if (scriptElement) {
        try {
            const campaigns = JSON.parse(scriptElement.getAttribute('data-campaigns'));
            new PropagandaWebPopupManager(campaigns);
        } catch (e) {
            console.error('Erro ao carregar campanhas WEB:', e);
        }
    }
});
