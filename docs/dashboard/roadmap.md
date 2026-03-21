# Roadmap - Dashboard de Metricas

## Objetivo
Consolidar historico, status atual e proximos ciclos do dashboard interno `/admin/metricas` em um unico documento de execucao.

## Historico canonico (modulo principal)

### Fase 1 - MVP de metricas
Status: `DONE`
Entregas:
- `metric_page_views`, `metric_sync_runs`, `metric_meeting_snapshots`.
- Coleta basica de acessos/acoes.
- Instrumentacao da sync.
- Dashboard admin inicial com cards e 2 graficos.

### Fase 2 - Performance e agregacao
Status: `DONE`
Entregas:
- `metric_request_metrics`.
- Metricas de latencia (media, p95, top lentas).
- `metric_hourly_aggregates` para consolidacao.
- Ampliacao de visualizacoes.

### Fase 3 - Operacao e seguranca
Status: `DONE`
Entregas:
- Alertas operacionais.
- Politicas de retencao com scheduler.
- Hardening da area admin.

## Historico canonico (meeting analysis)

### Baseline aprovado
Catalogo filtravel de reunioes baseado em `virtual_meetings`, sem substituir os cards operacionais atuais.

Colunas aprovadas:
- `name`
- `meeting_platform`
- `meeting_id`
- `weekday`
- `start_time`
- `end_time`
- `duration_minutes`
- `is_open`
- `is_study`
- `is_lgbt`
- `is_women`
- `is_hybrid`
- `is_active`

### Fase 1 - Contrato funcional
Status: `DONE`

### Fase 2 - Modelagem service-first
Status: `DONE`

### Fase 3 - API interna da listagem
Status: `DONE`

### Fase 4 - Tela no dashboard
Status: `DONE`

### Fase 5 - Qualidade/operacao
Status: `DONE`

### Fase 6 - Fechamento
Status: `DONE`

## Gaps reais observados (sem expandir escopo)
1. Exportacao CSV do resultado filtrado entregue em 2026-03-20.
2. Persistencia de filtros salvos por usuario entregue em 2026-03-20.
3. UX de paginacao numerica com seletor rapido entregue em 2026-03-20.
4. Secao de analise desacoplada para rota dedicada `/admin/metricas/reunioes` em 2026-03-20.
5. Telemetria especifica de uso da secao entregue em 2026-03-20.

## Backlog curto priorizado
Prioridade 1:
1. Exportacao CSV da listagem filtrada. Status: `DONE` (2026-03-20).
2. Paginacao numerica + seletor rapido de pagina. Status: `DONE` (2026-03-20).

Prioridade 2:
1. Presets de filtros (salvar/aplicar). Status: `DONE` (2026-03-20).
2. Melhoria visual de ordenacao por coluna. Status: `DONE` (2026-03-20).

Prioridade 3:
1. Telemetria de uso da secao de analise. Status: `DONE` (2026-03-20).
2. Sugestoes guiadas para filtros (`meeting_platform`, `weekday`). Status: `DONE` (2026-03-20).

Prioridade 4:
1. Desacoplamento da secao de analise para pagina dedicada `/admin/metricas/reunioes`. Status: `DONE` (2026-03-20).

## Proximo ciclo incremental (sem regressao)
  1. Entregar CSV mantendo contrato atual da API. Status: `DONE` (2026-03-20).
  2. Evoluir paginacao mantendo query string compativel. Status: `DONE` (2026-03-20).
  3. Preservar hardening admin e padrao service-first.
  4. Executar testes de regressao focados em payload, seguranca e consistencia de summary/meta.
---

## Ciclo proposto - DataTables + Filtro principal de cliques

  ### Objetivo
  Revisar a secao `Lista de reunioes` para melhorar desempenho/UX e incluir filtro principal de engajamento por bloco (`running`, `starting_soon`, `upcoming`).

    ### 1. Escopo do ciclo
      1. Frontend: migrar tabela para DataTables com `serverSide: true`.
      2. Backend: adaptar endpoint da listagem para responder ao contrato server-side do DataTables.
      3. Filtro principal: incluir dimensao de cliques por bloco na consulta e no payload.
      4. Compatibilidade: manter filtros atuais, hardening admin e contrato de erro 422.

    ### 2. Fases propostas
      1. Fase A - Contrato DataTables (request/response) e mapeamento de colunas/ordenacao. Status: `DONE`
      2. Fase B - Backend server-side (query/paginacao/ordenacao/filtro principal de cliques). Status: `DONE`
      3. Fase C - Frontend DataTables no dashboard (sem regressao dos cards existentes). Status: `DONE`
      4. Fase D - Qualidade operacional (testes impactados + validacao em dados reais + GO/NO-GO). Status: `DONE`
---

### Entrega da Fase A
- Contrato DataTables detalhado em `docs/dashboard/frontend-table.md`.
- Colunas canonicas e whitelist de ordenacao congeladas.
- Filtro principal de cliques definido com janela temporal.

### Risco tecnico conhecido
Hoje os cliques estao ligados a `meeting_name` no evento. Para maior precisao, planejar evolucao para identificador estavel no tracking (ex.: `external_id` ou outro ID canonico), com compatibilidade retroativa.











