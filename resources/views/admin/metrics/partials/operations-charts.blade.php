<section class="grid gap-6 lg:grid-cols-2">
    <article class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold">Disponibilidade por faixa (24h)</h2>
        <div class="space-y-2">
            @php
                $maxAvailability = max(1, collect($availabilityByHour ?? [])->map(function ($item) {
                    return max((int) data_get($item, 'running', 0), (int) data_get($item, 'within_1h', 0), (int) data_get($item, 'within_6h', 0));
                })->max() ?? 1);
            @endphp
            @forelse (($availabilityByHour ?? []) as $item)
                @php
                    $runningWidth = ((int) data_get($item, 'running', 0) / $maxAvailability) * 100;
                    $within1hWidth = ((int) data_get($item, 'within_1h', 0) / $maxAvailability) * 100;
                    $within6hWidth = ((int) data_get($item, 'within_6h', 0) / $maxAvailability) * 100;
                @endphp
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                        <span>{{ data_get($item, 'label') }}</span>
                        <span>run {{ (int) data_get($item, 'running', 0) }} | 1h {{ (int) data_get($item, 'within_1h', 0) }} | 6h {{ (int) data_get($item, 'within_6h', 0) }}</span>
                    </div>
                    <div class="space-y-1">
                        <div class="h-2 rounded bg-slate-100">
                            <div class="h-2 rounded bg-indigo-600" style="width: {{ number_format($runningWidth, 2, '.', '') }}%"></div>
                        </div>
                        <div class="h-2 rounded bg-slate-100">
                            <div class="h-2 rounded bg-cyan-600" style="width: {{ number_format($within1hWidth, 2, '.', '') }}%"></div>
                        </div>
                        <div class="h-2 rounded bg-slate-100">
                            <div class="h-2 rounded bg-teal-600" style="width: {{ number_format($within6hWidth, 2, '.', '') }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Sem dados.</p>
            @endforelse
        </div>
    </article>

    <article class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold">Sucesso x falha de sync (24h)</h2>
        <p class="mb-3 text-xs text-slate-600">
            Total sucesso: {{ (int) data_get($syncStatusTotals24h, 'success', 0) }} |
            Total falha: {{ (int) data_get($syncStatusTotals24h, 'failed', 0) }}
        </p>
        <div class="space-y-2">
            @php
                $maxSync = max(1, collect($syncStatusByHour ?? [])->map(function ($item) {
                    return max((int) data_get($item, 'success', 0), (int) data_get($item, 'failed', 0));
                })->max() ?? 1);
            @endphp
            @forelse (($syncStatusByHour ?? []) as $item)
                @php
                    $successWidth = ((int) data_get($item, 'success', 0) / $maxSync) * 100;
                    $failedWidth = ((int) data_get($item, 'failed', 0) / $maxSync) * 100;
                @endphp
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                        <span>{{ data_get($item, 'label') }}</span>
                        <span>ok {{ (int) data_get($item, 'success', 0) }} | erro {{ (int) data_get($item, 'failed', 0) }}</span>
                    </div>
                    <div class="space-y-1">
                        <div class="h-2 rounded bg-slate-100">
                            <div class="h-2 rounded bg-emerald-600" style="width: {{ number_format($successWidth, 2, '.', '') }}%"></div>
                        </div>
                        <div class="h-2 rounded bg-slate-100">
                            <div class="h-2 rounded bg-rose-600" style="width: {{ number_format($failedWidth, 2, '.', '') }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Sem dados.</p>
            @endforelse
        </div>
    </article>
</section>
