// assets/js/autocomplete.js - Sugestões e preenchimentos dinâmicos para formulários de anúncio.

const tiposCambio = {
    carro: ["Manual", "Automático", "Automatizado", "CVT", "Dualogic", "Tiptronic", "DSG"],
    moto: ["Manual", "Automático", "Semiautomático", "CVT"],
    caminhao: ["Manual", "Automatizado", "Automático", "Híbrido"],
    outros: ["Manual", "Automático"]
};

// Cache para cidades (será preenchido via API)
let cidadesCache = null;

/**
 * Busca cidades de MG via API
 * @param {string} search - Termo de busca (opcional)
 * @returns {Promise<string[]>} Array de cidades
 */
async function fetchCidades(search = '') {
    try {
        const url = search 
            ? `/api/get_cities.php?q=${encodeURIComponent(search)}`
            : `/api/get_cities.php`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && Array.isArray(data.cities)) {
            return data.cities;
        }
        return [];
    } catch (error) {
        console.error('[CITIES] Erro ao buscar cidades:', error);
        return [];
    }
}

/**
 * Pré-carrega todas as cidades para melhor performance
 */
async function preloadCidades() {
    if (!cidadesCache) {
        cidadesCache = await fetchCidades();
    }
    return cidadesCache;
}

const marcasModelos = {
    carro: {
        "Volkswagen": ["Gol", "Polo", "Virtus", "T-Cross", "Nivus", "Saveiro", "Constellation", "Meteor", "Delivery", "Fusca", "Kombi", "Brasília", "Variant", "TL", "SP2", "Passat", "Santana", "Parati", "Voyage", "Gol GTi", "Fox", "SpaceFox", "CrossFox", "Golf", "Bora", "Jetta", "Quantum", "Pointer", "Logus"],
        "Fiat": ["Uno", "Mobi", "Argo", "Cronos", "Toro", "Fiorino", "Ducato", "147", "Uno Mille", "Prêmio", "Elba", "Oggi", "Palio", "Siena", "Weekend", "Idea", "Stilo", "Brava", "Marea", "Tempra", "Doblo", "Linea"],
        "Chevrolet": ["Onix", "Prisma", "Cruze", "Tracker", "S10", "Montana", "Opala", "Caravan", "Chevette", "Monza", "Kadett", "Veraneio", "D-20", "C-10", "Astra", "Vectra", "Omega", "Corsa", "Celta", "Classic", "Zafira", "Meriva", "Spin", "Captiva", "Blazer"],
        "Ford": ["Ka", "Fiesta", "Focus", "EcoSport", "Ranger", "Cargo", "Transit", "Maverick", "Corcel", "Del Rey", "Belina", "Escort XR3", "Pampa", "F-1000", "F-75", "Verona", "Fusion"],
        "Toyota": ["Corolla", "Etios", "Hilux", "SW4", "Yaris", "Bandeirante"],
        "Honda": ["Civic", "Fit", "HR-V", "City", "CR-V"],
        "Hyundai": ["HB20", "Creta", "HB20S", "Tucson", "ix35", "i30", "Azera"],
        "Renault": ["Kwid", "Sandero", "Logan", "Duster", "Captur", "Master", "Oroch", "Kangoo", "Clio", "Symbol", "Scenic", "Megane", "Fluence"],
        "Jeep": ["Renegade", "Compass", "Cherokee", "Grand Cherokee", "CJ-5", "Willys"],
        "Nissan": ["March", "Versa", "Kicks", "Sentra", "Frontier"],
        "BMW": ["Série 1", "Série 3", "X1", "X3", "X5"],
        "Mercedes-Benz": ["Classe A", "Classe C", "GLA", "GLC", "GLE"],
        "Peugeot": ["208", "2008", "Partner", "Rifter", "Landtrek", "206", "207", "307", "308", "408"],
        "Citroën": ["C3", "C4 Cactus", "Jumpy", "Jumper", "Berlingo", "C4", "Xsara", "Picasso"],
        "Chery": ["Tiggo 3X", "Tiggo 5X", "Tiggo 7 Pro", "Tiggo 8", "Arrizo 6", "Celer"],
        "Mitsubishi": ["ASX", "Outlander", "Eclipse Cross", "Pajero", "L200 Triton", "Lancer"],
        "Subaru": ["Forester", "XV", "Outback", "WRX", "Impreza"],
        "Land Rover": ["Range Rover Evoque", "Range Rover Velar", "Range Rover Sport", "Discovery Sport", "Defender"],
        "Audi": ["A3", "A4", "A5", "Q3", "Q5", "Q7"],
        "Porsche": ["Macan", "Cayenne", "Panamera", "Taycan", "911"],
        "Mini": ["Cooper", "Countryman", "Clubman"],
        "Volvo": ["XC40", "XC60", "XC90", "S60", "S90"],
        "JAC": ["T40", "T50", "T60", "iEV40", "iEV330P", "J3"],
        "Ram": ["1500", "2500", "3500"],
        "BYD": ["Dolphin", "Yuan Plus", "Seal", "Song Plus", "Han"],
        "Tesla": ["Model 3", "Model Y", "Model S", "Model X"],
        "Dodge": ["Dart", "Charger R/T", "Polara"],
        "Gurgel": ["X12", "BR-800"],
        "Puma": ["GT", "GTE", "GTS"],
        "MP Lafer": ["MP Lafer"],
        "Willys": ["Rural"],
        "Geely": ["GC2"],
        "Kia": ["Picanto", "Cerato", "Sportage"]
    },
    moto: {
        "Yamaha": ["Factor 150", "Fazer FZ25", "Fazer 250", "Lander 250", "Crosser 150", "XTZ 250", "XTZ 125", "XTZ 150 Crosser", "MT-03", "MT-07", "MT-09", "MT-10", "Tracer 900", "NMax 160", "Neo 125", "Grizzly", "Kodiak", "Wolverine", "RD 350", "DT 180", "DT 200R", "TDM 225", "XT 225", "XT 660", "Fazer 150", "Ténéré 250", "Ténéré 660", "R3", "R6", "R1", "Versys 300"],
        "Harley-Davidson": ["Sportster S", "Nightster", "Iron 883", "Fat Bob", "Heritage Classic", "Road Glide", "Street Glide", "Pan America", "Sportster 883", "Sportster 1200", "Night Rod", "V-Rod", "Fat Boy", "Breakout"],
        "Kawasaki": ["Z400", "Z650", "Z900", "Ninja 400", "Ninja 650", "Ninja ZX-6R", "ZX-10R", "ZX-14R", "Versys 650", "Versys 1000", "Vulcan S", "Ninja 250R", "Ninja 300", "ER-6n", "Versys 300", "Z300"],
        "Suzuki": ["GSX-S750", "V-Strom 650", "V-Strom 1050", "GSX-R1000", "Burgman 125", "Burgman 400", "Haojue DK 150", "Haojue NK 150", "Bandit 650", "Bandit 1250", "Yes 125", "Intruder 125", "V-Strom 250", "V-Strom 1000", "Hayabusa 1300", "GS 500", "GSR 750"],
        "Shineray": ["Jet 125", "Phoenix 50", "XY 125", "XY 150", "XY 200", "Street 50", "Street 150", "XY 50", "XY 110", "Explorer 150", "Explorer 180", "XY 250GY"],
        "Honda": ["CG 160 Fan", "CG 160 Titan", "CG 160 Start", "Biz 110i", "Biz 125", "Pop 110i", "CB 250F Twister", "CB 500F", "CB 500X", "CBR 500R", "XRE 190", "XRE 300", "Africa Twin", "CB 650R", "CBR 650R", "Hornet 600", "CB 1000R", "CBR 1000RR", "Shadow 750"],
        "BMW": ["G 310 R", "G 310 GS", "F 750 GS", "F 850 GS", "S 1000 R", "S 1000 RR", "R 1250 GS", "R 1250 RT", "R 18"],
        "Dafra": ["Citycom 300i", "Next 300", "Apache RTR 200", "Cruisym 150", "Fiddle III", "Maxsym 400i"]
    },
    caminhao: {
        "Mercedes-Benz": ["Accelo 815", "Accelo 1016", "Atego 1719", "Atego 2426", "Axor 2544", "Axor 3344", "Actros 2651"],
        "Volkswagen": ["Delivery 6.160", "Delivery 9.170", "Constellation 24.280", "Constellation 19.360", "Constellation 33.460", "Meteor 29.520"],
        "Scania": ["P310", "P360", "R420", "R440", "R500", "R620"],
        "Volvo": ["VM 270", "VM 330", "FH 440", "FH 460", "FH 540", "FMX 500"],
        "Iveco": ["Tector 170E21", "Tector 240E28", "Hi-Way 440", "Hi-Road 380"],
        "Ford": ["Cargo 1119", "Cargo 1719", "Cargo 2429", "Cargo 2842"],
        "DAF": ["XF105", "XF530", "CF85"],
        "MAN": ["TGX 29.480", "TGS 26.440"]
    },
    outros: {
        "Agrale": ["Marruá AM200", "Marruá AM300", "Marruá AM400"],
        "John Deere": ["5310", "5405", "5600", "6110J", "6115J", "6115M", "6125J", "6130J", "6150J", "6170J"],
        "Valmet": ["88", "98", "985", "128", "138", "148", "158"],
        "Case": ["580N", "580M", "SV185", "Farmall 80", "Farmall 90", "Farmall 100"]
    }
};

