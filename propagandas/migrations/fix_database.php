<?php
/**
 * Script para corrigir tabelas do banco de dados
 * Caminho: /propagandas/migrations/fix_database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../sqlconnect.php';

echo "<pre style='background: #f0f0f0; padding: 20px; border-radius: 5px; font-family: monospace;'>";
echo "🔧 INICIANDO CORREÇÃO DO BANCO DE DADOS...\n\n";

try {
    $queries = [
        "ALTER TABLE propagandas_campanhas ADD COLUMN campaign_type ENUM('web', 'local') DEFAULT 'web' AFTER title" => "Adicionando coluna campaign_type",
        "ALTER TABLE propagandas_campanhas ADD COLUMN company_name VARCHAR(255) NULL AFTER campaign_type" => "Adicionando coluna company_name",
        "ALTER TABLE propagandas_campanhas ADD COLUMN whatsapp VARCHAR(20) NULL AFTER company_name" => "Adicionando coluna whatsapp",
        "ALTER TABLE propagandas_campanhas ADD COLUMN phone VARCHAR(20) NULL AFTER whatsapp" => "Adicionando coluna phone",
        "ALTER TABLE propagandas_campanhas ADD COLUMN company_image VARCHAR(500) NULL AFTER phone" => "Adicionando coluna company_image",
        "ALTER TABLE propagandas_campanhas MODIFY COLUMN affiliate_link VARCHAR(500) NULL" => "Modificando affiliate_link para NULL",
    ];

    foreach ($queries as $sql => $desc) {
        echo "▶ $desc... ";
        try {
            $mysqli->query($sql);
            echo "✅ OK\n";
        } catch (Exception $e) {
            // Se a coluna já existe, não é erro crítico
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠️ Coluna já existe (OK)\n";
            } else {
                echo "❌ ERRO: " . $e->getMessage() . "\n";
            }
        }
    }

    // Criar índices
    echo "\n▶ Adicionando índices... ";
    try {
        $mysqli->query("ALTER TABLE propagandas_campanhas ADD INDEX idx_campaign_type (campaign_type)");
        $mysqli->query("ALTER TABLE propagandas_campanhas ADD INDEX idx_company_name (company_name)");
        echo "✅ OK\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⚠️ Índices já existem (OK)\n";
        } else {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
        }
    }

    // Verificar
    echo "\n✅ VERIFICANDO ESTRUTURA FINAL...\n\n";
    $result = $mysqli->query("DESCRIBE propagandas_campanhas");
    while ($row = $result->fetch_assoc()) {
        echo "  • " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }

    echo "\n✅ BANCO DE DADOS CORRIGIDO COM SUCESSO!\n";

} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
