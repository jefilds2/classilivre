/**
 * Sistema de Pop-ups EMBED (HTML Customizado)
 * Arquivo: /propagandas/frontend/assets/js/propaganda_embed.js
 * Versão: 2.3.0 (2025-11-09) - Centraliza conteúdo EMBED no popup
 * Mudança: Conteúdo agora centrado com flexbox, sem espaçamentos extras
 */

class PropagandaEmbedPopupManager {
    constructor(campaigns = []) {
        this.campaigns = campaigns;
        this.currentIndex = 0;
        this.popup = null;
        this.timers = { initial: null, reopen: null };
        this.clickedInCurrentSession = false; // Flag para rastrear se já clicou nesta sessão
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
        // setupVideoListeners é chamado em displayCampaign, não aqui
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
            <div id="propagandaPopupEmbed" class="propaganda-popup propaganda-popup--hidden propaganda-popup--embed">
                <div class="propaganda-popup__overlay"></div>
                <div class="propaganda-popup__container--embed">
                    <button class="propaganda-popup__close" aria-label="Fechar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <div id="propagandaEmbedContent"></div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);
        this.popup = document.getElementById('propagandaPopupEmbed');
    }

    attachEventListeners() {
        const overlay = this.popup.querySelector('.propaganda-popup__overlay');
        const closeBtn = this.popup.querySelector('.propaganda-popup__close');
        const contentDiv = document.getElementById('propagandaEmbedContent');

        if (overlay) overlay.addEventListener('click', () => this.close());
        if (closeBtn) closeBtn.addEventListener('click', () => this.close());
        
        // Rastrear cliques em qualquer conteúdo interativo dentro do EMBED
        if (contentDiv) {
            // Listener geral para qualquer clique no conteúdo
            contentDiv.addEventListener('click', (e) => {
                const target = e.target;
                
                // Verificar se é um elemento interativo
                const isInteractive = 
                    target.tagName === 'A' ||                    // Links diretos
                    target.tagName === 'IFRAME' ||              // Iframes (vídeos YouTube, etc)
                    target.tagName === 'VIDEO' ||               // Vídeos HTML5
                    target.tagName === 'BUTTON' ||              // Botões diretos
                    target.closest('a') ||                      // Link envolvendo o alvo
                    target.closest('video') ||                  // Vídeo envolvendo o alvo
                    target.closest('button') ||                 // Botões dentro do conteúdo
                    target.closest('iframe') ||                 // Iframe envolvendo o alvo
                    (target.tagName === 'IMG' && target.closest('a'));  // Imagens com link
                
                // Rastrear se houver qualquer interação
                if (isInteractive) {
                    console.log('🎬 Clique detectado em:', target.tagName, target);
                    // Rastrear apenas se não clicou nesta sessão
                    if (!this.clickedInCurrentSession) {
                        console.log('📊 Primeiro clique desta sessão - RASTREANDO');
                        this.trackMetric(this.campaigns[this.currentIndex].id, 'click');
                        this.clickedInCurrentSession = true; // Bloquear próximos cliques
                    } else {
                        console.log('⏸️ Clique bloqueado - já foi contado nesta sessão');
                    }
                }
            }, true); // Usar captura para pegar todos os eventos
        }
    }

    selectCampaign() {
        if (this.campaigns.length === 0) return;
        this.currentIndex = Math.floor(Math.random() * this.campaigns.length);
        console.log('✨ Nova campanha selecionada - Flag de clique será resetada em displayCampaign');
        this.displayCampaign();
    }

    displayCampaign() {
        const campaign = this.campaigns[this.currentIndex];
        if (!campaign || !campaign.html_code) {
            console.warn('⚠️ Campanha ou HTML inválido:', campaign);
            return;
        }

        const contentDiv = document.getElementById('propagandaEmbedContent');
        if (!contentDiv) {
            console.error('❌ Div de conteúdo não encontrada!');
            return;
        }

        console.log('📺 Carregando campanha:', campaign.title || campaign.id);
        console.log('📝 HTML Code length:', campaign.html_code.length);
        console.log('📝 HTML Code preview:', campaign.html_code.substring(0, 100));
        
        // IMPORTANTE: Resetar flag de clique para nova sessão
        this.clickedInCurrentSession = false;
        console.log('🔄 Flag de clique resetada para nova sessão');
        
        // Limpar completamente o conteúdo anterior
        contentDiv.innerHTML = '';
        
        // Injetar o novo conteúdo diretamente
        contentDiv.innerHTML = campaign.html_code;
        
        // Forçar estilos do contentDiv para ocupar 100% da área
        contentDiv.style.display = 'flex';
        contentDiv.style.alignItems = 'center';
        contentDiv.style.justifyContent = 'center';
        contentDiv.style.padding = '0';
        contentDiv.style.margin = '0';
        
        // Remover margens/paddings de qualquer div filha
        const allDivs = contentDiv.querySelectorAll('div');
        allDivs.forEach(div => {
            div.style.margin = '0';
            div.style.padding = '0';
        });
        
        // Re-registrar listeners de vídeo e iframe para o novo conteúdo
        this.setupVideoListeners();
        
        console.log('✅ Conteúdo EMBED carregado com sucesso');
        console.log('📊 Content div HTML:', contentDiv.innerHTML.substring(0, 100));
    }

