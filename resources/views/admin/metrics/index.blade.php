<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Metricas</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <header>
            <h1 class="text-2xl font-bold">Dashboard de Metricas</h1>
            <p class="text-sm text-slate-600">Painel interno de observabilidade da aplicacao.</p>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Acessos hoje</p>
                <p class="text-2xl font-semibold">{{ (int) ($accessesToday ?? 0) }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Ultima hora</p>
                <p class="text-2xl font-semibold">{{ (int) ($accessesLastHour ?? 0) }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Em andamento</p>
                <p class="text-2xl font-semibold">{{ (int) ($runningNow ?? 0) }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Taxa de sync 24h</p>
                <p class="text-2xl font-semibold">{{ number_format((float) ($syncSuccessRate24h ?? 0), 2, ',', '.') }}%</p>
            </article>
            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Latencia media 24h</p>
                <p class="text-2xl font-semibold">{{ number_format((float) ($averageLatency24h ?? 0), 0, ',', '.') }}ms</p>
                <p class="text-xs text-slate-500">P95: {{ (int) ($p95Latency24h ?? 0) }}ms</p>
            </article>
            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Ultimo sync</p>
                <p class="text-sm font-semibold">{{ data_get($lastSyncRun, 'status', 'sem dados') }}</p>
                <p class="text-xs text-slate-500">{{ data_get($lastSyncRun, 'started_at', '-') }}</p>
            </article>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Acessos por hora (24h)</h2>
                <div class="space-y-2">
                    @php
                        $maxHourly = max(1, collect($hourlyAccesses ?? [])->max('total') ?? 1);
                    @endphp
                    @forelse (($hourlyAccesses ?? []) as $item)
                        @php
                            $width = ((int) data_get($item, 'total', 0) / $maxHourly) * 100;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                                <span>{{ data_get($item, 'label') }}</span>
                                <span>{{ (int) data_get($item, 'total', 0) }}</span>
                            </div>
                            <div class="h-2 rounded bg-slate-100">
                                <div class="h-2 rounded bg-blue-600" style="width: {{ number_format($width, 2, '.', '') }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Sem dados.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Cliques por categoria (24h)</h2>
                <div class="space-y-2">
                    @php
                        $maxCategory = max(1, collect($categoryClicks ?? [])->max('total') ?? 1);
                    @endphp
                    @forelse (($categoryClicks ?? []) as $item)
                        @php
                            $width = ((int) data_get($item, 'total', 0) / $maxCategory) * 100;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                                <span>{{ data_get($item, 'category') }}</span>
                                <span>{{ (int) data_get($item, 'total', 0) }}</span>
                            </div>
                            <div class="h-2 rounded bg-slate-100">
                                <div class="h-2 rounded bg-emerald-600" style="width: {{ number_format($width, 2, '.', '') }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Sem dados.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Latencia por hora (24h)</h2>
                <div class="space-y-2">
                    @php
                        $maxLatency = max(1, collect($latencyByHour ?? [])->max('p95_ms') ?? 1);
                    @endphp
                    @forelse (($latencyByHour ?? []) as $item)
                        @php
                            $width = ((int) data_get($item, 'p95_ms', 0) / $maxLatency) * 100;
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                                <span>{{ data_get($item, 'label') }}</span>
                                <span>med {{ (int) data_get($item, 'avg_ms', 0) }}ms | p95 {{ (int) data_get($item, 'p95_ms', 0) }}ms</span>
                            </div>
                            <div class="h-2 rounded bg-slate-100">
                                <div class="h-2 rounded bg-amber-500" style="width: {{ number_format($width, 2, '.', '') }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Sem dados.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-xl border bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold">Top rotas lentas (24h)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-2 py-2">Rota</th>
                                <th class="px-2 py-2">Media (ms)</th>
                                <th class="px-2 py-2">P95 (ms)</th>
                                <th class="px-2 py-2">Reqs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($topSlowRoutes ?? []) as $item)
                                <tr class="border-b">
                                    <td class="px-2 py-2">{{ data_get($item, 'route', '-') }}</td>
                                    <td class="px-2 py-2">{{ (int) data_get($item, 'avg_ms', 0) }}</td>
                                    <td class="px-2 py-2">{{ (int) data_get($item, 'p95_ms', 0) }}</td>
                                    <td class="px-2 py-2">{{ (int) data_get($item, 'total', 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-2 py-3 text-slate-500">Sem dados de latencia registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="rounded-xl border bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold">Ultimas sincronizacoes</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-2 py-2">Inicio</th>
                            <th class="px-2 py-2">Status</th>
                            <th class="px-2 py-2">Duracao (ms)</th>
                            <th class="px-2 py-2">Encontradas</th>
                            <th class="px-2 py-2">Erro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($recentSyncRuns ?? []) as $item)
                            <tr class="border-b">
                                <td class="px-2 py-2">{{ data_get($item, 'started_at', '-') }}</td>
                                <td class="px-2 py-2">{{ data_get($item, 'status', '-') }}</td>
                                <td class="px-2 py-2">{{ data_get($item, 'duration_ms', '-') }}</td>
                                <td class="px-2 py-2">{{ data_get($item, 'meetings_found', '-') }}</td>
                                <td class="px-2 py-2 text-xs text-rose-700">{{ data_get($item, 'error_message', '-') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-2 py-3 text-slate-500">Sem sincronizacoes registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
