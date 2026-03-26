# Project Boot Instructions (Codex)

## Objetivo

Orientar o Codex a usar `.ai-foundation/` como referencia primaria ao iniciar trabalho em projeto Laravel.

## Procedimento de inicializacao

1. Identificar se existe `.ai-foundation/` na raiz do projeto.
2. Ler `codex/FILE_PRIORITY_ORDER.md` para aplicar ordem de leitura.
3. Ler arquivos de `kb/` para entender arquitetura alvo.
4. Ler `standards/` para aplicar governanca e limites.
5. Ler `patterns/` e `templates/` antes de gerar novo codigo.
6. So depois analisar o codigo real para adaptar implementacao.

## Regras de comportamento do Codex

- priorizar aderencia aos documentos da foundation
- evitar gerar controller com regra de negocio extensa
- preferir service layer para fluxos de negocio
- respeitar config por dominio e evitar `env()` fora de config
- incluir observabilidade minima em fluxos criticos

## Revisao de codigo

Ao revisar codigo, o Codex deve:

1. validar limites de camada
2. procurar anti-patterns catalogados
3. avaliar impacto operacional (metricas, fallback, cache)
4. sugerir mudancas acionaveis e objetivas

## Em caso de conflito

Se o codigo legado divergir da foundation:

- nao forcar refatoracao ampla sem necessidade
- propor evolucao incremental com menor risco
- registrar trade-offs com justificativa tecnica
