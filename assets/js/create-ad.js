// assets/js/create-ad.js - fluxo completo do formulario multi-etapas de anuncio.

(function () {
    const form = document.querySelector('#createAdForm');
    if (!form) {
        return;
    }

    const STEPS = 7;
    const MIN_PHOTOS = Number(form.dataset.minPhotos || 3);
    const draftKey = form.dataset.draftKey || 'clautos:draft';
    const isPremium = form.dataset.isPremium === '1';

    const stepPanes = Array.from(form.querySelectorAll('[data-step-pane]'));
    const stepTriggers = Array.from(document.querySelectorAll('[data-step-trigger]'));
    const progressBar = document.querySelector('[data-steps-progress]');
    const photoInput = form.querySelector('[data-photos-input]');
    const photoGrid = form.querySelector('[data-photos-grid]');
    const dropArea = form.querySelector('[data-drop-area]');
    const photoCounter = form.querySelector('[data-photos-count]');
    const checklistItems = Array.from(document.querySelectorAll('[data-check]'));
    const summaryFields = Array.from(document.querySelectorAll('[data-summary]'));
    const priceDisplay = form.querySelector('[data-price-display]');
    const priceHidden = form.querySelector('[data-price-hidden]');
    const saveableFields = Array.from(form.querySelectorAll('[data-save]'));

    saveableFields.forEach((field) => {
        if (!(field instanceof HTMLElement)) {
            return;
        }

        if (field instanceof HTMLSelectElement) {
            field.dataset.resetDefault = field.value;
            return;
        }

        if (field instanceof HTMLInputElement) {
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.dataset.resetDefaultChecked = field.checked ? '1' : '0';
                return;
            }
            if (field.type === 'file') {
                return;
            }
            field.dataset.resetDefault = '';
            return;
        }

        if (field instanceof HTMLTextAreaElement) {
            field.dataset.resetDefault = '';
        }
    });

    let currentStep = 1;
    let photos = [];
    let saveTimeout = null;

    const findField = (name) => form.querySelector(`[name="${name}"]`);

    const focusField = (field) => {
        if (!field) return;
        if (typeof field.focus === 'function') {
            field.focus({ preventScroll: true });
        }
        if (typeof field.scrollIntoView === 'function') {
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    const feedbackFor = (field) => {
        if (!(field instanceof HTMLElement)) return null;
        return form.querySelector(`[data-feedback-for="${field.id}"]`);
    };

    const setFieldError = (field, message = '') => {
        if (!(field instanceof HTMLElement)) return;
        if (message) {
            field.classList.add('field-error');
        } else {
            field.classList.remove('field-error');
        }
        const feedback = feedbackFor(field);
        if (feedback) {
            if (feedback.dataset.defaultHint === undefined) {
                feedback.dataset.defaultHint = feedback.textContent || '';
            }
            feedback.textContent = message || feedback.dataset.defaultHint || '';
        }
    };

    const sanitizeNumber = (value) => {
        const digits = (value || '').toString().replace(/[^\d]/g, '');
        return digits ? Number(digits) : 0;
    };

    const formatCurrencyBRL = (value) => {
        if (Number.isNaN(value)) return '';
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    const updateProgress = () => {
        if (!progressBar) return;
        const ratio = Math.max(0, Math.min(currentStep - 1, STEPS - 1)) / (STEPS - 1);
        progressBar.style.width = `${ratio * 100}%`;
    };

    const updateStepTriggers = () => {
        stepTriggers.forEach((btn) => {
            const step = Number(btn.dataset.stepTrigger);
            btn.classList.toggle('active', step === currentStep);
            btn.classList.toggle('completed', step < currentStep);
        });
    };

    const showStep = (step) => {
        currentStep = Math.min(Math.max(step, 1), STEPS);
        stepPanes.forEach((pane) => {
            pane.classList.toggle('active', Number(pane.dataset.stepPane) === currentStep);
        });
        updateProgress();
        updateStepTriggers();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const validators = {
        1: (focusOnError = true) => {
            const typeField = findField('vehicle_type');
            const marca = findField('brand');
            const modelo = findField('model');
            let valid = true;
            let firstInvalid = null;

            if (!typeField?.value) {
                setFieldError(typeField, 'Escolha o tipo de veículo.');
                firstInvalid = firstInvalid || typeField;
                valid = false;
            } else {
                setFieldError(typeField);
            }

            if (!marca?.value.trim()) {
                setFieldError(marca, 'Informe a marca.');
                firstInvalid = firstInvalid || marca;
                valid = false;
            } else {
                setFieldError(marca);
            }

            if (!modelo?.value.trim()) {
                setFieldError(modelo, 'Informe o modelo.');
                firstInvalid = firstInvalid || modelo;
                valid = false;
            } else {
                setFieldError(modelo);
            }

            const typeSpecific = Array.from(form.querySelectorAll('[data-step-pane="1"] [data-required-for]'));
            typeSpecific.forEach((field) => {
                const requiredFor = field.dataset.requiredFor?.split(',') || [];
                const activeType = typeField?.value || '';
                if (requiredFor.includes(activeType)) {
                    if (!field.value.trim()) {
                        setFieldError(field, 'Campo obrigatório para este tipo de veículo.');
                        firstInvalid = firstInvalid || field;
                        valid = false;
                    } else {
                        setFieldError(field);
                    }
                } else {
                    setFieldError(field);
                }
            });

            if (!valid && focusOnError) {
                focusField(firstInvalid);
            }
            return valid;
        },
        2: (focusOnError = true) => {
            const manufacture = findField('manufacture_year');
            const modelYear = findField('model_year');
            const mileage = findField('mileage');
            const color = findField('color');
            const transmission = findField('transmission');
            const fuel = findField('fuel_type');
            const doors = findField('doors');
            const plate = findField('plate_last_digit');

            let valid = true;
            let firstInvalid = null;
            const currentYear = new Date().getFullYear() + 1;

            if (!manufacture?.value || Number(manufacture.value) < 1950 || Number(manufacture.value) > currentYear) {
                setFieldError(manufacture, 'Informe o ano de fabricação válido.');
                firstInvalid = firstInvalid || manufacture;
                valid = false;
            } else {
                setFieldError(manufacture);
            }

            if (!modelYear?.value || Number(modelYear.value) < 1950 || Number(modelYear.value) > currentYear) {
                setFieldError(modelYear, 'Informe o ano do modelo válido.');
                firstInvalid = firstInvalid || modelYear;
                valid = false;
            } else if (Number(modelYear.value) < Number(manufacture.value)) {
                setFieldError(modelYear, 'Ano do modelo não pode ser menor que o de fabricação.');
                firstInvalid = firstInvalid || modelYear;
                valid = false;
            } else {
                setFieldError(modelYear);
            }

            if (!mileage?.value || sanitizeNumber(mileage.value) <= 0) {
                setFieldError(mileage, 'Informe a quilometragem.');
                firstInvalid = firstInvalid || mileage;
                valid = false;
            } else {
                setFieldError(mileage);
            }

            [color, transmission, fuel].forEach((field) => {
                if (!field?.value) {
                    setFieldError(field, 'Campo obrigatório.');
                    firstInvalid = firstInvalid || field;
                    valid = false;
                } else {
                    setFieldError(field);
                }
            });

            const activeType = findField('vehicle_type')?.value || '';
            if (doors) {
                const requiredFor = doors.dataset.requiredFor?.split(',') || [];
                if (requiredFor.includes(activeType)) {
                    if (!doors.value) {
                        setFieldError(doors, 'Informe a quantidade de portas.');
                        firstInvalid = firstInvalid || doors;
                        valid = false;
                    } else {
                        setFieldError(doors);
                    }
                } else {
                    setFieldError(doors);
                }
            }

            if (!plate?.value) {
                setFieldError(plate, 'Selecione o final da placa.');
                firstInvalid = firstInvalid || plate;
                valid = false;
            } else {
                setFieldError(plate);
            }

            if (!valid && focusOnError) {
                focusField(firstInvalid);
            }
            return valid;
        },
        3: (focusOnError = true) => {
            const state = findField('state');
            const city = findField('city');
            let valid = true;
            let firstInvalid = null;
            if (!state?.value) {
                setFieldError(state, 'Selecione o estado.');
                firstInvalid = firstInvalid || state;
                valid = false;
            } else {
                setFieldError(state);
            }
            if (!city?.value.trim()) {
                setFieldError(city, 'Informe a cidade.');
                firstInvalid = firstInvalid || city;
                valid = false;
            } else {
                setFieldError(city);
            }
            if (!valid && focusOnError) {
                focusField(firstInvalid);
            }
            return valid;
        },
        4: (focusOnError = true) => {
            const price = priceDisplay;
            const title = findField('title');
            const description = findField('description');
            let valid = true;
            let firstInvalid = null;

            const amount = sanitizeNumber(price?.value || '');
            if (!amount) {
                setFieldError(price, 'Informe o preço.');
                firstInvalid = firstInvalid || price;
                valid = false;
            } else {
                setFieldError(price);
            }

            if (!title?.value || title.value.trim().length < 10) {
                setFieldError(title, 'Título muito curto.');
                firstInvalid = firstInvalid || title;
                valid = false;
            } else {
                setFieldError(title);
            }

            if (!description?.value || description.value.trim().length < 60) {
                setFieldError(description, 'Descrição precisa de pelo menos 60 caracteres.');
                firstInvalid = firstInvalid || description;
                valid = false;
            } else {
                setFieldError(description);
            }

            if (!valid && focusOnError) {
                focusField(firstInvalid);
            }
            return valid;
        },
        5: (focusOnError = true) => {
            if (photos.length < MIN_PHOTOS) {
                dropArea?.classList.add('field-error');
                if (photoCounter) {
                    photoCounter.textContent = `Adicione pelo menos ${MIN_PHOTOS} fotos - você tem ${photos.length}.`;
                }
                if (focusOnError) {
                    focusField(dropArea);
                }
                return false;
            }
            dropArea?.classList.remove('field-error');
            if (photoCounter) {
                photoCounter.textContent = `${photos.length} fotos selecionadas (mínimo ${MIN_PHOTOS})`;
            }
            return true;
        },
        6: () => checklistItems.every((item) => item.classList.contains('complete')),
        7: () => true, // Step 7 é apenas para revisar e enviar - sem validação adicional
    };

    const updateChecklist = () => {
        const checks = {
            basic: validators[1](false),
            technical: validators[2](false),
            location: validators[3](false),
            description: validators[4](false),
            media: photos.length >= MIN_PHOTOS,
        };

        checklistItems.forEach((item) => {
            const key = item.dataset.check;
            const complete = checks[key];
            item.classList.toggle('complete', Boolean(complete));

            const textEl = item.querySelector('[data-check-text]');

            if (key === 'media') {
                const required = Number(item.dataset.threshold || MIN_PHOTOS);
                if (textEl) {
                    if (complete) {
                        textEl.textContent = `Você adicionou ${photos.length} fotos.`;
                    } else {
                        textEl.textContent = `Adicione pelo menos ${required} fotos — você tem ${photos.length}/${required}.`;
                    }
                }
                if (photos.length >= required) {
                    dropArea?.classList.remove('field-error');
                }
            } else if (textEl) {
                textEl.textContent = complete ? 'Pronto!' : textEl.dataset.defaultMessage || textEl.textContent;
            }
        });
    };

    const updateSummary = () => {
        const brandValue = findField('brand')?.value || '';
        const modelValue = findField('model')?.value || '';
        const priceValue = Number(priceHidden?.value || 0);

        const values = {
            type: findField('vehicle_type')?.dataset.label || '—',
            brand: [brandValue, modelValue].filter(Boolean).join(' / ') || '—',
            price: priceValue ? formatCurrencyBRL(priceValue) : '—',
            location: [findField('city')?.value, findField('state')?.value].filter(Boolean).join(' / ') || '—',
            photos: `${photos.length} / ${MIN_PHOTOS} mín.`,
        };

        summaryFields.forEach((field) => {
            const key = field.dataset.summary;
            if (!key) return;
            field.textContent = values[key] || '—';
        });
    };

    const syncPhotoInput = () => {
        if (!photoInput) return;
        const dt = new DataTransfer();
        photos.forEach((item) => {
            // ✅ Validar que item.file é um File válido antes de adicionar
            if (item.file instanceof File) {
                dt.items.add(item.file);
            } else if (item.file instanceof Blob) {
                console.warn(`[SYNC] ⚠️ Blob detectado (não é File). Tipo:`, item.file.type, `Tamanho: ${item.file.size} bytes`);
            } else {
                console.warn(`[SYNC] ⚠️ Tipo inválido descartado:`, typeof item.file, item.file);
            }
        });
        photoInput.files = dt.files;
    };

    const renderPhotos = () => {
        if (!photoGrid) return;
        photoGrid.innerHTML = '';
        if (!photos.length) {
            photoGrid.innerHTML = '<p class="field-hint">Nenhuma foto selecionada ainda.</p>';
            if (photoCounter) {
                photoCounter.textContent = `0 fotos selecionadas (mínimo ${MIN_PHOTOS})`;
            }
            updateSummary();
            updateChecklist();
            syncPhotoInput();
            return;
        }

        photos.forEach((item, index) => {
            const container = document.createElement('div');
            container.className = 'photos-grid__item';

            const img = document.createElement('img');
            img.src = item.preview;
            img.alt = `Foto ${index + 1}`;
            container.appendChild(img);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.innerHTML = '&times;';
            removeBtn.className = 'remove-photo-btn';
            removeBtn.addEventListener('click', () => {
                photos.splice(index, 1);
                renderPhotos();
            });
            container.appendChild(removeBtn);

            const badge = document.createElement('span');
            badge.textContent = `${index + 1}`;
            container.appendChild(badge);

            photoGrid.appendChild(container);
        });

        if (photoCounter) {
            photoCounter.textContent = `${photos.length} fotos selecionadas (mínimo ${MIN_PHOTOS})`;
        }
        updateSummary();
        updateChecklist();
        syncPhotoInput();
    };

    const readFilePreview = (file) => new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (event) => resolve(event.target?.result);
        reader.readAsDataURL(file);
    });

    const addPhotos = async (fileList) => {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        // ✅ Validar tamanho de acordo com configuração do admin
        const maxSizeMB = window.uploadConfig?.maxImageSizeMB || 2;
        const maxSize = maxSizeMB * 1024 * 1024;
        console.log(`[CREATE-AD] 📸 Validando fotos - Tamanho máximo: ${maxSizeMB}MB`);

        let rejectedCount = 0;
        const filesToCompress = [];

        // Fase 1: Converter HEIC e filtrar arquivos válidos
        for (const file of fileList) {
            if (photos.length >= 12) break;
            
            // ✅ NOVO: Converter HEIC → JPG automaticamente
            let processedFile = file;
            if (file.type === 'image/heic' || file.type === 'image/heif') {
                console.log(`[CREATE-AD] 🔄 Convertendo HEIC: ${file.name}`);
                await ensureHeic2anyLoaded();
                processedFile = await convertHeicToJpg(file);
            }
            
            if (!allowedTypes.includes(processedFile.type)) continue;
            
            // ✅ Rejeitar se exceder limite configurado
            if (processedFile.size > maxSize) {
                console.warn(`[CREATE-AD] Foto ${processedFile.name} rejeitada - Tamanho: ${(processedFile.size / 1024 / 1024).toFixed(2)}MB (máx: ${maxSizeMB}MB)`);
                rejectedCount++;
                continue;
            }

            filesToCompress.push(processedFile);
        }
        
        // ✅ Mostrar mensagem se alguma foto foi rejeitada
        if (rejectedCount > 0) {
            const message = `${rejectedCount} foto(s) rejeitada(s) por exceder o limite de ${maxSizeMB}MB. Comprima as imagens e tente novamente.`;
            console.warn(`[CREATE-AD] ${message}`);
            alert(message);
        }

        // Fase 2: Comprimir imagens válidas usando função global
        if (filesToCompress.length > 0) {
            console.log(`[CREATE-AD] 🚀 Iniciando compressão via window.compressImages()`);
            
            // ✅ Verificar se função global existe
            if (typeof window.compressImages === 'function') {
                try {
                    // Chamar função global de compressão
                    const compressedFiles = await window.compressImages(
                        filesToCompress,
                        (progress) => {
                            console.log(`[CREATE-AD] ⏳ Progresso: ${progress}%`);
                        },
                        (info) => {
                            console.log(`[CREATE-AD] ✅ Imagem processada: ${info.filename}`);
                            console.log(`   Original: ${(info.originalSize / 1024 / 1024).toFixed(2)}MB → Comprimida: ${(info.compressedSize / 1024 / 1024).toFixed(2)}MB (${info.reduction}%)`);
                        }
                    );

                    // Adicionar imagens comprimidas ao array
                    for (const compressedFile of compressedFiles) {
                        const preview = await readFilePreview(compressedFile);
                        photos.push({ file: compressedFile, preview });
                    }

                    console.log(`[CREATE-AD] ✨ Todas as imagens comprimidas com sucesso!`);
                } catch (error) {
                    console.error(`[CREATE-AD] ❌ Erro ao comprimir:`, error);
                    console.warn(`[CREATE-AD] ⚠️ Usando imagens originais sem compressão`);
                    
                    // Fallback: usar imagens originais
                    for (const file of filesToCompress) {
                        const preview = await readFilePreview(file);
                        photos.push({ file, preview });
                    }
                }
            } else {
                console.error(`[CREATE-AD] ❌ window.compressImages() NÃO DISPONÍVEL`);
                console.warn(`[CREATE-AD] ⚠️ Usando imagens originais sem compressão`);
                
                // Fallback: usar imagens originais
                for (const file of filesToCompress) {
                    const preview = await readFilePreview(file);
                    photos.push({ file, preview });
                }
            }
        }
        
        renderPhotos();
    };

    const handleDrop = (event) => {
        event.preventDefault();
        dropArea?.classList.remove('dragover');
        const files = event.dataTransfer?.files;
        if (files) {
            addPhotos(files);
        }
    };

    const scheduleSave = () => {
        clearTimeout(saveTimeout);
        saveTimeout = window.setTimeout(() => {
            const payload = {};
            form.querySelectorAll('[data-save]').forEach((field) => {
                if (!(field instanceof HTMLElement)) return;
                if (field.type === 'checkbox' || field.type === 'radio') {
                    payload[field.name] = field.checked;
                } else {
                    payload[field.name] = field.value;
                }
            });
            payload.currentStep = currentStep;
            payload.timestamp = Date.now();
            // ✅ BUG FIX: Salvar preço do campo hidden
            if (priceHidden?.value) {
                payload.price_hidden = priceHidden.value;
            }
            // ✅ BUG FIX: Salvar fotos em localStorage com Base64
            if (photos.length > 0) {
                payload.photos = photos.map(p => ({
                    preview: p.preview,
                    fileName: p.file?.name || 'photo.jpg'
                }));
            }
            try {
                localStorage.setItem(draftKey, JSON.stringify(payload));
            } catch (error) {
                console.warn('Não foi possível salvar o rascunho:', error);
            }
        }, 400);
    };

    const loadDraft = () => {
        try {
            const raw = localStorage.getItem(draftKey);
            if (!raw) return;
            const data = JSON.parse(raw);
            if (!data || typeof data !== 'object') return;

            form.querySelectorAll('[data-save]').forEach((field) => {
                if (!(field instanceof HTMLElement)) return;
                if (!(field.name in data)) return;
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = Boolean(data[field.name]);
                } else {
                    field.value = data[field.name] ?? '';
                }
            });

            // ✅ BUG FIX: Restaurar preço do campo hidden
            if (data.price_hidden && priceHidden) {
                priceHidden.value = String(data.price_hidden);
                updateSummary(); // Atualizar resumo com o preço
            }

            // ✅ BUG FIX: Restaurar fotos do localStorage com conversão Base64 → File
            if (Array.isArray(data.photos) && data.photos.length > 0) {
                photos = data.photos
                    .map(p => {
                        let file = null;
                        try {
                            if (p.preview && p.preview.startsWith('data:')) {
                                const [header, data64] = p.preview.split(',');
                                const mimeType = header.match(/:(.*?);/)?.[1] || 'image/jpeg';
                                const byteCharacters = atob(data64);
                                const byteNumbers = new Array(byteCharacters.length);
                                for (let i = 0; i < byteCharacters.length; i++) {
                                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                                }
                                const byteArray = new Uint8Array(byteNumbers);
                                const blob = new Blob([byteArray], { type: mimeType });
                                file = new File([blob], p.fileName || 'photo.jpg', { type: mimeType });
                            }
                        } catch (error) {
                            console.error('Erro ao converter Base64 para File:', error);
                            return null; // Descartar foto inválida
                        }
                        return { preview: p.preview, file };
                    })
                    .filter(item => item !== null && item.file instanceof File); // ✅ Filtrar inválidas
                
                if (photos.length > 0) {
                    // Sincronizar com photoInput
                    syncPhotoInput();
                    renderPhotos();
                }
            }

            if (typeof data.currentStep === 'number') {
                currentStep = Math.min(Math.max(data.currentStep, 1), STEPS);
            }
        } catch (error) {
            console.warn('Não foi possível carregar o rascunho:', error);
        }
    };

    const setupTypeButtons = () => {
        // Convert NodeList to array so we can safely use array helpers like .find().
        const buttons = Array.from(document.querySelectorAll('[data-type-button]'));
        const hidden = findField('vehicle_type');

        const resetFormForType = () => {
            saveableFields.forEach((field) => {
                if (!(field instanceof HTMLElement)) {
                    return;
                }
                if (field === hidden) {
                    return;
                }

                if (field instanceof HTMLInputElement) {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        const shouldCheck = field.dataset.resetDefaultChecked === '1';
                        field.checked = shouldCheck;
                    } else if (field.type === 'file') {
                        field.value = '';
                    } else {
                        const resetValue = field.dataset.resetDefault ?? '';
                        field.value = resetValue;
                    }
                } else if (field instanceof HTMLTextAreaElement) {
                    const resetValue = field.dataset.resetDefault ?? '';
                    field.value = resetValue;
                } else if (field instanceof HTMLSelectElement) {
                    const resetValue = field.dataset.resetDefault ?? '';
                    if (resetValue !== '') {
                        field.value = resetValue;
                    } else {
                        const emptyOption = Array.from(field.options).find((option) => option.value === '');
                        if (emptyOption) {
                            field.value = '';
                        } else if (field.options.length > 0) {
                            field.selectedIndex = 0;
                        } else {
                            field.value = '';
                        }
                    }
                }

                setFieldError(field);
            });

            if (priceHidden) {
                priceHidden.value = '';
            }
            if (priceDisplay) {
                priceDisplay.value = '';
            }

            photos = [];
            renderPhotos();
            if (photoInput) {
                photoInput.value = '';
            }
            const videoInput = form.querySelector('input[name="video"]');
            if (videoInput instanceof HTMLInputElement) {
                videoInput.value = '';
            }
            dropArea?.classList.remove('field-error', 'dragover');

            localStorage.removeItem(draftKey);
            showStep(1);
        };

        const applyType = (type, label, shouldResetAutocomplete = true) => {
            buttons.forEach((button) => {
                const active = (button.dataset.typeButton || '') === type;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            document.querySelectorAll('[data-type-pane]').forEach((pane) => {
                pane.toggleAttribute('hidden', pane.dataset.typePane !== type);
            });

            document.querySelectorAll('[data-required-for]').forEach((field) => {
                const requiredFor = field.dataset.requiredFor?.split(',') || [];
                field.toggleAttribute('required', requiredFor.includes(type));
            });

            if (shouldResetAutocomplete) {
                resetFormForType();
            }

            if (hidden) {
                hidden.value = type;
                hidden.dataset.label = label || '';
                setFieldError(hidden);
            }

            if (typeof atualizarAutocompletePorTipo === 'function') {
                setTimeout(() => atualizarAutocompletePorTipo(shouldResetAutocomplete), 0);
            }

            updateSummary();
            scheduleSave();
            updateChecklist();
        };

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                applyType(btn.dataset.typeButton || '', btn.dataset.typeLabel || '', true);
            });
        });

        const initialType = hidden?.value || buttons[0]?.dataset.typeButton || '';
        const initialLabel = buttons.find((btn) => btn.dataset.typeButton === initialType)?.dataset.typeLabel || '';
        applyType(initialType, initialLabel, false);

        // ✅ BUG FIX: Atualizar opções de câmbio baseado no tipo de veículo
        const tiposCambio = {
            carro: ['Manual', 'Automático', 'Automatizado', 'CVT', 'Dualogic', 'Tiptronic', 'DSG'],
            moto: ['Manual', 'Automático', 'Semiautomático', 'CVT'],
            caminhao: ['Manual', 'Automatizado', 'Automático', 'Híbrido'],
            outros: ['Manual', 'Automático'],
        };

        const atualizarOpcoesCambio = (tipo) => {
            const transmissionField = form.querySelector('[name="transmission"]');
            if (!transmissionField) return;

            const valorAtual = transmissionField.value;
            transmissionField.innerHTML = '<option value="">Selecione</option>';

            const opcoesCambio = tiposCambio[tipo] || tiposCambio.outros;
            opcoesCambio.forEach((opcao) => {
                const option = document.createElement('option');
                option.value = opcao;
                option.textContent = opcao;
                if (valorAtual === opcao) {
                    option.selected = true;
                }
                transmissionField.appendChild(option);
            });
        };

        // Carregar opções de câmbio para o tipo atual
        atualizarOpcoesCambio(initialType);

        // Atualizar câmbio quando tipo mudar
        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                atualizarOpcoesCambio(btn.dataset.typeButton || 'carro');
            });
        });
    };

    const setupMedia = () => {
        if (photoInput) {
            photoInput.addEventListener('change', () => {
                addPhotos(Array.from(photoInput.files || []));
                scheduleSave();
            });
        }

        if (dropArea) {
            ['dragenter', 'dragover'].forEach((eventName) => {
                dropArea.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropArea.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'));
            });

            dropArea.addEventListener('drop', handleDrop);
            dropArea.addEventListener('click', () => photoInput?.click());
        }
    };

    const setupVideoGate = () => {
        const videoSection = document.querySelector('[data-video-section]');
        const upsell = document.querySelector('[data-upsell]');
        if (isPremium) {
            videoSection?.classList.remove('locked');
            upsell?.setAttribute('hidden', 'true');
        } else {
            videoSection?.classList.add('locked');
            upsell?.removeAttribute('hidden');
        }
    };

    const setupVideoValidation = () => {
        const videoInput = form.querySelector('input[name="video"]');
        if (!videoInput) return;

        videoInput.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            if (!file) return;

            const fileSizeMB = (file.size / 1024 / 1024).toFixed(1);

            // ✅ Validação de tamanho - rejeitar se exceder limite
            const maxVideoSizeMB = window.uploadConfig?.maxVideoSizeMB || 30;
            const maxVideoBytes = maxVideoSizeMB * 1024 * 1024;
            
            if (file.size > maxVideoBytes) {
                const message = `Seu vídeo tem ${fileSizeMB}MB, mas o limite é ${maxVideoSizeMB}MB. Comprima o vídeo e tente novamente.`;
                setFieldError(videoInput, message);
                videoInput.value = ''; // Limpar arquivo inválido
                console.warn(`[VIDEO] Vídeo rejeitado: ${message}`);
                alert(message);
                return;
            }

            // Validação de formato
            const allowedMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/avi'];
            if (!allowedMimes.includes(file.type)) {
                const message = `Formato ${file.type} não suportado. Use MP4, MOV ou AVI.`;
                setFieldError(videoInput, message);
                videoInput.value = ''; // Limpar arquivo inválido
                return;
            }

            // Se passou nas validações, chamar função de compressão
            console.log(`[VIDEO] ✅ Arquivo validado: ${file.name} (${fileSizeMB}MB) - iniciando processamento`);
            setFieldError(videoInput, '');
            
            // ✅ Chamar função global de compressão de vídeo
            if (typeof window.compressVideoForUpload === 'function') {
                window.compressVideoForUpload(file, videoInput).catch(error => {
                    console.error('[VIDEO] Erro ao processar vídeo:', error);
                });
            } else {
                console.warn('[VIDEO] ⚠️ window.compressVideoForUpload() não disponível');
            }
        });
    };

    const setupVideoSourceToggle = () => {
        const videoSourceRadios = form.querySelectorAll('input[name="video_source"]');
        const videoUploadSection = document.getElementById('videoUploadSection');
        const instagramLinkSection = document.getElementById('instagramLinkSection');
        const videoInput = form.querySelector('input[name="video"]');
        const instagramInput = form.querySelector('input[name="video_instagram_link"]');

        if (!videoSourceRadios.length) return;

        videoSourceRadios.forEach(radio => {
            radio.addEventListener('change', (event) => {
                const sourceValue = event.target.value;

                if (sourceValue === 'upload') {
                    // Mostrar upload, esconder Instagram
                    videoUploadSection.style.display = 'block';
                    instagramLinkSection.style.display = 'none';
                    instagramInput.value = ''; // Limpar link Instagram
                    instagramInput.removeAttribute('required');
                    console.log('[VIDEO] Modo: Anexar arquivo');
                } else if (sourceValue === 'instagram') {
                    // Esconder upload, mostrar Instagram
                    videoUploadSection.style.display = 'none';
                    instagramLinkSection.style.display = 'block';
                    videoInput.value = ''; // Limpar arquivo
                    instagramInput.setAttribute('required', 'required');
                    console.log('[VIDEO] Modo: Link Instagram');
                }
            });
        });
    };

    const setupModals = () => {
        document.querySelectorAll('[data-open-modal]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.openModal;
                const backdrop = document.querySelector(`[data-modal-backdrop="${modalId}"]`);
                if (!backdrop) return;

                if (modalId === 'title') {
                    const typeLabel = findField('vehicle_type')?.dataset.label || '';
                    const brand = findField('brand')?.value || '';
                    const model = findField('model')?.value || '';
                    const year = findField('model_year')?.value || '';
                    const price = priceHidden?.value ? formatCurrencyBRL(Number(priceHidden.value)) : '';
                    const suggestions = [
                        `${brand} ${model} ${year}`.trim(),
                        `${typeLabel} ${brand} ${model} completo`.trim(),
                        `${brand} ${model} ${year} por ${price}`.trim(),
                    ].filter(Boolean);

                    const list = backdrop.querySelector('.modal__list');
                    list.innerHTML = '';
                    suggestions.forEach((text) => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'modal__option';
                        option.textContent = text;
                        option.addEventListener('click', () => {
                            const title = findField('title');
                            if (title) {
                                title.value = text;
                                setFieldError(title);
                                updateSummary();
                            }
                            backdrop.classList.remove('active');
                        });
                        list.appendChild(option);
                    });
                }

                if (modalId === 'description') {
                    const brand = findField('brand')?.value || '';
                    const model = findField('model')?.value || '';
                    const year = findField('model_year')?.value || '';
                    const mileage = findField('mileage')?.value ? `${sanitizeNumber(findField('mileage').value).toLocaleString('pt-BR')} km` : '';
                    const fuel = findField('fuel_type')?.value || '';
                    const transmission = findField('transmission')?.value || '';

                    const suggestions = [
                        `${brand} ${model} ${year} em ótimo estado, ${mileage}, combustível ${fuel}, câmbio ${transmission}. Revisões em dia e documentação ok.`,
                        `${brand} ${model} ${year} muito bem conservado, pronto para rodar. Interior impecável, ${mileage}.`,
                        `Oportunidade: ${brand} ${model} ${year} com ${mileage}, ${fuel}, ${transmission}. Aceito avaliação.`,
                    ].filter(Boolean);

                    const list = backdrop.querySelector('.modal__list');
                    list.innerHTML = '';
                    suggestions.forEach((text) => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'modal__option';
                        option.textContent = text;
                        option.addEventListener('click', () => {
                            const desc = findField('description');
                            if (desc) {
                                desc.value = text;
                                setFieldError(desc);
                            }
                            backdrop.classList.remove('active');
                        });
                        list.appendChild(option);
                    });
                }

                backdrop.classList.add('active');
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modalId = btn.dataset.closeModal;
                document.querySelector(`[data-modal-backdrop="${modalId}"]`)?.classList.remove('active');
            });
        });

        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
            backdrop.addEventListener('click', (event) => {
                if (event.target === backdrop) {
                    backdrop.classList.remove('active');
                }
            });
        });
    };

    const setupStepNavigation = () => {
        form.querySelectorAll('[data-action="next"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (validators[currentStep]?.()) {
                    showStep(currentStep + 1);
                    scheduleSave();
                }
            });
        });

        form.querySelectorAll('[data-action="prev"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                showStep(currentStep - 1);
                scheduleSave();
            });
        });

        stepTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const targetStep = Number(trigger.dataset.stepTrigger);
                if (targetStep < currentStep || validators[currentStep]?.()) {
                    showStep(targetStep);
                }
            });
        });
    };

    const setupListeners = () => {
        form.addEventListener('input', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;
            if (target.matches('[data-save]')) {
                setFieldError(target);
                updateSummary();
                updateChecklist();
                scheduleSave();
            }
        });

        if (priceDisplay && priceHidden) {
            priceDisplay.addEventListener('input', () => {
                const digits = priceDisplay.value.replace(/[^\d]/g, '');
                const numeric = Number(digits) / 100;
                priceHidden.value = numeric ? numeric.toFixed(2) : '';
                priceDisplay.value = digits ? formatCurrencyBRL(numeric) : '';
                updateSummary();
                scheduleSave(); // ✅ BUG FIX: Salvar preço ao alterar
            });
        }

        form.addEventListener('submit', (event) => {
            priceDisplay?.dispatchEvent(new Event('input'));
            // ✅ BUG FIX: Sincronizar fotos antes de enviar
            syncPhotoInput();
            let valid = true;
            for (let step = 1; step <= STEPS; step += 1) {
                if (!validators[step]?.()) {
                    valid = false;
                    showStep(step);
                    break;
                }
            }
            if (!valid) {
                event.preventDefault();
                return;
            }
            // ✅ Limpar dados locais ao enviar com sucesso
            localStorage.removeItem(draftKey);
            localStorage.removeItem(draftKey + ':verification_doc');
        });

        window.addEventListener('beforeunload', () => {
            clearTimeout(saveTimeout);
        });
    };

    const setupVerificationDocument = () => {
        const verificationInput = document.getElementById('verificationDocument');
        const verificationInfo = document.getElementById('verificationDocumentInfo');
        const verificationName = document.getElementById('verificationDocumentName');
        const verificationDocKey = draftKey + ':verification_doc';

        if (!verificationInput) return;

        // ✅ Restaurar documento do localStorage ao carregar página
        const loadVerificationDocument = () => {
            try {
                const stored = localStorage.getItem(verificationDocKey);
                if (stored) {
                    const docData = JSON.parse(stored);
                    if (docData && docData.preview && docData.fileName) {
                        // Converter Base64 → Blob → File → DataTransfer → input.files
                        try {
                            const [header, data64] = docData.preview.split(',');
                            const mimeType = docData.fileType || header.match(/:(.*?);/)?.[1] || 'application/octet-stream';
                            const byteCharacters = atob(data64);
                            const byteNumbers = new Array(byteCharacters.length);
                            for (let i = 0; i < byteCharacters.length; i++) {
                                byteNumbers[i] = byteCharacters.charCodeAt(i);
                            }
                            const byteArray = new Uint8Array(byteNumbers);
                            const blob = new Blob([byteArray], { type: mimeType });
                            const file = new File([blob], docData.fileName, { type: mimeType });
                            
                            // ✅ Colocar arquivo no input usando DataTransfer
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            verificationInput.files = dataTransfer.files;
                        } catch (conversionError) {
                            console.warn('Erro ao converter Base64 para File:', conversionError);
                        }
                        
                        // Mostrar informações do arquivo armazenado
                        verificationName.textContent = `${docData.fileName} (armazenado)`;
                        verificationInfo.style.display = 'block';
                        
                        // Marcar que temos um documento salvo
                        verificationInput.dataset.hasSavedFile = '1';
                        verificationInput.title = `Documento: ${docData.fileName}\n(Alteré para mudar)`;
                    }
                }
            } catch (error) {
                console.warn('Erro ao carregar documento de verificação:', error);
            }
        };

        verificationInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            
            if (!file) {
                verificationInfo.style.display = 'none';
                verificationInput.dataset.hasSavedFile = '0';
                // Remover arquivo salvo se usuário limpar
                localStorage.removeItem(verificationDocKey);
                return;
            }

            // Validar tipo e tamanho
            const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            const maxSizeMB = window.uploadConfig?.maxDocumentSizeMB || 10;
            const maxSize = maxSizeMB * 1024 * 1024;

            if (!validTypes.includes(file.type)) {
                alert('Tipo de arquivo inválido. Use JPEG, PNG ou PDF.');
                verificationInput.value = '';
                verificationInfo.style.display = 'none';
                verificationInput.dataset.hasSavedFile = '0';
                localStorage.removeItem(verificationDocKey);
                return;
            }

            if (file.size > maxSize) {
                alert(`Arquivo muito grande. Tamanho máximo: ${maxSizeMB}MB.`);
                verificationInput.value = '';
                verificationInfo.style.display = 'none';
                verificationInput.dataset.hasSavedFile = '0';
                localStorage.removeItem(verificationDocKey);
                return;
            }

            // ✅ Salvar documento no localStorage com Base64
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const docData = {
                        preview: e.target.result,
                        fileName: file.name,
                        fileType: file.type,
                        fileSize: file.size,
                        savedAt: new Date().toISOString()
                    };
                    localStorage.setItem(verificationDocKey, JSON.stringify(docData));
                    verificationInput.dataset.hasSavedFile = '1';
                } catch (error) {
                    console.warn('Erro ao salvar documento:', error);
                }
            };
            reader.readAsDataURL(file);

            // Mostrar informações do arquivo
            verificationName.textContent = `${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
            verificationInfo.style.display = 'block';
        });

        // Carregar documento ao inicializar
        loadVerificationDocument();
    };

    const initialise = () => {
        loadDraft();
        setupTypeButtons();
        setupMedia();
        setupVideoGate();
        setupVideoValidation();
        setupVideoSourceToggle();
        setupModals();
        setupVerificationDocument();
        setupStepNavigation();
        setupListeners();
        renderPhotos();
        updateSummary();
        updateChecklist();
        showStep(currentStep);
        
        // ✅ Inicializar compressor de vídeo (popup com barra de progresso)
        if (typeof initializeVideoCompressor === 'function') {
            initializeVideoCompressor();
            console.log('[CREATE-AD] ✓ Video compressor inicializado');
        } else {
            console.warn('[CREATE-AD] ⚠️ initializeVideoCompressor não disponível - verifique se image-compressor.js foi carregado');
        }
    };

    initialise();
})();



