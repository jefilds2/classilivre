// assets/js/main.js — Scripts de UX para formulários, pré-visualização e interações simples.

/**
 * ✅ COMPRESSOR DE IMAGENS
 * Inicializa o sistema de compressão client-side
 */
console.log('[MAIN.JS] Verificando se image-compressor.js foi carregado...');
if (typeof window.imageCompressorUtils === 'object') {
    console.log('✅ Image Compressor Utils disponível');
    console.log('   - Configuração:', window.imageCompressorUtils.compressionConfig);
    console.log('   - Estado:', window.imageCompressorUtils.compressionState.size, 'arquivo(s) processado(s)');
} else {
    console.log('⏳ Aguardando carregamento do image-compressor.js...');
    // Será inicializado quando o script carregar
}

/**
 * Proteção contra downloads de mídia
 * Desabilita clique direito, drag e salvar em imagens e vídeos de anúncios
 */
function protectAdMediaFromDownload() {
    // Imagens: bloquear clique direito e drag
    const imageElements = document.querySelectorAll('[data-ad-image]');
    imageElements.forEach(element => {
        // Desabilitar clique direito
        element.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            return false;
        });

        // Desabilitar drag
        element.addEventListener('dragstart', (e) => {
            e.preventDefault();
            return false;
        });

        // Adicionar atributo de proteção
        element.setAttribute('data-protected', 'true');
    });

    // Vídeos: apenas configurar controlsList (bloqueia download)
    const videoElements = document.querySelectorAll('[data-ad-video]');
    videoElements.forEach(element => {
        element.setAttribute('controlsList', 'nodownload');
        element.setAttribute('data-protected', 'true');
    });

    // Observar para novos elementos adicionados ao DOM
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                // Novas imagens
                const newImageElements = mutation.target.querySelectorAll(
                    '[data-ad-image]:not([data-protected])'
                );
                newImageElements.forEach((element) => {
                    element.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        return false;
                    });
                    element.addEventListener('dragstart', (e) => {
                        e.preventDefault();
                        return false;
                    });
                    element.setAttribute('data-protected', 'true');
                });

                // Novos vídeos
                const newVideoElements = mutation.target.querySelectorAll(
                    '[data-ad-video]:not([data-protected])'
                );
                newVideoElements.forEach((element) => {
                    element.setAttribute('controlsList', 'nodownload');
                    element.setAttribute('data-protected', 'true');
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

const previewContainerSelector = '[data-preview-container]';

function handleImagePreview(input) {
    const container = document.querySelector(previewContainerSelector);
    if (!container) return;

    container.innerHTML = '';
    const files = Array.from(input.files || []);
    if (!files.length) {
        container.innerHTML = '<p class="alert alert-error">Selecione imagens para gerar a prévia.</p>';
        return;
    }

    files.slice(0, 12).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = event => {
            const img = document.createElement('img');
            img.src = event.target?.result;
            img.alt = 'Pré-visualização da imagem do anúncio';
            img.style.maxHeight = '140px';
            img.style.borderRadius = '12px';
            img.style.objectFit = 'cover';
            container.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

function toggleVideoField() {
    const checkbox = document.querySelector('[data-video-toggle]');
    const videoWrapper = document.querySelector('[data-video-wrapper]');
    if (!checkbox || !videoWrapper) return;

    const toggle = () => {
        videoWrapper.hidden = !checkbox.checked;
    };

    checkbox.addEventListener('change', toggle);
    toggle();
}

function attachEventDelegates() {
    document.addEventListener('change', event => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.matches('[data-image-input]')) {
            handleImagePreview(target);
        }
    });
}

function autoSubmitOnChange() {
    document.querySelectorAll('[data-auto-submit]').forEach(select => {
        select.addEventListener('change', () => {
            select.form?.submit();
        });
    });
}

function initAccountDropdown() {
    // Dropdown é gerenciado pelo header.php via onclick + window.toggleAccountDropdown()
    // Não adicionar listener aqui para evitar conflito com a implementação do header
}

function initMobileMenu() {
    const button = document.querySelector('[data-mobile-menu-button]');
    const panel = document.querySelector('[data-mobile-menu-panel]');
    if (!button || !panel) {
        return;
    }

    const toggle = () => {
        const isHidden = panel.classList.toggle('hidden');
        if (isHidden) {
            button.innerHTML = '<i class="fas fa-bars text-2xl"></i>';
        } else {
            button.innerHTML = '<i class="fas fa-times text-2xl"></i>';
        }
    };

    button.addEventListener('click', (event) => {
        event.stopPropagation();
        toggle();
    });

    document.addEventListener('click', (event) => {
        if (!panel.contains(event.target) && event.target !== button) {
            if (!panel.classList.contains('hidden')) {
                toggle();
            }
        }
    });
}

function setupPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        const wrapper = button.closest('.password-field');
        if (!wrapper) {
            return;
        }
        const input = wrapper.querySelector('input[data-password-toggle-target]');
        if (!input) {
            return;
        }
        button.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.innerHTML = isVisible ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            button.setAttribute('aria-label', isVisible ? 'Mostrar senha' : 'Ocultar senha');
        });
    });
}

