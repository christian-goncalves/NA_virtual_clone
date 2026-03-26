# KB Metrics

## Objetivo

Garantir observabilidade minima para operacao e evolucao de features Laravel com dados de saude, desempenho e erro.

## Tipos de metricas

- tecnicas: latencia, taxa de erro, throughput
- funcionais: volume por acao de negocio
- operacionais: cache hit ratio, fallback acionado, timeout externo

## Pontos obrigatorios de instrumentacao

- inicio/fim de fluxo critico em service
- chamadas a integracoes externas
- eventos de fallback
- erros inesperados com contexto de feature

## Convencoes de nome

- prefixo por dominio (`billing_`, `catalog_`, `admin_`)
- sufixo por tipo (`_count`, `_ms`, `_ratio`)
- tags controladas (ambiente, feature, resultado)

## Dashboards administrativos

Minimo recomendado:

1. latencia p50/p95 por endpoint critico
2. taxa de erro por feature
3. volume de execucao por caso de uso
4. percentual de fallback por dependencia

## Alertas iniciais

- erro acima de limite por 5 minutos
- aumento abrupto de latencia p95
- queda significativa de throughput
- taxa de fallback acima do baseline

## Boas praticas

- metrica sem acao operacional definida tende a perder valor
- evitar cardinalidade alta sem necessidade
- manter definicao de metrica proxima ao contexto do service
