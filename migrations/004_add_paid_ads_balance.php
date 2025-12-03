<?php
/**
 * Migration 004: OBSOLETA - Sistema de créditos individuais removido
 * 
 * Esta migração pertencia ao sistema de saldos de anúncios por usuário
 * que foi descontinuado e completamente removido em 28/10/2025.
 * 
 * As colunas free_paid_ads_balance e free_ads_remaining (se existissem)
 * foram removidas em favor do novo sistema de moedas Clivre.
 * 
 * Ver migrations/008_add_coin_system.php para o novo sistema.
 */

declare(strict_types=1);

// Sem operações - apenas documentação
echo "[004] ℹ Migração obsoleta (sistema de créditos removido completamente)\n";
exit(0);
?>
