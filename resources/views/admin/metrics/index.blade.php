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

        @include('admin.metrics.partials.cards')
        @include('admin.metrics.partials.traffic-charts')
        @include('admin.metrics.partials.operations-charts')
        @include('admin.metrics.partials.latency-charts')
        @include('admin.metrics.partials.sync-table')
    </main>
</body>
</html>
