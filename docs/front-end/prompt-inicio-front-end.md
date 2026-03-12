Você está no projeto Laravel em `c:\laragon\www\NA_virtual_clone`.

A estrutura de documentação foi reorganizada.
Use estes caminhos atualizados:

- `docs/back-end/etapas`
- `docs/back-end/progresso`

Antes de qualquer alteração, carregue o contexto completo a partir de:

1) Fonte de verdade de etapas:
- `docs/back-end/etapas/revisão-pos-desenvolvimento-v3.md`
- `docs/back-end/etapas/_project-guide.md`
- `docs/back-end/etapas/_fluxo-geral-de-desenvolvimento.md`

2) Contexto de progresso:
- `docs/back-end/progresso/progresso-etapas.md`
- `docs/back-end/progresso/hand-off-2026-03-11.md`
- `docs/back-end/progresso/hand-off-2026-03-12.md`
- todos os `docs/back-end/progresso/hand-off-*.md` existentes

Regras de trabalho:
- Não assumir nada sem evidência nos arquivos.
- Sempre citar arquivos/linhas relevantes.
- Não alterar backend/sync/API nesta conversa; foco exclusivo em frontend.
- Preservar comportamento funcional atual (dados, rotas e contratos).
- Responder em português, objetivamente.

Resumo do estado atual (baseline; confirme nos arquivos):
- Etapas 1 a 6 concluídas (banco, sync, grouping, controller, UI, scheduler).
- Pós-etapas concluídas:
  - fallback com snapshot válido
  - API pública `GET /api/reunioes-virtuais`
  - alerta operacional ativo (falhas consecutivas + queda brusca)
  - cobertura complementar de testes
  - refino de partials (`section-running`, `section-starting-soon`, `section-upcoming`)
- Pendência principal:
  - parametrização da frequência do scheduler por ambiente (`5/10/30`) via env/config.
- Higiene documental final já aplicada:
  - `v1` e `v2` marcados como históricos superados
  - `v3` consolidado como referência principal

Tarefa inicial obrigatória nesta conversa:
1. Diagnosticar o frontend atual (estrutura Blade, estilos, UX e pontos de melhoria).
2. Propor plano de refactor visual em fases (baixo risco -> médio impacto), sem quebrar renderização existente.
3. Indicar exatamente quais arquivos de view/CSS serão alterados.
4. Só depois disso, perguntar qual fase devo executar primeiro.
