<?php
/**
 * INSTRUÇÕES DE USO DO SISTEMA DE ANALYTICS
 * 
 * 1. ACESSO AO DASHBOARD
 *    URL: /propagandas/analytics/dashboard.php
 *    Apenas admins podem acessar
 *    Botão de acesso rápido em: /propagandas/admin/index.php
 * 
 * 2. DADOS COMEÇAM A SER RASTREADOS
 *    - Imediatamente para páginas principais (index, listar, servicos)
 *    - Os dados aparecem no arquivo: /propagandas/analytics/logs/raw_YYYY-MM-DD.log
 * 
 * 3. CONSOLIDAÇÃO DIÁRIA
 *    Os logs são consolidados automaticamente quando consolidator.php é executado.
 *    Você pode:
 *    
 *    A) Configurar CRON (automático):
 *       59 23 * * * curl -s "https://seusite.com/propagandas/analytics/consolidator.php?cron=1"
 *    
 *    B) Executar manualmente:
 *       Via CLI: php /caminho/para/consolidator.php
 *       Via URL: /propagandas/analytics/consolidator.php?cron=1
 * 
 * 4. VISUALIZAR DADOS
 *    - Dashboard: /propagandas/analytics/dashboard.php
 *    - Selecionar data desejada
 *    - Clicar em "Ver Detalhes" para usuário específico
 *    - Exportar como CSV
 * 
 * 5. ESTRUTURA DE PASTAS
 *    /propagandas/analytics/
 *      ├── tracker.php ........... Rastreamento (AUTO)
 *      ├── consolidator.php ...... Consolidação diária (CRON)
 *      ├── dashboard.php ......... Painel de admin (VISUALIZAÇÃO)
 *      ├── api.php ............... API REST (INTERNO)
 *      ├── helper.js ............. Helper JavaScript (OPCIONAL)
 *      ├── export.php ............ Exportar CSV (AUTO)
 *      ├── logs/ ................. Dados armazenados
 *      │   ├── raw_*.log ......... Eventos brutos em tempo real
 *      │   ├── analytics_*.json .. Resumo consolidado
 *      │   └── archive/ .......... Meses antigos comprimidos
 *      └── README.md ............. Documentação completa
 * 
 * 6. DADOS RASTREADOS
 *    - Acessos a página (index, listar, serviços)
 *    - Cliques em anúncios
 *    - Cliques em WhatsApp
 *    - Visualizações de serviços
 *    - Solicitações de contato
 *    - Visitas a vitrines
 * 
 * 7. RESULTADO ESPERADO
 *    Dashboard mostrará:
 *    ┌──────────────────────────────────────────┐
 *    │ Acessos de Página: X                     │
 *    │ Cliques em Anúncios: X                   │
 *    │ Contatos WhatsApp: X                     │
 *    │ Visitas à Loja: X                        │
 *    │ Visualizações Serviço: X                 │
 *    │ Contatos Serviço: X                      │
 *    ├──────────────────────────────────────────┤
 *    │ 🏆 RANKING DE USUÁRIOS                   │
 *    │                                          │
 *    │ 1. João Silva (ID: 55)                   │
 *    │    - Total: 105 interações               │
 *    │    - Anúncios: 45 cliques, 12 WhatsApp  │
 *    │    - Serviços: 8 visualizações           │
 *    │                                          │
 *    │ 2. Pedro Santos (ID: 58)                 │
 *    │    ...                                   │
 *    └──────────────────────────────────────────┘
 * 
 * 8. COMO ADICIONAR RASTREAMENTO EM NOVOS ELEMENTOS
 *    
 *    A) Anúncios/Cards:
 *       <div class="card" data-ad-id="142" data-owner-id="55">
 *           ...
 *       </div>
 *       + Incluir: <script src="/propagandas/analytics/helper.js"></script>
 *    
 *    B) Serviços:
 *       <div class="service" data-service-id="5" data-owner-id="59">
 *           <button data-service-contact>Contato</button>
 *       </div>
 *    
 *    C) Vitrines:
 *       <a href="/user/profile.php?id=58" data-store-owner="58">Ver Loja</a>
 * 
 * 9. ARQUIVO DE LOGS
 *    
 *    raw_2025-11-18.log (exemplo):
 *    {"timestamp":"2025-11-18 14:30:45","type":"page_load","user_id":0,"ip":"192.168.1.1","user_agent":"Mozilla/5.0...","page":"index"}
 *    {"timestamp":"2025-11-18 14:31:12","type":"ad_click","user_id":0,"ip":"192.168.1.5","user_agent":"Mozilla/5.0...","ad_id":142,"owner_user":55}
 *    
 *    analytics_2025-11-18.json (exemplo):
 *    {
 *      "summary": { "total_page_loads": 2543, "total_ad_clicks": 856 },
 *      "users": {
 *        "55": { "name": "João Silva", "stats": { "ads": [...], "store_visits": 15, "total_interactions": 105 } }
 *      }
 *    }
 * 
 * 10. TROUBLESHOOTING
 *    
 *    ❌ Problema: Dashboard mostra "Nenhum dado disponível"
 *    ✅ Solução:
 *       - Verifique se /logs/ foi criada e tem permissão 755
 *       - Verifique se há acessos ao site no dia selecionado
 *       - Execute consolidator.php manualmente
 *    
 *    ❌ Problema: Analytics não está rastreando
 *    ✅ Solução:
 *       - Verifique error.log do servidor
 *       - Confirme que tracker.php está sendo incluído
 *       - Verifique permissões de /logs/ (755)
 *    
 *    ❌ Problema: Helper.js não funciona
 *    ✅ Solução:
 *       - Verifique se <script> está no <head> ou </body>
 *       - Verifique atributos data-ad-id, data-owner-id nos elementos
 *       - Abra Console (F12) para ver erros
 */

// Este arquivo é apenas documentação
// Pode ser deletado sem afetar o funcionamento
?>
