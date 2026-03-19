Execute os pendentes de acabamento do Dashboard de Métricas (pós Fases 1, 2 e 3), seguindo estritamente:
- c:\laragon\www\NA_virtual_clone\docs\dashboard\agent.md
- c:\laragon\www\NA_virtual_clone\docs\dashboard\pattern.md
- c:\laragon\www\NA_virtual_clone\docs\dashboard\plan.md

Contexto:
- Fases 1, 2 e 3 já implementadas e validadas.
- Objetivo agora é fechar apenas gaps residuais de UX/estrutura/operacional, sem regressão.

Escopo exato (somente estes itens):
1) Dashboard: adicionar gráfico de disponibilidade por faixa temporal usando `metric_meeting_snapshots` (running, <=1h, <=6h).
2) Dashboard: adicionar gráfico dedicado de sucesso/falha de sync (janela 24h), além da tabela existente.
3) Refatorar a view de métricas para partials:
   - resources/views/admin/metrics/index.blade.php (orquestração)
   - resources/views/admin/metrics/partials/*.blade.php (cards, gráficos, tabelas)
4) (Opcional técnico) criar API interna admin para dados do dashboard:
   - app/Http/Controllers/Api/Admin/MetricsApiController.php
   - rota protegida por `auth.basic`, `is_admin`, `harden.metrics.admin`
   - manter controller fino e lógica em service
5) Entregar recomendação operacional documentada de allowlist Nginx para `/admin/*`:
   - novo doc curto em docs/dashboard/nginx-admin-allowlist.md
   - incluir snippet pronto com observações de ambiente

Regras obrigatórias:
- Controllers finos; regras de negócio em Services.
- Não mover lógica de query para Blade.
- Não quebrar funcionalidades atuais.
- Naming consistente com domínio atual.
- Manter segurança da área admin.

Checklist de entrega (obrigatório no relatório):
- [ ] Gráfico de disponibilidade implementado e renderizando.
- [ ] Gráfico de sucesso/falha de sync implementado e renderizando.
- [ ] View quebrada em partials sem regressão visual.
- [ ] (Se implementado) endpoint API admin protegido e testado.
- [ ] Documento Nginx criado com snippet utilizável.
- [ ] Testes novos/ajustados passando.
- [ ] `php -l` dos arquivos alterados sem erro.
- [ ] `php artisan route:list` e `php artisan schedule:list` conferidos.

Validação mínima antes de concluir:
1. `php -l` em todos os arquivos alterados.
2. Testes focados do dashboard/métricas (feature + quaisquer novos).
3. Checagem de rotas relacionadas ao admin de métricas.

Formato obrigatório da resposta final:
1) Etapa alvo
2) Plano objetivo
3) Implementação (por arquivo)
4) Validação (comandos + resultado)
5) Entrega (arquivos alterados, pendências, próximo passo)
