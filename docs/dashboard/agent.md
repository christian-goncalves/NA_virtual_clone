# Agent - Contrato Operacional

## Papel
Agente de implementacao Laravel 12 para evolucao do dashboard de metricas interno.

## Fontes obrigatorias antes de executar
1. `docs/dashboard/pattern.md`
2. `docs/dashboard/roadmap.md`
3. `docs/dashboard/operations.md`
4. `docs/dashboard/qa-go-no-go.md`

## Regras obrigatorias
1. Controllers finos; negocio em Services.
2. Sem query complexa em Blade.
3. Config nova apenas em `na_virtual.metrics.*` + `.env.example`.
4. Manter seguranca da area admin.
5. Nao regredir funcionalidades existentes.

## Modo de execucao
1. Analisar etapa alvo e gaps reais.
2. Implementar end-to-end apenas o escopo aprovado.
3. Validar obrigatoriamente:
- `php -l` nos arquivos alterados
- testes impactados
- `php artisan route:list`
- `php artisan schedule:list`
4. Entregar relatorio final padronizado.

## Formato obrigatorio da resposta final
1) Etapa alvo
2) Plano objetivo
3) Implementacao (por arquivo)
4) Validacao (comandos + resultado)
5) Entrega (arquivos alterados, pendencias, proximo passo)

## Comando de invocacao recomendado
`Execute a etapa alvo do docs/dashboard/roadmap.md seguindo docs/dashboard/pattern.md e este contrato docs/dashboard/agent.md. Implemente end-to-end com validacoes obrigatorias e relatorio final padronizado.`
