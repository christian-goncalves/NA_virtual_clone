<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analise de Reunioes - Dashboard de Metricas</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="space-y-2">
            <a href="{{ route('admin.metrics.index') }}" class="inline-flex rounded border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Voltar ao dashboard</a>
            <div>
                <h1 class="text-2xl font-bold">Analise de Reunioes</h1>
                <p class="text-sm text-slate-600">Secao dedicada para filtros, paginacao e exportacao da lista de reunioes.</p>
            </div>
        </header>

        @if (session('meeting_json_sync_success'))
            <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('meeting_json_sync_success') }}
            </div>
        @endif

        @if (session('meeting_pdf_error'))
            <div class="rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('meeting_pdf_error') }}
            </div>
        @endif

        @if (isset($curatedExportSummary) && is_array($curatedExportSummary))
            <section class="rounded border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                <h2 class="text-sm font-semibold text-slate-900">Resumo da ultima exportacao de PDF</h2>
                <div class="mt-2 grid gap-2 md:grid-cols-4">
                    <div><span class="font-semibold">Lidas na planilha:</span> {{ (int) data_get($curatedExportSummary, 'total_sheet_rows', 0) }}</div>
                    <div><span class="font-semibold">Grupos validos (nome):</span> {{ (int) data_get($curatedExportSummary, 'total_sheet_valid_pairs', 0) }}</div>
                    <div><span class="font-semibold">Resolvidas na base:</span> {{ (int) data_get($curatedExportSummary, 'resolved_count', 0) }}</div>
                    <div><span class="font-semibold">Conflitos:</span> {{ (int) data_get($curatedExportSummary, 'conflicts_count', 0) }}</div>
                </div>
                @if ((int) data_get($curatedExportSummary, 'conflicts_count', 0) > 0)
                    <p class="mt-2 text-xs text-amber-700">
                        PDF gerado parcialmente. Conflitos por motivo:
                        id_not_found={{ (int) data_get($curatedExportSummary, 'conflicts_by_reason.id_not_found', 0) }},
                        name_mismatch={{ (int) data_get($curatedExportSummary, 'conflicts_by_reason.name_mismatch', 0) }},
                        ambiguous_match={{ (int) data_get($curatedExportSummary, 'conflicts_by_reason.ambiguous_match', 0) }}.
                    </p>
                @endif
            </section>
        @endif

        @include('admin.metrics.partials.meeting-analysis-table')
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
</body>
</html>

