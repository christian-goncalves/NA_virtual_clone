# PR Checklist

## Arquitetura e camadas

- [ ] Controller permanece fino e delega para service
- [ ] Service concentra regra de negocio do caso de uso
- [ ] Nao ha violacao de fronteiras entre camadas
- [ ] Config por dominio foi respeitada

## Qualidade de codigo

- [ ] Nomes de classes e metodos estao claros
- [ ] Nao ha duplicacao desnecessaria
- [ ] Erros sao tratados com contexto operacional
- [ ] Anti-patterns listados em `standards/ANTI_PATTERNS.md` foram evitados

## Observabilidade

- [ ] Fluxos criticos possuem metrica de sucesso/falha
- [ ] Latencia relevante esta instrumentada
- [ ] Eventos de fallback estao visiveis em logs/metricas

## Testes

- [ ] Existe cobertura de caminho feliz
- [ ] Existem testes de borda/falha quando risco justificar
- [ ] Testes executados localmente antes do merge

## Pronto para merge

- [ ] Documentacao impactada foi atualizada
- [ ] Contratos de API nao sofreram quebra nao planejada
- [ ] Mudanca esta apta para manutencao por outra pessoa
