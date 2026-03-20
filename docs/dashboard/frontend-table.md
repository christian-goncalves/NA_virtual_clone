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
