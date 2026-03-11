# Reconstrução da página de reuniões virtuais de NA em Laravel

## Objetivo
Reconstruir a página pública de reuniões virtuais, sem login, com atualização dinâmica a partir da base pública do site oficial de Narcóticos Anônimos.

---

## Leitura técnica do comportamento atual

A página publicada em `navirtual.reunioes.org` aparenta ter dois blocos distintos:

1. **Camada institucional e visual estática**
   - header
   - hero
   - CTAs
   - bloco institucional
   - footer

2. **Camada dinâmica de reuniões**
   - reuniões em andamento
   - iniciando em breve
   - próximas reuniões
   - agrupamento por horário e status
   - badges por tipo/formato
   - botão de entrada por reunião

Pelo HTML disponível, a página é uma aplicação **Next.js** com renderização inicial e posterior hidratação no cliente.

---

## Conclusão arquitetural

A melhor reconstrução em Laravel é esta:

- **Laravel** para backend, scraping, normalização e cache
- **Blade + Tailwind** para a camada visual
- **Scheduler + Queue** para atualização periódica da base
- **Cache local** para evitar dependência síncrona do site oficial a cada request

Isso evita:
- lentidão
- indisponibilidade do site origem derrubando sua página
- parsing em tempo real a cada acesso
- risco operacional por excesso de requisições

---

## Fluxo recomendado

```text
Site oficial NA
   ↓
Job de coleta
   ↓
Parser / normalização
   ↓
Tabela local de reuniões
   ↓
Serviço de agrupamento por status e janela de horário
   ↓
Controller público
   ↓
Blade renderiza a página
```

---

## Fonte de dados

## Fonte primária
- `https://www.na.org.br/virtual/`

## Observação operacional
A página oficial lista reuniões virtuais e filtros públicos. A reconstrução deve consumir essa origem como fonte principal de dados, e não depender da página `navirtual.reunioes.org` como fonte de verdade.

---

## Modelo de dados sugerido

### Tabela `virtual_meetings`

Campos mínimos:

```php
id
external_id nullable
name
meeting_platform nullable        // zoom, zello, outro
meeting_url nullable
meeting_id nullable
meeting_password nullable
phone nullable
region nullable
state nullable
city nullable
neighborhood nullable
format_labels json nullable
type_label nullable              // aberta, fechada, estudo etc.
interest_labels json nullable
weekday nullable
start_time
end_time nullable
duration_minutes nullable
timezone default America/Sao_Paulo
is_open boolean default false
is_study boolean default false
is_lgbt boolean default false
is_women boolean default false
is_hybrid boolean default false
source_url nullable
source_hash nullable
is_active boolean default true
last_seen_at nullable
synced_at nullable
auto_join_enabled boolean default true
created_at
updated_at
```

### Tabela `virtual_meeting_snapshots` (opcional)

Para auditoria de mudanças:

```php
id
virtual_meeting_id
payload json
captured_at
created_at
updated_at
```

---

## Regras de negócio para os 3 grandes grupos

### 1. Reuniões em andamento
Critério:
- `now` entre `start_time` e `end_time`

### 2. Iniciando em breve
Critério:
- reuniões futuras
- início entre `now` e `now + 60 minutos`

### 3. Próximas reuniões
Critério:
- após a janela de 60 minutos
- ordenadas por horário crescente
- opcionalmente limitar as primeiras 10, 20 ou 30

---

## Regras auxiliares

### Cálculo de duração
Se a origem não trouxer duração explícita:
- inferir por `end_time - start_time`
- se não houver `end_time`, usar fallback configurável, por exemplo `120 min`

### Status visual
- **Em andamento**: azul
- **Iniciando em breve**: laranja
- **Próximas**: neutro

### Badges
Mapear formatos para badges:
- Aberta
- Fechada
- Estudo
- LGBTQIAPN+
- Interesse feminino
- Híbrida
- Temática

---

## Estratégia de coleta

## Melhor abordagem
Não fazer scraping do HTML visual da página clonada.

Fazer:
1. buscar a página oficial
2. localizar a estrutura que contém os itens de reunião
3. extrair os campos relevantes
4. normalizar
5. armazenar localmente

---

## Serviço principal

### `app/Services/NaVirtualMeetingSyncService.php`
Responsabilidades:
- baixar HTML da origem
- parsear reuniões
- transformar em DTO
- persistir no banco
- desativar reuniões não encontradas na rodada atual

### `app/Services/NaVirtualMeetingGroupingService.php`
Responsabilidades:
- montar os 3 blocos da interface
- ordenar
- calcular texto como `termina em 28 min` ou `em 27 min`

---

## Scheduler

### Frequência recomendada
A cada 5 minutos:

```php
Schedule::job(new SyncNaVirtualMeetingsJob)->everyFiveMinutes();
```

Se o volume e o risco operacional forem baixos:
- pode iniciar com 10 minutos

---

## Rotas

```php
Route::get('/reunioes-virtuais', [VirtualMeetingController::class, 'index']);
Route::get('/api/reunioes-virtuais', [VirtualMeetingApiController::class, 'index']);
```

---

## Controller público

