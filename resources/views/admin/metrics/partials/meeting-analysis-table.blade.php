<section class="rounded-xl border bg-white p-4 shadow-sm space-y-4" data-meeting-analysis-section>
    <header class="flex flex-col gap-1">
        <h2 class="text-lg font-semibold">Lista de reunioes</h2>
        <p class="text-xs text-slate-600">Catalogo dinamico com DataTables e filtro principal de cliques.</p>
    </header>

    <form id="meeting-analysis-filters" class="grid gap-3 md:grid-cols-4">
        <input type="text" name="search_name" placeholder="Nome do grupo" class="rounded border px-3 py-2 text-sm" />

        <select name="weekday" class="rounded border px-3 py-2 text-sm">
            <option value="">Dia da semana</option>
            @foreach (['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'] as $weekday)
                <option value="{{ $weekday }}">{{ ucfirst($weekday) }}</option>
            @endforeach
        </select>

        <input type="time" name="time_start" class="rounded border px-3 py-2 text-sm" />
        <input type="time" name="time_end" class="rounded border px-3 py-2 text-sm" />

        <input type="text" name="meeting_platform" placeholder="Plataforma" class="rounded border px-3 py-2 text-sm" />

        @foreach (['is_open' => 'Aberta', 'is_study' => 'Estudo', 'is_lgbt' => 'LGBT', 'is_women' => 'Mulheres', 'is_hybrid' => 'Hibrida', 'is_active' => 'Ativa'] as $field => $label)
            <select name="{{ $field }}" class="rounded border px-3 py-2 text-sm">
                <option value="">{{ $label }} (todos)</option>
                <option value="1">Sim</option>
                <option value="0">Nao</option>
            </select>
        @endforeach

        <select name="click_block" class="rounded border px-3 py-2 text-sm">
            <option value="">Cliques (todos)</option>
            <option value="all">Qualquer bloco</option>
            <option value="running">Running</option>
            <option value="starting_soon">Starting Soon</option>
            <option value="upcoming">Upcoming</option>
        </select>

        <select name="click_window" class="rounded border px-3 py-2 text-sm">
            <option value="24h">Cliques em 24h</option>
            <option value="7d">Cliques em 7 dias</option>
            <option value="30d">Cliques em 30 dias</option>
        </select>

        <div class="md:col-span-4 flex gap-2">
            <button type="button" id="meeting-analysis-apply" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Aplicar</button>
            <button type="button" id="meeting-analysis-clear" class="rounded border px-4 py-2 text-sm font-semibold text-slate-700">Limpar</button>
        </div>
    </form>

    <div id="meeting-analysis-errors" class="hidden rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"></div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
        <div class="rounded border p-3">Total filtrado: <strong id="ma-total-filtered">0</strong></div>
        <div class="rounded border p-3">Ativas: <strong id="ma-active-count">0</strong></div>
        <div class="rounded border p-3">Inativas: <strong id="ma-inactive-count">0</strong></div>
        <div class="rounded border p-3">Registros DataTable: <strong id="ma-records-filtered">0</strong></div>
    </div>

    <div class="overflow-x-auto">
        <table id="meeting-analysis-datatable" class="display stripe hover w-full text-xs sm:text-sm" data-api-url="{{ route('admin.metrics.api.meetings.index') }}">
            <thead>
            <tr>
                <th>name</th>
                <th>meeting_platform</th>
                <th>meeting_id</th>
                <th>weekday</th>
                <th>start_time</th>
                <th>end_time</th>
                <th>duration_minutes</th>
                <th>is_open</th>
                <th>is_study</th>
                <th>is_lgbt</th>
                <th>is_women</th>
                <th>is_hybrid</th>
                <th>is_active</th>
                <th>clicks_total</th>
                <th>clicks_running</th>
                <th>clicks_starting_soon</th>
                <th>clicks_upcoming</th>
            </tr>
            </thead>
        </table>
    </div>
