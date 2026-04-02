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
        <p class="text-xs text-slate-500">Salas abertas</p>
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


