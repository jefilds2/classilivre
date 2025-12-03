<?php
/**
 * Check FFmpeg Installation Status
 * 
 * Acesse: https://seu-site.com/api/check_ffmpeg.php
 * Verifica se FFmpeg está instalado e funcional no servidor
 */

header('Content-Type: application/json');

// Detectar FFmpeg
function detect_ffmpeg_path() {
    $paths = [
        '/usr/bin/ffmpeg',
        '/usr/local/bin/ffmpeg',
        '/opt/ffmpeg/bin/ffmpeg',
        '/opt/local/bin/ffmpeg',
        'ffmpeg', // Tenta encontrar no PATH
    ];
    
    foreach ($paths as $path) {
        if (@is_executable($path)) {
            return $path;
        }
    }
    
    return null;
}

// Tentar com which/where
if (PHP_OS_FAMILY !== 'Windows') {
    $output = shell_exec('which ffmpeg 2>/dev/null');
    if ($output) {
        $ffmpegPath = trim($output);
        if (@is_executable($ffmpegPath)) {
            $result = [
                'status' => 'success',
                'installed' => true,
                'path' => $ffmpegPath,
                'message' => 'FFmpeg detectado com sucesso!',
                'version_output' => shell_exec("$ffmpegPath -version 2>&1 | head -n 1")
            ];
            echo json_encode($result, JSON_PRETTY_PRINT);
            exit;
        }
    }
} else {
    // Windows
    $output = shell_exec('where ffmpeg 2>nul');
    if ($output) {
        $ffmpegPath = trim($output);
        $result = [
            'status' => 'success',
            'installed' => true,
            'path' => $ffmpegPath,
            'message' => 'FFmpeg detectado com sucesso!',
            'version_output' => shell_exec("$ffmpegPath -version 2>&1 | head -n 1")
        ];
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
}

// Fallback: testar caminhos conhecidos
$ffmpegPath = detect_ffmpeg_path();

if ($ffmpegPath) {
    $version = shell_exec("$ffmpegPath -version 2>&1 | head -n 1");
    $result = [
        'status' => 'success',
        'installed' => true,
        'path' => $ffmpegPath,
        'message' => 'FFmpeg detectado com sucesso!',
        'version_output' => trim($version)
    ];
} else {
    $result = [
        'status' => 'error',
        'installed' => false,
        'path' => null,
        'message' => 'FFmpeg NÃO encontrado no servidor',
        'help' => 'Contate seu provedor de hospedagem para instalar FFmpeg',
        'note' => 'Em cPanel, geralmente está disponível. Tente contatar suporte.'
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