function setupDocumentMask() {
    const documentInputs = document.querySelectorAll('[data-document-input]');
    if (!documentInputs.length) {
        return;
    }

    const sanitizeDigits = (value) => (value || '').toString().replace(/\D+/g, '');

    const formatCpf = (digits) => {
        const d = digits.slice(0, 11);
        let output = '';
        if (d.length > 0) {
            output = d.slice(0, 3);
        }
        if (d.length >= 4) {
            output += '.' + d.slice(3, 6);
        }
        if (d.length >= 7) {
            output += '.' + d.slice(6, 9);
        }
        if (d.length >= 10) {
            output += '-' + d.slice(9, 11);
        }
        return output;
    };

    const formatCnpj = (digits) => {
        const d = digits.slice(0, 14);
        let output = '';
        if (d.length > 0) {
            output = d.slice(0, 2);
        }
        if (d.length >= 3) {
            output += '.' + d.slice(2, 5);
        }
        if (d.length >= 6) {
            output += '.' + d.slice(5, 8);
        }
        if (d.length >= 9) {
            output += '/' + d.slice(8, 12);
        }
        if (d.length >= 13) {
            output += '-' + d.slice(12, 14);
        }
        return output;
    };

    documentInputs.forEach(documentInput => {
        const applyMask = () => {
            const type = documentInput.getAttribute('data-document-type') === 'cnpj' ? 'cnpj' : 'cpf';
            const digits = sanitizeDigits(documentInput.value);
            documentInput.value = type === 'cnpj' ? formatCnpj(digits) : formatCpf(digits);
        };

        const updateType = (type, shouldReset = false) => {
            const normalized = type === 'cnpj' ? 'cnpj' : 'cpf';
            documentInput.setAttribute('data-document-type', normalized);
            documentInput.maxLength = normalized === 'cnpj' ? 18 : 14;
            documentInput.placeholder = normalized === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00';
            if (shouldReset) {
                documentInput.value = '';
            }
            applyMask();
        };

        documentInput.addEventListener('input', applyMask);

        const radioName = documentInput.getAttribute('data-document-radio');
        const radios = radioName ? document.querySelectorAll(`input[name="${radioName}"]`) : [];

        radios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.checked) {
                    updateType(radio.value, true);
                }
            });
        });

        const initialType = Array.from(radios).find(radio => radio.checked)?.value || documentInput.getAttribute('data-document-type') || 'cpf';
        updateType(initialType, false);
    });
}

