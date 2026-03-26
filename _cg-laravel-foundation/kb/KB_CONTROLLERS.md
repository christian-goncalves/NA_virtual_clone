# KB Controllers

## Papel do controller

Controller deve receber a requisicao, acionar o caso de uso e devolver resposta HTTP consistente. Nao e lugar de regra de negocio complexa.

## Responsabilidades permitidas

- receber input HTTP
- delegar validacao para Form Requests
- chamar service adequado
- traduzir resultado para resposta HTTP (Resource/JSON)
- mapear excecoes para codigos HTTP apropriados

## O que controller nao deve fazer

- acessar repositorio diretamente para regra complexa
- conter regras de negocio extensas
- montar payloads complexos com transformacao pesada
- acoplar-se a clientes externos
- gerenciar cache de forma detalhada

## Estrutura minima recomendada

- injecao de dependencias no construtor
- metodos curtos por acao (`index`, `show`, `store`, `update`, `destroy`)
- uso de request validado
- retorno padronizado

## Tratamento de erro

- erros de dominio: mapear para 4xx quando aplicavel
- erros inesperados: 5xx + log estruturado
- nao mascarar excecoes criticas sem registro

## Checklist rapido de controller fino

- ha chamada a apenas um service principal por acao?
- existe regra de negocio pesada no metodo?
- o metodo cabe em leitura curta (aprox. ate 25-35 linhas)?
- a resposta e previsivel e padronizada?
