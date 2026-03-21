<section class="rounded-xl border bg-white p-4 shadow-sm space-y-4" data-meeting-analysis-section>
    <header class="flex flex-col gap-1">
        <h2 class="text-lg font-semibold">Lista de reunioes</h2>
        <p class="text-xs text-slate-600">Tabela simples com busca global, ordenacao e filtro rapido por bloco.</p>
    </header>

    <div class="rounded border border-slate-200 bg-slate-50 p-3 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Filtro rapido por cliques (24h)</p>
        <div id="meeting-analysis-click-block-buttons" class="flex flex-wrap gap-2">
                        <button type="button" data-click-block="accessed" class="ma-click-block-btn rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Acessadas</button>
            <button type="button" data-click-block="running" class="ma-click-block-btn rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Em andamento</button>
            <button type="button" data-click-block="starting_soon" class="ma-click-block-btn rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Em breve</button>
            <button type="button" data-click-block="upcoming" class="ma-click-block-btn rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Proximas</button>
        </div>

        <div class="grid gap-2 md:grid-cols-[1fr_1fr_auto_auto] items-end">
            <label class="flex flex-col gap-1 text-xs text-slate-600">
                Inicio (dia e hora)
                <input id="meeting-analysis-click-from-hour" type="datetime-local" step="3600" class="rounded border border-slate-300 px-2 py-2 text-sm text-slate-700" />
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-600">
                Fim (dia e hora)
                <input id="meeting-analysis-click-to-hour" type="datetime-local" step="3600" class="rounded border border-slate-300 px-2 py-2 text-sm text-slate-700" />
            </label>
            <button id="meeting-analysis-apply-click-range" type="button" class="rounded border border-blue-600 bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Aplicar periodo</button>
            <button id="meeting-analysis-clear-click-range" type="button" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Limpar periodo</button>
        </div>
    </div>

    <div id="meeting-analysis-errors" class="hidden rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"></div>

    <div class="overflow-x-auto">
        <table id="meeting-analysis-datatable" class="display stripe hover w-full text-sm" data-api-url="{{ route('admin.metrics.api.meetings.index') }}">
            <thead>
            <tr>
                <th>nome</th>
                <th>meeting_id</th>
                <th>hora_clique</th>
                <th>inicio</th>
                <th>formato</th>
                <th>clique</th>
            </tr>
            </thead>
        </table>
    </div>
</section>

