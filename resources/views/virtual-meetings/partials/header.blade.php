<header class="py-6 sm:py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300">Narcoticos Anonimos</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">Reunioes Virtuais</h1>
        </div>
        <div class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-2 text-sm text-slate-300">
            Atualizado: {{ $serverTime->format('d/m/Y H:i') }}
        </div>
    </div>
</header>