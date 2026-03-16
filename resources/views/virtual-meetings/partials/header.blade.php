<header class="fixed inset-x-0 top-0 z-50 w-full border-b border-[hsl(var(--border))] bg-white/95 shadow-sm backdrop-blur">
    <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2">
            <div class="vm-logo-wrap flex h-9 w-9 items-center justify-center overflow-hidden rounded-full">
                <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo NA" class="vm-logo-image h-full w-full object-cover" />
            </div>
            <p class="hidden text-sm font-bold leading-tight text-[hsl(var(--na-blue))] sm:block">
                Narcóticos<br>
                <span class="text-[hsl(var(--na-blue))]">Anônimos</span>
            </p>
        </div>

        <div class="hidden items-center gap-2 lg:flex">
            <span class="text-xs uppercase tracking-wide text-[hsl(var(--muted-foreground))]">BRT</span>
            <span class="font-mono text-sm font-bold tracking-wider text-[hsl(var(--na-blue))]" data-vm-live-clock data-server-time="{{ $serverTime->toIso8601String() }}">{{ $serverTime->format('H:i:s') }}</span>
        </div>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Menu principal">
            <a href="tel:30035222" class="vm-nav-link">
                <i class="fa-solid fa-phone text-[0.72rem]" aria-hidden="true"></i>
                3003-5222
            </a>
            <a href="{{ route('virtual-meetings.index') }}" class="vm-nav-link">
                <i class="fa-solid fa-video text-[0.72rem]" aria-hidden="true"></i>
                Reuniões Online
            </a>
            <a href="https://www.na.org.br/grupos" target="_blank" rel="noopener noreferrer" class="vm-nav-link">
                <i class="fa-solid fa-location-dot text-[0.72rem]" aria-hidden="true"></i>
                Reuniões Presenciais
            </a>
            <a href="https://www.na.org.br" target="_blank" rel="noopener noreferrer" class="vm-nav-link">
                <i class="fa-solid fa-arrow-up-right-from-square text-[0.72rem]" aria-hidden="true"></i>
                Site de N.A.
            </a>
        </nav>

        <div class="flex items-center gap-3 lg:hidden">
            <div class="flex items-center gap-1.5">
                <span class="text-xs uppercase tracking-wide text-[hsl(var(--muted-foreground))]">BRT</span>
                <span class="font-mono text-sm font-bold tracking-wider text-[hsl(var(--na-blue))]" data-vm-live-clock data-server-time="{{ $serverTime->toIso8601String() }}">{{ $serverTime->format('H:i:s') }}</span>
            </div>
            <button type="button" aria-label="Menu" aria-expanded="false" data-vm-menu-toggle class="rounded-md p-2 text-[hsl(var(--muted-foreground))] transition-colors hover:bg-[hsl(var(--muted))] hover:text-[hsl(var(--na-blue))]">
                <i class="fa-solid fa-bars h-5 w-5" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <nav class="vm-mobile-menu hidden border-t border-[hsl(var(--border))] bg-white px-4 py-3 lg:hidden" data-vm-menu aria-label="Menu mobile">
        <div class="mx-auto flex max-w-6xl flex-col gap-1 sm:px-2">
            <a href="tel:30035222" class="vm-nav-link">
                <i class="fa-solid fa-phone text-[0.72rem]" aria-hidden="true"></i>
                3003-5222
            </a>
            <a href="{{ route('virtual-meetings.index') }}" class="vm-nav-link">
                <i class="fa-solid fa-video text-[0.72rem]" aria-hidden="true"></i>
                Reuniões Online
            </a>
            <a href="https://www.na.org.br/grupos" target="_blank" rel="noopener noreferrer" class="vm-nav-link">
                <i class="fa-solid fa-location-dot text-[0.72rem]" aria-hidden="true"></i>
                Reuniões Presenciais
            </a>
            <a href="https://www.na.org.br" target="_blank" rel="noopener noreferrer" class="vm-nav-link">
                <i class="fa-solid fa-arrow-up-right-from-square text-[0.72rem]" aria-hidden="true"></i>
                Site de N.A.
            </a>
        </div>
    </nav>
</header>

