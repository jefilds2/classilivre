# Lista de Parâmetros do Filtro de Categorias - servicos/index.php

## 📊 Mapeamento Completo do Dropdown

O dropdown do filtro busca na BD usando os seguintes parâmetros (linhas 20-42 de index.php):

---

## ✅ CATEGORIAS E SEUS PARÂMETROS DE BUSCA

### 1. **Oficina mecânica**
- **Chave interna:** `mechanic`
- **Busca na BD por:** `Oficina mecânica` OR `Oficina mecanica`
- **Status:** ✓ Sincronizado com meus.php

### 2. **Funilaria e pintura**
- **Chave interna:** `bodyshop`
- **Busca na BD por:** `Funilaria e pintura`
- **Status:** ✓ Sincronizado com meus.php

### 3. **Estética automotiva**
- **Chave interna:** `detailing`
- **Busca na BD por:** `Estética automotiva` OR `Estetica automotiva`
- **Status:** ✓ Sincronizado com meus.php

### 4. **Pneus e suspensão**
- **Chave interna:** `tire`
- **Busca na BD por:** `Pneus e suspensão` OR `Pneus e suspensao`
- **Status:** ✓ Sincronizado com meus.php

### 5. **Alinhamento e balanceamento**
- **Chave interna:** `alignment`
- **Busca na BD por:** `Alinhamento e balanceamento`
- **Status:** ✓ Sincronizado com meus.php

### 6. **Elétrica automotiva**
- **Chave interna:** `electric`
- **Busca na BD por:** `Elétrica automotiva` OR `Eletrica automotiva`
- **Status:** ✓ Sincronizado com meus.php

### 7. **Ar-condicionado automotivo**
- **Chave interna:** `air_conditioning`
- **Busca na BD por:** `Ar-condicionado automotivo` OR `Ar condicionado automotivo`
- **Status:** ✓ Sincronizado com meus.php

### 8. **Baterias e elétrica leve**
- **Chave interna:** `battery`
- **Busca na BD por:** `Baterias e elétrica leve` OR `Baterias e eletrica leve`
- **Status:** ✓ Sincronizado com meus.php

### 9. **Martelinho de ouro**
- **Chave interna:** `paintless_dent`
- **Busca na BD por:** `Martelinho de ouro`
- **Status:** ✓ Sincronizado com meus.php

### 10. **Vidros e para-brisas**
- **Chave interna:** `glass`
- **Busca na BD por:** `Vidros e para-brisas` OR `Vidros e para brisas`
- **Status:** ✓ Sincronizado com meus.php

### 11. **Autopeças**
- **Chave interna:** `parts`
- **Busca na BD por:** `Autopeças` OR `Autopecas`
- **Status:** ✓ Sincronizado com meus.php

### 12. **Guincho e reboque**
- **Chave interna:** `tow`
- **Busca na BD por:** `Guincho e reboque`
- **Status:** ✓ Sincronizado com meus.php

### 13. **Vistoria veicular**
- **Chave interna:** `inspection`
- **Busca na BD por:** `Vistoria veicular`
- **Status:** ✓ Sincronizado com meus.php

### 14. **Lavagem e polimento**
- **Chave interna:** `wash`
- **Busca na BD por:** `Lavagem e polimento`
- **Status:** ✓ Sincronizado com meus.php

### 15. **Som e multimídia**
- **Chave interna:** `car_audio`
- **Busca na BD por:** `Som e multimídia` OR `Som e multimedia`
- **Status:** ✓ Sincronizado com meus.php

### 16. **Corretora de seguros**
- **Chave interna:** `insurance`
- **Busca na BD por:** `Corretora de seguros`
- **Status:** ✓ Sincronizado com meus.php

### 17. **Rastreamento veicular**
- **Chave interna:** `tracker`
- **Busca na BD por:** `Rastreamento veicular`
- **Status:** ✓ Sincronizado com meus.php

### 18. **Outro serviço**
- **Chave interna:** `custom`
- **Busca na BD por:** `Outro serviço` OR `Outros serviços` OR `Outro servico`
- **Status:** ✓ Sincronizado com meus.php

---

## 📋 Tabela Resumida

| # | Categoria | Parâmetro 1 | Parâmetro 2 | Parâmetro 3 |
|---|-----------|-------------|-------------|-------------|
| 1 | Oficina mecânica | Oficina mecânica | Oficina mecanica | - |
| 2 | Funilaria e pintura | Funilaria e pintura | - | - |
| 3 | Estética automotiva | Estética automotiva | Estetica automotiva | - |
| 4 | Pneus e suspensão | Pneus e suspensão | Pneus e suspensao | - |
| 5 | Alinhamento e balanceamento | Alinhamento e balanceamento | - | - |
| 6 | Elétrica automotiva | Elétrica automotiva | Eletrica automotiva | - |
| 7 | Ar-condicionado automotivo | Ar-condicionado automotivo | Ar condicionado automotivo | - |
| 8 | Baterias e elétrica leve | Baterias e elétrica leve | Baterias e eletrica leve | - |
| 9 | Martelinho de ouro | Martelinho de ouro | - | - |
| 10 | Vidros e para-brisas | Vidros e para-brisas | Vidros e para brisas | - |
| 11 | Autopeças | Autopeças | Autopecas | - |
| 12 | Guincho e reboque | Guincho e reboque | - | - |
| 13 | Vistoria veicular | Vistoria veicular | - | - |
| 14 | Lavagem e polimento | Lavagem e polimento | - | - |
| 15 | Som e multimídia | Som e multimídia | Som e multimedia | - |
| 16 | Corretora de seguros | Corretora de seguros | - | - |
| 17 | Rastreamento veicular | Rastreamento veicular | - | - |
| 18 | Outro serviço | Outro serviço | Outros serviços | Outro servico |

---

## 🔍 Como Funciona a Busca (Código - linhas 84-92)

```php
if ($category !== '') {
    if (isset($categoryOptions[$category])) {
        $categoryVariants = $categoryOptions[$category];
        $escapedVariants = array_map(function($v) use ($pdo) {
            return $pdo->quote($v);
        }, $categoryVariants);
        $whereParts[] = 'sl.category IN (' . implode(', ', $escapedVariants) . ')';
    }
}
```

**Lógica:**
1. Usuário seleciona uma categoria no dropdown (ex: `detailing`)
2. Sistema busca por TODOS os parâmetros dessa categoria
3. Query fica: `WHERE sl.category IN ('Estética automotiva', 'Estetica automotiva')`
4. Encontra todos os serviços com qualquer uma dessas variantes

---

## ✨ Conclusão

- **Total de categorias:** 18
- **Categorias com variantes de acentuação:** 9
- **Categorias simples (sem variantes):** 7
- **Categoria com 3 variantes:** 1 (Outro serviço)

**O filtro está PERFEITAMENTE sincronizado com meus.php!**
