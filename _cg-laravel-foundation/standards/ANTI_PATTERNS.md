# Anti-Patterns

## Lista objetiva

- controller com regra de negocio complexa
- service com responsabilidades sem relacao
- uso de `env()` fora de `config/*.php`
- classe unica fazendo HTTP + regra + persistencia + serializacao
- fallback silencioso sem log/metrica
- excecao capturada e ignorada sem contexto
- cache sem estrategia de invalidação clara
- metrica com nome inconsistente ou inutil para operacao

## Sinais de regressao arquitetural

- aumento continuo de linhas em controllers
- proliferacao de metodos estaticos utilitarios sem dominio
- dependencia circular entre services
- validacao espalhada em multiplas camadas sem criterio

## Acoes recomendadas

- extrair regra de negocio para service dedicado
- quebrar service grande em servicos menores por caso de uso
- centralizar configuracao por dominio
- adicionar observabilidade nos fluxos criticos
