# KB Services

## Papel da service layer

Services concentram o fluxo de negocio da aplicacao. Eles orquestram entidades, repositorios e integracoes externas, preservando controllers simples e objetivos.

## Quando criar um service

- fluxo com mais de uma etapa de negocio
- regra que precisa ser reutilizada por multiplos endpoints
- necessidade de isolamento para testes
- integracao com dependencia externa ou cache

## Criterios de coesao

- cada service com contexto de dominio claro
- metodos publicos orientados a caso de uso
- evitar classes "god service" com multiplos assuntos

## Limites da camada

- service nao depende de classe HTTP
- service nao renderiza resposta de view
- service pode usar repositorios, gateways, clients e configs

## Contratos de entrada e saida

- entrada valida e explicita (DTO/array estruturado)
- retorno previsivel com estrutura consistente
- erros de negocio representados de forma clara

## Boas praticas operacionais

- instrumentar latencia e sucesso/falha de fluxos criticos
- aplicar cache com chave e TTL definidos por dominio
- registrar logs com contexto minimo (feature, acao, identificadores)
- prever fallback quando dependencia externa for instavel

## Testabilidade

- isolar dependencias por interface quando houver alto acoplamento
- testar caminho feliz, regras de borda e falhas operacionais
- manter service livre de detalhes de framework quando possivel
