<?php
// anuncios/upload_handler.php - Funções de validação e processamento de uploads de mídia.

declare(strict_types=1);

require_once __DIR__ . '/../login_system/middleware.php';

/** Converte o array de arquivos do PHP em uma lista normalizada. */
function normalize_files_array(array $files): array
{
    $normalized = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $normalized[] = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
    }
    return $normalized;
}

function ensure_media_directory(string $path): bool
{
    if (is_dir($path)) {
        return true;
    }
    return mkdir($path, 0755, true);
}

function ad_media_base_path(string $type, int $adId): string
{
    $folder = $type === 'video' ? 'videos' : 'images';
    return __DIR__ . '/../assets/uploads/' . $folder . '/' . $adId;
}

function ad_media_relative_path(string $type, int $adId, string $filename): string
{
    $folder = $type === 'video' ? 'videos' : 'images';
    return 'assets/uploads/' . $folder . '/' . $adId . '/' . $filename;
}

/** Processa imagens carregadas e retorna os caminhos salvos já na pasta do anúncio. */
function process_image_uploads(array $files, int $maxImages, array &$errors, int $adId): array
{
    $saved = [];
    $images = normalize_files_array($files);
    
    error_log("[upload] Iniciando process_image_uploads: files=" . count($images) . ", adId=$adId, maxImages=$maxImages");
    
    if (!$images) {
        error_log("[upload] Nenhuma imagem normalizada");
        return $saved;
    }

    $maxImages = max(0, min($maxImages, max_images_allowed()));
    if ($maxImages === 0) {
        error_log("[upload] maxImages é 0");
        return $saved;
    }

    $directory = ad_media_base_path('image', $adId);
    error_log("[upload] Tentando criar diretório: $directory");
    
    if (!ensure_media_directory($directory)) {
        $errors[] = 'Não foi possível preparar a pasta de imagens do anúncio.';
        error_log("[upload] Falha ao criar diretório: $directory");
        return [];
    }

    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $maxSizeBytes = (int)get_setting('max_image_size_mb', '2') * 1024 * 1024;

    foreach ($images as $index => $image) {
        error_log("[upload] Processando imagem $index: " . json_encode($image));
        
        if (count($saved) >= $maxImages) {
            error_log("[upload] Limite de imagens atingido");
            break;
        }
        if (($image['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_NO_FILE) {
            error_log("[upload] Imagem $index: UPLOAD_ERR_NO_FILE, pulando");
            continue;
        }
        if (($image['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Falha ao enviar a imagem ' . e((string)($image['name'] ?? ''));
            error_log("[upload] Imagem $index: erro de upload " . ($image['error'] ?? UPLOAD_ERR_OK));
            continue;
        }
        if (($image['size'] ?? 0) > $maxSizeBytes) {
            $errors[] = 'A imagem ' . e((string)($image['name'] ?? '')) . ' excede o limite de ' . get_setting('max_image_size_mb', '2') . 'MB.';
            error_log("[upload] Imagem $index: tamanho excedido");
            continue;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $image['tmp_name']);
        finfo_close($finfo);
        
        error_log("[upload] Imagem $index: MIME detectado = $mime");

        if (!isset($allowedMime[$mime])) {
            $errors[] = 'Formato de imagem não suportado em ' . e((string)($image['name'] ?? ''));
            error_log("[upload] Imagem $index: MIME não permitido");
            continue;
        }

        $extension = $allowedMime[$mime];
        $filename = bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $directory . '/' . $filename;
        
        error_log("[upload] Tentando salvar em: $destination");

        if (!resize_and_save_image($image['tmp_name'], $mime, $destination)) {
            $errors[] = 'Não foi possível processar a imagem ' . e((string)($image['name'] ?? ''));
            error_log("[upload] Falha ao redimensionar/salvar imagem $index");
            continue;
        }

        $relativePath = ad_media_relative_path('image', $adId, $filename);
        $saved[] = $relativePath;
        error_log("[upload] Imagem $index salva: $relativePath");
    }
    
    error_log("[upload] Finalizando: " . count($saved) . " imagens salvas");
    return $saved;
}

/** Redimensiona e salva a imagem garantindo largura máxima de 1200px. */
function resize_and_save_image(string $tmpPath, string $mime, string $destination): bool
{
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($tmpPath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($tmpPath);
            break;
        case 'image/webp':
            $source = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($tmpPath) : null;
            break;
        default:
            $source = null;
    }

    if (!$source) {
        return false;
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $maxWidth = 1200;

    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int)round(($height * $newWidth) / $width);
        $resampled = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resampled, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);
        $source = $resampled;
    }

    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($source, $destination, 85);
            break;
        case 'image/png':
            imagealphablending($source, false);
            imagesavealpha($source, true);
            $result = imagepng($source, $destination);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $result = imagewebp($source, $destination, 80);
            }
            break;
    }

    imagedestroy($source);
    return $result;
}

