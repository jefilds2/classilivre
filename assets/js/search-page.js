(() => {
    const CAROUSEL_INTERVAL = 5000;
    const carouselTimers = new WeakMap();
    const VIEW_STORAGE_KEY = 'search_view_mode';

    function normalizeString(value) {
        return (value || '').toString().trim();
    }

    function initAccountDropdown() {
        // Dropdown is managed by header.php via onclick + window.toggleAccountDropdown()
        // Don't add listener here to avoid conflict with header implementation
    }

    function initTypeSelector() {
        const selector = document.querySelector('[data-type-selector]');
        const hiddenInput = document.querySelector('[data-type-input]');
        if (!selector || !hiddenInput) return;

        const buttons = Array.from(selector.querySelectorAll('button[data-type]'));
        const setActive = (type) => {
            selector.dataset.selected = type;
            hiddenInput.value = type;
            buttons.forEach((button) => {
                if (button.dataset.type === type) {
                    button.classList.add('ativo');
                } else {
                    button.classList.remove('ativo');
                }
            });
        };

        const brandInput = document.querySelector('#marcaInput');
        const modelInput = document.querySelector('#modeloInput');

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                setActive(button.dataset.type || 'todos');

                if (brandInput) {
                    brandInput.value = '';
                    brandInput.dispatchEvent(new Event('input'));
                }

                if (modelInput) {
                    modelInput.value = '';
                    modelInput.dispatchEvent(new Event('input'));
                }

                refreshBrandOptions();
            });
        });
    }

    function buildSuggestions(container, items) {
        if (!container) return;
        if (!items.length) {
            container.classList.add('hidden');
            container.innerHTML = '';
            return;
        }
        container.innerHTML = items.map((item) => `<div data-value="${item}">${item}</div>`).join('');
        container.classList.remove('hidden');
    }

    function initCityAutocomplete() {
        // Tenta primeiro os seletores do listar.php
        let input = document.querySelector('#city');
        let suggestions = document.querySelector('#cidadeSuggestions');
        
        // Se não encontrar, tenta os seletores da homepage
        if (!input || !suggestions) {
            input = document.querySelector('[data-city-input]');
            suggestions = document.querySelector('[data-city-suggestions]');
        }
        
        if (!input || !suggestions) return;

        // Se setupAutocompleteElements estiver disponível (em listar.php), usar ela
        if (typeof window.setupAutocompleteElements === 'function' && window.cidadesProximas) {
            console.log('[SEARCH-PAGE] Usando setupAutocompleteElements para city autocomplete');
            window.setupAutocompleteElements(input, suggestions, window.cidadesProximas, null);
            return;
        }

        // Fallback para implementação local (para casos onde setupAutocompleteElements não está disponível)
        const baseCities = Array.isArray(window.cidadesProximas) ? window.cidadesProximas : [];
        const locations = Array.from(new Set(baseCities));
        const maxSuggestions = 8;

        const renderSuggestions = (rawValue) => {
            const value = normalizeString(rawValue).toLowerCase();
            const filtered = value
                ? locations.filter((item) => item.toLowerCase().startsWith(value))
                : []; // Não mostrar sugestões ao focar com campo vazio
            buildSuggestions(suggestions, filtered.slice(0, maxSuggestions));
        };

        input.addEventListener('input', () => {
            renderSuggestions(input.value);
        });

        input.addEventListener('focus', () => {
            renderSuggestions(input.value);
        });

        suggestions.addEventListener('mousedown', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            const value = target.dataset.value;
            if (!value) return;
            input.value = value;
            suggestions.classList.add('hidden');
            suggestions.innerHTML = '';
        });

        document.addEventListener('click', (event) => {
            if (!suggestions.contains(event.target) && event.target !== input) {
                suggestions.classList.add('hidden');
            }
        });
    }


    function getSelectedType() {
        const selector = document.querySelector('[data-type-selector]');
        return selector?.dataset.selected || 'todos';
    }

    function getBrandList() {
        const type = getSelectedType();
        const source = window.marcasModelos || {};
        if (type && type !== 'todos' && source[type]) {
            return Object.keys(source[type]);
        }
        const brands = new Set();
        Object.values(source).forEach((group) => {
            Object.keys(group || {}).forEach((brand) => brands.add(brand));
        });
        return Array.from(brands);
    }

    function getModelList(brandValue) {
        const type = getSelectedType();
        const source = window.marcasModelos || {};
        let models = [];
        if (type && type !== 'todos' && source[type] && source[type][brandValue]) {
            models = source[type][brandValue];
        } else {
            Object.values(source).forEach((group) => {
                if (group && group[brandValue]) {
                    models = models.concat(group[brandValue]);
                }
            });
        }
        return Array.from(new Set(models));
    }

    function uniqueSorted(list) {
        return Array.from(new Set(list)).sort((a, b) => a.localeCompare(b, 'pt-BR'));
    }

    function attachSuggestionBehavior(input, suggestions, getItems) {
        if (!input || !suggestions) return;
        const maxSuggestions = 8;

        input.addEventListener('input', () => {
            const value = normalizeString(input.value).toLowerCase();
            const items = uniqueSorted(getItems());
            if (!value) {
                buildSuggestions(suggestions, items.slice(0, maxSuggestions));
                return;
            }
            const filtered = items.filter((item) => item.toLowerCase().startsWith(value)).slice(0, maxSuggestions);
            buildSuggestions(suggestions, filtered);
        });

        suggestions.addEventListener('mousedown', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            const value = target.dataset.value;
            if (!value) return;
            input.value = value;
            suggestions.classList.add('hidden');
            suggestions.innerHTML = '';
            input.dispatchEvent(new Event('change'));
        });

        document.addEventListener('click', (event) => {
            if (!suggestions.contains(event.target) && event.target !== input) {
                suggestions.classList.add('hidden');
            }
        });
    }

    function refreshBrandOptions() {
        const brandInput = document.querySelector('#marcaInput');
        if (!brandInput) return;
        brandInput.dispatchEvent(new Event('input'));
        refreshModelOptions();
    }

    function refreshModelOptions() {
        const modelInput = document.querySelector('#modeloInput');
        if (!modelInput) return;
        modelInput.dispatchEvent(new Event('input'));
    }

    function initBrandModelAutocomplete() {
        const brandInput = document.querySelector('#marcaInput');
        const brandSuggestions = document.querySelector('[data-brand-suggestions]');
        const modelInput = document.querySelector('#modeloInput');
        const modelSuggestions = document.querySelector('[data-model-suggestions]');
        if (!brandInput || !brandSuggestions || !modelInput || !modelSuggestions) return;

        attachSuggestionBehavior(brandInput, brandSuggestions, () => getBrandList());
        attachSuggestionBehavior(modelInput, modelSuggestions, () => getModelList(normalizeString(brandInput.value)));

        brandInput.addEventListener('change', () => {
            refreshModelOptions();
        });
    }

    function initSortSelect() {
        const select = document.querySelector('[data-sort-select]');
        if (!select) return;
        select.addEventListener('change', () => {
            const params = new URLSearchParams(window.location.search);
            params.set('sort', select.value);
            params.delete('page');
            window.location.search = params.toString();
        });
    }

    function initPriceSanitizer() {
        const priceInputs = document.querySelectorAll('input[name="price_min"], input[name="price_max"]');
        priceInputs.forEach((input) => {
            input.addEventListener('input', () => {
                const digits = input.value.replace(/\D+/g, '');
                input.value = digits;
            });
        });

        const form = document.querySelector('[data-filter-form]');
        if (!form) return;
        form.addEventListener('submit', () => {
            priceInputs.forEach((input) => {
                input.value = input.value.replace(/\D+/g, '');
            });
        });
    }

    function initViewToggle() {
        const container = document.getElementById('cardsContainer');
        const toggleWrapper = document.querySelector('[data-view-toggle]');
        if (!container || !toggleWrapper) return;

        const buttons = Array.from(toggleWrapper.querySelectorAll('button[data-view]'));
        const applyView = (view) => {
            buttons.forEach((button) => {
                button.classList.toggle('active', button.dataset.view === view);
            });
            container.classList.toggle('list-view', view === 'list');
            try {
                window.localStorage.setItem(VIEW_STORAGE_KEY, view);
            } catch (error) {
                /* ignore */
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                applyView(button.dataset.view || 'grid');
            });
        });

        let storedView = 'grid';
        try {
            storedView = window.localStorage.getItem(VIEW_STORAGE_KEY) || 'grid';
        } catch (error) {
            storedView = 'grid';
        }
        applyView(storedView);
    }

    function initCarousels(root = document) {
        const cards = root.querySelectorAll('[data-images]');
        cards.forEach((card) => {
            let images = [];
            try {
                const raw = card.getAttribute('data-images') || '[]';
                images = JSON.parse(raw);
            } catch (error) {
                images = [];
            }

            if (!Array.isArray(images) || images.length <= 1) {
                if (carouselTimers.has(card)) {
                    clearInterval(carouselTimers.get(card));
                    carouselTimers.delete(card);
                }
                return;
            }

            const imgElement = card.querySelector('[data-primary-image]');
            if (!imgElement) return;

            if (carouselTimers.has(card)) {
                clearInterval(carouselTimers.get(card));
            }

            let current = 0;
            const intervalId = window.setInterval(() => {
                current = (current + 1) % images.length;
                imgElement.src = images[current];
            }, CAROUSEL_INTERVAL);

            carouselTimers.set(card, intervalId);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initAccountDropdown();
        initTypeSelector();
        initCityAutocomplete();
        initBrandModelAutocomplete();
        initSortSelect();
        initPriceSanitizer();
        initViewToggle();
        initCarousels();
        
        // Retry initCityAutocomplete se não tiver funcionado na primeira vez
        // (esperando que cidadesProximas estejam carregadas)
        setTimeout(() => {
            const cityInput = document.querySelector('#city');
            const citySuggestions = document.querySelector('#cidadeSuggestions');
            
            // Se o autocomplete não foi inicializado (não há listener), tenta novamente
            if (cityInput && citySuggestions && !citySuggestions.dataset.initialized) {
                console.log('[SEARCH-PAGE] Retry: Inicializando autocomplete de localização');
                initCityAutocomplete();
            }
        }, 500);
    });
    
    // Expor funcções globalmente para permitir reinicinalizações
    window.reinitCityAutocomplete = initCityAutocomplete;
    window.initCarousels = initCarousels;
})();



