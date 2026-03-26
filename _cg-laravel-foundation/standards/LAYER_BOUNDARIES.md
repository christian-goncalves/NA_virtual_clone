# Layer Boundaries

## Objetivo

Definir limites claros entre camadas para reduzir acoplamento e facilitar manutencao.

## Controller

Pode:

- receber request
- delegar para service
- retornar resposta HTTP

Nao pode:

- conter regra de negocio extensa
- integrar cliente externo diretamente
- manipular persistencia complexa de negocio

## Service

Pode:

- executar caso de uso
- orquestrar repositorios/clients
- aplicar cache, fallback e metricas

Nao pode:

- depender de `Request`/`Response`
- renderizar view
- conhecer detalhes de rota

## Model

Pode:

- representar dados e relacoes
- oferecer escopos e comportamento de dominio simples

Nao pode:

- centralizar fluxo aplicacional de alto nivel
- assumir papel de orchestrator de integrações

## Config

Pode:

- armazenar parametros por dominio
- definir defaults por ambiente

Nao pode:

- conter logica de negocio
- acessar componentes da aplicacao

## View/Resource

Pode:

- formatar saida para consumidor

Nao pode:

- executar regra de negocio
- acionar dependencias externas

## Infraestrutura

Pode:

- encapsular detalhes tecnicos (HTTP, fila, cache, banco)

Nao pode:

- decidir regra de negocio de alto nivel
