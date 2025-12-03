<?php

declare(strict_types=1);

/**
 * Configurações do Sistema de Propaganda
 * Caminho: /propagandas/config.php
 */

// Verificar se está sendo acessado corretamente
if (!defined('PROPAGANDAS_INITIALIZED')) {
    define('PROPAGANDAS_INITIALIZED', true);
}

// Caminho base do sistema de propagandas
if (!defined('PROPAGANDAS_BASE_PATH')) {
    define('PROPAGANDAS_BASE_PATH', __DIR__);
}

if (!defined('PROPAGANDAS_ADMIN_PATH')) {
    define('PROPAGANDAS_ADMIN_PATH', __DIR__ . '/admin');
}

if (!defined('PROPAGANDAS_FRONTEND_PATH')) {
    define('PROPAGANDAS_FRONTEND_PATH', __DIR__ . '/frontend');
}

if (!defined('PROPAGANDAS_UPLOADS_PATH')) {
    define('PROPAGANDAS_UPLOADS_PATH', __DIR__ . '/admin/uploads');
}

// URLs
if (!defined('PROPAGANDAS_UPLOADS_URL')) {
    define('PROPAGANDAS_UPLOADS_URL', '/propagandas/admin/uploads');
}

// Configurações de tamanho de arquivo
const PROPAGANDAS_MAX_FILE_SIZE = 5242880; // 5MB
const PROPAGANDAS_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const PROPAGANDAS_ALLOWED_MIMETYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
];

// Configurações padrão
const PROPAGANDAS_DEFAULT_CONFIG = [
    'initial_delay_ms' => 3000,
    'reopen_delay_ms' => 300000,
    'max_displays_per_period' => 1,
    'position' => 'center',
    'display_frequency' => 'por_sessao',
    'rotation_type' => 'random',
];

// Frequências disponíveis
const PROPAGANDAS_FREQUENCIES = [
    'sempre' => 'Sempre (sem limite)',
    'por_sessao' => 'Por sessão do navegador',
    'por_dia' => 'Uma vez por dia',
    'por_semana' => 'Uma vez por semana',
];

// Modos de exibição
const PROPAGANDAS_DISPLAY_MODES = [
    'normal' => 'Normal (rotaciona com outras)',
    'exclusive' => 'Exclusiva (aparece sozinha)',
    'prioritized' => 'Priorizada (maior chance)',
];

// Tipos de rotação
const PROPAGANDAS_ROTATION_TYPES = [
    'random' => 'Aleatória',
    'sequential' => 'Sequencial (Carousel)',
    'both' => 'Ambas (Aleatória + Carousel)',
];

// Posições no tela
const PROPAGANDAS_POSITIONS = [
    'center' => 'Centro da tela',
    'bottom-right' => 'Canto inferior direito',
    'bottom-left' => 'Canto inferior esquerdo',
];

/**
 * Função auxiliar para obter caminho relativo
 */
function propagandas_asset_path(string $path): string
{
    return '/' . ltrim($path, '/');
}

/**
 * Função auxiliar para validar extensão de arquivo
 */
function propagandas_is_valid_image(string $filename): bool
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, PROPAGANDAS_ALLOWED_EXTENSIONS, true);
}

/**
 * Gerar nome único para arquivo
 */
function propagandas_generate_filename(string $originalName): string
{
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    return sprintf(
        'campanha_%s_%d.%s',
        date('Y-m-d'),
        time(),
        strtolower($ext)
    );
}

/**
 * Formatar tamanho de arquivo
 */
function propagandas_format_filesize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

// NÃO incluir sqlconnect aqui - deixa para ser feito ANTES de enviar headers
// require_once __DIR__ . '/../sqlconnect.php';