const typeField = document.getElementById('vehicle_type') || document.getElementById('tipo') || null;
const brandField = document.getElementById('brand') || document.getElementById('marca') || null;
const modelField = document.getElementById('model') || document.getElementById('modelo') || null;
const cityField = document.getElementById('city') || document.getElementById('cidade') || null;
const transmissionField = document.getElementById('transmission') || document.getElementById('cambio') || null;

const brandSuggestions = document.getElementById('marcaSuggestions') || document.getElementById('brandSuggestions') || null;
const modelSuggestions = document.getElementById('modeloSuggestions') || document.getElementById('modelSuggestions') || null;
const citySuggestions = document.getElementById('cidadeSuggestions') || document.getElementById('citySuggestions') || null;

function setupAutocompleteElements(input, suggestions, dataList, callback) {
    if (!input || !suggestions) {
        console.warn('[AUTOCOMPLETE] Input ou suggestions não encontrados');
        return;
    }

    // Remove duplicatas do array (caso tenha sido passado com itens duplicados)
    const uniqueDataList = Array.from(new Set(dataList));
    console.log('[AUTOCOMPLETE] DataList original:', dataList.length, '| Após remover duplicatas:', uniqueDataList.length);
    
    // Limpar conteúdo anterior (importante para evitar duplicatas)
    suggestions.innerHTML = '';
    suggestions.classList.add('hidden');

    const refreshPosition = () => {
        try {
            // Verificar se está dentro de um drawer (mobile) ou não
            const isInDrawer = suggestions.closest('.filters-drawer-content') !== null;
            
            if (isInDrawer) {
                // Mobile: posicionar com fixed dentro do drawer
                const inputRect = input.getBoundingClientRect();
                suggestions.style.position = 'fixed';
                suggestions.style.left = `${inputRect.left}px`;
                suggestions.style.top = `${inputRect.top + inputRect.height + 4}px`;
                suggestions.style.width = `${inputRect.width}px`;
                suggestions.style.minWidth = `${inputRect.width}px`;
                console.log('[AUTOCOMPLETE] Posição (Mobile/Drawer):', {
                    left: inputRect.left,
                    top: inputRect.top + inputRect.height + 4,
                    width: inputRect.width
                });
            } else {
                // Desktop: posicionar com absolute relativo ao pai
                const inputRect = input.getBoundingClientRect();
                const parentElement = suggestions.parentElement;
                
                if (!parentElement) {
                    console.warn('[AUTOCOMPLETE] Parent element não encontrado');
                    return;
                }
                
                const parentRect = parentElement.getBoundingClientRect();
                
                const left = inputRect.left - parentRect.left;
                const top = inputRect.top - parentRect.top + inputRect.height + 4;
                const width = inputRect.width;
                
                suggestions.style.position = 'absolute';
                suggestions.style.left = `${left}px`;
                suggestions.style.top = `${top}px`;
                suggestions.style.width = `${width}px`;
                suggestions.style.minWidth = `${width}px`;
                suggestions.style.right = 'auto';
                
                console.log('[AUTOCOMPLETE] Posição (Desktop):', {
                    left,
                    top,
                    width
                });
            }
        } catch (err) {
            console.error('[AUTOCOMPLETE] Erro ao atualizar posição:', err);
        }
    };

    input.setAttribute('autocomplete', 'off');

    if (input._autocompleteHandler) {
        input.removeEventListener('input', input._autocompleteHandler);
    }

    if (input._autocompleteReposition) {
        window.removeEventListener('resize', input._autocompleteReposition);
        window.removeEventListener('scroll', input._autocompleteReposition, true);
    }

    const handler = () => {
        const value = input.value.trim().toLowerCase();
        suggestions.innerHTML = '';

        console.log('[AUTOCOMPLETE] Input digitado:', { value, dataListLength: uniqueDataList.length });

        if (!value) {
            suggestions.classList.add('hidden');
            return;
        }

        const filtered = uniqueDataList.filter((item) => item.toLowerCase().startsWith(value));
        
        console.log('[AUTOCOMPLETE] Filtrados:', filtered.length, 'de', uniqueDataList.length);

        filtered.forEach((item) => {
            const div = document.createElement('div');
            div.textContent = item;
            div.onclick = () => {
                input.value = item;
                suggestions.innerHTML = '';
                suggestions.classList.add('hidden');
                if (callback) callback(item);
                const inputEvent = new Event('input', { bubbles: true });
                input.dispatchEvent(inputEvent);
                const changeEvent = new Event('change', { bubbles: true });
                input.dispatchEvent(changeEvent);
            };
            div.className = 'autocomplete-option';
            suggestions.appendChild(div);
        });

        if (filtered.length > 0) {
            refreshPosition();
            suggestions.classList.remove('hidden');
            console.log('[AUTOCOMPLETE] Sugestões exibidas:', filtered.length);
        } else {
            suggestions.classList.add('hidden');
            console.log('[AUTOCOMPLETE] Nenhuma sugestão encontrada');
        }
    };

    input._autocompleteHandler = handler;
    input.addEventListener('input', handler);
    input.addEventListener('focus', () => {
        console.log('[AUTOCOMPLETE] Focus no input:', input.id);
        refreshPosition();
    });
    
    console.log('[AUTOCOMPLETE] Setup completo para:', input.id);
    suggestions.dataset.initialized = 'true';

    const repositionListener = () => {
        if (!suggestions.classList.contains('hidden')) {
            refreshPosition();
        }
    };
    input._autocompleteReposition = repositionListener;
    window.addEventListener('resize', repositionListener);
    window.addEventListener('scroll', repositionListener, true);

    if (!input._autocompleteOutsideHandler) {
        const outsideHandler = (e) => {
            if (e.target !== input && !suggestions.contains(e.target)) {
                suggestions.innerHTML = '';
                suggestions.classList.add('hidden');
            }
        };
        input._autocompleteOutsideHandler = outsideHandler;
        document.addEventListener('click', outsideHandler);
    }
}

