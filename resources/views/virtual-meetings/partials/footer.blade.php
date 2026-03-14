<div class="space-y-8 pt-2">
    <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
        <section class="rounded-2xl bg-gradient-to-br from-[hsl(var(--na-blue))] to-[hsl(var(--na-light-blue))] p-6 text-white md:p-10">
            <div class="flex flex-col items-center gap-6 md:flex-row">
                <div class="flex-shrink-0">
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm md:h-24 md:w-24">
                        <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo NA" class="h-16 w-16 rounded-xl object-cover" />
                    </div>
                </div>

                <div class="flex-1 text-center md:text-left">
                    <h2 class="font-display mb-2 text-2xl font-bold md:text-3xl">Site de Narcoticos Anonimos</h2>
                    <p class="mb-4 text-sm leading-relaxed text-white/80">
                        Todas as informacoes apresentadas nesta pagina foram retiradas do site oficial de Narcoticos Anonimos.
                    </p>
                    <a
                        href="https://www.na.org.br"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-[hsl(var(--na-blue))] transition-colors hover:bg-white/90"
                    >
                        <i class="fa-solid fa-globe w-4 text-center" aria-hidden="true"></i>
                        Acessar na.org.br
                        <i class="fa-solid fa-arrow-up-right-from-square text-[0.8rem]" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    </div>

    <footer class="w-full bg-[hsl(var(--foreground))] px-4 py-10 text-[hsl(var(--background))] sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-6xl">
            <div class="mb-8 flex flex-col items-center gap-4 sm:flex-row">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo NA" class="h-12 w-12 rounded-full object-cover" />
                    <div>
                        <div class="text-base font-bold">Narcoticos Anonimos</div>
                        <div class="text-xs text-[hsl(var(--background))/0.6]">Recuperacao, Servico e Unidade</div>
                    </div>
                </div>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <a href="#reunioes" class="flex items-center justify-center gap-2 rounded-xl bg-[hsl(var(--na-blue))] px-4 py-4 text-sm font-bold text-white transition-colors hover:bg-[hsl(var(--na-blue))/0.82]">
                    <i class="fa-solid fa-video" aria-hidden="true"></i>
                    Reunioes Online
                </a>

                <a href="https://www.na.org.br/grupos" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 rounded-xl border border-[hsl(var(--background))/0.2] bg-[hsl(var(--background))/0.1] px-4 py-4 text-sm font-bold text-[hsl(var(--background))] transition-colors hover:bg-[hsl(var(--background))/0.2]">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    Reunioes Presenciais
                </a>

                <a href="tel:30035222" class="flex items-center justify-center gap-2 rounded-xl bg-[hsl(var(--na-gold))] px-4 py-4 text-sm font-bold text-[hsl(var(--na-blue))] transition-colors hover:bg-[hsl(var(--na-gold))/0.9]">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    Linha de Ajuda - 3003-5222
                </a>
            </div>

            <div class="flex flex-col items-center justify-between gap-3 border-t border-[hsl(var(--background))/0.1] pt-6 text-xs text-[hsl(var(--background))/0.4] sm:flex-row">
                <span>Desenvolvida por CG Stack.</span>
                <a href="https://www.na.org.br" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1 transition-colors hover:text-[hsl(var(--background))/0.6]">
                    na.org.br
                    <i class="fa-solid fa-arrow-up-right-from-square text-[0.72rem]" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </footer>
</div>
