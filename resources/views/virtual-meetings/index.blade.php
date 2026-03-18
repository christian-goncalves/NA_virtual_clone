<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0046A3">
    <title>NA Virtual - Reunioes Virtuais</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen overflow-x-hidden bg-[hsl(var(--background))] text-[hsl(var(--foreground))] antialiased">
    <div class="relative overflow-hidden bg-gradient-to-b from-[hsl(var(--background))] via-[hsl(var(--background))] to-[hsl(var(--muted))]/55">
        @include('virtual-meetings.partials.header')
        @include('virtual-meetings.partials.hero')

        <div class="relative mx-auto max-w-6xl px-4 pb-14 pt-8 sm:px-6 lg:px-8">
            @include('virtual-meetings.partials.sections')
        </div>

        @include('virtual-meetings.partials.footer')
    </div>
</body>
</html>

