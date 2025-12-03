<?php
// config/upload.php - Configurações de compressão e upload de mídia

declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════
 * CONFIGURAÇÕES DE COMPRESSÃO DE IMAGENS (CLIENT-SIDE)
 * ═══════════════════════════════════════════════════════════════
 */

// Máximo tamanho da imagem comprimida (em MB)
const IMAGE_COMPRESSION_MAX_SIZE_MB = 3;

// Máxima dimensão (largura ou altura) da imagem
const IMAGE_COMPRESSION_MAX_WIDTH_HEIGHT = 1920;

// Qualidade inicial de compressão (0-1)
const IMAGE_COMPRESSION_INITIAL_QUALITY = 0.9;

// Iterações máximas de compressão
const IMAGE_COMPRESSION_MAX_ITERATIONS = 10;

// Usar Web Worker para compressão (não trava UI)
const IMAGE_COMPRESSION_USE_WEB_WORKER = true;

// Formato de saída
const IMAGE_COMPRESSION_OUTPUT_FORMAT = 'image/webp';

// Preservar metadados EXIF
const IMAGE_COMPRESSION_PRESERVE_EXIF = true;

// Manter resolução (só reduz qualidade)
const IMAGE_COMPRESSION_ALWAYS_KEEP_RESOLUTION = false;

/**
 * ═══════════════════════════════════════════════════════════════
 * CONFIGURAÇÕES DE COMPRESSÃO DE VÍDEOS (CLIENT-SIDE)
 * ═══════════════════════════════════════════════════════════════
 */

// Bitrate de vídeo para compressão (em kbps)
const VIDEO_COMPRESSION_BITRATE = '1500k'; // 1.5 Mbps - qualidade boa para web

// Bitrate de áudio para compressão (em kbps)
const VIDEO_COMPRESSION_AUDIO_BITRATE = '96k'; // 96 kbps - suficiente para áudio

// Máxima largura da imagem do vídeo
const VIDEO_COMPRESSION_MAX_WIDTH = 1280; // 1280px

// Presets de compressão: ultrafast, superfast, veryfast, faster, fast, medium, slow, slower, veryslow
const VIDEO_COMPRESSION_PRESET = 'fast'; // Mais rápido

/**
 * ═══════════════════════════════════════════════════════════════
 * CONFIGURAÇÕES DE UPLOAD (SERVER-SIDE)
 * ═══════════════════════════════════════════════════════════════
 */

// Máximo tamanho aceito no servidor (segurança)
const MAX_IMAGE_SIZE_BYTES = 5 * 1024 * 1024; // 5MB

// Máximo tamanho para vídeos
const MAX_VIDEO_SIZE_BYTES = 500 * 1024 * 1024; // 500MB

// Tipos MIME aceitos para imagens
const ALLOWED_IMAGE_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/webp',
];

// Tipos MIME aceitos para vídeos
const ALLOWED_VIDEO_MIME_TYPES = [
    'video/mp4',
    'video/quicktime',
    'video/x-msvideo',
];

// Extensões aceitas para imagens
const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

// Extensões aceitas para vídeos
const ALLOWED_VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'mkv'];

// Diretório base de uploads
const UPLOAD_BASE_DIR = __DIR__ . '/../assets/uploads';

/**
 * ═══════════════════════════════════════════════════════════════
 * UTILIDADES
 * ═══════════════════════════════════════════════════════════════
 */

/**
 * Retorna as configurações de compressão em formato JSON
 * para uso no JavaScript cliente
 */
function get_image_compression_config(): array
{
    return [
        'maxSizeMB' => IMAGE_COMPRESSION_MAX_SIZE_MB,
        'maxWidthOrHeight' => IMAGE_COMPRESSION_MAX_WIDTH_HEIGHT,
        'useWebWorker' => IMAGE_COMPRESSION_USE_WEB_WORKER,
        'initialQuality' => IMAGE_COMPRESSION_INITIAL_QUALITY,
        'maxIteration' => IMAGE_COMPRESSION_MAX_ITERATIONS,
        'preserveExif' => IMAGE_COMPRESSION_PRESERVE_EXIF,
        'alwaysKeepResolution' => IMAGE_COMPRESSION_ALWAYS_KEEP_RESOLUTION,
        'fileType' => IMAGE_COMPRESSION_OUTPUT_FORMAT,
    ];
}

/**
 * Retorna as configurações de compressão de vídeos
 */
function get_video_compression_config(): array
{
    return [
        'videoBitrate' => VIDEO_COMPRESSION_BITRATE,
        'audioBitrate' => VIDEO_COMPRESSION_AUDIO_BITRATE,
        'maxWidth' => VIDEO_COMPRESSION_MAX_WIDTH,
        'preset' => VIDEO_COMPRESSION_PRESET,
    ];
}
?>