function setupPhoneMask() {
    const phoneInputs = document.querySelectorAll('[data-mask="phone"]');
    if (!phoneInputs.length) {
        return;
    }

    const countrySelect = document.querySelector('[data-phone-country]');
    const phoneLengths = {
        BR: 11,
        US: 10,
        PT: 9,
        AR: 10,
        PY: 10,
        UY: 9,
        ES: 9,
        FR: 10,
    };

    const placeholders = {
        BR: '(00) 00000-0000',
        US: '(000) 000-0000',
        PT: '000 000 000',
        AR: '0000-000000',
        PY: '0000-000000',
        UY: '0000 0000',
        ES: '000 000 000',
        FR: '00 00 00 00 00',
    };

    const sanitizeDigits = (value) => (value || '').toString().replace(/\D+/g, '');

    const formatPhone = (digits, iso) => {
        const cleaned = sanitizeDigits(digits);
        switch (iso) {
            case 'BR': {
                const max = cleaned.slice(0, 11);
                if (max.length === 0) {
                    return '';
                }
                if (max.length <= 2) {
                    return `(${max}`;
                }
                if (max.length <= 6) {
                    return `(${max.slice(0, 2)}) ${max.slice(2)}`;
                }
                if (max.length <= 10) {
                    return `(${max.slice(0, 2)}) ${max.slice(2, 6)}-${max.slice(6, 10)}`;
                }
                return `(${max.slice(0, 2)}) ${max.slice(2, 7)}-${max.slice(7, 11)}`;
            }
            case 'US': {
                const max = cleaned.slice(0, 10);
                if (max.length <= 3) return max;
                if (max.length <= 6) {
                    return `(${max.slice(0, 3)}) ${max.slice(3)}`;
                }
                return `(${max.slice(0, 3)}) ${max.slice(3, 6)}-${max.slice(6, 10)}`;
            }
            case 'PT':
            case 'ES': {
                const max = cleaned.slice(0, 9);
                if (max.length <= 3) return max;
                if (max.length <= 6) return `${max.slice(0, 3)} ${max.slice(3)}`;
                return `${max.slice(0, 3)} ${max.slice(3, 6)} ${max.slice(6, 9)}`.trim();
            }
            case 'FR': {
                const max = cleaned.slice(0, 10);
                return max.replace(/(\d{2})(?=\d)/g, '$1 ').trim();
            }
            default: {
                const max = cleaned.slice(0, phoneLengths[iso] || 12);
                return max.replace(/(\d{3})(?=\d)/g, '$1 ').trim();
            }
        }
    };

    const updatePlaceholder = (input, iso) => {
        const placeholder = placeholders[iso] || '000000000';
        input.setAttribute('placeholder', placeholder);
    };

    const applyMask = (input) => {
        const iso = (countrySelect?.value || 'BR').toUpperCase();
        const max = phoneLengths[iso] || 12;
        const digits = sanitizeDigits(input.value).slice(0, max);
        input.value = formatPhone(digits, iso);
        updatePlaceholder(input, iso);
    };

    phoneInputs.forEach(input => {
        input.addEventListener('input', () => applyMask(input));
        applyMask(input);
    });

    if (countrySelect) {
        countrySelect.addEventListener('change', () => {
            phoneInputs.forEach(input => applyMask(input));
        });
    }
}

function setupPostalMask() {
    const postalInputs = document.querySelectorAll('[data-mask="postal"]');
    if (!postalInputs.length) {
        return;
    }

    const countrySelect = document.querySelector('[data-phone-country]');
    const sanitizeDigits = (value) => (value || '').toString().replace(/\D+/g, '');

    const applyPostalMask = (input) => {
        const iso = (countrySelect?.value || 'BR').toUpperCase();
        const digits = sanitizeDigits(input.value);

        if (iso === 'BR') {
            const max = digits.slice(0, 8);
            const firstSegment = max.slice(0, 5);
            const secondSegment = max.slice(5, 8);
            input.value = secondSegment ? `${firstSegment}-${secondSegment}` : firstSegment;
            input.placeholder = '00000-000';
        } else {
            input.value = digits.slice(0, 12);
            input.placeholder = 'Código postal';
        }
    };

    postalInputs.forEach(input => {
        input.addEventListener('input', () => applyPostalMask(input));
        applyPostalMask(input);
    });

    if (countrySelect) {
        countrySelect.addEventListener('change', () => {
            postalInputs.forEach(input => applyPostalMask(input));
        });
    }
}

