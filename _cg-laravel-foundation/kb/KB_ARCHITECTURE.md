# KB Architecture

## Visao macro

Arquitetura Laravel orientada a feature com service layer como centro da regra de negocio. O controller atua como orquestrador de entrada/saida HTTP e delega processamento para services especializados.

## Principios-base

- separacao de responsabilidades por camada
- regras de negocio fora de controllers e views
- dependencia apontando para contratos estaveis
- observabilidade acoplada ao fluxo critico
- configuracao externa por dominio

## Camadas recomendadas

1. HTTP (Controllers, Requests, Resources)
2. Application (Services, DTOs, Policies de caso de uso)
3. Domain (Regras centrais e invariantes, quando aplicavel)
4. Infrastructure (Clientes externos, cache, persistencia, filas)

## Organizacao sugerida por feature

- `app/Http/Controllers/<Dominio>/...`
- `app/Http/Requests/<Dominio>/...`
- `app/Services/<Dominio>/...`
- `app/DTOs/<Dominio>/...` (quando necessario)
- `config/<dominio>.php`

## Fluxo padrao de uma feature

1. Request validado entra no Controller.
2. Controller chama Service com dados tipados/estruturados.
3. Service executa regra de negocio e integra dependencias.
4. Service emite logs/metricas nos pontos relevantes.
5. Controller mapeia resposta para Resource/JSON padrao.

## Decisoes arquiteturais praticas

- criar novo service quando houver regra reutilizavel ou fluxo complexo
- manter services pequenos e compostos quando o caso exigir etapas
- aplicar cache em leitura de alto volume e baixa variacao
- usar fallback apenas com criterio operacional definido

## Sinais de alerta

- controller com muitos `if/else` e loops complexos
- service acessando request/resposta HTTP diretamente
- config hardcoded em classe de negocio
- ausencia de metrica em fluxo critico para operacao
