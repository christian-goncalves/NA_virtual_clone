Perfeito. Fiz o scan de aderência e já dá para manter o padrão do projeto com segurança, sem “inventar arquitetura”.

**Scan De Aderência (MVC + padrões do projeto)**

**1) Referências-guia por camada**
- Controller Web: [VirtualMeetingController.php](/c:/laragon/www/NA_virtual_clone/app/Http/Controllers/VirtualMeetingController.php)
- Controller API: [VirtualMeetingApiController.php](/c:/laragon/www/NA_virtual_clone/app/Http/Controllers/Api/VirtualMeetingApiController.php)
- Services (regra de negócio): [NaVirtualMeetingHomepageDataService.php](/c:/laragon/www/NA_virtual_clone/app/Services/NaVirtualMeetingHomepageDataService.php), [NaVirtualMeetingGroupingService.php](/c:/laragon/www/NA_virtual_clone/app/Services/NaVirtualMeetingGroupingService.php), [NaVirtualMeetingSnapshotService.php](/c:/laragon/www/NA_virtual_clone/app/Services/NaVirtualMeetingSnapshotService.php)
- Models: [VirtualMeeting.php](/c:/laragon/www/NA_virtual_clone/app/Models/VirtualMeeting.php), [VirtualMeetingSnapshot.php](/c:/laragon/www/NA_virtual_clone/app/Models/VirtualMeetingSnapshot.php)
- Rotas: [web.php](/c:/laragon/www/NA_virtual_clone/routes/web.php), [api.php](/c:/laragon/www/NA_virtual_clone/routes/api.php), [console.php](/c:/laragon/www/NA_virtual_clone/routes/console.php)
- Config customizada: [na_virtual.php](/c:/laragon/www/NA_virtual_clone/config/na_virtual.php)
- Views/partials: [index.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/virtual-meetings/index.blade.php), [sections.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/virtual-meetings/partials/sections.blade.php)
- Testes de padrão: [VirtualMeetingControllerTest.php](/c:/laragon/www/NA_virtual_clone/tests/Feature/VirtualMeetingControllerTest.php), [NaVirtualMeetingSchedulerTest.php](/c:/laragon/www/NA_virtual_clone/tests/Feature/NaVirtualMeetingSchedulerTest.php)

**2) Padrões de código observados (e que devemos seguir)**
- Controllers finos: delegam para service + retornam view/json.
- Lógica de negócio em `app/Services`.
- Config por `config('na_virtual.*')` + `.env`.
- Uso forte de `data_get`, `collect`, `Carbon`, `Cache`.
- Tipagem em docblocks (`@return array<string, mixed>`, etc.).
- Model com `fillable` + `casts()` método (não propriedade `$casts`).
- Nomes de classes de domínio com prefixo `NaVirtualMeeting...`.
- Scheduler definido em `routes/console.php`.
- Testes feature cobrindo rota, cache, fallback e scheduler.

**3) Como isso vira o módulo de métricas (aderente ao projeto)**
- **Models**
  - `app/Models/MetricPageView.php`
  - `app/Models/MetricSyncRun.php`
  - `app/Models/MetricRequestMetric.php`
  - `app/Models/MetricMeetingSnapshot.php`
  - Todos com `fillable` + `casts()` no padrão dos models atuais.
- **Services**
  - `app/Services/NaVirtualMeetingMetricsService.php` (queries agregadas para dashboard)
  - `app/Services/NaVirtualMeetingMetricsIngestionService.php` (coleta leve)
- **Controllers**
  - Web privado: `app/Http/Controllers/Admin/MetricsDashboardController.php`
  - API interna opcional: `app/Http/Controllers/Api/Admin/MetricsApiController.php`
- **Views**
  - `resources/views/admin/metrics/index.blade.php`
  - Partials em `resources/views/admin/metrics/partials/*`
- **Rotas**
  - `routes/web.php`: grupo `/admin/metricas` com middleware de proteção
- **Config**
  - Acrescentar bloco `metrics` em [na_virtual.php](/c:/laragon/www/NA_virtual_clone/config/na_virtual.php)
- **Scheduler**
  - `routes/console.php`: snapshots/retention (ex.: a cada 5 min / diário)
- **Migrations**
  - Convenção existente: classes anônimas + índices explícitos.

**4) Regras de aderência para o blueprint**
- Não colocar query complexa em Blade.
- Controller não agrega métricas diretamente.
- Tudo que for ajustável vai para `na_virtual.metrics.*`.
- Dashboard com cache curto (30-60s) para consultas pesadas.
- Coleta sem payload sensível (hash para IP/session).
- Testes feature para rota admin e agregações principais.

**5) Ponto de atenção detectado no scan**
- O teste de scheduler parece desatualizado após renome de schedule name:
  - Código atual usa `sync-na-virtual-meetings-command` em [console.php](/c:/laragon/www/NA_virtual_clone/routes/console.php)
  - Teste procura `sync-na-virtual-meetings-job` em [NaVirtualMeetingSchedulerTest.php](/c:/laragon/www/NA_virtual_clone/tests/Feature/NaVirtualMeetingSchedulerTest.php)

Se você quiser, no próximo passo eu já te entrego o **blueprint Fase 1** com:
- nomes finais de arquivos,
- migrations completas,
- contrato dos services,
- rotas/middleware,
- e checklist de testes.
