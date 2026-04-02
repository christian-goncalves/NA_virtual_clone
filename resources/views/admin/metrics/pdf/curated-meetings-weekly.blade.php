<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Grupos - Reunioes Virtuais</title>
    <style>
        @page {
            size: {{ (float) ($exportPageWidthPt ?? data_get($layout, 'page_width_pt', 842)) }}pt {{ (float) ($exportPageHeightPt ?? data_get($layout, 'page_height_pt', 595)) }}pt;
            margin: {{ (int) data_get($layout, 'margins.top_pt', 16) }}pt {{ (int) data_get($layout, 'margins.right_pt', 14) }}pt {{ (int) data_get($layout, 'margins.bottom_pt', 16) }}pt {{ (int) data_get($layout, 'margins.left_pt', 14) }}pt;
        }

        body {
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        .export-version-marker {
            color: #94a3b8;
            font-family: DejaVu Sans, sans-serif;
            font-size: 6px;
            margin-top: 2pt;
            text-align: right;
        }

@include('admin.metrics.pdf.partials.export-grid-styles')
    </style>
</head>
@php($exportStyleVersion = 'EXPORT_STYLE_VERSION_V4_20260401_1')
<body data-export-version="{{ $exportStyleVersion }}">
    <header class="brand-header" role="banner">
        <div class="brand-header-left">
            <h1 class="brand-title">Lista de reuniões dos grupos | <span class="brand-title-highlight">CSA Novo</span></h1>
        </div>
        <div class="brand-header-right">
            @if (! empty($logoDataUri))
                <img class="brand-logo" src="{{ $logoDataUri }}" alt="NA" />
            @else
                <span class="brand-title">NA</span>
            @endif
        </div>
    </header>

@include('admin.metrics.pdf.partials.export-grid-table')

    <div class="legend legend-bottom">
        <span class="item"><span class="pdf-badge pdf-badge-type-closed" title="Fechada" aria-label="Fechada"></span><span class="legend-label"> Fechada</span></span>
        <span class="item"><span class="pdf-badge pdf-badge-type-study" title="Estudo" aria-label="Estudo"></span><span class="legend-label"> Estudo</span></span>
        <span class="item"><span class="pdf-badge pdf-badge-type-open" title="Aberta" aria-label="Aberta"></span><span class="legend-label"> Aberta</span></span>
    </div>

    <div class="export-version-marker">{{ $exportStyleVersion }}</div>
</body>
</html>
