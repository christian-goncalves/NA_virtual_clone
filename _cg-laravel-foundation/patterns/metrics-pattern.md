# Pattern: Metrics

## Objetivo

Padronizar implementacao de metricas em services e fluxos criticos.

## Estrategia minima

1. medir inicio/fim de caso de uso
2. registrar sucesso/falha
3. registrar latencia total
4. registrar ativacao de fallback

## Exemplo curto

```php
$start = microtime(true);

try {
    $result = $this->reportService->handle($input);
    $this->metrics->increment('report_generate_success_count');

    return $result;
} catch (\Throwable $e) {
    $this->metrics->increment('report_generate_error_count');
    throw $e;
} finally {
    $elapsedMs = (int) ((microtime(true) - $start) * 1000);
    $this->metrics->timing('report_generate_duration_ms', $elapsedMs);
}
```

## Regras praticas

- nome de metrica estavel e orientado a dominio
- evitar tags com alta cardinalidade
- manter telemetria proxima da regra de negocio
- nao engolir excecao para preservar sinal operacional

## Checklist de adesao

- fluxo critico esta medido?
- ha contagem de sucesso e erro?
- latencia esta registrada?
- fallback gera evento/metrica especifica?
