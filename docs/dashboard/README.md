# Dashboard de Metricas - Documentacao Canonica

## Objetivo
Esta pasta concentra as regras operacionais e tecnicas do dashboard interno `/admin/metricas` em Laravel 12.

## Fontes de verdade (source of truth)
1. `pattern.md` - padrao arquitetural e regras obrigatorias.
2. `operations.md` - operacao, hardening e rotina de validacao.
3. `agent.md` - contrato de execucao para implementacoes.
4. `roadmap.md` - historico de fases, status e backlog priorizado.
5. `frontend-table.md` - diretrizes da tabela de analise no frontend.
6. `qa-go-no-go.md` - checklist de aprovacao por ciclo.

## Fluxo de execucao recomendado
1. Ler `pattern.md` e confirmar aderencia.
2. Ler `roadmap.md` e escolher a etapa alvo.
3. Executar conforme `agent.md`.
4. Validar e operar conforme `operations.md` + `qa-go-no-go.md`.

## Escopo do modulo
- Dashboard privado de observabilidade para reunioes virtuais.
- Coleta e agregacao de metricas operacionais.
- Alertas, retencao e hardening de acesso admin.
- Secao de analise de reunioes com foco em uso operacional.

## Regra de manutencao da documentacao
- Evitar arquivos temporarios de prompt nesta pasta.
- Consolidar novidades no arquivo canonico do tema.
- Nao duplicar orientacoes entre arquivos.
- Sempre atualizar `roadmap.md` ao fechar um ciclo.