function atualizarModelos(tipo, marcaSelecionada) {
    // Se tipo é 'todos', procurar a marca em todos os tipos
    let modelos = [];
    
    if (!tipo || tipo === 'todos') {
        // Procurar a marca em todos os tipos disponíveis
        for (const typeKey in marcasModelos) {
            if (marcasModelos[typeKey] && marcasModelos[typeKey][marcaSelecionada]) {
                modelos = marcasModelos[typeKey][marcaSelecionada];
                break;
            }
        }
    } else {
        modelos = (marcasModelos[tipo] && marcasModelos[tipo][marcaSelecionada]) || [];
    }
    
    setupAutocompleteElements(modelField, modelSuggestions, modelos, null);
}

function atualizarOpcoesCambio(tipo, preservarValor = false) {
    if (!transmissionField) {
        return;
    }

    const valorAtual = preservarValor ? transmissionField.value : '';
    transmissionField.innerHTML = '<option value="">Todos</option>';

    // Se tipo é 'todos' ou não definido, mostrar todas as opções de câmbio sem repetição
    let opcoesCambio;
    if (!tipo || tipo === 'todos') {
        // Coletar todas as opções de câmbio de todos os tipos, sem repetição
        const allTransmissions = new Set();
        Object.keys(tiposCambio).forEach(key => {
            if (tiposCambio[key]) {
                tiposCambio[key].forEach(transmission => {
                    allTransmissions.add(transmission);
                });
            }
        });
        opcoesCambio = Array.from(allTransmissions).sort();
    } else {
        opcoesCambio = tiposCambio[tipo] || tiposCambio.outros;
    }
    
    opcoesCambio.forEach((opcao) => {
        const option = document.createElement('option');
        option.value = opcao;
        option.textContent = opcao;
        if (valorAtual === opcao) {
            option.selected = true;
        }
        transmissionField.appendChild(option);
    });
}

