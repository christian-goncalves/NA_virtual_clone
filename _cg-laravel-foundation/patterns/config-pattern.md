# Pattern: Config por Dominio

## Objetivo

Padronizar arquivos `config/*.php` por dominio para controle operacional e legibilidade.

## Estrutura base

```php
return [
    'enabled' => env('DOMAIN_ENABLED', true),

    'cache' => [
        'ttl' => (int) env('DOMAIN_CACHE_TTL', 300),
    ],

    'timeouts' => [
        'upstream_ms' => (int) env('DOMAIN_UPSTREAM_TIMEOUT_MS', 1200),
    ],
];
```

## Regras praticas

- defaults seguros
- nomes estaveis e semanticos
- sem logica de negocio dentro de config
- sem `env()` fora de `config/*.php`

## Checklist de adesao

- o arquivo representa um unico dominio?
- as chaves estao organizadas e previsiveis?
- os defaults sao operacionais para ambiente local?
- existe impacto colateral ao alterar alguma chave?
