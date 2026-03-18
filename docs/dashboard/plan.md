Excelente base. Com esse guia, o melhor é transformar em um plano de execução objetivo, já pensado para sua aplicação atual.

**Plano Proposto (Dashboard Privado de Métricas)**

1. **Objetivo do módulo**
- Criar `/admin/metricas` apenas para uso interno.
- Mostrar uso real da página, saúde da sync e desempenho da aplicação.

2. **Segurança (sem acesso público)**
- Proteger rota com `auth` + `is_admin`.
- Em produção, adicionar allowlist de IP no Nginx para `/admin/*` (opcional e recomendado).
- Sem indexação: `X-Robots-Tag: noindex`.

3. **Modelo de dados (tabelas)**
- `metric_page_views`: acessos e cliques de categoria.
- `sync_runs`: cada execução de sync (status, duração, contagens, erro).
- `request_metrics`: tempo de resposta por rota.
- `snapshot_meeting_counts`: disponibilidade por faixa temporal.
- `metric_hourly_aggregates` (fase 2): consolidados por hora para consultas rápidas.

4. **Coleta (como medir)**
- Middleware de request para `/reunioes-virtuais` gravar duração/status.
- Endpoint leve de evento (`POST /metrics/event`) para clique em card/categoria.
- Instrumentar `na:sync-virtual-meetings` para gravar em `sync_runs`.
- Scheduler a cada 5 min para snapshot de reuniões (`running`, `<=1h`, `1h-6h`).

5. **Dashboard `/admin/metricas`**
- **Cards**: acessos hoje, acessos última hora, reuniões em andamento, última sync, latência média.
- **Gráficos**: acessos por hora, cliques por categoria, disponibilidade por faixa, sucesso/falha de sync.
- **Tabelas**: últimas syncs, últimos erros, top rotas.

6. **Fases de entrega**
- **Fase 1 (MVP, 2-3 dias)**:
  - tabelas `metric_page_views`, `sync_runs`, `snapshot_meeting_counts`
  - coleta básica + dashboard com cards e 2 gráficos
- **Fase 2 (2 dias)**:
  - `request_metrics`, p95, top lentas, agregação horária
- **Fase 3 (1-2 dias)**:
  - alertas (sync atrasada/falha), retenção automática, hardening

7. **Retenção e custo**
- Bruto: 30 dias (`page_views`, `request_metrics`)
- Sync logs: 90 dias
- Agregados horários: 6 meses
- Job diário para limpeza (`schedule:command`)

8. **Critérios de sucesso**
- Você consegue responder em segundos:
  - “a sync está saudável?”
  - “qual categoria é mais usada?”
  - “o sistema está lento?”
  - “houve queda de oferta de reuniões hoje?”

Se quiser, no próximo passo eu já te entrego o **blueprint técnico exato da Fase 1** (migrations, nomes de campos, rotas, middleware e queries do dashboard) pronto para implementar.