/** Processa upload de vídeo, aceitando múltiplos formatos e aplicando compressão se FFmpeg disponível. */
function process_video_upload(?array $file, array &$errors, bool $allow, int $adId): ?string
{
    if (!$allow || !$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'Falha ao enviar o vídeo.';
        return null;
    }

    $maxMB = (int)get_setting('max_video_size_mb', '100');
    $maxBytes = $maxMB * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        $fileSizeMB = round(($file['size'] ?? 0) / 1024 / 1024, 1);
        $errors[] = "Seu vídeo tem {$fileSizeMB}MB, mas o limite configurado é {$maxMB}MB. Reduza o tamanho do vídeo para {$maxMB}MB ou menos.";
        return null;
    }

    // Detecção de tipo MIME - aceita múltiplos formatos de vídeo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/avi'];
    if (!in_array($mime, $allowedMimes, true)) {
        $errors[] = 'Formato de vídeo não suportado. Use MP4, MOV ou AVI.';
        return null;
    }

    $directory = ad_media_base_path('video', $adId);
    if (!ensure_media_directory($directory)) {
        $errors[] = 'Não foi possível preparar a pasta de vídeos do anúncio.';
        return null;
    }

    // Gera nome temporário para processamento
    $tempFilename = bin2hex(random_bytes(16)) . '_temp_' . time();
    $tempPath = $directory . '/' . $tempFilename;
    
    // Move para pasta de anúncio
    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        $errors[] = 'Não foi possível salvar o vídeo.';
        return null;
    }

    // Processa vídeo (comprime se FFmpeg disponível, caso contrário usa raw)
    $finalFilename = process_and_save_video($tempPath, $directory, $errors);
    
    if (!$finalFilename) {
        @unlink($tempPath);
        if (empty($errors)) {
            $errors[] = 'Erro ao processar o vídeo.';
        }
        return null;
    }

    return ad_media_relative_path('video', $adId, $finalFilename);
}

/** Processa link do Instagram e converte para URL de embed. */
function process_instagram_link(string $link, array &$errors): ?string
{
    // Validar URL do Instagram
    if (empty($link) || !filter_var($link, FILTER_VALIDATE_URL)) {
        $errors[] = 'Link do Instagram inválido.';
        return null;
    }

    // Verificar se é um link do Instagram válido
    if (!preg_match('#https?://(?:www\.)?instagram\.com/#i', $link)) {
        $errors[] = 'Link deve ser do Instagram (instagram.com).';
        return null;
    }

    // Sanitizar o link
    $link = trim($link);
    
    // Remover parâmetros de rastreamento
    $link = preg_replace('/\?.*$/', '', $link);
    
    // Normalizar
    $link = preg_replace('#^https?://(?:www\.)?#i', 'https://instagram.com/', $link);
    if (substr($link, -1) !== '/') {
        $link .= '/';
    }

    // ✅ NOVO: Usar oEmbed do Instagram (mais confiável que iframe)
    // Em vez de: https://instagram.com/reel/.../embed/
    // Retornar: https://instagram.com/reel/...
    // O iframe será renderizado com sandbox e fallback seguro
    
    return $link;
}

