# Pattern - Dashboard de Metricas

## Objetivo
Garantir implementacoes consistentes, seguras e sem regressao no dashboard admin de metricas.

## Regras arquiteturais obrigatorias
1. Controllers finos; orquestracao somente.
2. Regras de negocio em `app/Services`.
3. Models com `fillable` e `casts()` metodo (nao usar propriedade `$casts`).
4. Config nova apenas em `config/na_virtual.php` sob `na_virtual.metrics.*` + `.env.example`.
5. Sem query complexa em Blade.
6. Evitar dados sensiveis em texto puro (usar hash/mascara quando necessario).
7. Naming consistente com dominio atual (`NaVirtualMeeting...`).
8. Toda mudanca deve ter testes feature/unit impactados.

## Referencias de padrao do projeto
- `app/Http/Controllers/VirtualMeetingController.php`
- `app/Http/Controllers/Api/VirtualMeetingApiController.php`
- `app/Services/NaVirtualMeetingHomepageDataService.php`
- `app/Services/NaVirtualMeetingGroupingService.php`
- `app/Services/NaVirtualMeetingSnapshotService.php`
- `app/Models/VirtualMeeting.php`
- `app/Models/VirtualMeetingSnapshot.php`
- `routes/web.php`
- `routes/api.php`
- `routes/console.php`
- `config/na_virtual.php`

## Escopo tecnico esperado do modulo
- Models: `MetricPageView`, `MetricSyncRun`, `MetricRequestMetric`, `MetricMeetingSnapshot`, `MetricHourlyAggregate`.
- Services: `NaVirtualMeetingMetricsService`, `NaVirtualMeetingMetricsIngestionService`, `NaVirtualMeetingMetricsOperationalAlertService`, `NaVirtualMeetingMetricsRetentionService`.
- Dashboard: `/admin/metricas` protegido por `auth.basic`, `is_admin`, `harden.metrics.admin`.
- Scheduler: snapshot, consolidacao horaria, alertas operacionais e retencao.

## Checklist minimo por ciclo
1. Auditoria curta dos gaps reais.
2. Implementar apenas o escopo aprovado.
3. Validar com `php -l`, testes impactados, `php artisan route:list`, `php artisan schedule:list`.
4. Entregar relatorio final padronizado.
