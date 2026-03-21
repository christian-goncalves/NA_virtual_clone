# Frontend Table - Meeting Analysis

## Objetivo
Padronizar a evolucao da secao `Lista de reunioes` com DataTables `serverSide: true`, mantendo seguranca admin e performance operacional.

## Estado atual
- Tabela renderizada em Blade (server-side tradicional).
- Filtros funcionais, UX limitada para volume alto.
- Endpoint atual: `GET /api/admin/metricas/reunioes`.

## Escopo da Fase A (contrato)
1. Definir contrato request/response para DataTables server-side.
2. Definir mapeamento canonico de colunas e whitelist de ordenacao.
3. Definir filtro principal de cliques por bloco (`running`, `starting_soon`, `upcoming`).
4. Definir compatibilidade retroativa com filtros existentes.

## Endpoint alvo
`GET /api/admin/metricas/reunioes`

Protecao obrigatoria:
- `auth.basic`
- `is_admin`
- `harden.metrics.admin`

## Contrato de request (DataTables)
Parametros nativos:
1. `draw` (int)
2. `start` (int)
3. `length` (int)
4. `search[value]` (string)
5. `order[0][column]` (int)
6. `order[0][dir]` (`asc|desc`)
7. `columns[i][data]` (string)

Parametros de filtro adicionais (compativeis com legado):
1. `search_name`
2. `weekday`
3. `time_start`
4. `time_end`
5. `meeting_platform`
6. `is_open`
7. `is_study`
8. `is_lgbt`
9. `is_women`
10. `is_hybrid`
11. `is_active`

Filtro principal de cliques (novo):
1. `click_block` (`all|running|starting_soon|upcoming`)
2. `click_window` (`24h|7d|30d|custom`)
3. `click_from` (datetime, obrigatorio quando `custom`)
4. `click_to` (datetime, obrigatorio quando `custom`)

## Contrato de response (DataTables)
Resposta de sucesso (200):
1. `draw` (int)
2. `recordsTotal` (int)
3. `recordsFiltered` (int)
4. `data` (array)
5. `summary` (objeto auxiliar)
6. `applied_filters` (objeto)

Resposta de validacao (422):
1. `ok=false`
2. `code=VALIDATION_ERROR`
3. `message`
4. `errors`

## Colunas da tabela (ordem canonica)
1. `name`
2. `meeting_platform`
3. `meeting_id`
4. `weekday`
5. `start_time`
6. `end_time`
7. `duration_minutes`
8. `is_open`
9. `is_study`
10. `is_lgbt`
11. `is_women`
12. `is_hybrid`
13. `is_active`
14. `clicks_total`
15. `clicks_running`
16. `clicks_starting_soon`
17. `clicks_upcoming`

## Whitelist de ordenacao
Permitidas:
- `name`
- `weekday`
- `start_time`
- `meeting_platform`
- `is_active`
- `clicks_total`
- `clicks_running`
- `clicks_starting_soon`
- `clicks_upcoming`

Bloqueadas:
- qualquer coluna fora da whitelist.

## Regras de compatibilidade
1. Se DataTables nao enviar `draw/start/length`, endpoint continua aceitando formato legado.
2. Filtros atuais permanecem validos sem alteracao de nome.
3. Contrato 422 padronizado permanece identico.

## Criterios de aceite da Fase A
1. Contrato DataTables documentado e aprovado.
2. Mapeamento de colunas e ordenacao congelado.
3. Filtro principal de cliques definido com janela temporal.
4. Compatibilidade retroativa explicitamente definida.

## Extensao CSV (ciclo incremental)
- Endpoint dedicado: `GET /api/admin/metricas/reunioes/export.csv`.
- Mesmo hardening admin da API de listagem (`auth.basic`, `is_admin`, `harden.metrics.admin`).
- Reaproveita filtros e ordenacao existentes para manter consistencia com a tabela.
- Retorno em arquivo CSV (`text/csv; charset=UTF-8`) com colunas canonicas e colunas de cliques.

## Presets de filtros (Prioridade 2)
- Endpoints:
  - `GET /api/admin/metricas/reunioes/presets` (listar presets do usuario autenticado).
  - `POST /api/admin/metricas/reunioes/presets` (salvar/atualizar preset por nome).
  - `DELETE /api/admin/metricas/reunioes/presets/{presetId}` (remover preset proprio).
- Presets persistem apenas filtros canonicos e ordenacao (`sort_by`, `sort_dir`).
- Contrato 422 padronizado preservado para erros de validacao.

## Ordenacao visual (Prioridade 2)
- Coluna ordenada recebe destaque visual no header.
- Indicador textual de ordenacao ativa mostra `coluna` + `asc|desc`.
- UX de ordenacao permanece compativel com whitelist de ordenacao server-side.

## Paginacao numerica + seletor rapido (Prioridade 1 - item 2)
- DataTables configurado com paginação numerica (`full_numbers`).
- Barra de navegacao com:
  - indicador `Pagina X de Y`;
  - campo `Ir para` com botao de salto;
  - seletor `Por pagina` (10/20/50/100).
- Compatibilidade de query string preservada com `page`, `per_page`, `sort_by`, `sort_dir` e filtros ativos.

## Telemetria da secao (Prioridade 3 - item 1)
- Eventos enviados para `POST /api/metrics/event` com `event_type=meeting_analysis_usage`.
- Acoes instrumentadas: aplicar/limpar filtros, exportar CSV, salvar/aplicar/remover preset, trocar ordenacao e interacoes de paginacao.
- Campos usados no evento:
  - `category`: acao executada.
  - `route`: `/admin/metricas`.
  - `source_section`: `admin_meeting_analysis`.
  - `meeting_name`: nome do preset quando aplicavel.

## Sugestoes guiadas (Prioridade 3 - item 2)
- Bloco visual `Sugestoes guiadas` na secao `Lista de reunioes`.
- Sugestoes de `weekday` e `meeting_platform` alimentadas por `summary.weekday_distribution` e `summary.platform_distribution`.
- Clique em sugestao preenche o filtro correspondente e reaplica a busca sem alterar o contrato atual da query string.
- Sem alteracao de nomes de filtros (`weekday`, `meeting_platform`) e sem mudanca no hardening admin.

## Desacoplamento da secao (Prioridade 4)
- Tela dedicada criada em `/admin/metricas/reunioes` para a secao `Lista de reunioes`.
- Dashboard principal (`/admin/metricas`) permanece com KPIs operacionais e atalho para a analise detalhada.
- Hardening admin preservado em ambas as rotas web com `auth.basic`, `is_admin`, `harden.metrics.admin`.
- Contratos da API interna (`/api/admin/metricas/reunioes`, CSV e presets) permanecem inalterados.

