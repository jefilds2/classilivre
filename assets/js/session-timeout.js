// assets/js/session-timeout.js - Monitorar inatividade de sessão

(function() {
    'use strict';
    
    // Configurações
    const CONFIG = {
        checkInterval: 10 * 1000,           // Verificar a cada 10s (reduzido para detecção rápida)
        warningThreshold: 5 * 60 * 1000,    // Avisar com 5 minutos
        resetActivityEvents: ['mousedown', 'keydown', 'scroll', 'touchstart'],
    };
    
    let warningVisible = false;
    let timeoutHandle = null;
    
    /**
     * Busca status da sessão via API
     */
    async function checkSessionStatus() {
        try {
            const response = await fetch('/api/session_status.php');
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[SESSION] Erro ao verificar status:', error);
            return null;
        }
    }
    
    /**
     * Redireciona para login com mensagem apropriada
     */
    function redirectToLogin(reason = 'timeout') {
        let message = 'Sua sessão expirou.';
        
        if (reason === 'invalidated') {
            message = 'Sua conta foi acessada em outro navegador e você foi desconectado.';
        } else if (reason === 'timeout') {
            message = 'Sua sessão expirou por inatividade (30 minutos).';
        } else if (reason === 'max_lifetime') {
            message = 'Sua sessão expirou (limite máximo de 30 dias).';
        }
        
        localStorage.setItem('sessionExpiredMsg', message);
        window.location.href = '/login_system/login.php?expired=1&reason=' + reason;
    }
    
    /**
     * Exibe aviso de timeout próximo
     */
    function showWarning(remaining) {
        if (warningVisible) return;
        warningVisible = true;
        
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        
        // Criar modal de aviso
        const modal = document.createElement('div');
        modal.id = 'session-warning-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            font-family: Arial, sans-serif;
        `;
        
        modal.innerHTML = `
            <div style="
                background: white;
                padding: 30px;
                border-radius: 8px;
                max-width: 400px;
                text-align: center;
                box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            ">
                <div style="font-size: 24px; margin-bottom: 15px;">⏰</div>
                <h2 style="margin: 0 0 15px 0; color: #c62828; font-size: 20px;">
                    Sessão Expirando
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px;">
                    Sua sessão expirará em:
                </p>
                <div style="
                    font-size: 48px;
                    font-weight: bold;
                    color: #ff9800;
                    margin: 20px 0;
                    font-family: monospace;
                " id="timeout-counter">
                    ${minutes}:${String(seconds).padStart(2, '0')}
                </div>
                <p style="margin: 0 0 25px 0; color: #999; font-size: 12px;">
                    Clique no botão abaixo ou na página para permanecer conectado
                </p>
                <button onclick="document.getElementById('session-warning-modal').remove();" style="
                    padding: 10px 30px;
                    background: #4caf50;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: bold;
                ">
                    ✓ Permanecer Conectado
                </button>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Atualizar contador a cada segundo
        let counter = remaining;
        const intervalId = setInterval(() => {
            counter--;
            const mins = Math.floor(counter / 60);
            const secs = counter % 60;
            const counterEl = document.getElementById('timeout-counter');
            if (counterEl) {
                counterEl.textContent = `${mins}:${String(secs).padStart(2, '0')}`;
            }
            if (counter <= 0) {
                clearInterval(intervalId);
                hideWarning();
            }
        }, 1000);
    }
    
    /**
     * Esconde o aviso
     */
    function hideWarning() {
        const modal = document.getElementById('session-warning-modal');
        if (modal) {
            modal.remove();
        }
        warningVisible = false;
    }
    
    /**
     * Monitora inatividade e verifica status
     */
    async function monitorSession() {
        const status = await checkSessionStatus();
        
        if (!status) {
            // Erro ao checar status - tentar novamente em 10 segundos
            timeoutHandle = setTimeout(monitorSession, 10000);
            return;
        }
        
        if (!status.logged_in) {
            // Usuário foi deslogado (ou sessão inválida)
            if (warningVisible) hideWarning();
            clearTimeout(timeoutHandle);
            
            // Determinar o motivo
            const reason = status.reason || 'unknown';
            if (reason === 'invalidated') {
                redirectToLogin('invalidated');
            } else if (reason === 'inactivity_timeout') {
                redirectToLogin('timeout');
            } else if (reason === 'max_lifetime_exceeded') {
                redirectToLogin('max_lifetime');
            } else {
                redirectToLogin('unknown');
            }
            return;
        }
        
        const remaining = status.remaining_seconds;
        
        // Se deve avisar
        if (status.should_warn && remaining > 0) {
            showWarning(remaining);
        } else if (!status.should_warn && warningVisible) {
            hideWarning();
        }
        
        // Se sessão expirou
        if (remaining <= 0) {
            hideWarning();
            redirectToLogin('timeout');
            return;
        }
        
        // Próxima verificação
        timeoutHandle = setTimeout(monitorSession, CONFIG.checkInterval);
    }
    
    /**
     * Reseta o timer de inatividade ao detectar atividade
     */
    function resetActivityTimer() {
        // Qualquer interação do usuário reseta o timer
        // Isso acontece automaticamente no servidor quando current_user() valida o token
        // e atualiza last_activity_at
    }
    
    /**
     * Inicializa o monitorador de sessão
     */
    function init() {
        // Verificar se está logado
        const status = checkSessionStatus().then(data => {
            if (data && data.logged_in) {
                // Iniciar monitoramento
                monitorSession();
                
                // Registrar eventos de atividade
                CONFIG.resetActivityEvents.forEach(event => {
                    document.addEventListener(event, resetActivityTimer, true);
                });
            }
        });
    }
    
    // Iniciar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Cleanup
    window.addEventListener('beforeunload', () => {
        if (timeoutHandle) clearTimeout(timeoutHandle);
    });
})();
