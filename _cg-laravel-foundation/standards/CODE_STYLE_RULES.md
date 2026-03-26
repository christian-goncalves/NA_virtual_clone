# Code Style Rules (PHP/Laravel)

## Objetivo

Garantir consistencia de codigo e legibilidade entre projetos.

## Regras obrigatorias

- seguir PSR-12
- declarar `strict_types=1` quando viavel no contexto do projeto
- nomes de classes e metodos orientados a dominio
- metodos curtos e foco unico
- evitar comentario redundante; comentar apenas decisoes nao obvias

## Controllers

- finos, sem regra de negocio extensa
- preferir Form Requests para validacao
- respostas HTTP padronizadas

## Services

- classes coesas por caso de uso
- sem dependencia de request HTTP
- retorno consistente e previsivel

## Config e ambiente

- usar `env()` somente em `config/*.php`
- acessar configuracao via helper `config()`
- evitar valores magicos hardcoded

## Qualidade minima

- lint e testes passando antes de PR
- nomes e contratos claros
- sem duplicacao desnecessaria
