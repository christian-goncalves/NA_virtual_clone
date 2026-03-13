Inicie a Fase 1 (fundação visual) em `/reunioes-virtuais` usando os insumos de:
- docs/front-end/elements/css.app.md
- docs/front-end/elements/card-andamento.md
- resources/css/colors.jpeg
- HTML de header e hero fornecidos na conversa

Objetivo:
- Criar design tokens e classes base reutilizáveis no Tailwind v4 via `resources/css/app.css`.
- Não alterar backend, rotas, contratos de dados, controllers, API ou sync.

Regras:
- Não usar `tailwind.config.js` (projeto sem esse arquivo).
- Preservar comportamento funcional atual das views Blade.
- Implementar apenas fundação visual (tokens + classes base), sem refactor completo de layout nesta fase.

Tokens base:
- usar azul primário próximo ao site referência (`--na-blue` ~ #0046A3)
- dourado (`--na-gold` ~ #FFC400)
- neutros para fundo/cartão/borda/texto
- radius padrão ~10px

Entregáveis:
1) Mapa final de tokens em `resources/css/app.css`.
2) Classes base: botões, badges, card shell, section shell, tipografia display.
3) Lista de arquivos alterados com justificativa.
4) Checklist de aceite da Fase 1.




Fase 1 executada seguindo `docs/front-end/etapas/fase-1.md`, sem tocar backend.

**Entregável 1: mapa de tokens**
Atualizado em [app.css](/c:/laragon/www/NA_virtual_clone/resources/css/app.css:8):
- Tipografia: `--font-sans`, `--font-display`
- Raios: `--radius-md/lg/xl`
- Neutros: `--background`, `--foreground`, `--card`, `--card-foreground`, `--muted`, `--muted-foreground`, `--border`
- Marca: `--na-blue` (217 100% 32%), `--na-light-blue`, `--na-gold` (48 100% 50%), `--na-indigo`
- Suporte visual: `--ring`, `--shadow-card`

**Entregável 2: classes base**
Criadas em [app.css](/c:/laragon/www/NA_virtual_clone/resources/css/app.css:34):
- Tipografia: `.font-display`
- Shells: `.vm-section-shell`, `.vm-card-shell`
- Botões: `.vm-btn`, `.vm-btn-primary`, `.vm-btn-secondary`, `.vm-btn-accent`
- Badges: `.vm-badge`, `.vm-badge-status`, `.vm-badge-muted`, `.vm-badge-accent`

**Entregável 3: arquivos alterados**
1. [app.css](/c:/laragon/www/NA_virtual_clone/resources/css/app.css)  
Justificativa: centralizar fundação visual (tokens + componentes base) em Tailwind v4 via CSS, sem alterar contratos/Blade/backend.

**Entregável 4: checklist de aceite da Fase 1**
1. Tokens visuais definidos: OK  
2. Classes base de botão/badge/card/section: OK  
3. Sem alteração de backend/rotas/controllers/API/sync: OK  
4. Sem mudança de chaves do view model: OK  
5. Sem refactor completo de layout nesta fase: OK

Próximo passo natural: aplicar essas classes em `header/hero` (Fase 2 visual incremental) mantendo a estrutura Blade atual.
