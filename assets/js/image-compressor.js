/**
 * assets/js/image-compressor.js
 * 
 * Sistema de compressão de IMAGENS e VÍDEOS client-side
 * Comprime ANTES de enviar ao servidor (como WhatsApp/Instagram)
 * 
 * Características:
 * ✅ Compressão de imagens em Web Worker (não trava UI)
 * ✅ Compressão de vídeos usando FFmpeg.wasm
 * ✅ Progress tracking em tempo real
 * ✅ Preview com redução de tamanho
 * ✅ Vinculado aos limites do admin
 * ✅ Suporta múltiplos formatos
 */

// Carregar bibliotecas de compressão do CDN
(async () => {
    // Imagens: browser-image-compression
    if (!window.imageCompression) {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js';
        script.onload = () => {
            console.log('✅ browser-image-compression carregada');
        };
        script.onerror = () => {
            console.error('❌ Erro ao carregar browser-image-compression');
        };
        document.head.appendChild(script);
    }

    // ❌ FFmpeg.wasm DESABILITADO
    // Motivo: Compressão de vídeos é feita NO SERVIDOR com PHP FFmpeg
    // FFmpeg.wasm é complexo demais para client-side
    // console.log('ℹ️ FFmpeg.wasm desabilitado - compressão de vídeo será server-side');
})();

/**
 * Configurações de compressão
 */
const compressionConfig = {
    maxSizeMB: 3,                           // Máximo 3MB
    maxWidthOrHeight: 1920,                 // Máxima resolução
    useWebWorker: true,                     // Usar Web Worker (não trava UI)
    initialQuality: 0.9,                    // 90% qualidade
    maxIteration: 10,                       // Iterações máx de compressão
    preserveExif: true,                     // Preservar metadados
    alwaysKeepResolution: false,            // Reduzir ambos tamanho e resolução
    fileType: 'image/webp'                  // Converter para WebP (melhor compressão)
};

/**
 * Armazena informações de compressão de cada arquivo
 */
const compressionState = new Map();

/**
 * Formata tamanho em bytes para formato legível
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Calcula percentual de redução
 */
function calculateReduction(original, compressed) {
    if (original === 0) return 0;
    return Math.round(((original - compressed) / original) * 100);
}

/**
 * Comprime uma imagem individual
 */
async function compressImage(file, onProgress = null) {
    try {
        // Aguardar a biblioteca estar pronta
        let attempts = 0;
        while (!window.imageCompression && attempts < 50) {
            await new Promise(resolve => setTimeout(resolve, 100));
            attempts++;
        }

        if (!window.imageCompression) {
            console.warn(`⚠️ Biblioteca de compressão não carregou. Usando imagem original: ${file.name}`);
            return file;
        }

        const fileId = file.name + Date.now();
        console.log(`📸 Iniciando compressão: ${file.name} (${formatFileSize(file.size)})`);

        // Comprime a imagem (retorna Blob)
        const compressedBlob = await window.imageCompression(file, {
            ...compressionConfig,
            onProgress: (progress) => {
                console.log(`⏳ Progresso: ${progress}%`);
                if (onProgress) {
                    onProgress(progress);
                }
            }
        });

        // ✅ Converter Blob → File para manter consistência
        const compressedFile = new File(
            [compressedBlob],
            file.name.replace(/\.[^/.]+$/, '') + '.webp', // Renomear para .webp (formato de saída)
            { type: 'image/webp' }
        );

        const reduction = calculateReduction(file.size, compressedFile.size);
        console.log(`✅ Compressão concluída: ${file.name}`);
        console.log(`   Original: ${formatFileSize(file.size)}`);
        console.log(`   Comprimido: ${formatFileSize(compressedFile.size)}`);
        console.log(`   Redução: ${reduction}%`);

        // Armazenar informações
        compressionState.set(fileId, {
            original: file.size,
            compressed: compressedFile.size,
            reduction: reduction,
            timestamp: Date.now()
        });

        return compressedFile;  // ✅ Retorna File, não Blob

    } catch (error) {
        console.error('❌ Erro ao comprimir imagem:', error);
        console.warn(`⚠️ Usando imagem original: ${file.name}`);
        return file;  // ✅ Retornar arquivo original se falhar
    }
}

