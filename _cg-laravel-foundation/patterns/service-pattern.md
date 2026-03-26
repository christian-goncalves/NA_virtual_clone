# Pattern: Service Class

## Objetivo

Definir o padrao de service para casos de uso com regra de negocio, integracoes e observabilidade.

## Estrutura padrao

- construtor com dependencias explicitas
- metodo publico principal (`handle`, `execute` ou nome de caso de uso)
- metodos privados para etapas internas relevantes
- retorno consistente

## Exemplo curto

```php
final class GenerateMetricsSnapshotService
{
    public function __construct(
        private MetricsRepository $repository,
        private CacheRepository $cache,
    ) {}

    public function handle(array $filters): array
    {
        $cacheKey = 'metrics:snapshot:' . md5(json_encode($filters));

        return $this->cache->remember($cacheKey, 300, function () use ($filters) {
            return $this->repository->snapshot($filters);
        });
    }
}
```

## Regras praticas

- service nao depende de request HTTP
- retornar estrutura previsivel
- encapsular complexidade por etapas pequenas
- explicitar fallback quando aplicavel

## Checklist de adesao

- objetivo do metodo principal esta claro?
- dependencias refletem responsabilidade real?
- existe acoplamento indevido com camada HTTP?
- ha pontos de observabilidade nos fluxos criticos?
