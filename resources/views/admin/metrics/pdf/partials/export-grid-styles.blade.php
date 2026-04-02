        /* Export-only stylesheet (DomPDF-safe). Do not share with preview. */
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
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.2px;
            line-height: 1;
            margin: 0;
            white-space: nowrap;
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

        /* DomPDF width lock: keep class widths aligned with colgroup. */
        th.col-group,
        td.col-group {
            width: {{ (float) data_get($layout, 'col_group_pt', 134.22) }}pt;
            max-width: {{ (float) data_get($layout, 'col_group_pt', 134.22) }}pt;
            min-width: {{ (float) data_get($layout, 'col_group_pt', 134.22) }}pt;
        }

        th.col-link,
        td.col-link {
            width: {{ (float) data_get($layout, 'col_link_pt', 67.7325) }}pt;
            max-width: {{ (float) data_get($layout, 'col_link_pt', 67.7325) }}pt;
            min-width: {{ (float) data_get($layout, 'col_link_pt', 67.7325) }}pt;
            text-align: center;
        }

        th.col-day,
        td.col-day {
            width: {{ (float) data_get($layout, 'col_day_pt', 40.68) }}pt;
            max-width: {{ (float) data_get($layout, 'col_day_pt', 40.68) }}pt;
            min-width: {{ (float) data_get($layout, 'col_day_pt', 40.68) }}pt;
            text-align: center;
        }

        td.col-group {
            text-align: left;
            vertical-align: middle;
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

        .day-cell {
            text-align: center;
            vertical-align: middle;
        }

        .link-btn {
            background: #003FA3;
            border: 1px solid #003FA3;
            border-radius: 999px;
            box-shadow: none !important;
            color: #ffffff;
            display: inline-table;
            filter: none !important;
            font-size: 7px;
            font-weight: 700;
            line-height: 1;
            margin: 0 auto;
            min-height: 18px;
            padding: 3px 8px;
            text-decoration: none;
            white-space: nowrap;
        }

        .link-btn-icon,
        .link-btn-text {
            display: table-cell;
            vertical-align: middle;
        }

        .link-btn-icon {
            line-height: 1;
            padding-right: 3px;
            text-align: center;
            width: 10px;
        }

        .link-btn-icon-svg {
            display: inline-block;
            height: 10px;
            width: 10px;
        }

        .slot {
            display: table;
            margin: 0 auto 1px auto;
            min-height: 14px;
            white-space: nowrap;
        }

        .slot:last-child {
            margin-bottom: 0;
        }

        .slot-time,
        .slot-marker {
            display: table-cell;
            vertical-align: middle;
        }

        .slot-time {
            color: #0f172a;
            font-size: 9.5px;
            line-height: 1.1;
            padding: 0 2px;
        }

        .slot-marker {
            line-height: 1;
            padding-left: 4px;
        }

        .pdf-badge {
            background-clip: padding-box;
            border: 0;
            border-radius: 50%;
            display: inline-block;
            height: 5px;
            margin: 0;
            min-height: 5px;
            min-width: 5px;
            vertical-align: middle;
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

        .legend .item {
            display: inline-table;
            line-height: 1.2;
            margin-right: 10px;
            vertical-align: middle;
        }

        .legend .item .pdf-badge,
        .legend .item .legend-label {
            display: table-cell;
            vertical-align: middle;
        }

        .legend .item .pdf-badge {
            margin-right: 6px;
        }

        .no-slots {
            color: #94a3b8;
            font-size: 8px;
        }