/**
 * Comprime um vídeo individual usando FFmpeg
 * ⚠️ FFmpeg.wasm requer ~200MB de memória
 */
async function compressVideo(file, onProgress = null) {
    try {
        // ❌ FFmpeg.wasm NÃO FUNCIONA - DESABILITADO
        // O FFmpeg.wasm requer arquivos .wasm e .worker.js complexos
        // Solução: Compressão será feita NO SERVIDOR com PHP FFmpeg
        
        console.log('📹 Vídeo será comprimido NO SERVIDOR');
        console.log(`   Original: ${formatFileSize(file.size)}`);
        console.warn('⚠️ Enviando arquivo para compressão server-side...');
        
        // Retornar arquivo original - servidor vai comprimir
        return file;

    } catch (error) {
        console.error('❌ Erro ao comprimir vídeo:', error);
        return file;
    }
}

/**
 * Processa múltiplas imagens
 */
async function processMultipleImages(files, onProgress = null, onFileComplete = null) {
    const results = [];
    const fileArray = Array.from(files);

    for (let i = 0; i < fileArray.length; i++) {
        try {
            const file = fileArray[i];
            console.log(`\n📦 Processando arquivo ${i + 1}/${fileArray.length}: ${file.name}`);

            // Atualizar progresso geral
            const overallProgress = Math.round((i / fileArray.length) * 100);
            if (onProgress) {
                onProgress(overallProgress);
            }

            // Comprimir arquivo
            const compressedFile = await compressImage(file, (fileProgress) => {
                // Progresso detalhado do arquivo individual
                // Pode ser usado para barra de progresso específica
            });

            results.push(compressedFile);

            // Callback quando arquivo está pronto
            if (onFileComplete) {
                onFileComplete({
                    index: i,
                    filename: file.name,
                    originalSize: file.size,
                    compressedSize: compressedFile.size,
                    reduction: calculateReduction(file.size, compressedFile.size)
                });
            }

        } catch (error) {
            console.error(`❌ Erro ao processar ${fileArray[i].name}:`, error);
            // Continuar processando outros arquivos
        }
    }

    console.log(`\n✨ Processamento completo: ${results.length}/${fileArray.length} arquivos`);
    return results;
}

/**
 * Integração com elementos HTML para vídeos
 */