function atualizarAutocompletePorTipo(resetFields = false) {
    if (!typeField) {
        return;
    }

    const tipo = typeField.value || 'carro';

    if (resetFields) {
        if (brandField) brandField.value = '';
        if (modelField) modelField.value = '';
        if (transmissionField) transmissionField.value = '';
    }

    // Se tipo é 'todos', mostrar todas as marcas de todos os tipos sem repetição
    let marcas;
    if (!tipo || tipo === 'todos') {
        const allBrands = new Set();
        Object.keys(marcasModelos).forEach(key => {
            if (marcasModelos[key]) {
                Object.keys(marcasModelos[key]).forEach(brand => {
                    allBrands.add(brand);
                });
            }
        });
        marcas = Array.from(allBrands).sort();
    } else {
        marcas = marcasModelos[tipo] ? Object.keys(marcasModelos[tipo]) : [];
    }

    setupAutocompleteElements(brandField, brandSuggestions, marcas, (marcaSelecionada) => {
        atualizarModelos(tipo, marcaSelecionada);
    });

    setupAutocompleteElements(modelField, modelSuggestions, [], null);
    if (!resetFields && brandField?.value) {
        atualizarModelos(tipo, brandField.value);
    }
    atualizarOpcoesCambio(tipo, !resetFields);  // Preservar valor apenas se não estiver resetando
}

