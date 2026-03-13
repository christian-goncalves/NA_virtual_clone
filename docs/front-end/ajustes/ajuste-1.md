```md
Execute a etapa de ajustes finais documentada em `docs/front-end/ajustes/ajustes.md`, usando como referência visual as imagens de `docs/front-end/images` (`80.png`, `110-largura dos cards. Quero 3 cards como na pagina base.png`, `150.png`).

Objetivo:
- Aproximar o front-end da página `/reunioes-virtuais` ao app base com foco em:
  1) horário em tempo real,
  2) menu mobile com dropdown,
  3) largura/alinhamento do hero,
  4) ícones faltantes,
  5) grid de cards em andamento com 3 colunas no breakpoint correto,
  6) padronização de badges/cores por tipo e regra visual de “faltam 30 min”.

Escopo de implementação:
1) Header:
- manter fixo no topo
- transformar horário BRT em relógio vivo no cliente (atualização por segundo)
- implementar menu mobile dropdown funcional com os mesmos links do desktop
- manter largura/alinhamento coerentes com referência
- preparar espaço para logo oficial (usar placeholder atual até receber arquivo da logo)

2) Hero:
- ajustar largura, padding, altura e alinhamentos para maior semelhança com a referência
- preservar hierarquia visual de título, chips e CTAs

3) Ícones:
- mapear ícones visíveis da referência (telefone, reunião online, localização, menu, etc.)
- usar Font Awesome nos pontos equivalentes da UI
- manter alinhamento ícone+texto consistente

4) Seção “Reuniões em andamento”:
- ajustar grid para exibir 3 cards como na referência no breakpoint adequado (desktop/larguras intermediárias)
- reduzir diferenças de densidade entre nossa versão e a base

5) Badges e estados:
- tipos:
  - Aberta -> verde
  - Fechada -> cinza
  - Estudo -> roxo
  - Temática -> laranja
- badges internas dos cards com mesma semântica de cor
- regra de status:
  - se “faltam <= 30 min para o fim” -> laranja
  - caso contrário -> cinza/neutral

Restrições:
- Não alterar backend, rotas, contratos, controllers, API ou sync.
- Não alterar estrutura/chaves do view model.
- Não alterar lógica de negócio fora da camada visual e interação de front.
- Alterar somente front-end (views/CSS/JS de interface).

Arquivos-alvo:
- `resources/views/virtual-meetings/partials/header.blade.php`
- `resources/views/virtual-meetings/partials/hero.blade.php`
- `resources/views/virtual-meetings/partials/section-running.blade.php`
- `resources/views/virtual-meetings/partials/meeting-card.blade.php`
- `resources/views/virtual-meetings/partials/meeting-row.blade.php` (se necessário para consistência visual)
- `resources/views/virtual-meetings/index.blade.php` (se necessário)
- `resources/css/app.css`
- `resources/js/app.js` (para relógio/menu mobile, se necessário)

Critérios de aceite:
- horário atualiza em tempo real no header
- dropdown mobile funcional e acessível
- hero mais próximo da referência em largura/proporção
- ícones aplicados e alinhados
- seção running com 3 cards no breakpoint alvo
- badges/tipos e regra de “<=30 min” visualmente corretos
- sem regressão funcional na rota `/reunioes-virtuais`

Validação obrigatória:
- executar `php artisan test --filter=VirtualMeetingControllerTest`
- informar resultado
- listar arquivos alterados com justificativa objetiva.
```
