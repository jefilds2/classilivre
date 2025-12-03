/**
 * Sistema de Pop-ups LOCAL
 * Arquivo: /propagandas/frontend/assets/js/propaganda_local.js
 */

class PropagandaLocalPopupManager {
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
            <div id="propagandaPopupLocal" class="propaganda-popup--local propaganda-popup--hidden">
                <div class="propaganda-popup__overlay"></div>
                <div class="propaganda-popup__container">
                    <button class="propaganda-popup__close" aria-label="Fechar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <img id="propagandaImageLocal" class="propaganda-popup__image-local" src="" alt="Propaganda" />
                    <div class="propaganda-popup__content">
                        <h3 id="propagandaTitleLocal" class="propaganda-popup__title"></h3>
                        <div class="propaganda-popup__city">
                            <span class="propaganda-popup__city-text">📍 <span id="propagandaCityLocal"></span></span>
                        </div>
                        <p id="propagandaDescriptionLocal" class="propaganda-popup__description"></p>
                    </div>
                    <div class="propaganda-popup__buttons-container">
                        <a id="propagandaPhoneLink" class="propaganda-popup__button-local propaganda-popup__button-phone" href="">
                            <i class="fas fa-phone"></i><span id="propagandaPhoneText"></span>
                        </a>
                        <a id="propagandaWhatsappLink" class="propaganda-popup__button-local propaganda-popup__button-whatsapp" href="" target="_blank">
                            <i class="fab fa-whatsapp"></i><span id="propagandaWhatsappText"></span>
                        </a>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);
        this.popup = document.getElementById('propagandaPopupLocal');
    }

    attachEventListeners() {
        const overlay = this.popup.querySelector('.propaganda-popup__overlay');
        const closeBtn = this.popup.querySelector('.propaganda-popup__close');
        const phoneLink = this.popup.querySelector('#propagandaPhoneLink');
        const whatsappLink = this.popup.querySelector('#propagandaWhatsappLink');

        if (overlay) overlay.addEventListener('click', () => this.close());
        if (closeBtn) closeBtn.addEventListener('click', () => this.close());
        if (phoneLink) phoneLink.addEventListener('click', () => this.recordClick());
        if (whatsappLink) whatsappLink.addEventListener('click', () => this.recordClick());
    }

    selectCampaign() {
        if (this.campaigns.length === 0) return;
        this.currentIndex = Math.floor(Math.random() * this.campaigns.length);
        this.displayCampaign();
    }

    displayCampaign() {
        const campaign = this.campaigns[this.currentIndex];
        if (!campaign) return;

        document.getElementById('propagandaImageLocal').src = campaign.image_url || '';
        document.getElementById('propagandaTitleLocal').textContent = campaign.title;
        document.getElementById('propagandaCityLocal').textContent = campaign.city || 'Localização não especificada';
        document.getElementById('propagandaDescriptionLocal').textContent = campaign.description;

        const phoneLink = document.getElementById('propagandaPhoneLink');
        const whatsappLink = document.getElementById('propagandaWhatsappLink');

        if (campaign.phone) {
            phoneLink.href = 'tel:' + campaign.phone;
            document.getElementById('propagandaPhoneText').textContent = campaign.phone;
            phoneLink.style.display = 'flex';
        } else {
            phoneLink.style.display = 'none';
        }

        if (campaign.whatsapp) {
            const whatsappNumber = campaign.whatsapp.replace(/\D/g, '');
            whatsappLink.href = 'https://wa.me/' + whatsappNumber;
            document.getElementById('propagandaWhatsappText').textContent = campaign.whatsapp;
            whatsappLink.style.display = 'flex';
        } else {
            whatsappLink.style.display = 'none';
        }
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
    const scriptElement = document.getElementById('propagandas-local');
    if (scriptElement) {
        try {
            const campaigns = JSON.parse(scriptElement.getAttribute('data-campaigns'));
            new PropagandaLocalPopupManager(campaigns);
        } catch (e) {
            console.error('Erro ao carregar campanhas LOCAL:', e);
        }
    }
});