function setupCepLookup(input, selectors, options = {}) {
    const streetField = selectors.street ? document.querySelector(selectors.street) : null;
    const neighborhoodField = selectors.neighborhood ? document.querySelector(selectors.neighborhood) : null;
    const cityField = selectors.city ? document.querySelector(selectors.city) : null;
    const stateField = selectors.state ? document.querySelector(selectors.state) : null;

    const clearFields = (preserveManual = false) => {
        if (streetField && !preserveManual) streetField.value = '';
        if (neighborhoodField && !preserveManual) neighborhoodField.value = '';
        if (cityField && !preserveManual) cityField.value = '';
        if (stateField && !preserveManual) stateField.value = '';
    };

    const setLoading = (isLoading) => {
        if (!options.loadingTarget) return;
        const target = typeof options.loadingTarget === 'string'
            ? document.querySelector(options.loadingTarget)
            : options.loadingTarget;
        if (!target) return;
        target.dataset.cepLoading = isLoading ? 'true' : 'false';
    };

    const sanitizeCep = (value) => value.replace(/\D+/g, '').slice(0, 8);

    const handleError = (message) => {
        if (typeof options.onError === 'function') {
            options.onError(message);
        } else {
            input.setCustomValidity(message || 'CEP inválido');
            input.reportValidity();
        }
    };

    input.addEventListener('input', () => {
        input.setCustomValidity('');
    });

    input.addEventListener('blur', async () => {
        const rawCep = sanitizeCep(input.value);
        // Se CEP está vazio e é opcional, permitir submit
        if (rawCep.length === 0) {
            input.setCustomValidity('');
            clearFields();
            return;
        }
        if (rawCep.length !== 8) {
            handleError('Informe um CEP válido com 8 dígitos.');
            clearFields();
            return;
        }

        setLoading(true);
        try {
            const response = await fetch(`https://viacep.com.br/ws/${rawCep}/json/`);
            if (!response.ok) {
                throw new Error('Não foi possível consultar o CEP.');
            }
            const data = await response.json();
            if (data.erro) {
                throw new Error('CEP não encontrado.');
            }

            if (streetField && data.logradouro !== undefined) {
                streetField.value = data.logradouro;
            }
            if (neighborhoodField && data.bairro !== undefined) {
                neighborhoodField.value = data.bairro;
            }
            if (cityField && data.localidade !== undefined) {
                cityField.value = data.localidade;
            }
            if (stateField && data.uf !== undefined) {
                stateField.value = data.uf;
                stateField.dispatchEvent(new Event('change'));
            }
        } catch (error) {
            clearFields();
            handleError(error instanceof Error ? error.message : 'CEP inválido.');
        } finally {
            setLoading(false);
        }
    });
}

function setupServiceAddressLookup() {
    const serviceCepInput = document.querySelector('[data-service-cep]');
    if (serviceCepInput) {
        setupCepLookup(serviceCepInput, {
            street: '[data-service-logradouro]',
            neighborhood: '[name="service_address_neighborhood"]',
            city: '[data-service-cidade]',
            state: '[data-service-uf]',
        });
    }
}

function setupProfileAddressLookup() {
    const profileCepInput = document.querySelector('[data-profile-cep]');
    if (profileCepInput) {
        setupCepLookup(profileCepInput, {
            street: '[data-profile-logradouro]',
            neighborhood: '[data-profile-bairro]',
            city: '[data-profile-cidade]',
            state: '[data-profile-uf]',
        });
    }
}

function setupPriceMask() {
    const displayInput = document.querySelector('[data-price-display]');
    const hiddenInput = document.querySelector('[data-price-hidden]');

    const sanitizeDigits = (value) => (value || '').toString().replace(/[\D]/g, '');
    const formatCurrencyDigits = (digits) => {
        const amount = digits ? Number(digits) / 100 : 0;
        return amount > 0 ? amount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '';
    };

    const syncHiddenValue = () => {
        if (!displayInput || !hiddenInput) {
            return;
        }
        const digits = sanitizeDigits(displayInput.value || hiddenInput.value);
        if (!digits) {
            hiddenInput.value = '';
            displayInput.value = '';
            return;
        }
        const amount = Number(digits) / 100;
        hiddenInput.value = amount.toFixed(2);
        displayInput.value = formatCurrencyDigits(digits);
    };

    const handleInput = () => {
        if (!displayInput || !hiddenInput) {
            return;
        }
        const digits = sanitizeDigits(displayInput.value);
        const amount = digits ? Number(digits) / 100 : 0;
        hiddenInput.value = digits ? amount.toFixed(2) : '';
        const formatted = formatCurrencyDigits(digits);
        const caretOffset = formatted.length;
        displayInput.value = formatted;
        displayInput.setSelectionRange(caretOffset, caretOffset);
    };

    if (displayInput && hiddenInput) {
        window.updatePriceHidden = syncHiddenValue;
        displayInput.addEventListener('input', handleInput);
        displayInput.addEventListener('blur', syncHiddenValue);
        syncHiddenValue();
    } else {
        window.updatePriceHidden = () => {};
    }
}