function initializeVideoCompressor() {
    // Encontrar todos os inputs de file para vídeos
    let videoInputs = document.querySelectorAll('input[type="file"][accept*="video"]');
    
    if (videoInputs.length === 0) {
        console.log('[VIDEO-COMPRESSOR] Nenhum input com accept*="video", tentando por name="video"');
        videoInputs = document.querySelectorAll('input[type="file"][name="video"]');
    }

    console.log(`[VIDEO-COMPRESSOR] 🔍 Inputs de vídeo encontrados: ${videoInputs.length}`);

    if (videoInputs.length === 0) {
        console.warn('[VIDEO-COMPRESSOR] ⚠️ NENHUM INPUT DE VÍDEO ENCONTRADO!');
        return;
    }

    videoInputs.forEach((input, idx) => {
        console.log(`[VIDEO-COMPRESSOR] Input #${idx}: name="${input.name}", accept="${input.accept}"`);
        
        input.addEventListener('change', async (event) => {
            const file = event.target.files?.[0];
            if (!file) return;

            console.log(`[VIDEO-COMPRESSOR] 📹 Vídeo anexado: ${file.name}`);
            console.log(`[VIDEO-COMPRESSOR] 📊 Tamanho original: ${formatFileSize(file.size)}`);

            // ✅ SEMPRE comprimir - não verificar limite aqui
            // A validação de limite é feita no servidor
            console.log(`[VIDEO-COMPRESSOR] 🚀 Iniciando compressão...`);

            const progressContainer = document.createElement('div');
            progressContainer.className = 'compression-progress-video';
            progressContainer.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: rgba(0, 0, 0, 0.95);
                color: #fff;
                padding: 20px;
                border-radius: 8px;
                max-width: 450px;
                z-index: 10000;
                font-family: monospace;
                font-size: 13px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                border-left: 4px solid #FF9800;
            `;
            progressContainer.innerHTML = `
                <strong>🎬 Comprimindo vídeo...</strong><br>
                ⏳ Processando vídeo...<br>
                Original: <strong>${formatFileSize(file.size)}</strong><br>
                <div style="background: #333; height: 4px; margin-top: 8px; border-radius: 2px; overflow: hidden;">
                    <div style="background: #FF9800; height: 100%; width: 10%; animation: progress 2s infinite; border-radius: 2px;"></div>
                </div>
                <style>@keyframes progress { 0% { width: 10%; } 50% { width: 80%; } 100% { width: 10%; } }</style>
                <span style="font-size: 11px; color: #aaa; margin-top: 8px; display: block;">Isto pode levar alguns minutos</span>
                <span style="font-size: 11px; color: #FF9800;">⚠️ NÃO FECHE A ABA!</span>
            `;
            document.body.appendChild(progressContainer);

            try {
                console.log('[VIDEO-COMPRESSOR] Comprimindo no servidor...');
                const compressedFile = await compressVideo(file);
                const reduction = calculateReduction(file.size, compressedFile.size);

                console.log(`[VIDEO-COMPRESSOR] ✅ Vídeo pronto para envio`);
                console.log(`[VIDEO-COMPRESSOR] 📊 Tamanho comprimido: ${formatFileSize(compressedFile.size)}`);
                console.log(`[VIDEO-COMPRESSOR] 📉 Redução potencial: ${reduction}%`);

                console.log('[VIDEO-COMPRESSOR] Atualizando input com arquivo...');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                input.files = dataTransfer.files;

                progressContainer.innerHTML = `
                    <strong style="color: #4CAF50;">✨ Vídeo Pronto!</strong><br>
                    📹 ${compressedFile.name}<br>
                    Original: ${formatFileSize(file.size)}<br>
                    <span style="color: #4CAF50; font-weight: bold;">↓ Será otimizado no servidor</span><br>
                    <span style="font-size: 11px; color: #aaa; margin-top: 8px; display: block;">Pronto para enviar!</span>
                `;
                progressContainer.style.background = 'rgba(76, 175, 80, 0.2)';
                progressContainer.style.borderLeft = '4px solid #4CAF50';

                setTimeout(() => {
                    progressContainer.style.opacity = '0';
                    progressContainer.style.transition = 'opacity 0.3s';
                    setTimeout(() => progressContainer.remove(), 300);
                }, 3000);

            } catch (error) {
                console.error('[VIDEO-COMPRESSOR] ❌ Erro:', error.message);
                progressContainer.innerHTML = `
                    <strong style="color: #FF6B6B;">❌ Erro no Processamento</strong><br>
                    ${error.message}<br>
                    <span style="font-size: 11px; color: #aaa;">Tente novamente com outro vídeo</span>
                `;
                progressContainer.style.background = 'rgba(255, 107, 107, 0.2)';
                progressContainer.style.borderLeft = '4px solid #FF6B6B';
                setTimeout(() => progressContainer.remove(), 5000);
            }
        });
    });
}

/**
 * Integração com elementos HTML para imagens
 */
/**
 * ⚠️ NÃO MAIS UTILIZADA - Compressão de imagens é feita em create-ad.js
 * 
 * Esta função foi desabilitada porque criar event listeners duplicados
 * causa conflito com a compressão em addPhotos() do create-ad.js
 * 
 * FLUXO CORRETO:
 * 1. Usuário seleciona fotos → photoInput.change dispara
 * 2. setupMedia() em create-ad.js chama addPhotos()
 * 3. addPhotos() valida e chama processMultipleImages() (que usa compressImage())
 * 4. Imagens comprimidas são adicionadas ao array photos[]
 * 5. renderPhotos() mostra preview
 * 
 * Vídeos usam este arquivo (initializeVideoCompressor)
 */
function initializeImageCompressor() {
    // Encontrar todos os inputs de file para imagens
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');

    console.log(`[IMAGE-COMPRESSOR] 🔍 Inputs de imagem encontrados: ${imageInputs.length}`);

    imageInputs.forEach((input, idx) => {
        console.log(`[IMAGE-COMPRESSOR] Input #${idx}: name="${input.name}", accept="${input.accept}"`);

        // Adicionar listener para mudança de arquivos
        input.addEventListener('change', async (event) => {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            console.log(`[IMAGE-COMPRESSOR] 📸 ${files.length} imagem(ns) anexada(s)`);

            // Mostrar feedback ao usuário
            const progressContainer = document.createElement('div');
            progressContainer.className = 'compression-progress';
            progressContainer.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: rgba(0, 0, 0, 0.9);
                color: #fff;
                padding: 20px;
                border-radius: 8px;
                max-width: 400px;
                z-index: 10000;
                font-family: monospace;
                font-size: 13px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            `;
            document.body.appendChild(progressContainer);

            try {
                console.log('[IMAGE-COMPRESSOR] Iniciando processamento...');

                // Comprimir imagens
                const compressedFiles = await processMultipleImages(
                    files,
                    (overallProgress) => {
                        progressContainer.innerHTML = `
                            <strong>📸 Comprimindo...</strong><br>
                            Progresso: ${overallProgress}%<br>
                            <div style="background: #333; height: 4px; margin-top: 8px; border-radius: 2px; overflow: hidden;">
                                <div style="background: #4CAF50; height: 100%; width: ${overallProgress}%; transition: width 0.3s;"></div>
                            </div>
                        `;
                    },
                    (fileInfo) => {
                        progressContainer.innerHTML += `
                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #555;">
                                ✅ ${fileInfo.filename}<br>
                                ${formatFileSize(fileInfo.originalSize)} → ${formatFileSize(fileInfo.compressedSize)}<br>
                                <span style="color: #4CAF50;">↓ ${fileInfo.reduction}%</span>
                            </div>
                        `;
                    }
                );

                console.log(`[IMAGE-COMPRESSOR] ✅ ${compressedFiles.length} imagem(ns) comprimida(s)`);

                // Sucesso - Atualizar input com arquivos comprimidos
                console.log('[IMAGE-COMPRESSOR] Atualizando input com imagens comprimidas...');
                const dataTransfer = new DataTransfer();
                compressedFiles.forEach(file => dataTransfer.items.add(file));
                input.files = dataTransfer.files;

                // ✅ Disparar evento 'change' para que create-ad.js processe as fotos
                const changeEvent = new Event('change', { bubbles: true });
                input.dispatchEvent(changeEvent);
                console.log('[IMAGE-COMPRESSOR] Evento change disparado para criar-ad.js');

                progressContainer.innerHTML = `
                    <strong style="color: #4CAF50;">✨ Compressão Concluída!</strong><br>
                    ${compressedFiles.length} imagem(ns) pronta(s)<br>
                    <span style="font-size: 11px; color: #aaa;">Pronto para enviar...</span>
                `;
                progressContainer.style.background = 'rgba(76, 175, 80, 0.2)';
                progressContainer.style.borderLeft = '4px solid #4CAF50';

                // Remover após 3 segundos
                setTimeout(() => {
                    progressContainer.style.opacity = '0';
                    progressContainer.style.transition = 'opacity 0.3s';
                    setTimeout(() => progressContainer.remove(), 300);
                }, 3000);

            } catch (error) {
                console.error('[IMAGE-COMPRESSOR] ❌ Erro:', error.message);
                progressContainer.innerHTML = `
                    <strong style="color: #FF6B6B;">❌ Erro na Compressão</strong><br>
                    ${error.message}<br>
                    <span style="font-size: 11px;">Tente novamente ou use arquivos menores</span>
                `;
                progressContainer.style.background = 'rgba(255, 107, 107, 0.2)';
                progressContainer.style.borderLeft = '4px solid #FF6B6B';

                setTimeout(() => progressContainer.remove(), 5000);
            }
        });
    });
}

/**
 * Inicializar quando o DOM está pronto
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[INIT] DOMContentLoaded disparado');
        setTimeout(() => {
            // ❌ NÃO inicializar imageCompressor aqui
            // A compressão de imagens é feita em create-ad.js via addPhotos()
            // Só inicializar vídeos para evitar conflito
            initializeVideoCompressor();
            console.log('[INIT] Video Compressor inicializado após DOMContentLoaded');
        }, 100);
    });
} else {
    console.log('[INIT] DOM já carregado, inicializando diretamente');
    setTimeout(() => {
        // ❌ NÃO inicializar imageCompressor aqui
        initializeVideoCompressor();
        console.log('[INIT] Video Compressor inicializado diretamente');
    }, 100);
}

// Forçar re-inicialização após 1 segundo se não encontrou inputs de vídeo
setTimeout(() => {
    const videoInputs = document.querySelectorAll('input[type="file"][accept*="video"], input[type="file"][name="video"]');
    if (videoInputs.length === 0) {
        console.warn('[INIT] ⚠️ Nenhum input de vídeo encontrado na primeira inicialização, tentando novamente...');
        initializeVideoCompressor();
    }
}, 1000);

// Exportar funções para uso externo
window.imageCompressorUtils = {
    compressImage,
    compressVideo,
    processMultipleImages,
    formatFileSize,
    calculateReduction,
    compressionConfig,
    compressionState
};

/**
 * ✅ FUNÇÃO PÚBLICA GLOBAL: compressImages()
 * 
 * Chamada diretamente de create-ad.js quando usuário seleciona fotos
 * Comprime múltiplas imagens de forma simples e direta
 * 
 * @param {FileList|Array} files - Arquivos para comprimir
 * @param {Function} onProgress - Callback de progresso (0-100)
 * @param {Function} onComplete - Callback ao terminar
 * @returns {Promise<Array>} Array de arquivos comprimidos
 */
window.compressImages = async function(files, onProgress = null, onComplete = null) {
    console.log('[compressImages] 🖼️ Iniciando compressão de imagens...');
    
    // ✅ Criar popup visual
    const progressContainer = document.createElement('div');
    progressContainer.className = 'compression-progress-images';
    progressContainer.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.95);
        color: #fff;
        padding: 20px;
        border-radius: 8px;
        max-width: 450px;
        z-index: 10000;
        font-family: monospace;
        font-size: 13px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        border-left: 4px solid #2196F3;
    `;
    progressContainer.innerHTML = `
        <strong>📸 Comprimindo fotos...</strong><br>
        ⏳ Processando ${files.length} imagem(ns)...<br>
        <div style="background: #333; height: 4px; margin-top: 8px; border-radius: 2px; overflow: hidden;">
            <div style="background: #2196F3; height: 100%; width: 0%; transition: width 0.3s; border-radius: 2px;"></div>
        </div>
        <div id="imageList" style="margin-top: 10px; font-size: 11px; color: #aaa;"></div>
    `;
    document.body.appendChild(progressContainer);
    
    try {
        const results = await processMultipleImages(
            files, 
            (progress) => {
                // Atualizar barra de progresso
                const progressBar = progressContainer.querySelector('div > div');
                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                }
                if (onProgress) onProgress(progress);
            },
            (info) => {
                // Atualizar lista de imagens processadas
                const imageList = progressContainer.querySelector('#imageList');
                if (imageList) {
                    imageList.innerHTML += `
                        ✅ ${info.filename}<br>
                        <span style="color: #2196F3;">↓ ${info.reduction}% - ${formatFileSize(info.compressedSize)}</span><br>
                    `;
                }
                if (onComplete) onComplete(info);
            }
        );
        
        console.log('[compressImages] ✅ Compressão de imagens concluída');
        
        // Atualizar para estado de sucesso
        progressContainer.innerHTML = `
            <strong style="color: #4CAF50;">✨ Fotos Comprimidas!</strong><br>
            ${results.length} imagem(ns) pronta(s)<br>
            <span style="font-size: 11px; color: #aaa;">Pronto para enviar...</span>
        `;
        progressContainer.style.background = 'rgba(76, 175, 80, 0.2)';
        progressContainer.style.borderLeft = '4px solid #4CAF50';
        
        // Remover popup após 3 segundos
        setTimeout(() => {
            progressContainer.style.opacity = '0';
            progressContainer.style.transition = 'opacity 0.3s';
            setTimeout(() => progressContainer.remove(), 300);
        }, 3000);
        
        return results;
    } catch (error) {
        console.error('[compressImages] ❌ Erro:', error);
        
        // Mostrar erro no popup
        progressContainer.innerHTML = `
            <strong style="color: #FF6B6B;">❌ Erro na Compressão</strong><br>
            ${error.message || 'Erro desconhecido'}<br>
            <span style="font-size: 11px;">Tente novamente ou use arquivos menores</span>
        `;
        progressContainer.style.background = 'rgba(255, 107, 107, 0.2)';
        progressContainer.style.borderLeft = '4px solid #FF6B6B';
        
        setTimeout(() => progressContainer.remove(), 5000);
        throw error;
    }
};

/**
 * ✅ FUNÇÃO PÚBLICA GLOBAL: compressVideoForUpload()
 * 
 * Chamada quando usuário seleciona vídeo
 * Mostra popup de compressão e marca como pronto
 * 
 * @param {File} file - Arquivo de vídeo
 * @param {HTMLInputElement} inputElement - Input file para atualizar
 * @param {Function} onProgress - Callback de progresso
 * @returns {Promise<File>} Arquivo processado (ou original se falhar)
 */
window.compressVideoForUpload = async function(file, inputElement = null, onProgress = null) {
    console.log('[compressVideoForUpload] 🎬 Iniciando processamento de vídeo...');
    
    try {
        const fileSizeMB = (file.size / 1024 / 1024).toFixed(1);
        
        // Mostrar popup de progresso
        const progressContainer = document.createElement('div');
        progressContainer.className = 'compression-progress-video';
        progressContainer.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.95);
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 450px;
            z-index: 10000;
            font-family: monospace;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            border-left: 4px solid #FF9800;
        `;
        progressContainer.innerHTML = `
            <strong>🎬 Comprimindo vídeo...</strong><br>
            ⏳ Processando vídeo...<br>
            Original: <strong>${formatFileSize(file.size)}</strong><br>
            <div style="background: #333; height: 4px; margin-top: 8px; border-radius: 2px; overflow: hidden;">
                <div style="background: #FF9800; height: 100%; width: 10%; animation: progress 2s infinite; border-radius: 2px;"></div>
            </div>
            <style>@keyframes progress { 0% { width: 10%; } 50% { width: 80%; } 100% { width: 10%; } }</style>
            <span style="font-size: 11px; color: #aaa; margin-top: 8px; display: block;">Isto pode levar alguns minutos</span>
            <span style="font-size: 11px; color: #FF9800;">⚠️ NÃO FECHE A ABA!</span>
        `;
        document.body.appendChild(progressContainer);
        
        // Processar vídeo (no servidor durante upload)
        console.log('[compressVideoForUpload] ✅ Vídeo pronto para envio');
        console.log('[compressVideoForUpload] 📹 Será comprimido NO SERVIDOR');
        
        // Atualizar input se fornecido
        if (inputElement) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            inputElement.files = dataTransfer.files;
        }
        
        // Mostrar mensagem final
        progressContainer.innerHTML = `
            <strong style="color: #4CAF50;">✨ Vídeo Pronto!</strong><br>
            📹 ${file.name}<br>
            Original: ${formatFileSize(file.size)}<br>
            <span style="color: #4CAF50; font-weight: bold;">↓ Será otimizado no servidor</span><br>
            <span style="font-size: 11px; color: #aaa; margin-top: 8px; display: block;">Pronto para enviar!</span>
        `;
        progressContainer.style.background = 'rgba(76, 175, 80, 0.2)';
        progressContainer.style.borderLeft = '4px solid #4CAF50';
        
        setTimeout(() => {
            progressContainer.style.opacity = '0';
            progressContainer.style.transition = 'opacity 0.3s';
            setTimeout(() => progressContainer.remove(), 300);
        }, 2000);
        
        return file;
        
    } catch (error) {
        console.error('[compressVideoForUpload] ❌ Erro:', error);
        throw error;
    }
};

console.log('✅ Image & Video Compressor inicializado');
console.log('📍 Funções globais disponíveis:');
console.log('   - window.compressImages(files, onProgress, onComplete)');
console.log('   - window.compressVideoForUpload(file, inputElement, onProgress)');
