```md
Execute a **Fase 7: Refino final por pixel** da página `/reunioes-virtuais`.

Objetivo:
- Fazer microajustes finais de espaçamento, pesos de fonte, bordas, ícones e alinhamentos para maximizar fidelidade visual com a referência.

Escopo:
1) Microajustes visuais:
- espaçamentos (padding/margin/gap) por bloco
- pesos/tamanhos de fonte e line-height
- bordas, raio e sombras
- alinhamento de ícones com texto
- alinhamento horizontal/vertical de badges, contadores e CTAs

2) Consistência de densidade:
- reduzir ruído visual onde necessário
- garantir ritmo uniforme entre header, hero, seções, cards e rows
- manter CTA “Entrar” com destaque proporcional sem competir com headline/hero

3) Fidelidade com referência:
- comparar estrutura, paleta, hierarquia e composição geral
- aproximar visual desktop/mobile sem quebrar identidade já implementada

Restrições:
- Não alterar backend, rotas, contratos, controller, API ou sync.
- Não alterar chaves/estrutura do view model.
- Não refatorar lógica de loops/condições.
- Alterações apenas em front-end (views + CSS).

Arquivos permitidos:
- `resources/css/app.css`
- `resources/views/virtual-meetings/index.blade.php`
- `resources/views/virtual-meetings/partials/header.blade.php`
- `resources/views/virtual-meetings/partials/hero.blade.php`
- `resources/views/virtual-meetings/partials/sections.blade.php`
- `resources/views/virtual-meetings/partials/section-running.blade.php`
- `resources/views/virtual-meetings/partials/section-starting-soon.blade.php`
- `resources/views/virtual-meetings/partials/section-upcoming.blade.php`
- `resources/views/virtual-meetings/partials/meeting-card.blade.php`
- `resources/views/virtual-meetings/partials/meeting-row.blade.php`
- `resources/views/virtual-meetings/partials/footer.blade.php`

Checklist de aceite (meta >= 90%):
1) Estrutura:
- composição da página e blocos principais alinhados com a referência

2) Cores:
- contraste e paleta institucional coerentes

3) Densidade:
- informação escaneável, sem excesso de peso visual

4) CTA:
- prioridade visual correta dos botões principais

5) Hierarquia:
- leitura clara de título > metadados > status > ação

Validação obrigatória:
- Executar `php artisan test --filter=VirtualMeetingControllerTest`
- Reportar resultado.
- Entregar checklist final com nota por critério (0-100) e média geral.
- Listar arquivos alterados com justificativa objetiva.
```