function initFavoriteButtons() {
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-favorite-btn]');
        if (!btn) return;
        
        event.preventDefault();
        event.stopPropagation();
        
        const adId = btn.getAttribute('data-ad-id');
        if (!adId) return;
        
        try {
            const response = await fetch('/api/validate_favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ad_id: parseInt(adId), action: 'toggle' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                btn.classList.toggle('is-active', data.is_favorite);
            }
        } catch (error) {
            console.error('Erro ao favoritar:', error);
        }
    });
}

function initServiceCatalogFilters() {
    const form = document.querySelector('[data-service-filters]');
    const list = document.querySelector('[data-service-list]');
    if (!form || !list) {
        return;
    }

    const cards = Array.from(list.querySelectorAll('[data-service-card]'));
    if (!cards.length) {
        return;
    }

    const searchInput = form.querySelector('[data-service-search]');
    const categorySelect = form.querySelector('select[name="category"]');
    const cityInput = form.querySelector('input[name="city"]');
    const stateSelect = form.querySelector('select[name="state"]');
    const whatsappCheckbox = form.querySelector('input[name="has_whatsapp"]');
    const resultsLabel = document.querySelector('[data-service-results-label]');
    const defaultResultsLabel = resultsLabel ? resultsLabel.textContent : '';
    const totalCards = cards.length;
    const emptyState = document.querySelector('[data-service-empty]');

    const normalize = (value) => {
        const str = (value ?? '').toString().trim();
        if (!str) {
            return '';
        }
        try {
            return str
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
        } catch (_error) {
            return str.toLowerCase();
        }
    };

    const cardMeta = cards.map((card) => {
        const dataset = card.dataset;
        return {
            el: card,
            name: normalize(dataset.serviceName),
            categoryKey: dataset.serviceCategory || '',
            categoryLabel: normalize(dataset.serviceCategoryLabel),
            custom: normalize(dataset.serviceCustom),
            city: normalize(dataset.serviceCity),
            state: (dataset.serviceState || '').toUpperCase(),
            location: normalize(dataset.serviceLocation),
            hasWhatsapp: dataset.serviceHasWhatsapp === '1',
        };
    });

    const cityDatalist = document.getElementById('service_city_suggestions');
    let citySuggestionsData = {};
    const cityDataElement = document.getElementById('service-city-suggestions-data');
    if (cityDataElement) {
        try {
            const parsed = JSON.parse(cityDataElement.textContent || '{}');
            if (parsed && typeof parsed === 'object') {
                citySuggestionsData = parsed;
            }
        } catch (error) {
            console.warn('Nao foi possivel analisar as sugestoes de cidades:', error);
        }
    }

    const getCitySuggestions = (stateValue) => {
        const normalizedState = (stateValue || '').toString().toUpperCase();
        if (normalizedState && Array.isArray(citySuggestionsData[normalizedState])) {
            return citySuggestionsData[normalizedState];
        }
        if (Array.isArray(citySuggestionsData.__all)) {
            return citySuggestionsData.__all;
        }
        return [];
    };

    const renderCityOptions = (stateValue) => {
        if (!cityDatalist) {
            return;
        }
        const suggestions = getCitySuggestions(stateValue);
        cityDatalist.innerHTML = '';
        if (!suggestions.length) {
            return;
        }
        const fragment = document.createDocumentFragment();
        suggestions.forEach((cityName) => {
            const option = document.createElement('option');
            option.value = cityName;
            fragment.appendChild(option);
        });
        cityDatalist.appendChild(fragment);
    };

    const updateResultsLabel = (visibleCount) => {
        if (!resultsLabel) {
            return;
        }
        if (Number.isNaN(visibleCount)) {
            return;
        }
        if (visibleCount === totalCards && defaultResultsLabel) {
            resultsLabel.textContent = defaultResultsLabel;
            return;
        }
        resultsLabel.textContent = visibleCount === 1
            ? '1 serviço filtrado'
            : `${visibleCount} serviços filtrados`;
    };

    const applyFilters = () => {
        const searchTerm = normalize(searchInput ? searchInput.value : '');
        const categoryValue = categorySelect ? (categorySelect.value || '') : '';
        const cityTerm = normalize(cityInput ? cityInput.value : '');
        const stateValue = stateSelect ? (stateSelect.value || '') : '';
        const whatsappOnly = whatsappCheckbox ? whatsappCheckbox.checked : false;

        let visibleCount = 0;

        cardMeta.forEach((meta) => {
            let matches = true;

            if (matches && searchTerm) {
                matches = meta.name.includes(searchTerm)
                    || meta.categoryLabel.includes(searchTerm)
                    || meta.custom.includes(searchTerm);
            }

            if (matches && categoryValue) {
                matches = meta.categoryKey === categoryValue;
            }

            if (matches && cityTerm) {
                matches = meta.city.includes(cityTerm) || meta.location.includes(cityTerm);
            }

            if (matches && stateValue) {
                matches = meta.state === stateValue.toUpperCase();
            }

            if (matches && whatsappOnly) {
                matches = meta.hasWhatsapp;
            }

            meta.el.classList.toggle('hidden', !matches);
            meta.el.setAttribute('aria-hidden', matches ? 'false' : 'true');

            if (matches) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount !== 0);
        }
        updateResultsLabel(visibleCount);
    };

    renderCityOptions(stateSelect ? stateSelect.value : '');

    const liveInputs = [searchInput, cityInput];
    liveInputs.forEach((input) => {
        if (input) {
            input.addEventListener('input', applyFilters);
        }
    });

    if (categorySelect) {
        let lastCategoryValue = categorySelect.value || '';
        categorySelect.addEventListener('change', () => {
            const selectedCategory = categorySelect.value || '';
            // Atualizar URL com a categoria selecionada (ou limpar se vazio)
            const url = new URL(window.location);
            if (selectedCategory) {
                url.searchParams.set('category', selectedCategory);
            } else {
                url.searchParams.delete('category');
            }
            window.history.replaceState({}, '', url);

            // Reaplica filtros no front (nome/categoria via busca, estado, cidade, WhatsApp)
            applyFilters();

            // Se a categoria mudou (inclusive de vazio -> preenchida), recarrega para backend aplicar filtro
            if (selectedCategory !== lastCategoryValue || selectedCategory || lastCategoryValue) {
                lastCategoryValue = selectedCategory;
                window.location.href = url.toString();
            }
        });
    }

    if (whatsappCheckbox) {
        whatsappCheckbox.addEventListener('change', applyFilters);
    }

    if (stateSelect) {
        stateSelect.addEventListener('change', () => {
            renderCityOptions(stateSelect.value);
            applyFilters();
        });
    }

    form.addEventListener('reset', () => {
        window.setTimeout(() => {
            renderCityOptions(stateSelect ? stateSelect.value : '');
            applyFilters();
        }, 0);
    });

    // Previne submit do formulário (filtro é dinâmico apenas)
    form.addEventListener('submit', (e) => {
        e.preventDefault();
    });

    applyFilters();
}

function setupServiceDescriptionCounter() {
    const textarea = document.querySelector('[data-word-counter]');
    if (!textarea) {
        return;
    }

    const updateCounter = () => {
        const text = textarea.value;
        const charCount = text.length;
        const counterEl = document.getElementById('word-count');
        if (counterEl) {
            const color = charCount > 150 ? '#dc2626' : '#136f33';
            counterEl.textContent = `${charCount} / 150 caracteres`;
            counterEl.style.color = color;
        }
    };

    textarea.addEventListener('input', updateCounter);
    updateCounter();
}

function init() {
    attachEventDelegates();
    toggleVideoField();
    autoSubmitOnChange();
    initAccountDropdown();
    initMobileMenu();
    setupPasswordToggles();
    setupDocumentMask();
    setupPhoneMask();
    setupPostalMask();
    setupServiceAddressLookup();
    setupProfileAddressLookup();
    setupPriceMask();
    setupServiceDescriptionCounter();
    initFavoriteButtons();
    initServiceCatalogFilters();
    protectAdMediaFromDownload();
}

document.addEventListener('DOMContentLoaded', init);
