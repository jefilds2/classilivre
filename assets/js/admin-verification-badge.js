/**
 * admin-verification-badge.js
 * Sistema de toggle para badge de verificação
 */

document.addEventListener('DOMContentLoaded', function() {
    // Encontrar todos os botões de toggle de verificação
    const verificationButtons = document.querySelectorAll('[data-toggle-badge]');
    
    verificationButtons.forEach(button => {
        button.addEventListener('click', toggleVerificationBadge);
    });
});

async function toggleVerificationBadge(event) {
    event.preventDefault();
    
    const button = event.target.closest('[data-toggle-badge]');
    if (!button) return;
    
    const adId = button.dataset.adId;
    const action = button.dataset.toggleBadge;
    const csrfToken = button.dataset.csrf;
    const container = button.closest('[data-badge-container]');
    
    if (!adId || !action) {
        console.error('Dados incompletos no botão');
        return;
    }
    
    // Desabilitar botão enquanto processa
    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = '⏳ Processando...';
    
    try {
        const formData = new FormData();
        formData.append('ad_id', adId);
        formData.append('action', action);
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch('/api/toggle_verification_badge.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Erro ao processar solicitação');
        }
        
        // Atualizar UI
        if (container) {
            // Atualizar status visual
            const statusElement = container.querySelector('[data-badge-status]');
            if (statusElement) {
                statusElement.textContent = data.badge_text;
                statusElement.className = data.new_status === 1 
                    ? 'admin-verification-badge verified' 
                    : 'admin-verification-badge empty';
            }
        }
        
        // Atualizar botão
        button.dataset.toggleBadge = data.new_action;
        button.textContent = data.new_button_text;
        button.className = `button ${data.new_button_class === 'danger' ? 'danger' : 'primary'}`;
        button.style.background = data.new_button_class === 'danger' ? '#d32f2f' : '#4caf50';
        
        // Mostrar notificação
        showNotification(data.message, 'success');
        
        // Re-ativar botão
        button.disabled = false;
        
    } catch (error) {
        console.error('Erro:', error);
        button.textContent = originalText;
        button.disabled = false;
        showNotification(error.message, 'error');
    }
}

function showNotification(message, type = 'info') {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#d32f2f' : '#2196f3'};
        color: white;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 9999;
        font-weight: 600;
        animation: slideIn 0.3s ease-out;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Remover após 3 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Adicionar animações
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