// Exportar função globalmente para ser acessível de listar.php
window.atualizarAutocompletePorTipo = atualizarAutocompletePorTipo;

function inicializarAutocomplete() {
    if (!typeField) {
        return;
    }

    atualizarAutocompletePorTipo(false);
    
    // Inicializar autocomplete para campo de cidade
    if (cityField && citySuggestions) {
        preloadCidades().then(cidades => {
            setupAutocompleteElements(cityField, citySuggestions, cidades, null);
        });
    }
}

/**
 * Reinicializa o autocomplete para inputs clonados no drawer (mobile)
 * Esta função é chamada após o formulário ser clonado para o drawer
 */
function setupAutocompleteForDrawer() {
    try {
        // Encontrar o drawer e os inputs clonados
        const drawer = document.getElementById('filtersDrawer');
        if (!drawer) {
            console.warn('[AUTOCOMPLETE] Drawer não encontrado');
            return;
        }

        const drawerForm = drawer.querySelector('form');
        if (!drawerForm) {
            console.warn('[AUTOCOMPLETE] Formulário no drawer não encontrado');
            return;
        }

        // Configurar autocomplete para brand no drawer
        const drawerBrand = drawerForm.querySelector('[name="brand"]');
        const drawerBrandSuggestions = drawerForm.querySelector('#marcaSuggestions');
        
        if (drawerBrand && drawerBrandSuggestions) {
            console.log('[AUTOCOMPLETE] Configurando brand no drawer');
            
            // Obter o tipo de veículo selecionado no drawer
            let selectedType = 'carro'; // padrão
            const typeButtons = drawerForm.querySelectorAll('[class*="tipo"], button');
            typeButtons.forEach(btn => {
                if (btn.classList.contains('bg-orange-500') || btn.classList.contains('active')) {
                    const btnText = btn.textContent.trim().toLowerCase();
                    if (btnText === 'moto') selectedType = 'moto';
                    if (btnText === 'caminhão') selectedType = 'caminhao';
                    if (btnText === 'outros') selectedType = 'outros';
                }
            });
            
            // Obter as marcas do tipo de veículo selecionado
            const brandList = selectedType && marcasModelos[selectedType] 
                ? Object.keys(marcasModelos[selectedType]).sort()
                : Object.keys(marcasModelos.carro || {}).sort();
            
            console.log('[AUTOCOMPLETE] Marcas do drawer para tipo', selectedType, ':', brandList.length);
            
            // Callback para atualizar modelos quando marca é selecionada
            const onBrandSelected = (marcaSelecionada) => {
                const drawerModel = drawerForm.querySelector('[name="model"]');
                const drawerModelSuggestions = drawerForm.querySelector('#modeloSuggestions');
                
                if (drawerModel && drawerModelSuggestions) {
                    let modelos = [];
                    
                    // Procurar a marca em todos os tipos
                    for (const typeKey in marcasModelos) {
                        if (marcasModelos[typeKey] && marcasModelos[typeKey][marcaSelecionada]) {
                            modelos = marcasModelos[typeKey][marcaSelecionada];
                            break;
                        }
                    }
                    
                    console.log('[AUTOCOMPLETE] Modelos para', marcaSelecionada, ':', modelos.length);
                    setupAutocompleteElements(drawerModel, drawerModelSuggestions, modelos, null);
                }
            };
            
            setupAutocompleteElements(drawerBrand, drawerBrandSuggestions, brandList, onBrandSelected);
        }

        // Configurar autocomplete para model no drawer
        const drawerModel = drawerForm.querySelector('[name="model"]');
        const drawerModelSuggestions = drawerForm.querySelector('#modeloSuggestions');
        
        if (drawerModel && drawerModelSuggestions) {
            console.log('[AUTOCOMPLETE] Configurando model no drawer');
            setupAutocompleteElements(drawerModel, drawerModelSuggestions, [], null);
        }

        // Configurar autocomplete para city no drawer
        const drawerCity = drawerForm.querySelector('[name="city"]');
        const drawerCitySuggestions = drawerForm.querySelector('#cidadeSuggestions');
        
        if (drawerCity && drawerCitySuggestions) {
            console.log('[AUTOCOMPLETE] Configurando city no drawer');
            
            // Precarregar cidades
            preloadCidades().then(cidades => {
                setupAutocompleteElements(drawerCity, drawerCitySuggestions, cidades, null);
            });
        }

        console.log('[AUTOCOMPLETE] Autocomplete do drawer configurado com sucesso!');
    } catch (err) {
        console.error('[AUTOCOMPLETE] Erro ao configurar autocomplete do drawer:', err);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-type-button], .tipo-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            setTimeout(() => atualizarAutocompletePorTipo(true), 50);
        });
    });

    inicializarAutocomplete();
});

// Expor preloadCidades globalmente para uso em homepage.js e index.php
window.preloadCidades = preloadCidades;





