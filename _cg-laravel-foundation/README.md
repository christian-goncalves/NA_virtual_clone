````markdown
# cg-laravel-foundation

## O que e este repositorio

`cg-laravel-foundation` e uma base reutilizavel para padronizar arquitetura, organizacao e criterios tecnicos em projetos Laravel.

Seu objetivo e funcionar como fonte de verdade operacional para desenvolvimento assistido por Codex/LLM, com foco em:
- consistencia arquitetural
- velocidade com governanca
- geracao de codigo com menos ambiguidade
- revisao tecnica orientada por regras
- evolucao continua da base durante a vida do projeto

Quando aplicada a um projeto Laravel, esta base normalmente deve existir na raiz do projeto com o nome:

```txt
.ai-foundation/
````

---

## Para que serve

Esta foundation existe para:

* reduzir variacao arquitetural entre projetos Laravel
* acelerar criacao de features com padroes claros
* evitar anti-patterns recorrentes
* orientar o Codex com instrucoes objetivas
* estabelecer base de service layer, config por dominio, observabilidade e revisao tecnica
* permitir que cada projeto tenha uma camada local de conhecimento arquitetural evolutivo

---

## Modelo de uso

Existem dois niveis de uso desta foundation.

### 1. Foundation central

E o repositorio `cg-laravel-foundation`, com padroes reutilizaveis e regras estaveis.

Use este nivel para:

* manter padroes compartilhaveis
* consolidar boas praticas recorrentes
* armazenar agentes, templates e regras de governanca

### 2. Foundation local do projeto

E a copia desta estrutura dentro de um projeto Laravel, normalmente em:

```txt
.ai-foundation/
```

Use este nivel para:

* adaptar a foundation ao contexto real do projeto
* registrar decisoes arquiteturais locais
* atualizar criterios conforme o projeto evolui
* orientar o Codex com base no estado atual do sistema

---

## Como usar em novos projetos Laravel

### Opcao 1: copiar para dentro do projeto

1. Crie o novo projeto Laravel.
2. Copie esta estrutura para a raiz do projeto.
3. Garanta que a pasta se chame `.ai-foundation/`.
4. Oriente o Codex a ler primeiro os arquivos de `codex/`.
5. Use a `.ai-foundation` como referencia principal para planejamento, geracao e revisao.

### Opcao 2: manter a base central e espelhar localmente

1. Mantenha `cg-laravel-foundation` como repositorio central.
2. Ao iniciar um novo projeto, copie a estrutura para `.ai-foundation/`.
3. Permita que a copia local evolua de acordo com o projeto.
4. Periodicamente, revise o que deve ser promovido da foundation local para a foundation central.

---

## Como o Codex deve operar

Ao entrar em um projeto que contenha `.ai-foundation/`, o Codex deve tratar essa estrutura como referencia prioritaria.

### Ordem obrigatoria de leitura

1. `codex/PROJECT_BOOT_INSTRUCTIONS.md`
2. `codex/FILE_PRIORITY_ORDER.md`
3. `kb/*`
4. `standards/*`
5. `patterns/*`
6. `templates/*`
7. codigo real do projeto

### Objetivos operacionais do Codex

O Codex deve usar a foundation para:

1. entender a arquitetura alvo antes de sugerir mudancas
2. mapear a estrutura atual do projeto
3. propor planejamento tecnico por etapas
4. gerar codigo respeitando limites de camada
5. manter controllers finos e services coesos
6. evitar anti-patterns definidos nos documentos
7. revisar mudancas com base em checklists
8. atualizar a foundation local quando surgirem novas decisoes do projeto

---

## Tarefas objetivas que o Codex deve executar

Quando acionado em um projeto com `.ai-foundation/`, o Codex deve ser capaz de executar as seguintes frentes.

### 1. Planejamento

* mapear arquitetura existente
* identificar lacunas estruturais
* propor organizacao por camadas
* sugerir plano de implementacao por etapas
* registrar decisoes relevantes na foundation local

### 2. Geracao de codigo

* criar controllers seguindo os padroes da foundation
* criar services coesos e especializados
* criar arquivos config por dominio
* criar testes iniciais com base nos templates
* respeitar limites entre controller, service, model, view e infraestrutura

### 3. Revisao tecnica

* revisar controllers
* identificar violacoes de camada
* apontar anti-patterns
* validar aderencia a padroes documentados
* usar `checklists/PR_CHECKLIST.md` como referencia minima

### 4. Evolucao da base local

* atualizar a `.ai-foundation` do projeto com decisoes novas
* registrar convencoes especificas do projeto
* refinar documentacao local conforme a arquitetura amadurece
* separar o que e regra local do que pode virar padrao global

---

## Politica de evolucao da foundation

A foundation local do projeto pode ser atualizada ao longo da execucao.

Isso e esperado e recomendado.

### Deve ser atualizado localmente quando:

* surgir uma convencao especifica do projeto
* uma decisao arquitetural precisar ser registrada
* o dominio do sistema exigir regras proprias
* o time precisar reduzir ambiguidades futuras

### Deve ser promovido para a foundation central quando:

* a regra servir para multiplos projetos
* o padrao estiver estavel
* a decisao nao depender de contexto especifico de dominio
* a melhoria aumentar a qualidade da base reutilizavel

### Regra de governanca

* foundation local = contexto vivo do projeto
* foundation central = padrao consolidado e reutilizavel

---

## Fluxo recomendado para novos projetos

1. Criar ou abrir o projeto Laravel.
2. Adicionar `.ai-foundation/` na raiz do projeto.
3. Ler `codex/PROJECT_BOOT_INSTRUCTIONS.md`.
4. Ler `codex/FILE_PRIORITY_ORDER.md`.
5. Aplicar `checklists/NEW_PROJECT_CHECKLIST.md`.
6. Fazer estudo de planejamento antes das primeiras implementacoes relevantes.
7. Implementar features com apoio de `patterns/` e `templates/`.
8. Revisar mudancas com `checklists/PR_CHECKLIST.md`.
9. Atualizar a foundation local sempre que surgirem decisoes arquiteturais relevantes.
10. Promover para a foundation central os padroes que se mostrarem reutilizaveis.

---

## Prompt-base recomendado para iniciar um projeto com Codex

Use o texto abaixo ao iniciar o trabalho no projeto:

```md
Use a pasta `.ai-foundation` como fonte de verdade deste projeto.

Ordem de leitura obrigatoria:
1. `.ai-foundation/codex/PROJECT_BOOT_INSTRUCTIONS.md`
2. `.ai-foundation/codex/FILE_PRIORITY_ORDER.md`
3. `.ai-foundation/kb/*`
4. `.ai-foundation/standards/*`
5. `.ai-foundation/patterns/*`
6. `.ai-foundation/templates/*`
7. codigo real do projeto

Tarefa inicial:
- mapear a arquitetura atual
- identificar lacunas estruturais
- propor um plano de implementacao por etapas
- respeitar os padroes e limites documentados
- sugerir tambem atualizacoes uteis na `.ai-foundation` local do projeto quando houver ambiguidades, decisoes novas ou padroes emergentes
```

---

## Escopo e limites

Esta foundation:

* nao e package Composer
* nao e framework proprio
* nao substitui analise tecnica do projeto real
* nao elimina a necessidade de revisao arquitetural
* nao deve gerar abstracoes desnecessarias

Esta foundation existe para:

* orientar
* padronizar
* acelerar
* documentar
* reduzir ambiguidade operacional

---

## Resultado esperado

Ao usar esta foundation de forma disciplinada, os projetos Laravel tendem a ter:

* maior previsibilidade tecnica
* menor custo de manutencao
* geracao de codigo mais consistente
* revisoes mais objetivas
* arquitetura mais estavel
* onboarding mais rapido
* menor ambiguidade na atuacao do Codex
* documentacao viva acoplada a evolucao do projeto

```
```
