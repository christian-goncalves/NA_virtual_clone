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