    setupVideoListeners() {
        const contentDiv = document.getElementById('propagandaEmbedContent');
        if (!contentDiv) return;
        
        console.log('🔧 setupVideoListeners iniciado');
        
        // Rastrear especificamente o play de vídeos HTML5
        const videos = contentDiv.querySelectorAll('video');
        console.log(`📹 Encontrados ${videos.length} vídeos HTML5`);
        videos.forEach((video, idx) => {
            console.log(`  [${idx}] Video encontrado, adicionando listener de play`);
            video.addEventListener('play', () => {
                console.log('▶️ Reprodução de vídeo detectada');
                // Rastrear apenas se não clicou nesta sessão
                if (!this.clickedInCurrentSession) {
                    console.log('📊 Primeiro play desta sessão - RASTREANDO');
                    this.trackMetric(this.campaigns[this.currentIndex].id, 'click');
                    this.clickedInCurrentSession = true;
                } else {
                    console.log('⏸️ Play bloqueado - já foi contado nesta sessão');
                }
            });
        });
        
        // Rastrear cliques em iframes (YouTube, Vimeo, etc)
        const iframes = contentDiv.querySelectorAll('iframe');
        console.log(`🎬 Encontrados ${iframes.length} iframes`);
        iframes.forEach((iframe, idx) => {
            console.log(`  [${idx}] Iframe encontrado, criando overlay...`);
            
            // Obter dimensões do iframe
            let iframeWidth = iframe.getAttribute('width') || iframe.width || '100%';
            let iframeHeight = iframe.getAttribute('height') || iframe.height || 'auto';
            
            // Se for string com px, manter, senão adicionar
            if (typeof iframeWidth === 'number' && iframeWidth !== '100%') {
                iframeWidth = iframeWidth + 'px';
            }
            if (typeof iframeHeight === 'number' && iframeHeight !== 'auto') {
                iframeHeight = iframeHeight + 'px';
            }
            
            // Se não tiver altura explícita, usar 100%
            if (!iframe.getAttribute('height') && !iframe.style.height) {
                iframeHeight = '100%';
            }
            
            console.log(`  [${idx}] Dimensões detectadas: ${iframeWidth} x ${iframeHeight}`);
            
            // Criar overlay clicável sobre o iframe
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: transparent;
                cursor: pointer;
                z-index: 10;
                border-radius: 8px;
            `;
            
            // Envolver iframe em container com posição relativa
            const wrapper = document.createElement('div');
            wrapper.style.cssText = `
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
            `;
            
            iframe.parentNode.insertBefore(wrapper, iframe);
            wrapper.appendChild(iframe);
            wrapper.appendChild(overlay);
            
            // Garantir que iframe ocupa 100% do wrapper
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.style.display = 'block';
            iframe.style.margin = '0';
            iframe.style.padding = '0';
            iframe.style.border = 'none';
            iframe.style.borderRadius = '8px';
            
            console.log(`  [${idx}] Overlay criado, adicionando listener de clique`);
            
            // Rastrear clique no overlay
            overlay.addEventListener('click', () => {
                console.log('🎥 Clique detectado no iframe');
                // Rastrear apenas se não clicou nesta sessão
                if (!this.clickedInCurrentSession) {
                    console.log('📊 Primeiro clique no iframe desta sessão - RASTREANDO');
                    this.trackMetric(this.campaigns[this.currentIndex].id, 'click');
                    this.clickedInCurrentSession = true;
                } else {
                    console.log('⏸️ Clique no iframe bloqueado - já foi contado nesta sessão');
                }
                // Remover overlay após clique para permitir interação com iframe
                overlay.style.display = 'none';
            }, { once: true });
        });
        
        console.log('✅ setupVideoListeners finalizado');
    }

    showWithDelay() {
        const campaign = this.campaigns[this.currentIndex];
        const delay = campaign.initial_delay_ms || 3000;
        this.timers.initial = setTimeout(() => this.show(), delay);
    }

    show() {
        if (this.popup) {
            console.log('👁️ Mostrando popup EMBED...');
            this.popup.classList.remove('propaganda-popup--hidden');
        } else {
            console.error('❌ Popup não encontrado para mostrar!');
        }
    }

    close() {
        if (this.popup) {
            this.popup.classList.add('propaganda-popup--hidden');
        }

        // Parar todos os iframes completamente
        const contentDiv = document.getElementById('propagandaEmbedContent');
        if (contentDiv) {
            // Remover todos os iframes (para parar completamente)
            const iframes = contentDiv.querySelectorAll('iframe');
            iframes.forEach(iframe => {
                iframe.remove();
            });

            // Pausar e limpar vídeos nativos HTML5
            const videos = contentDiv.querySelectorAll('video');
            videos.forEach(video => {
                video.pause();
                video.currentTime = 0;
                video.src = '';
            });

            // Pausar e limpar áudios
            const audios = contentDiv.querySelectorAll('audio');
            audios.forEach(audio => {
                audio.pause();
                audio.currentTime = 0;
                audio.src = '';
            });

            // Limpar conteúdo completamente
            contentDiv.innerHTML = '';
        }

        // Registrar visualização quando fecha o popup
        const campaign = this.campaigns[this.currentIndex];
        this.trackMetric(campaign.id, 'view');
        
        const reopenDelay = campaign.reopen_delay_ms || 300000;
        
        this.timers.reopen = setTimeout(() => {
            console.log('🔄 Reabrindo propaganda EMBED - Verificando frequência...');
            
            // Verificar se pode exibir novamente baseado na frequência
            if (!this.shouldDisplay()) {
                console.log('📅 Frequência 24h ainda não expirou - aguardando próxima oportunidade');
                return;
            }
            
            // Recomeçar o fluxo se passou na verificação
            this.selectCampaign();
            this.showWithDelay();
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
}

document.addEventListener('DOMContentLoaded', () => {
    const scriptElement = document.getElementById('propagandas-embed');
    if (scriptElement) {
        try {
            const campaigns = JSON.parse(scriptElement.getAttribute('data-campaigns'));
            new PropagandaEmbedPopupManager(campaigns);
        } catch (e) {
            console.error('Erro ao carregar campanhas EMBED:', e);
        }
    }
});
