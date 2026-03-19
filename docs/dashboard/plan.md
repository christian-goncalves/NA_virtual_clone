# Plano - Dashboard de Metricas

## Objetivo
Manter e evoluir o dashboard interno `/admin/metricas` com seguranca, observabilidade e baixo acoplamento.

## Estado atual (marco concluido)
- Fase 1 concluida: coleta base + dashboard inicial + sync runs.
- Fase 2 concluida: request metrics + agregacao horaria + latencia.
- Fase 3 concluida: alertas operacionais + retencao + hardening.
- Acabamento concluido: graficos operacionais, view em partials, API admin interna, recomendacao de allowlist Nginx.

## Fases canonicas

### Fase 1 - MVP de metricas
Entregas:
- `metric_page_views`, `metric_sync_runs`, `metric_meeting_snapshots`.
- Coleta basica de acessos/acoes.
- Instrumentacao da sync.
- Dashboard admin inicial com cards e 2 graficos.

Criterios de aceite:
- KPIs basicos renderizando.
- Persistencia correta nas tabelas da fase.
- Registro de sucesso/falha da sync.
- Testes da fase passando.

### Fase 2 - Performance e agregacao
Entregas:
- `metric_request_metrics`.
- Metricas de latencia (media, p95, top lentas).
- `metric_hourly_aggregates` para consolidacao.
- Ampliacao de visualizacoes.

Criterios de aceite:
- Dashboard exibe performance por janela.
- Consolidacao horaria funcional.
- Testes da fase passando.

### Fase 3 - Operacao e seguranca
Entregas:
- Alertas operacionais.
- Politicas de retencao com scheduler.
- Hardening da area admin.

Criterios de aceite:
- Rotina de retencao ativa.
- Alertas disparam em cenarios esperados.
- Protecao de acesso validada.

## Proximo ciclo (modo continuo)
- Trabalhar por gaps reais identificados em auditoria.
- Nao refazer entregas concluidas.
- Priorizar risco operacional, seguranca e regressao.
