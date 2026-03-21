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

        @include('admin.metrics.partials.meeting-analysis-table')
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
</body>
</html>