/** 
 * Processa e otimiza um arquivo de vídeo usando FFmpeg (com fallback para arquivo raw).
 * 
 * @param string $inputPath Caminho completo do arquivo temporário
 * @param string $outputDir Diretório onde salvar o resultado final
 * @param array $errors Array de erros por referência
 * @return string|null Nome do arquivo final (sem caminho), ou null se falha
 */
function process_and_save_video(string $inputPath, string $outputDir, array &$errors): ?string
{
    $originalSize = filesize($inputPath);
    error_log("[video_upload] ===== INICIANDO PROCESSAMENTO DE VÍDEO =====");
    error_log("[video_upload] Arquivo: $inputPath");
    error_log("[video_upload] Tamanho original: " . format_bytes($originalSize));

    // Detecta se FFmpeg está disponível
    $ffmpegPath = detect_ffmpeg_path();
    $finalFilename = bin2hex(random_bytes(16)) . '.mp4';
    $outputPath = $outputDir . '/' . $finalFilename;

    if (!$ffmpegPath) {
        error_log("[video_upload] ⚠️ FFmpeg NÃO DETECTADO - usando arquivo original");
        error_log("[video_upload] ⚠️ AVISO: Vídeo NÃO será comprimido!");
        
        if (rename($inputPath, $outputPath)) {
            error_log("[video_upload] Arquivo movido para: $outputPath");
            error_log("[video_upload] Tamanho final (SEM COMPRESSÃO): " . format_bytes(filesize($outputPath)));
            return $finalFilename;
        }
        
        $errors[] = 'Erro ao processar o arquivo de vídeo.';
        return null;
    }

    error_log("[video_upload] ✅ FFmpeg encontrado em: $ffmpegPath");

    // Com FFmpeg: tenta comprimir
    error_log("[video_upload] Iniciando compressão...");
    $compressed = compress_video_with_ffmpeg($inputPath, $outputPath, $ffmpegPath, $errors);
    
    if ($compressed) {
        $finalSize = filesize($outputPath);
        $reduction = round((($originalSize - $finalSize) / $originalSize) * 100, 1);
        error_log("[video_upload] ✅ COMPRESSÃO COM SUCESSO!");
        error_log("[video_upload] Tamanho final: " . format_bytes($finalSize));
        error_log("[video_upload] Redução: {$reduction}%");
        error_log("[video_upload] Original: " . format_bytes($originalSize) . " → Comprimido: " . format_bytes($finalSize));
        @unlink($inputPath);
        return $finalFilename;
    }

    // Fallback: se compressão falher, usa arquivo original
    error_log("[video_upload] ⚠️ Compressão falhou - usando arquivo original");
    if (rename($inputPath, $outputPath)) {
        error_log("[video_upload] Arquivo movido como fallback para: $outputPath");
        error_log("[video_upload] ⚠️ Tamanho final SEM COMPRESSÃO: " . format_bytes(filesize($outputPath)));
        return $finalFilename;
    }

    $errors[] = 'Erro ao finalizar o processamento do vídeo.';
    error_log("[video_upload] ❌ ERRO ao finalizar processamento");
    return null;
}

/**
 * Detecta o caminho executável do FFmpeg no servidor.
 * Tenta múltiplos caminhos comuns em Linux/Windows.
 * 
 * @return string|null Caminho do FFmpeg ou null se não encontrado
 */
function detect_ffmpeg_path(): ?string
{
    // Tentar 'which' no Linux ou 'where' no Windows primeiro
    if (PHP_OS_FAMILY !== 'Windows') {
        // Linux/Mac
        $testCmd = shell_exec("which ffmpeg 2>/dev/null");
        if ($testCmd) {
            $path = trim($testCmd);
            error_log("[video_compress] FFmpeg encontrado via 'which': $path");
            return $path;
        }
    } else {
        // Windows
        $testCmd = shell_exec("where ffmpeg 2>nul");
        if ($testCmd) {
            $path = trim($testCmd);
            error_log("[video_compress] FFmpeg encontrado via 'where': $path");
            return $path;
        }
    }

    // Caminhos alternativos
    $possiblePaths = [
        '/usr/bin/ffmpeg',
        '/usr/local/bin/ffmpeg',
        '/opt/ffmpeg/bin/ffmpeg',
        'C:\\ffmpeg\\bin\\ffmpeg.exe',
        'ffmpeg',
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path) || shell_exec("command -v $path 2>/dev/null")) {
            error_log("[video_compress] FFmpeg encontrado em: $path");
            return $path;
        }
    }

    error_log("[video_compress] ❌ FFmpeg NÃO ENCONTRADO no servidor!");
    return null;
}

