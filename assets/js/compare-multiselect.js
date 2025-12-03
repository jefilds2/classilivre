// assets/js/compare-multiselect.js - Sistema de comparação com seleção de múltiplos favoritos

(function() {
    'use strict';

    // Globais
    window.CompareMultiselect = window.CompareMultiselect || {};

    /**
     * Modal de seleção de comparação
     * Permite ao usuário escolher quais favoritos comparar (de 2 a 10)
     */
    CompareMultiselect.showSelectionModal = function() {
        const favorites = JSON.parse(localStorage.getItem('clautos:favorites') || '[]');

        if (!favorites.length) {
            alert('Você não tem nenhum favorito. Marque anúncios como preferidos para comparar.');
            return;
        }

        if (favorites.length < 2) {
            alert('Você precisa de pelo menos 2 favoritos para comparar.');
            return;
        }

        // Criar modal HTML
        const modalHtml = `
            <div class="compare-selection-overlay" id="compareSelectionOverlay" style="
                display: flex;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                align-items: center;
                justify-content: center;
                z-index: 99999;
            ">
                <div class="compare-selection-modal" style="
                    background: white;
                    border-radius: 1rem;
                    max-width: 600px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
                ">
                    <!-- Header -->
                    <div style="
                        padding: 1.5rem;
                        border-bottom: 1px solid #e5e7eb;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">
                            Escolha os veículos para comparar
                        </h2>
                        <button type="button" onclick="document.getElementById('compareSelectionOverlay').remove()" style="
                            background: none;
                            border: none;
                            font-size: 1.5rem;
                            color: #6b7280;
                            cursor: pointer;
                            padding: 0;
                        ">
                            ✕
                        </button>
                    </div>

                    <!-- Body -->
                    <div style="padding: 1.5rem; display: grid; gap: 1rem;">
                        <p style="color: #6b7280; margin: 0;">
                            Selecione de <strong>2 a 10</strong> veículos para comparar lado a lado.
                        </p>

                        <!-- Lista de Favoritos -->
                        <div id="compareSelectList" style="display: grid; gap: 0.75rem;"></div>

                        <!-- Info -->
                        <div style="
                            background: #f0fdf4;
                            border: 1px solid #dcfce7;
                            border-radius: 0.5rem;
                            padding: 0.75rem 1rem;
                            font-size: 0.9rem;
                            color: #166534;
                        ">
                            <strong id="selectCount">0</strong> de <strong>${favorites.length}</strong> selecionados
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="
                        padding: 1rem 1.5rem;
                        border-top: 1px solid #e5e7eb;
                        display: flex;
                        gap: 1rem;
                        justify-content: flex-end;
                    ">
                        <button type="button" onclick="document.getElementById('compareSelectionOverlay').remove()" style="
                            padding: 0.65rem 1.5rem;
                            border: 2px solid #e5e7eb;
                            background: white;
                            border-radius: 0.5rem;
                            cursor: pointer;
                            font-weight: 600;
                            color: #1f2937;
                        ">
                            Cancelar
                        </button>
                        <button type="button" id="compareConfirmBtn" onclick="CompareMultiselect.confirmSelection()" style="
                            padding: 0.65rem 1.5rem;
                            border: none;
                            background: #136f33;
                            color: white;
                            border-radius: 0.5rem;
                            cursor: pointer;
                            font-weight: 600;
                            disabled: true;
                        " disabled>
                            <i class="fas fa-compare"></i> Comparar
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Inserir modal no DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // PreenchER lista de favoritos
        const list = document.getElementById('compareSelectList');
        favorites.forEach((fav, index) => {
            const itemHtml = `
                <label style="
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    padding: 1rem;
                    border: 2px solid #e5e7eb;
                    border-radius: 0.5rem;
                    cursor: pointer;
                    transition: all 0.3s ease;
                " class="compare-select-item">
                    <input type="checkbox" value="${fav.id}" class="compare-select-checkbox" style="
                        width: 1.25rem;
                        height: 1.25rem;
                        cursor: pointer;
                        accent-color: #136f33;
                    ">
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${fav.title || 'Veículo'}
                        </div>
                        <div style="font-size: 0.9rem; color: #6b7280; display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-top: 0.25rem;">
                            <span><strong>R$</strong> ${(fav.price || 0).toLocaleString('pt-BR')}</span>
                            <span>${fav.city || 'N/A'}, ${fav.state || 'N/A'}</span>
                        </div>
                    </div>
                    <div style="font-weight: 700; color: #136f33; font-size: 1.2rem;">
                        ❤️
                    </div>
                </label>
            `;
            list.insertAdjacentHTML('beforeend', itemHtml);
        });

        // Event listeners para checkboxes
        const checkboxes = document.querySelectorAll('.compare-select-checkbox');
        const confirmBtn = document.getElementById('compareConfirmBtn');
        const countEl = document.getElementById('selectCount');

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const selected = Array.from(checkboxes).filter(cb => cb.checked).length;
                countEl.textContent = selected;

                // Habilitar/desabilitar botão
                confirmBtn.disabled = selected < 2 || selected > 10;

                // Atualizar visual do item
                const label = this.closest('label');
                if (this.checked) {
                    label.style.borderColor = '#136f33';
                    label.style.background = '#f0fdf4';
                } else {
                    label.style.borderColor = '#e5e7eb';
                    label.style.background = '#ffffff';
                }
            });
        });

        // Fechar ao clicar fora
        document.getElementById('compareSelectionOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                this.remove();
            }
        });
    };

    /**
     * Confirmar seleção e abrir comparação
     */
    CompareMultiselect.confirmSelection = async function() {
        const checkboxes = document.querySelectorAll('.compare-select-checkbox:checked');
        const selectedIds = Array.from(checkboxes).map(cb => parseInt(cb.value, 10));

        if (selectedIds.length < 2) {
            alert('Selecione pelo menos 2 veículos para comparar.');
            return;
        }

        // Remover modal
        const overlay = document.getElementById('compareSelectionOverlay');
        if (overlay) overlay.remove();

        const favorites = JSON.parse(localStorage.getItem('clautos:favorites') || '[]');
        const fallbackSelection = favorites.filter(f => selectedIds.includes(f.id));

        const normalizeAds = (ads) => {
            return ads.map(ad => {
                if (!ad || typeof ad !== 'object') {
                    return ad;
                }

                const normalized = { ...ad };

                if (!Array.isArray(normalized.images) || !normalized.images.length) {
                    if (normalized.image) {
                        normalized.images = [normalized.image];
                    } else {
                        normalized.images = ['/assets/uploads/images/sample-car-1.jpg'];
                    }
                }

                if (!normalized.price_formatted && typeof normalized.price !== 'undefined') {
                    const priceNumber = Number(normalized.price) || 0;
                    normalized.price_formatted = priceNumber.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });
                }

                return normalized;
            });
        };

        const renderSelection = (ads) => {
            if (typeof window.renderCompareModal === 'function') {
                const normalizedAds = normalizeAds(ads);
                window.renderCompareModal(normalizedAds);

                const openModalEvent = new CustomEvent('data-modal-open', {
                    detail: { modal: 'compare' }
                });
                document.dispatchEvent(openModalEvent);
            }
        };

        try {
            const response = await fetch('/api/get_ads_by_ids.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: selectedIds })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            const ads = Array.isArray(data) ? data.filter(ad => selectedIds.includes(ad.id)) : [];

            if (ads.length) {
                renderSelection(ads);
                return;
            }
        } catch (error) {
            // Falha ao carregar via API; fallback para dados locais
        }

        // Se não for possível obter dados completos, usar fallback do localStorage
        renderSelection(fallbackSelection);
    };

    // Expor globalmente
    window.showCompareSelectionModal = CompareMultiselect.showSelectionModal;

})();
