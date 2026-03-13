```md
Execute a **Fase 3: Header + Hero** da página `/reunioes-virtuais`, conforme o pipeline de front-end.

Objetivo:
- Refatorar o topo para barra clara e hero azul institucional com CTAs e selos visuais.
- Alcançar assinatura visual próxima da referência já na primeira dobra.

Arquivos-alvo:
- `resources/views/virtual-meetings/partials/header.blade.php`
- `resources/views/virtual-meetings/partials/hero.blade.php`
- `resources/views/virtual-meetings/partials/footer.blade.php`
- (somente se necessário) ajustes complementares em `resources/css/app.css`

Diretrizes de implementação:
1) Header:
- barra clara com borda inferior suave
- logo/título à esquerda
- horário BRT e links de navegação à direita (desktop) e versão compacta no mobile
- tipografia/cores alinhadas aos tokens já definidos na Fase 1

2) Hero:
- bloco azul institucional em destaque
- subtítulo “problemas com drogas?” + headline forte com destaque em “NA”
- selos/chips visuais (ex.: Reuniões Virtuais, 24 Horas, Gratuito)
- 3 CTAs visíveis:
  - Reuniões Online
  - Ligar Agora — 3003-5222
  - Sala Perto de Você

3) Footer:
- manter simples e compatível com o novo topo/hero
- sem alterar conteúdo funcional

Restrições:
- Não alterar backend, rotas, contratos, controller, API ou sync.
- Não alterar chaves/estrutura do view model.
- Não mexer nos cards/rows/seções nesta fase.
- Preservar responsividade e acessibilidade mínima (foco, contraste, labels).

Critérios de aceite:
- Primeira dobra reconhecível como a referência (barra clara + hero azul + CTAs/chips).
- Contraste e hierarquia visual corretos em desktop e mobile.
- Nenhuma regressão funcional na rota `/reunioes-virtuais`.
- Informar arquivos alterados e justificativa ao final.

Validação obrigatória:
- Executar: `php artisan test --filter=VirtualMeetingControllerTest`
- Reportar resultado no fim.
```
