<section class="w-full bg-[hsl(var(--na-blue))] pb-7 pt-16 text-white md:pb-9 md:pt-[4.5rem]">
    <div class="mx-auto max-w-6xl px-4 text-center sm:px-6 md:text-left lg:px-8">
        <p class="text-base font-semibold text-white/80">Problemas com drogas?</p>
        <h2 class="font-display mt-2 text-3xl font-bold leading-tight md:text-4xl lg:text-5xl">
            Se voce quiser, <span class="text-[hsl(var(--na-gold))]">NA</span> pode te ajudar
        </h2>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-2 md:justify-start">
            <span class="vm-badge bg-white/15 px-3 py-1.5 text-sm font-semibold text-white">Reunioes Virtuais</span>
            <span class="vm-badge vm-badge-accent px-3 py-1.5 text-sm font-bold">24 Horas</span>
            <span class="vm-badge bg-white/15 px-3 py-1.5 text-sm font-semibold text-white">Gratuito</span>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center md:justify-start">
            <a href="{{ route('virtual-meetings.index') }}" class="vm-btn vm-btn-secondary w-full px-5 py-3 text-sm font-bold sm:w-auto">
                <i class="fa-solid fa-video text-[0.78rem]" aria-hidden="true"></i>
                Reunioes Online
            </a>
            <a href="tel:30035222" class="vm-btn vm-btn-accent w-full px-5 py-3 text-sm font-bold sm:w-auto">
                <i class="fa-solid fa-phone text-[0.78rem]" aria-hidden="true"></i>
                Ligar Agora - 3003-5222
            </a>
            <a href="https://www.na.org.br/grupos" target="_blank" rel="noopener noreferrer" class="vm-btn w-full border border-white/30 bg-white/15 px-5 py-3 text-sm font-semibold text-white hover:bg-white/25 sm:w-auto">
                <i class="fa-solid fa-location-dot text-[0.78rem]" aria-hidden="true"></i>
                Sala Perto de Voce
            </a>
        </div>
    </div>
</section>
