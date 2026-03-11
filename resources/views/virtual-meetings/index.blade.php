<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NA Virtual - Reunioes</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(14,165,233,0.25),transparent_50%),radial-gradient(circle_at_75%_20%,rgba(249,115,22,0.16),transparent_35%)]"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            @include('virtual-meetings.partials.header')
            @include('virtual-meetings.partials.hero')
            @include('virtual-meetings.partials.sections')
            @include('virtual-meetings.partials.footer')
        </div>
    </div>
</body>
</html>