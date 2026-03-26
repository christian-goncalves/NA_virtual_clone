# KB Config

## Objetivo

Padronizar configuracao por dominio para evitar valores hardcoded e facilitar operacao entre ambientes.

## Convencoes

- um arquivo por dominio em `config/<dominio>.php`
- nomes descritivos e hierarquia curta
- valores com `env()` apenas em config
- consumo via `config('dominio.chave')`

## Estrutura recomendada

- `enabled` para feature toggle
- `timeouts` para integrações
- `cache` para TTL/chaves base
- `limits` para politicas operacionais

## Exemplo de chaves

- `metrics.enabled`
- `metrics.cache_ttl`
- `metrics.refresh_timeout_ms`

## Boas praticas

- definir default seguro para desenvolvimento
- documentar impacto operacional de cada chave sensivel
- manter coerencia de nomes entre dominios
- revisar config em PR com mesma severidade de codigo

## Anti-patterns comuns

- usar `env()` dentro de service/controller
- config sem default em ambiente nao preparado
- misturar configuracao de dominios distintos no mesmo arquivo
