<?php
/**
 * Teste do Sistema de Analytics
 * Acesse: /propagandas/analytics/test.php
 */

session_start();

// Teste 1: Verificar se tracker.php pode ser carregado
echo "<h2>🧪 Teste do Sistema de Analytics</h2>";
echo "<hr>";

echo "<h3>✓ Teste 1: Carregar tracker.php</h3>";
try {
    require_once __DIR__ . '/tracker.php';
    echo "✅ tracker.php carregado com sucesso";
} catch (Throwable $e) {
    echo "❌ Erro: " . $e->getMessage();
}

echo "<br><hr>";

// Teste 2: Verificar se AnalyticsTracker existe
echo "<h3>✓ Teste 2: Verificar classe AnalyticsTracker</h3>";
if (class_exists('AnalyticsTracker')) {
    echo "✅ Classe AnalyticsTracker existe";
} else {
    echo "❌ Classe AnalyticsTracker não encontrada";
}

echo "<br><hr>";

// Teste 3: Verificar pasta logs
echo "<h3>✓ Teste 3: Verificar pasta logs</h3>";
$logsDir = __DIR__ . '/logs';
if (is_dir($logsDir)) {
    echo "✅ Pasta /logs existe";
    echo "<br>Permissões: " . substr(sprintf('%o', fileperms($logsDir)), -4);
} else {
    echo "❌ Pasta /logs não existe";
    echo "<br>Criando pasta...";
    if (@mkdir($logsDir, 0755, true)) {
        echo "✅ Pasta criada com sucesso";
    } else {
        echo "❌ Não foi possível criar pasta";
    }
}

echo "<br><hr>";

// Teste 4: Tentar rastrear um evento
echo "<h3>✓ Teste 4: Rastrear evento de teste</h3>";
try {
    AnalyticsTracker::trackPageLoad('test');
    echo "✅ Evento rastreado com sucesso";
    
    // Verificar se arquivo foi criado
    $today = date('Y-m-d');
    $logFile = $logsDir . "/raw_{$today}.log";
    if (file_exists($logFile)) {
        echo "<br>✅ Arquivo log criado: " . basename($logFile);
        $size = filesize($logFile);
        echo "<br>Tamanho: " . $size . " bytes";
        
        // Mostrar última linha
        $lines = file($logFile);
        if (!empty($lines)) {
            $lastLine = trim(end($lines));
            echo "<br>Último evento: " . substr($lastLine, 0, 100) . "...";
        }
    } else {
        echo "<br>❌ Arquivo log não foi criado";
    }
} catch (Throwable $e) {
    echo "❌ Erro ao rastrear: " . $e->getMessage();
}

echo "<br><hr>";

// Teste 5: Dashboard acessível
echo "<h3>✓ Teste 5: Verificar Dashboard</h3>";
if (file_exists(__DIR__ . '/dashboard.php')) {
    echo "✅ dashboard.php existe";
    echo "<br><a href='/propagandas/analytics/dashboard.php' target='_blank'>Acessar Dashboard →</a>";
} else {
    echo "❌ dashboard.php não encontrado";
}

echo "<br><hr>";

// Teste 6: Consolidator acessível
echo "<h3>✓ Teste 6: Verificar Consolidador</h3>";
if (file_exists(__DIR__ . '/consolidator.php')) {
    echo "✅ consolidator.php existe";
    echo "<br><a href='/propagandas/analytics/consolidator.php?cron=1' target='_blank'>Executar Consolidação →</a>";
} else {
    echo "❌ consolidator.php não encontrado";
}

echo "<br><hr>";

echo "<h3>📊 Resumo:</h3>";
echo "Se todos os testes passaram (✅), o sistema está funcionando corretamente!";
echo "<br><br>";
echo "<a href='/propagandas/admin/index.php'>← Voltar ao Admin</a>";
?>
