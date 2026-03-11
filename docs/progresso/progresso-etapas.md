# Progresso das Etapas

## Resumo geral
- Etapa 1: concluída
- Etapa 2: concluída
- Etapa 3: concluída
- Etapa 4: pendente
- Etapa 5: pendente
- Etapa 6: pendente (opcional)

---

## Etapa 1 - Banco/model
**Status:** concluída

**Entregue**
- Migration `virtual_meetings`
- Model `VirtualMeeting`

**Observações**
- Estrutura alinhada ao guia do projeto.

---

## Etapa 2 - Coleta/sync
**Status:** concluída

**Entregue**
- `app/Services/NaVirtualMeetingSyncService.php`
- `app/Console/Commands/SyncNaVirtualMeetingsCommand.php`

**Comando**
- `php artisan na:sync-virtual-meetings`

**Observações**
- Filtros fixados conforme origem:
  - `weekdays=all`
  - `periodo=all`
- Parsing defensivo e persistência idempotente.
- Correção de duração para reuniões que cruzam meia-noite.

---

## Etapa 3 - Agrupamento
**Status:** concluída

**Entregue**
- `app/Services/NaVirtualMeetingGroupingService.php`

**Regras implementadas**
- Running: `now` entre início e fim.
- Starting soon: início em até 60 minutos.
- Upcoming: restante.
- Ordenação por horário.

---

## Etapa 4 - Controller público
**Status:** pendente

**Planejado**
- Criar `VirtualMeetingController`
- Integrar `NaVirtualMeetingGroupingService`

---

## Etapa 5 - Tela (Blade + Tailwind)
**Status:** pendente

**Planejado**
- Criar view `virtual-meetings/index.blade.php`
- Criar partials conforme estrutura definida no guia

---

## Etapa 6 - Scheduler/job
**Status:** pendente

**Planejado**
- Job recorrente para sync
- Schedule recomendado: a cada 5 minutos

---

## Testes automatizados já criados
- `tests/Feature/NaVirtualMeetingSyncCommandTest.php`
- `tests/Feature/NaVirtualMeetingGroupingServiceTest.php`

