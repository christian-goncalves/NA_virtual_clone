```md
Execute a **Fase 5: Componentes críticos (cards/rows/botões/badges)** da página `/reunioes-virtuais`, conforme `docs/front-end/etapas/pipeline.md`.

Objetivo:
- Padronizar `meeting-card` e `meeting-row` com classes reutilizáveis.
- Melhorar leitura rápida e deixar CTA de entrada visualmente dominante.
- Cobrir estados visuais: `em andamento`, `sem link`, `fechada/aberta`.

Arquivos-alvo:
- `resources/views/virtual-meetings/partials/meeting-card.blade.php`
- `resources/views/virtual-meetings/partials/meeting-row.blade.php`
- `resources/css/app.css`

Diretrizes de implementação:
1) Estrutura comum de componentes:
- criar/usar classes utilitárias próprias para shell, título, metadados, status e ações
- manter semântica HTML atual (`article`, `a`, `span`) e links externos com `target` + `rel`

2) `meeting-card` (running):
- destacar status “Em andamento” com badge consistente
- priorizar ordem visual: nome -> horário/plataforma -> tipo/formato -> status/CTA
- CTA `Entrar` como elemento primário
- estado sem link com estilo neutro, claramente desabilitado

3) `meeting-row` (starting/upcoming):
- layout de linha escaneável com bloco de infos + bloco de ação
- horário e nome com hierarquia clara
- estado “sem link” consistente com card
- manter texto de status (`status_text`) visível sem competir com CTA

4) Badges:
- usar padrão único para badges de status/tipo/formato
- contemplar variações `aberta`, `fechada`, `estudo` (via `typeLabel`/`groupedBadges`), sem mudar contrato de dados

5) CSS:
- centralizar estilos novos em `app.css` (camada components)
- evitar repetir blocos longos de classes inline em Blade quando houver padrão reutilizável

Restrições:
- Não alterar backend, rotas, contratos, controller, API, sync.
- Não mudar chaves/estrutura do view model.
- Não alterar lógica de agrupamento/contagem.
- Preservar conteúdo textual existente dos estados vazios.
- Manter responsividade (mobile e desktop).

Critérios de aceite:
- `meeting-card` e `meeting-row` visualmente padronizados.
- CTA “Entrar” é o elemento de maior destaque no bloco de ação.
- Estados `em andamento` e `sem link` claramente distinguíveis.
- Tipos e formatos exibidos com badges legíveis e consistentes.
- Nenhuma regressão funcional na rota `/reunioes-virtuais`.

Validação obrigatória:
- Executar `php artisan test --filter=VirtualMeetingControllerTest`
- Reportar resultado no final.
- Informar arquivos alterados + justificativa objetiva.
```



Fase 5 executada conforme `docs/front-end/etapas/fase-5.md`.

Arquivos alterados:
1. [app.css](/c:/laragon/www/NA_virtual_clone/resources/css/app.css:57)  
- adicionei classes reutilizáveis para componentes críticos:
`vm-meeting-card`, `vm-meeting-row`, `vm-title`, `vm-meta`, `vm-time`, `vm-status`, `vm-link-disabled`, `vm-format-badge`.
- CTA primário padronizado com foco acessível em `.vm-btn-primary:focus-visible`.

2. [meeting-card.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/virtual-meetings/partials/meeting-card.blade.php:22)  
- migrado para padrão reutilizável (`vm-card-shell vm-meeting-card`).
- badge de estado “Em andamento” padronizada.
- tipo/formato com badges consistentes.
- CTA `Entrar` como primário dominante; estado sem link com `vm-link-disabled`.

3. [meeting-row.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/virtual-meetings/partials/meeting-row.blade.php:21)  
- migrado para padrão reutilizável (`vm-card-shell vm-meeting-row`).
- hierarquia visual clara de horário/nome/metadados.
- bloco de ação com status + CTA primário.
- estado sem link padronizado com `vm-link-disabled`.

Validação obrigatória:
- `php artisan test --filter=VirtualMeetingControllerTest`
- Resultado: **5 testes passando** (sem regressão funcional).

Obs.: não alterei backend/rotas/contratos/controller/API/sync.


