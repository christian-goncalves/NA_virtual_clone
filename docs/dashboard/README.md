# Dashboard de Metricas - Documentacao Canonica

## Objetivo
Esta pasta concentra as regras operacionais e tecnicas do dashboard interno `/admin/metricas` em Laravel 12.

## Ordem de leitura
1. `pattern.md` - padrao arquitetural e regras obrigatorias.
2. `plan.md` - roadmap por fases e estado atual.
3. `operations.md` - operacao, hardening e rotina de validacao.
4. `agent.md` - contrato de execucao para implementacoes.

## Fluxo de execucao recomendado
1. Ler `pattern.md` e confirmar aderencia.
2. Ler `plan.md` e escolher a etapa alvo.
3. Executar conforme `agent.md`.
4. Validar e operar conforme `operations.md`.

## Escopo do modulo
- Dashboard privado de observabilidade para reunioes virtuais.
- Coleta e agregacao de metricas operacionais.
- Alertas, retencao e hardening de acesso admin.

## Regra de manutencao da documentacao
- Evitar arquivos temporarios de prompt nesta pasta.
- Consolidar novidades no arquivo canonico do tema.
- Nao duplicar orientacoes entre arquivos.
