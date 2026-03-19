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