### `VirtualMeetingController`

Responsabilidade:
- chamar serviço de agrupamento
- entregar para a view

Exemplo conceitual:

```php
public function index(NaVirtualMeetingGroupingService $service)
{
    $data = $service->buildHomePageData();

    return view('virtual-meetings.index', $data);
}
```

---

## Estrutura Blade sugerida

```text
resources/views/virtual-meetings/
  index.blade.php
  partials/
    header.blade.php
    hero.blade.php
    section-running.blade.php
    section-starting-soon.blade.php
    section-upcoming.blade.php
    meeting-card.blade.php
    meeting-row.blade.php
    footer.blade.php
```

---

## Estratégia de layout

### Seção 1 — Reuniões em andamento
- grid 3 colunas no desktop
- cards com destaque
- nome do grupo
- tipo
- horário
- plataforma
- id/senha se existir
- botão entrar
- texto de encerramento

### Seção 2 — Iniciando em breve
- lista vertical
- foco em escaneabilidade
- horário à esquerda
- grupo e badges no centro
- CTA à direita

### Seção 3 — Próximas reuniões
- lista vertical
- mesmo padrão da seção 2
- botão “ver todas” opcional

---

## ViewModel sugerido

```php
return [
    'serverTime' => now(),
    'runningCount' => $running->count(),
    'startingSoonCount' => $startingSoon->count(),
    'upcomingCount' => $upcoming->count(),
    'runningMeetings' => $running,
    'startingSoonMeetings' => $startingSoon,
    'upcomingMeetings' => $upcoming,
    'groupedBadges' => [
        'aberta' => 'Aberta — público em geral',
        'fechada' => 'Fechada — quem tem ou acha que tem problemas com drogas',
        'estudo' => 'Estudos — estudo de literatura',
    ],
];
```

---

## Parser: estratégia prática

Em Laravel, você pode usar:

- `Http::timeout(...)`
- `Symfony DomCrawler`
- fallback com regex somente se necessário

### Exemplo estrutural

```php
$response = Http::timeout(20)->get('https://www.na.org.br/virtual/');
$html = $response->body();

$crawler = new Crawler($html);
```

Depois você cria extratores para:
- nome do grupo
- horário
- link de entrada
- meeting id
- senha
- labels de formato
- plataforma

---

## Persistência idempotente

Use `updateOrCreate` com chave estável.

Se não existir um ID confiável da origem, gere um hash com:

```text
nome + horário + plataforma + meeting_id
```

Exemplo:

```php
$externalHash = sha1(
    mb_strtolower(trim($dto->name)) . '|' .
    $dto->start_time . '|' .
    ($dto->meeting_platform ?? '') . '|' .
    ($dto->meeting_id ?? '')
);
```

---

## Cache

Para a página pública:

- cache do dataset agrupado por 60 a 120 segundos
- invalidação após sync

Exemplo:

```php
Cache::remember('na.virtual.homepage', 120, fn () => $service->buildHomePageData());
```

---

## Segurança e compliance

Pontos críticos:
- não alterar a informação oficial
- deixar claro que os dados vêm do site oficial
- registrar `source_url`
- tratar falhas da origem com fallback do último cache válido
- não depender de automação que simule login, porque a página é pública

---

## Comportamento de falha

Se a coleta falhar:
- exibir último snapshot válido
- registrar log
- alertar operação
- não derrubar a página pública

---

## Estrutura mínima de classes

```text
app/
  Console/
    Kernel.php
  Jobs/
    SyncNaVirtualMeetingsJob.php
  Http/
    Controllers/
      VirtualMeetingController.php
      Api/
        VirtualMeetingApiController.php
  Models/
    VirtualMeeting.php
    VirtualMeetingSnapshot.php
  Services/
    NaVirtualMeetingSyncService.php
    NaVirtualMeetingGroupingService.php
  Data/
    VirtualMeetingData.php
```

---

## Ordem correta de implementação

### Etapa 1 — Banco
- migration de `virtual_meetings`
- model

### Etapa 2 — Coleta
- service de sync
- parser
- command manual para testar coleta

### Etapa 3 — Agrupamento
- service para separar em andamento / breve / próximas

### Etapa 4 — Tela
- blade principal
- componentes visuais

### Etapa 5 — Scheduler
- job recorrente
- logs e fallback

### Etapa 6 — API opcional
- endpoint JSON para consumo futuro

---

## Resultado operacional esperado

Você terá uma página:
- pública
- sem login
- rápida
- cacheada
- alimentada pela base oficial
- com agrupamento de reuniões por janela temporal
- com manutenção simples em Laravel MVC

---

## Próximo passo recomendado

Implementar primeiro este núcleo:

1. migration `virtual_meetings`
2. model `VirtualMeeting`
3. command `na:sync-virtual-meetings`
4. service `NaVirtualMeetingSyncService`
5. controller `VirtualMeetingController`
6. view `virtual-meetings/index.blade.php`

---

## Entrega técnica ideal na próxima rodada

Na próxima etapa, gerar os arquivos reais abaixo:

- migration completa
- model
- service de sync
- service de agrupamento
- controller
- blade da página

