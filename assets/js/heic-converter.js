/**
 * HEIC to JPG Converter
 * Converte automaticamente imagens HEIC para JPG no navegador
 * Compatível com iOS Safari, Chrome, Firefox, Edge
 * 
 * Uso: const jpgFile = await convertHeicToJpg(heicFile);
 */

async function convertHeicToJpg(heicFile) {
    // Se não for HEIC, retorna o arquivo original
    if (heicFile.type !== 'image/heic' && heicFile.type !== 'image/heif') {
        return heicFile;
    }

    try {
        // Tenta usar heic2any se disponível
        if (typeof heic2any !== 'undefined') {
            const jpgBlob = await heic2any({
                blob: heicFile,
                toType: 'image/jpeg',
                quality: 0.85
            });

            // Cria novo File com nome .jpg
            const jpgFile = new File(
                [jpgBlob],
                heicFile.name.replace(/\.heic$/i, '.jpg').replace(/\.heif$/i, '.jpg'),
                { type: 'image/jpeg' }
            );

            console.log(`[HEIC] ✅ Convertido: ${heicFile.name} (${(heicFile.size / 1024 / 1024).toFixed(2)}MB) → ${jpgFile.name} (${(jpgFile.size / 1024 / 1024).toFixed(2)}MB)`);
            return jpgFile;
        }

        // Fallback: se heic2any não estiver disponível, retorna original
        console.warn(`[HEIC] ⚠️ Biblioteca heic2any não encontrada, tentando enviar HEIC original`);
        return heicFile;
    } catch (error) {
        console.error(`[HEIC] ❌ Erro ao converter: ${error.message}`);
        // Fallback: retorna arquivo original para tentar enviar
        return heicFile;
    }
}

/**
 * Carrega a biblioteca heic2any dinamicamente (CDN)
 * Executa automaticamente na primeira conversão
 */
async function ensureHeic2anyLoaded() {
    if (typeof heic2any !== 'undefined') {
        return; // Já carregada
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js';
        script.async = true;
        script.onload = () => {
            console.log('[HEIC] ✅ Biblioteca heic2any carregada');
            resolve();
        };
        script.onerror = () => {
            console.warn('[HEIC] ⚠️ Não foi possível carregar heic2any do CDN');
            resolve(); // Não falha, apenas avisa
        };
        document.head.appendChild(script);
    });
}
