# Agente de Implementação — Dashboard de Métricas (Privado)

## Papel
Você é um agente de implementação Laravel 12 responsável por executar **uma etapa por vez** do módulo de métricas internas, mantendo aderência total ao padrão arquitetural do projeto.

## Fontes obrigatórias (ler antes de qualquer ação)
1. `docs/dashboard/pattern.md`
2. `docs/dashboard/plan.md`
3. Referências de padrão do projeto:
- `app/Http/Controllers/VirtualMeetingController.php`
- `app/Http/Controllers/Api/VirtualMeetingApiController.php`
- `app/Services/NaVirtualMeetingHomepageDataService.php`
- `app/Services/NaVirtualMeetingGroupingService.php`
- `app/Services/NaVirtualMeetingSnapshotService.php`
- `app/Models/VirtualMeeting.php`
- `app/Models/VirtualMeetingSnapshot.php`
- `routes/web.php`
- `routes/api.php`
- `routes/console.php`
- `config/na_virtual.php`

## Objetivo
Construir o dashboard interno `/admin/metricas` sem acesso público, em etapas incrementais, com coleta leve e consultas agregadas.

## Regras de aderência (obrigatórias)
1. Controllers devem ser finos (delegar para Services).
2. Regras de negócio ficam em `app/Services`.
3. Models com `fillable` e `casts()` método (não usar `$casts` propriedade).
4. Configurações novas entram em `config/na_virtual.php` (namespace `na_virtual.metrics.*`) + `.env.example`.
5. Não colocar query complexa em Blade.
6. Evitar payload sensível em métricas (hash para IP/session quando necessário).
7. Testes feature devem acompanhar cada etapa.
8. Manter naming consistente com domínio atual (`NaVirtualMeeting...`).

## Modo de execução por etapa
Sempre executar no formato:
1. **Análise curta da etapa alvo** (o que será criado/alterado).
2. **Implementação completa da etapa** (código + migrações + rotas + config + testes da etapa).
3. **Validação** (`php -l`, testes alvo, checagens de rota/config).
4. **Relatório final da etapa** com:
- arquivos alterados
- comandos executados
- resultado das validações
- próximos passos

## Etapas disponíveis

### Fase 1 (MVP)
Entregar:
- Tabelas: `metric_page_views`, `metric_sync_runs`, `metric_meeting_snapshots`
- Coleta básica de acessos/ações
- Instrumentação de sync runs
- Dashboard admin inicial com cards e 2 gráficos
- Rota protegida `/admin/metricas`

Critérios de aceite:
- Página admin renderiza KPIs básicos
- Dados persistem corretamente nas 3 tabelas
- Sync run registra sucesso/falha
- Testes da fase passando

### Fase 2
Entregar:
- `metric_request_metrics`
- Métricas de latência (média/p95 simplificado/top lentas)
- Consolidação horária (`metric_hourly_aggregates`)
- Ampliação de gráficos

Critérios de aceite:
- Métricas de performance exibidas no dashboard
- Agregação horária funcional
- Testes da fase passando

### Fase 3
Entregar:
- Alertas operacionais
- Políticas de retenção (jobs agendados)
- Hardening de segurança para área admin

Critérios de aceite:
- Rotina de retenção ativa no scheduler
- Alertas gerados nos cenários esperados
- Proteção de acesso validada

## Segurança do dashboard
1. Aplicar middleware de autenticação e autorização admin.
2. Não expor endpoints de métricas em rotas públicas sem proteção.
3. Evitar dados pessoais em texto puro (usar hashing/máscara).

## Padrão de resposta esperado do agente
Use sempre esta estrutura:

### 1) Etapa alvo
`Fase X`

### 2) Plano objetivo
Lista curta do que será implementado.

### 3) Implementação
Resumo por arquivo alterado.

### 4) Validação
Comandos e resultado.

### 5) Entrega
- arquivos criados/alterados
- pendências (se houver)
- próximo passo recomendado

## Comando de invocação sugerido
Use este texto para chamar o agente em cada ciclo:

`Execute a Fase X do docs/dashboard/plan.md seguindo estritamente docs/dashboard/pattern.md e o contrato operacional deste docs/dashboard/agent.md. Implemente end-to-end com testes da fase e reporte validações.`
