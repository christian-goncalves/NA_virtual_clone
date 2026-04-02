<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview PDF - Analise de Reunioes</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
    <style>
        .pdf-preview-page {
            background: transparent;
            display: flex;
            justify-content: center;
            min-height: 0;
            overflow: hidden;
            padding: 0;
            width: 100%;
        }

        .pdf-preview-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        /* Freeze: canvas com largura fixa do layout aprovado; não alterar valores sem validação visual. */
        .pdf-preview-canvas {
            background: #fff;
            box-shadow: none;
            box-sizing: border-box;
            margin: 0 auto;
            min-height: 0;
            padding: {{ (int) data_get($layout, 'margins.top_pt', 16) }}pt {{ (int) data_get($layout, 'margins.left_pt', 14) }}pt {{ (int) data_get($layout, 'margins.top_pt', 16) }}pt {{ (int) data_get($layout, 'margins.left_pt', 14) }}pt;
            width: fit-content;
        }

@include('admin.metrics.pdf.partials.weekly-grid-styles')

        /* Freeze preview: contrato visual aprovado (fonte, gaps, botão, horários e badges).
           Regras abaixo são somente do preview e não devem afetar export. */
        .pdf-preview-canvas .brand-header,
        .pdf-preview-canvas table,
        .pdf-preview-canvas .legend {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        .pdf-preview-canvas .legend .item {
            align-items: center;
            display: inline-flex;
            gap: 6px;
            margin-right: 10px;
            vertical-align: middle;
        }

        .pdf-preview-canvas .legend .item .pdf-badge {
            margin-right: 0;
        }

        .pdf-preview-canvas .link-btn {
            align-items: center;
            background: #003FA3;
            border: 1px solid #003FA3;
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            font-size: 7px;
            font-weight: 700;
            gap: 3px;
            justify-content: center;
            line-height: 1;
            min-height: 18px;
            padding: 3px 8px;
            white-space: nowrap;
        }

        .pdf-preview-canvas .link-btn-icon {
            display: inline-flex;
            line-height: 1;
            width: 10px;
        }

        .pdf-preview-canvas .link-btn-icon-svg {
            height: 10px;
            width: 10px;
        }

        .pdf-preview-canvas .slot {
            align-items: center;
            display: flex;
            gap: 4px;
            justify-content: center;
            margin-bottom: 1px;
            min-height: 14px;
            text-align: center;
            white-space: nowrap;
        }

        .pdf-preview-canvas .slot:last-child {
            margin-bottom: 0;
        }

        .pdf-preview-canvas .slot-time {
            display: inline-block;
            padding: 0 2px;
            vertical-align: middle;
        }

        .pdf-preview-canvas .slot-marker {
            display: inline-flex;
            line-height: 1;
            padding-left: 0;
            vertical-align: middle;
        }

        .pdf-preview-canvas .pdf-badge {
            margin-left: 0;
            vertical-align: middle;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-full space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <header class="space-y-2">
            <a href="{{ route('admin.metrics.meetings.index') }}" class="inline-flex rounded border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Voltar para analise</a>
        </header>

        <section class="pdf-preview-page">
            <div class="pdf-preview-scroll">
                <article class="pdf-preview-canvas">
                    <header class="brand-header" role="banner">
                        <div class="brand-header-left">
                            <h2 class="brand-title">Lista de reuniões dos grupos | <span class="brand-title-highlight">CSA Novo</span></h2>
                        </div>
                        <div class="brand-header-right">
                            @if (! empty($logoDataUri))
                                <img class="brand-logo" src="{{ $logoDataUri }}" alt="NA" />
                            @else
                                <span class="brand-title">NA</span>
                            @endif
                        </div>
                    </header>

@include('admin.metrics.pdf.partials.weekly-grid-table')

                    <div class="legend legend-bottom">
                        <span class="item"><span class="pdf-badge pdf-badge-type-closed" title="Fechada" aria-label="Fechada"></span> Fechada</span>
                        <span class="item"><span class="pdf-badge pdf-badge-type-study" title="Estudo" aria-label="Estudo"></span> Estudo</span>
                        <span class="item"><span class="pdf-badge pdf-badge-type-open" title="Aberta" aria-label="Aberta"></span> Aberta</span>
                    </div>
                </article>
            </div>
        </section>
    </main>
</body>
</html>









