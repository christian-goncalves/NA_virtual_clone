```md
Execute a **Fase 4: Seções (running/soon/upcoming)** da página `/reunioes-virtuais`, conforme `docs/front-end/etapas/pipeline.md`.

Objetivo:
- Padronizar visual e hierarquia das três seções, aproximando da referência.
- Unificar título, contador, badges de tipo e espaçamento entre blocos.

Arquivos-alvo:
- `resources/views/virtual-meetings/partials/sections.blade.php`
- `resources/views/virtual-meetings/partials/section-running.blade.php`
- `resources/views/virtual-meetings/partials/section-starting-soon.blade.php`
- `resources/views/virtual-meetings/partials/section-upcoming.blade.php`
- (somente se necessário) `resources/css/app.css` para classes auxiliares de seção

Diretrizes:
1) Estrutura comum das seções:
- cabeçalho com título à esquerda e contador à direita
- subtags/badges de tipo abaixo do título (quando aplicável)
- casca visual clara e consistente (`section shell`)
- respiro vertical uniforme entre seções

2) Running:
- manter grid existente
- manter semântica e loop atuais
- melhorar legibilidade do cabeçalho e contador

3) Starting Soon / Upcoming:
- manter lista vertical e includes existentes
- padronizar cabeçalho, contador e separações
- reduzir ruído visual e melhorar escaneabilidade

4) Empty states:
- manter mensagens atuais
- apenas alinhar estilo visual com o novo padrão

Restrições:
- Não alterar backend, rotas, contratos, controller, API ou sync.
- Não alterar estrutura/chaves do view model.
- Não mexer em `meeting-card` e `meeting-row` nesta fase (isso é Fase 5).
- Preservar renderização funcional atual.

Critérios de aceite:
- As três seções seguem o mesmo padrão visual e de espaçamento.
- Hierarquia título > badges > conteúdo > contador está clara.
- Fidelidade visual mais próxima da referência nas áreas de seção.
- Nenhuma regressão funcional na rota `/reunioes-virtuais`.

Validação obrigatória:
- Executar `php artisan test --filter=VirtualMeetingControllerTest`
- Reportar resultado no final.
- Informar lista de arquivos alterados e justificativa.
```

Fase 4 executada.

Arquivos alterados:
1. [sections.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/virtual-meetings/partials/sections.blade.php:1)  
- adicionado `id="reunioes"` e mantido espaçamento macro da fase anterior.

2. [section-running.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/virtual-meetings/partials/section-running.blade.php:1)  
- seção padronizada com `vm-section-shell`; cabeçalho/contador unificados; badges de tipo via `groupedBadges`; empty state alinhado aos tokens.

3. [section-starting-soon.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/virtual-meetings/partials/section-starting-soon.blade.php:1)  
- mesmo padrão visual/estrutural da seção de running; contador contextual; badges de tipo; empty state padronizado.

4. [section-upcoming.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/virtual-meetings/partials/section-upcoming.blade.php:1)  
- mesmo padrão visual/estrutural; contador e badges unificados; empty state padronizado.

Validação obrigatória:
- `php artisan test --filter=VirtualMeetingControllerTest`
- Resultado: **5 testes passando** (sem regressão funcional).

Obs.: `meeting-card` e `meeting-row` não foram alterados, conforme restrição da fase.