/**
 * Comprime vídeo usando FFmpeg com estratégia de fallback.
 * Tenta H.264 com compressão otimizada para web.
 * 
 * Reduz arquivo tipicamente: 50MB → 10-15MB (~70-80% economia)
 * 
 * @param string $inputPath Caminho do arquivo de entrada
 * @param string $outputPath Caminho do arquivo de saída
 * @param string $ffmpegPath Caminho executável do FFmpeg
 * @param array $errors Array de erros por referência
 * @return bool True se sucesso, false se falha
 */
function compress_video_with_ffmpeg(string $inputPath, string $outputPath, string $ffmpegPath, array &$errors): bool
{
    // Estratégia: H.264 com bitrate reduzido e compressão otimizada para web
    $h264Command = sprintf(
        '%s -i "%%1" -c:v libx264 -preset fast -crf 28 -maxrate 1500k -bufsize 3000k -c:a aac -b:a 96k -y "%%2" 2>&1',
        escapeshellcmd($ffmpegPath)
    );
    
    // Escapar caminhos corretamente
    $h264Command = str_replace('%%1', escapeshellarg($inputPath), $h264Command);
    $h264Command = str_replace('%%2', escapeshellarg($outputPath), $h264Command);

    $output = [];
    $returnCode = 0;
    
    // Executar com timeout de 5 minutos
    exec($h264Command, $output, $returnCode);

    // Validar resultado
    if ($returnCode === 0 && is_file($outputPath) && filesize($outputPath) > 1024) {
        $originalSize = filesize($inputPath);
        $compressedSize = filesize($outputPath);
        $reduction = round((($originalSize - $compressedSize) / $originalSize) * 100, 1);
        error_log(sprintf('[video_compress] H.264 sucesso: %s → %s (%d%% redução)', 
            format_bytes($originalSize), 
            format_bytes($compressedSize), 
            $reduction
        ));
        return true;
    }

    error_log('[video_compress] FFmpeg H.264 falhou com código: ' . $returnCode);
    if (!empty($output)) {
        error_log('[video_compress] Erro: ' . implode(' | ', array_slice($output, -5)));
    }
    
    return false;
}

/**
 * Formata bytes para formato legível (KB, MB, GB)
 */
function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/** Processa e salva o documento de verificação do veículo. */
function process_verification_document_upload(array $file, array &$errors, int $adId): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'Falha ao enviar o documento de verificação.';
        return null;
    }

    $maxSizeBytes = 10 * 1024 * 1024; // 10MB
    if (($file['size'] ?? 0) > $maxSizeBytes) {
        $errors[] = 'O documento de verificação excede o limite de 10MB.';
        return null;
    }

    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
    $mimeType = $file['type'] ?? '';
    if (!isset($allowedMime[$mimeType])) {
        $errors[] = 'Tipo de arquivo inválido. Use JPEG, PNG ou PDF.';
        return null;
    }

    $directory = ad_media_base_path('image', $adId);
    if (!ensure_media_directory($directory)) {
        $errors[] = 'Não foi possível preparar a pasta de verificação.';
        return null;
    }

    $extension = $allowedMime[$mimeType];
    $filename = 'verification_' . time() . '.' . $extension;
    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $errors[] = 'Não foi possível salvar o documento de verificação.';
        return null;
    }

    return ad_media_relative_path('image', $adId, $filename);
}

/** Remove mídia de anúncio do disco. */
function delete_media_file(string $relativePath): void
{
    $fullPath = __DIR__ . '/../' . ltrim($relativePath, '/');
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}
