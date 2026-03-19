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