<script>
(() => {
    const boot = () => {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
            return false;
        }

        const $ = window.jQuery;
        const tableNode = document.getElementById('meeting-analysis-datatable');
        const errorBox = document.getElementById('meeting-analysis-errors');
        const quickFilterButtons = Array.from(document.querySelectorAll('.ma-click-block-btn'));
        const clickFromHourInput = document.getElementById('meeting-analysis-click-from-hour');
        const clickToHourInput = document.getElementById('meeting-analysis-click-to-hour');
        const applyRangeButton = document.getElementById('meeting-analysis-apply-click-range');
        const clearRangeButton = document.getElementById('meeting-analysis-clear-click-range');

        if (!tableNode || !errorBox || quickFilterButtons.length === 0 || !clickFromHourInput || !clickToHourInput || !applyRangeButton || !clearRangeButton) {
            return true;
        }

        if ($.fn.dataTable.isDataTable(tableNode)) {
            return true;
        }

        let clickBlock = 'accessed';

        const sendUsageEvent = (action) => {
            const payload = {
                event_type: 'meeting_analysis_usage',
                category: action,
                route: '/admin/metricas',
                source_section: 'admin_meeting_analysis',
            };

            fetch('/api/metrics/event', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
                keepalive: true,
            }).catch(() => {
                // Telemetria nao deve quebrar a UX.
            });
        };

        const setError = (text) => {
            if (!text) {
                errorBox.classList.add('hidden');
                errorBox.textContent = '';
                return;
            }

            errorBox.textContent = text;
            errorBox.classList.remove('hidden');
        };

        const normalizeHourValue = (value) => {
            if (typeof value !== 'string' || value.trim() === '') {
                return null;
            }

            // datetime-local: YYYY-MM-DDTHH:mm -> API: YYYY-MM-DD HH:mm
            const normalized = value.replace('T', ' ').slice(0, 16);

            return /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(normalized) ? normalized : null;
        };

        const getCustomRange = () => {
            const fromHour = normalizeHourValue(clickFromHourInput.value);
            const toHour = normalizeHourValue(clickToHourInput.value);

            if (!fromHour || !toHour) {
                return null;
            }

            return { fromHour, toHour };
        };

        const setActiveQuickFilter = (value) => {
            clickBlock = value;

            quickFilterButtons.forEach((button) => {
                const isActive = button.getAttribute('data-click-block') === value;

                button.classList.toggle('bg-blue-600', isActive);
                button.classList.toggle('border-blue-600', isActive);
                button.classList.toggle('text-white', isActive);

                button.classList.toggle('bg-white', !isActive);
                button.classList.toggle('border-slate-300', !isActive);
                button.classList.toggle('text-slate-700', !isActive);
            });
        };

        const sortByFromColumnIndex = (columnIndex) => {
            const map = {
                0: 'name',
                1: 'meeting_id',
                2: 'start_time',
                3: 'start_time',
                4: 'is_open',
                5: 'click_bucket',
            };

            return map[columnIndex] || 'name';
        };

        const table = $(tableNode).DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            searchDelay: 300,
            pageLength: 20,
            lengthMenu: [10, 20, 50, 100],
            pagingType: 'simple_numbers',
            ajax: {
                url: tableNode.getAttribute('data-api-url'),
                type: 'GET',
                data: (d) => {
                    const order = Array.isArray(d.order) && d.order.length > 0 ? d.order[0] : { column: 0, dir: 'asc' };
                    const columnIndex = Number.isFinite(Number(order.column)) ? Number(order.column) : 0;
                    const direction = order.dir === 'desc' ? 'desc' : 'asc';

                    d.sort_by = sortByFromColumnIndex(columnIndex);
                    d.sort_dir = direction;

                    const customRange = getCustomRange();
                    if (customRange) {
                        d.click_window = 'custom';
                        d.click_from_hour = customRange.fromHour;
                        d.click_to_hour = customRange.toHour;
                    } else {
                        d.click_window = '24h';
                        delete d.click_from_hour;
                        delete d.click_to_hour;
                    }

                    d.click_block = clickBlock;
                },
                dataSrc: (json) => {
                    if (json?.ok === false) {
                        const errors = json?.errors ?? {};
                        const lines = Object.entries(errors).map(([field, messages]) => {
                            const text = Array.isArray(messages) ? messages.join(' ') : String(messages);
                            return `${field}: ${text}`;
                        });
                        setError(lines.join(' | ') || 'Falha ao carregar dados.');
                        return [];
                    }

                    setError('');

                    return Array.isArray(json?.data) ? json.data : [];
                },
                error: () => {
                    setError('Falha ao carregar dados da tabela.');
                },
            },
            columns: [
                { data: 'name_clean', defaultContent: '-', width: '40%' },
                { data: 'meeting_id', defaultContent: '-', width: '15%' },
                { data: 'hora_clique', defaultContent: '-', width: '15%' },
                { data: 'start_hour', defaultContent: '-', width: '15%' },
                { data: 'meeting_format', defaultContent: '-', width: '15%' },
                { data: 'click_bucket', defaultContent: '-', width: '15%' },
            ],
            order: [[0, 'asc']],
            language: {
                processing: 'Carregando...',
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                infoEmpty: 'Sem registros',
                zeroRecords: 'Nenhum resultado encontrado',
                paginate: {
                    first: 'Primeira',
                    last: 'Ultima',
                    next: 'Proxima',
                    previous: 'Anterior',
                },
            },
        });

        quickFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const selected = button.getAttribute('data-click-block') || 'accessed';
                setActiveQuickFilter(selected);
                sendUsageEvent(`click_block_filter:${selected}`);
                table.ajax.reload(null, true);
            });
        });

        applyRangeButton.addEventListener('click', () => {
            const hasFrom = clickFromHourInput.value.trim() !== '';
            const hasTo = clickToHourInput.value.trim() !== '';

            if ((hasFrom || hasTo) && !getCustomRange()) {
                setError('Preencha Inicio e Fim com data e hora validas para aplicar o periodo.');
                return;
            }

                        sendUsageEvent('click_period_apply');
            table.ajax.reload(null, true);
        });

        clearRangeButton.addEventListener('click', () => {
            clickFromHourInput.value = '';
            clickToHourInput.value = '';
            sendUsageEvent('click_period_clear');
            table.ajax.reload(null, true);
        });

        setActiveQuickFilter('accessed');

        return true;
    };

    const startWithRetry = () => {
        if (boot()) {
            return;
        }

        let attempts = 0;
        const timer = window.setInterval(() => {
            attempts += 1;
            if (boot() || attempts >= 20) {
                window.clearInterval(timer);
            }
        }, 100);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startWithRetry, { once: true });
        return;
    }

    startWithRetry();
})();
</script>
