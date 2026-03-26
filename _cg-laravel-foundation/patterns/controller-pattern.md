# Pattern: Controller Laravel

## Objetivo

Padrao de controller fino, orientado a orquestracao HTTP e delegacao para service.

## Estrutura padrao

1. receber request validado
2. extrair payload minimo necessario
3. chamar service
4. mapear resposta para JSON/Resource
5. tratar excecoes esperadas

## Exemplo curto

```php
public function store(StoreOrderRequest $request): JsonResponse
{
    $result = $this->createOrderService->handle($request->validated());

    return response()->json([
        'data' => $result,
    ], 201);
}
```

## Regras praticas

- metodos curtos e semanticos
- sem regra de negocio extensa no controller
- sem acesso direto a clients externos
- sem `env()` ou config hardcoded

## Checklist de adesao

- usa Form Request quando ha validacao?
- delega fluxo ao service?
- resposta HTTP esta padronizada?
- erros previstos estao mapeados corretamente?
