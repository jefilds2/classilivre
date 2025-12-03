#!/bin/bash
# Script de Monitoramento - Processos Simultâneos do ClassiLivre
# Uso: bash monitor_processes.sh
# Mostra quantos processos PHP estão rodando (deve ficar < 25)

echo "════════════════════════════════════════════════════════════"
echo "📊 MONITORAMENTO: Processos Simultâneos"
echo "════════════════════════════════════════════════════════════"
echo ""

# Total de processos no servidor
TOTAL=$(ps aux | wc -l)
echo "✓ Total de processos no servidor: $TOTAL"
echo ""

# Processos PHP (os que mais consomem recurso)
PHP_PROCESSES=$(ps aux | grep -i php | grep -v grep | wc -l)
echo "✓ Processos PHP em execução: $PHP_PROCESSES"

if [ $PHP_PROCESSES -gt 20 ]; then
    echo "   ⚠️  AVISO: Muitos processos PHP! (limite é 25)"
    echo ""
    echo "   Processos PHP detalhados:"
    ps aux | grep -i php | grep -v grep | awk '{print $2, $3, $11}' | head -20
fi
echo ""

# Email queue pendente (se houver MySQL disponível)
echo "✓ Verificando fila de emails..."
MYSQL_BIN="/usr/bin/mysql"
if [ -x "$MYSQL_BIN" ]; then
    PENDING_EMAILS=$($MYSQL_BIN -h localhost -u hg8cf561_admin -p'lZkS2LAQBWbq' -e "SELECT COUNT(*) FROM hg8cf561_classiautos.email_queue WHERE status='queued';" 2>/dev/null | tail -1)
    if [ -n "$PENDING_EMAILS" ]; then
        echo "   Emails na fila: $PENDING_EMAILS"
        if [ $PENDING_EMAILS -gt 1000 ]; then
            echo "   ⚠️  AVISO: Fila de email muito grande!"
        fi
    fi
fi
echo ""

# CPU e Memória
echo "✓ Recursos do servidor:"
TOP_OUTPUT=$(top -bn1 | head -3)
echo "$TOP_OUTPUT"
echo ""

# Conexões MySQL
echo "✓ Conexões MySQL ativas:"
MYSQL_CONN=$($MYSQL_BIN -h localhost -u hg8cf561_admin -p'lZkS2LAQBWbq' -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null | tail -1)
echo "   $MYSQL_CONN"
echo ""

echo "════════════════════════════════════════════════════════════"
echo "✅ Recomendação:"
if [ $PHP_PROCESSES -lt 20 ]; then
    echo "   ✓ Tudo OK! Processos PHP dentro do limite."
else
    echo "   ❌ Processos PHP acima de 20. Revisar CRONs."
    echo "   Próximas ações:"
    echo "   1. Aumentar intervalo dos CRONs no cPanel"
    echo "   2. Reduzir LIMIT nos scripts (de 50 para 10-20)"
    echo "   3. Reiniciar PHP-FPM: systemctl restart php-fpm"
fi
echo "════════════════════════════════════════════════════════════"
