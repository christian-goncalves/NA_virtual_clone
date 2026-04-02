# Contrato Visual Oficial: Preview x PDF Export (DomPDF)

## Objetivo
Manter paridade visual entre a pré-visualização e o PDF exportado, com referência em zoom de 125%, evitando regressões de estilo em refatorações.

## Decisões Travadas
- Ícone do botão `LINK` via inline SVG.
- Aceite visual por bloco em padrão `pixel-near`.
- Geometria fixa e consistente entre páginas do PDF.
- Fonte única de verdade: este documento + constantes em `CuratedMeetingPdfLayoutService` + CSS de export consolidado.

## Baseline de Medidas (preview 125%)
- `div.brand-header-left`: `543.62 x 56.89`, `padding: 0 16px`, `color: #FFFFFF`, `font: 16px Instrument Sans`
- `div.brand-header-right`: `103.55 x 56.89`, `padding: 0 13.3333px 0 0`, `color: #FFFFFF`, `font: 16px Instrument Sans`
- `th.col-group`: `178.96 x 20.44`, `padding: 4px`, `background: #003FA3`, `color: #FFFFFF`, `font: 8px Instrument Sans`
- `th.col-link`: `90.31 x 20.44`, `padding: 4px`, `background: #003FA3`, `color: #FFFFFF`, `font: 8px Instrument Sans`
- `th.col-day`: `54.24 x 20.44`, `padding: 4px`, `background: #003FA3`, `color: #FFFFFF`, `font: 8px Instrument Sans`
- `td` de horários/atributos: altura com auto-ajuste por conteúdo.

## Regras de Implementação
- Não duplicar largura de coluna com valores conflitantes em CSS e `colgroup`.
- Não usar Font Awesome remoto no template de exportação.
- Manter estrutura única de slot horário + badge (`slot`, `slot-time`, `slot-marker`, `pdf-badge`) no export.
- Alterações em header/tabela/link/badge/legenda exigem validação visual manual no preview e no PDF final.

## Checklist de Aceite Visual
- Proporção geral da tabela equivalente entre preview e export.
- Hierarquia visual de colunas perceptível (`grupo > link > dias`).
- Botão `LINK` com alinhamento de ícone e texto consistente.
- Centralização vertical consistente em linhas com múltiplos horários e com `-`.
- Badge alinhado ao horário sem deslocamento perceptível.
- Legenda inferior com espaçamento equivalente.
- Consistência visual entre página 1 e página 2.
