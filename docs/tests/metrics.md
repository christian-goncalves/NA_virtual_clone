**Roteiro (primeira etapa)**
1. Abra `http://na_virtual_clone.local/reunioes-virtuais` 1 vez.
2. Aguarde 5 segundos e atualize a página 5 vezes (intervalo de 2 segundos entre F5).
3. Na seção `Em andamento`, clique em `Entrar` 3 vezes (em reuniões diferentes, se possível).
4. Na seção `Próximos 60 min`, clique em `Entrar` 2 vezes.
5. Na seção `Próximas reuniões`, clique em `Entrar` 2 vezes.
6. Aguarde 40 segundos (cache do dashboard é 30s).
7. Abra `http://na_virtual_clone.local/admin/metricas` 1 vez.
8. Faça 1 refresh no dashboard após 10 segundos.

**Onde conferir no HeidiSQL (clique a clique)**
1. Clique em `local_server` > `db_na`.
2. Clique na tabela `metric_page_views` > aba `Data`.
3. Confira as últimas linhas:
- `event_type = page_view` (das visitas/F5)
- `event_type = category_click` (dos cliques em Entrar)
- `category = running | starting_soon | upcoming`
4. Clique na tabela `metric_request_metrics` > `Data`.
5. Confira novas linhas com `route` (`/` ou `reunioes-virtuais`) e `duration_ms`.
6. Clique na tabela `metric_meeting_snapshots` > `Data`.
7. Aguarde até 6 minutos e atualize (scheduler roda a cada 5 min).
8. Clique na tabela `metric_hourly_aggregates` > `Data`.
9. Aguarde até 11 minutos e atualize (scheduler roda a cada 10 min).
10. Clique na tabela `metric_sync_runs` > `Data`.
11. Aguarde até 31 minutos e atualize (sync roda a cada 30 min).

**Ações mapeadas para o dashboard (encontradas por `metrics/metricas`)**
1. Registrar `page_view` em GET da página pública (`track.vm.pageview`).
2. Registrar latência/HTTP em `metric_request_metrics` (`track.vm.request_metric`).
3. Registrar evento de clique `category_click` via `POST /api/metrics/event`.
4. Capturar snapshot operacional de reuniões (`metric_meeting_snapshots`) a cada 5 min.
5. Consolidar agregados horários de latência (`metric_hourly_aggregates`) a cada 10 min.
6. Registrar execuções de sincronização (`metric_sync_runs`) via comando `na:sync-virtual-meetings` (agendado 30 min).
7. Exibir tudo no dashboard `/admin/metricas` (cards, gráficos, tabela de sync).

**Arquivos-chave do mapeamento**
- [web.php](/c:/laragon/www/NA_virtual_clone/routes/web.php)
- [api.php](/c:/laragon/www/NA_virtual_clone/routes/api.php)
- [console.php](/c:/laragon/www/NA_virtual_clone/routes/console.php)
- [app.js](/c:/laragon/www/NA_virtual_clone/resources/js/app.js)
- [NaVirtualMeetingMetricsIngestionService.php](/c:/laragon/www/NA_virtual_clone/app/Services/NaVirtualMeetingMetricsIngestionService.php)
- [NaVirtualMeetingMetricsService.php](/c:/laragon/www/NA_virtual_clone/app/Services/NaVirtualMeetingMetricsService.php)
- [index.blade.php](/c:/laragon/www/NA_virtual_clone/resources/views/admin/metrics/index.blade.php)

Se quiser, seguimos agora pela **Etapa 1** juntos: você executa os passos 1-5 e eu te digo exatamente o que validar em cada tabela em seguida.
