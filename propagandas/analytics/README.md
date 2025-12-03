# Sistema de Analytics - Classi Livre Autos

## Visão Geral

Sistema de rastreamento de acessos e interações no site, totalmente armazenado em arquivos JSON/LOG sem dependência de banco de dados adicional.

## Estrutura de Pastas

```
📁 propagandas/analytics/
├── tracker.php           - Classe principal de rastreamento
├── consolidator.php      - Consolidador de logs diários
├── dashboard.php         - Painel de visualização (admin)
├── export.php            - Exportação em CSV
├── api.php               - API REST para tracking
├── helper.js             - Helper JavaScript para eventos
├── logs/                 - Pasta com dados (criada automaticamente)
│  ├── raw_2025-11-18.log
│  ├── analytics_2025-11-18.json
│  ├── ranking_2025-11-18.json
│  └── archive/           - Logs antigos comprimidos
└── README.md             - Este arquivo
```

## Como Funciona

### 1. **Rastreamento em Tempo Real**

Quando um usuário interage com o site:

```
Usuário acessa página → tracker.php registra em raw_YYYY-MM-DD.log
                    ↓
                JSON com timestamp, tipo, usuário, IP
```

### 2. **Consolidação Diária**

Um cron job (ou manual) consolida os logs:

```
raw_2025-11-18.log → consolidator.php → analytics_2025-11-18.json
                                    → ranking_2025-11-18.json
```

### 3. **Visualização no Dashboard**

Admin acessa `/propagandas/analytics/dashboard.php`:

```
dashboard.php → lê analytics_YYYY-MM-DD.json → exibe estatísticas
```

## Implementação

### A. Rastreamento Automático (já implementado)

**Páginas principais:**
- `index.php` - Rastreia acessos ao home
- `anuncios/listar.php` - Rastreia buscas
- `servicos/index.php` - Rastreia catálogo de serviços

**Código adicionado (1 linha por arquivo):**
```php
require_once __DIR__ . '/../propagandas/analytics/tracker.php';
AnalyticsTracker::trackPageLoad('index');
```

### B. Rastreamento de Eventos (via JavaScript)

**Adicione em templates HTML:**

```html
<!-- Card de anúncio -->
<div class="vehicle-card" data-ad-id="142" data-owner-id="55">
    ...
    <a href="/anuncios/ver.php?id=142" data-whatsapp-link>WhatsApp</a>
</div>

<!-- Card de serviço -->
<div class="service-card" data-service-id="5" data-owner-id="59">
    ...
    <button data-service-contact>Solicitar Contato</button>
</div>

<!-- Link de loja -->
<a href="/user/profile.php?id=58" data-store-owner="58">Ver Vitrine</a>
```

**Incluir helper JavaScript:**
```html
<script src="/propagandas/analytics/helper.js"></script>
```

## API de Tracking

### Função PHP

```php
// No início da página
require_once __DIR__ . '/propagandas/analytics/tracker.php';

// Rastrear página
AnalyticsTracker::trackPageLoad('index');

// Rastrear evento personalizado
AnalyticsTracker::trackEvent('custom_event', [
    'custom_field' => 'value'
]);
```

### Função JavaScript

```javascript
// Rastrear evento
AnalyticsHelper.trackEvent('tipo', {
    ad_id: 142,
    owner_user: 55
});

// Atalhos prontos
AnalyticsHelper.trackAdClick(142, 55);
AnalyticsHelper.trackWhatsApp(142, 55);
AnalyticsHelper.trackServiceContact(5, 59);
```

## Tipos de Eventos Rastreados

| Tipo | Descrição | Dados |
|------|-----------|-------|
| `page_load` | Carregamento de página | page, user_id |
| `ad_click` | Clique em anúncio | ad_id, owner_user |
| `ad_detail_view` | Visualiza detalhes | ad_id, owner_user |
| `whatsapp_click` | Clica em WhatsApp | ad_id, owner_user |
| `store_visit` | Visita vitrine | store_owner |
| `service_view` | Visualiza serviço | service_id, owner_user |
| `service_contact` | Solicita contato | service_id, owner_user |

## Consolidação Automática (CRON)

### Configurar no Cron Job

```bash
# Executar diariamente às 23:59
59 23 * * * curl -s "https://seusite.com/propagandas/analytics/consolidator.php?cron=1" > /dev/null 2>&1
```

Ou via PHP CLI:

```bash
php /caminho/para/propagandas/analytics/consolidator.php
```

## Dashboard de Métricas

**Acesso:** `/propagandas/analytics/dashboard.php` (apenas admin)

**Funcionalidades:**
- 📊 Resumo geral do dia
- 🏆 Ranking de usuários por interações
- 📢 Detalhes de anúncios por usuário
- 🔧 Detalhes de serviços por usuário
- 🏬 Estatísticas de vitrine
- 📥 Exportar como CSV

## Consultas SQL Úteis

### Usuários mais ativos

```json
GET /propagandas/analytics/dashboard.php?date=2025-11-18
```

### Anúncio com mais acessos

```json
Vir em: analytics_2025-11-18.json → users[USER_ID].ads
```

## Estrutura de Arquivo JSON

```json
{
  "summary": {
    "total_page_loads": 2543,
    "total_ad_clicks": 856,
    "total_whatsapp_clicks": 234
  },
  "users": {
    "55": {
      "name": "João Silva",
      "email": "joao@email.com",
      "stats": {
        "ads": [
          {
            "ad_id": 142,
            "title": "Volkswagen Gol 2020",
            "clicks": 45,
            "whatsapp_clicks": 12
          }
        ],
        "store_visits": 15,
        "total_interactions": 105
      }
    }
  }
}
```

## Limpeza e Arquivamento

### Limpar logs mensalmente

```bash
# Comprimir logs do mês passado
cd /caminho/para/propagandas/analytics/logs
zip -r archive/2025-10.zip *.log -m
```

### Limpar logs antigos

```bash
# Remover logs com mais de 90 dias
find /caminho/para/propagandas/analytics/logs -name "*.log" -mtime +90 -delete
```

## Troubleshooting

### "Nenhum dado disponível"

- [ ] Verifique se a pasta `/logs/` existe e tem permissão 755
- [ ] Verifique se há acessos ao site no dia selecionado
- [ ] Verifique se consolidation foi executado (próximo dia)

### Analytics não está rastreando

- [ ] Verifique se `tracker.php` está sendo incluído
- [ ] Verifique permissões da pasta `/logs/` (755)
- [ ] Verifique logs do servidor (error.log)

### Helper.js não funciona

- [ ] Verifique se está incluído: `<script src="/propagandas/analytics/helper.js"></script>`
- [ ] Verifique se os atributos `data-ad-id`, `data-owner-id` estão presentes
- [ ] Abra Console (F12) e veja erros

## Performance

- **Arquivo .log:** O(1) - append apenas
- **JSON consolidado:** ~50KB por dia (1 ano = 18MB)
- **Leitura dashboard:** <200ms para 1 dia

## Segurança

- ✅ Logs armazenados fora de `/public`
- ✅ Dashboard protegido por autenticação admin
- ✅ Sem exposição de dados sensíveis
- ✅ IPs normalizados (sem dados completos)

## Support

Para questões ou problemas, consulte a documentação ou logs do servidor.
