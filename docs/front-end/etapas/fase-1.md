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
