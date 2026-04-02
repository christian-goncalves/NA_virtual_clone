        /* Base compartilhada preview/export para grid semanal. */
        .brand-header,
        table,
        .legend {
            font-family: DejaVu Sans, sans-serif;
        }

        .brand-header {
            background: #003FA3;
            border: 1px solid #cbd5e1;
            color: #ffffff;
            display: table;
            height: {{ (float) data_get($layout, 'header_height_pt', 42.67) }}pt;
            margin-bottom: {{ (float) data_get($layout, 'header_bottom_gap_pt', 0) }}pt;
            table-layout: fixed;
            width: {{ (float) data_get($layout, 'content_width_pt', data_get($layout, 'table_width_pt', 0)) }}pt;
        }

        .brand-header-left,
        .brand-header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-header-left {
            padding: 0 {{ (float) data_get($layout, 'header_left_padding_x_pt', 12) }}pt;
            text-align: left;
            width: 84%;
        }

        .brand-header-right {
            padding: 0 {{ (float) data_get($layout, 'header_right_padding_right_pt', 10) }}pt 0 0;
            text-align: right;
            width: 16%;
        }

        .brand-title {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
            margin: 0;
        }

        .brand-title-highlight {
            color: #FFCC00;
        }

        .brand-logo {
            display: inline-block;
            height: {{ (float) data_get($layout, 'logo_height_pt', 34) }}pt;
            width: auto;
        }

        .legend-bottom {
            margin-top: {{ (float) data_get($layout, 'legend_top_gap_pt', 0) }}pt;
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: {{ (float) data_get($layout, 'table_width_pt', 0) }}pt;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: {{ (float) data_get($layout, 'cell_padding_pt', 3) }}pt;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background: #003FA3;
            border-top: 0;
            color: #ffffff;
            font-size: 8px;
            font-weight: 700;
            text-align: center;
            height: {{ (float) data_get($layout, 'table_header_height_pt', 15.33) }}pt;
        }

        .col-group,
        .col-link,
        .col-day {
            text-align: center;
        }

        td.col-group {
            text-align: left;
        }

        .group {
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.15;
            margin-bottom: 1px;
            max-height: 2.3em;
            overflow: hidden;
            word-break: break-word;
        }

        .meta {
            color: #475569;
            font-size: 8px;
            line-height: 1.1;
        }

        .vm-btn {
            text-decoration: none;
        }

        .vm-card-cta-main {
            box-shadow: none !important;
            filter: none !important;
            font-weight: 700;
        }

        .vm-btn-primary {
            background: #003FA3;
            border: 1px solid #003FA3;
            color: #ffffff;
        }

        .day-cell {
            text-align: center;
            vertical-align: middle;
        }

        .slot-time {
            color: #0f172a;
            font-size: 9.5px;
            line-height: 1.1;
        }

        .pdf-badge {
            background-clip: padding-box;
            border: 0;
            border-radius: 50%;
            display: inline-block;
            height: 5px;
            min-height: 5px;
            min-width: 5px;
            width: 5px;
        }

        .pdf-badge-type-open {
            background: #44CF42;
        }

        .pdf-badge-type-closed {
            background: #003FA3;
        }

        .pdf-badge-type-study {
            background: #FA8EA6;
        }

        .no-slots {
            color: #94a3b8;
            font-size: 8px;
        }