</section>

<script>
(() => {
    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
        return;
    }

    const $ = window.jQuery;
    const tableNode = document.getElementById('meeting-analysis-datatable');
    const filterForm = document.getElementById('meeting-analysis-filters');
    const applyBtn = document.getElementById('meeting-analysis-apply');
    const clearBtn = document.getElementById('meeting-analysis-clear');
    const errorBox = document.getElementById('meeting-analysis-errors');

    if (!tableNode || !filterForm || !applyBtn || !clearBtn || !errorBox) return;

    const filterParams = () => {
        const formData = new FormData(filterForm);
        const payload = {};

        for (const [key, value] of formData.entries()) {
            if (typeof value === 'string' && value.trim() !== '') {
                payload[key] = value.trim();
            }
        }

        return payload;
    };

    const renderErrors = (json) => {
        const errors = json?.errors ?? {};
        const lines = Object.entries(errors).map(([field, messages]) => {
            const text = Array.isArray(messages) ? messages.join(' ') : String(messages);
            return `${field}: ${text}`;
        });

        if (!lines.length) {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            return;
        }

        errorBox.textContent = lines.join(' | ');
        errorBox.classList.remove('hidden');
    };

    const boolToLabel = (value) => value ? 'Sim' : 'Nao';

    const table = $(tableNode).DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        pageLength: 20,
        lengthMenu: [10, 20, 50, 100],
        ajax: {
            url: tableNode.getAttribute('data-api-url'),
            type: 'GET',
            data: (d) => Object.assign(d, filterParams()),
            dataSrc: (json) => {
                if (json?.ok === false) {
                    renderErrors(json);
                    return [];
                }

                errorBox.classList.add('hidden');
                errorBox.textContent = '';

                document.getElementById('ma-total-filtered').textContent = String(json?.summary?.total_filtered ?? 0);
                document.getElementById('ma-active-count').textContent = String(json?.summary?.active_count ?? 0);
                document.getElementById('ma-inactive-count').textContent = String(json?.summary?.inactive_count ?? 0);
                document.getElementById('ma-records-filtered').textContent = String(json?.recordsFiltered ?? 0);

                return Array.isArray(json?.data) ? json.data : [];
            },
            error: () => {
                errorBox.textContent = 'Falha ao carregar dados da tabela.';
                errorBox.classList.remove('hidden');
            },
        },
        columns: [
            { data: 'name' },
            { data: 'meeting_platform', defaultContent: '-' },
            { data: 'meeting_id', defaultContent: '-' },
            { data: 'weekday', defaultContent: '-' },
            { data: 'start_time', defaultContent: '-' },
            { data: 'end_time', defaultContent: '-' },
            { data: 'duration_minutes', defaultContent: '-' },
            { data: 'is_open', render: (v) => boolToLabel(Boolean(v)) },
            { data: 'is_study', render: (v) => boolToLabel(Boolean(v)) },
            { data: 'is_lgbt', render: (v) => boolToLabel(Boolean(v)) },
            { data: 'is_women', render: (v) => boolToLabel(Boolean(v)) },
            { data: 'is_hybrid', render: (v) => boolToLabel(Boolean(v)) },
            { data: 'is_active', render: (v) => boolToLabel(Boolean(v)) },
            { data: 'clicks_total', defaultContent: 0 },
            { data: 'clicks_running', defaultContent: 0 },
            { data: 'clicks_starting_soon', defaultContent: 0 },
            { data: 'clicks_upcoming', defaultContent: 0 },
        ],
        order: [[3, 'asc'], [4, 'asc']],
        language: {
            processing: 'Carregando...',
            search: 'Busca global:',
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

    applyBtn.addEventListener('click', () => {
        table.ajax.reload();
    });

    clearBtn.addEventListener('click', () => {
        filterForm.reset();
        errorBox.classList.add('hidden');
        errorBox.textContent = '';
        table.ajax.reload();
    });
})();
</script>
