<?php

namespace App\Services;

use App\Models\MetricPageView;
use App\Models\VirtualMeeting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NaVirtualMeetingAnalysisService
{
    /**
     * @param  array<string, mixed>  $rawFilters
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function search(array $rawFilters = []): array
    {
        $contract = app(NaVirtualMeetingAnalysisContractService::class)->getDefinition();
        $filters = $this->resolveFilters($rawFilters, $contract);

        $recordsTotal = (int) VirtualMeeting::query()->count();

        $filteredQuery = $this->buildFilteredQuery($filters);
        $recordsFiltered = (int) (clone $filteredQuery)->count();
        $summary = $this->buildSummary($filteredQuery);

        $sortedQuery = $this->applySorting(clone $filteredQuery, $filters);
        $paginator = $sortedQuery->paginate(
            perPage: $filters->perPage,
            columns: ['*'],
            pageName: 'page',
            page: $filters->page,
        );

        return [
            'rows' => $this->transformRows($paginator),
            'summary' => $summary,
            'applied_filters' => $filters->toArray(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'sort_by' => $filters->sortBy,
                'sort_dir' => $filters->sortDirection,
                'performance' => $this->performanceStrategy($contract),
                'datatables' => [
                    'enabled' => $filters->isDataTable,
                    'draw' => $filters->draw,
                    'records_total' => $recordsTotal,
                    'records_filtered' => $recordsFiltered,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $rawFilters
     *
     * @throws ValidationException
     */
    public function exportCsv(array $rawFilters = []): string
    {
        $contract = app(NaVirtualMeetingAnalysisContractService::class)->getDefinition();
        $filters = $this->resolveFilters($rawFilters, $contract);

        $meetings = $this->applySorting(
            $this->buildFilteredQuery($filters),
            $filters,
        )->get();

        $handle = fopen('php://temp', 'w+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, [
            'name',
            'meeting_platform',
            'meeting_id',
            'weekday',
            'start_time',
            'end_time',
            'duration_minutes',
            'is_open',
            'is_study',
            'is_lgbt',
            'is_women',
            'is_hybrid',
            'is_active',
            'clicks_total',
            'clicks_running',
            'clicks_starting_soon',
            'clicks_upcoming',
        ]);

        foreach ($meetings as $meeting) {
            $row = $this->transformMeetingRow($meeting);

            fputcsv($handle, [
                (string) data_get($row, 'name', ''),
                (string) data_get($row, 'meeting_platform', ''),
                (string) data_get($row, 'meeting_id', ''),
                (string) data_get($row, 'weekday', ''),
                (string) data_get($row, 'start_time', ''),
                (string) data_get($row, 'end_time', ''),
                (string) data_get($row, 'duration_minutes', ''),
                $this->toCsvBoolLabel((bool) data_get($row, 'is_open', false)),
                $this->toCsvBoolLabel((bool) data_get($row, 'is_study', false)),
                $this->toCsvBoolLabel((bool) data_get($row, 'is_lgbt', false)),
                $this->toCsvBoolLabel((bool) data_get($row, 'is_women', false)),
                $this->toCsvBoolLabel((bool) data_get($row, 'is_hybrid', false)),
                $this->toCsvBoolLabel((bool) data_get($row, 'is_active', false)),
                (int) data_get($row, 'clicks_total', 0),
                (int) data_get($row, 'clicks_running', 0),
                (int) data_get($row, 'clicks_starting_soon', 0),
                (int) data_get($row, 'clicks_upcoming', 0),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF".(is_string($csv) ? $csv : '');
    }

    /**
     * @param  array<string, mixed>  $rawFilters
     * @param  array<string, mixed>  $contract
     *
     * @throws ValidationException
     */
    public function resolveFilters(array $rawFilters, array $contract): NaVirtualMeetingAnalysisFiltersData
    {
        $allowedSort = array_values(array_unique(array_merge(
            (array) data_get($contract, 'sorting.allowed', []),
            ['clicks_total', 'clicks_running', 'clicks_starting_soon', 'clicks_upcoming', 'meeting_id', 'is_open', 'click_bucket']
        )));
        $allowedPerPage = array_values(array_unique(array_merge(
            array_map('intval', (array) data_get($contract, 'pagination.allowed_per_page', [20, 50, 100])),
            [10]
        )));
        $allowedWeekdays = ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
        $allowedClickBlocks = ['all', 'accessed', 'running', 'starting_soon', 'upcoming'];
        $allowedClickWindows = ['24h', '7d', '30d', 'custom'];

        $validator = Validator::make($rawFilters, [
            'search_name' => ['nullable', 'string', 'max:120'],
            'weekday' => ['nullable', 'string', Rule::in($allowedWeekdays)],
            'time_start' => ['nullable', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i'],
            'meeting_platform' => ['nullable', 'string', 'max:40'],
            'is_open' => ['nullable', 'boolean'],
            'is_study' => ['nullable', 'boolean'],
            'is_lgbt' => ['nullable', 'boolean'],
            'is_women' => ['nullable', 'boolean'],
            'is_hybrid' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'click_block' => ['nullable', 'string', Rule::in($allowedClickBlocks)],
            'click_window' => ['nullable', 'string', Rule::in($allowedClickWindows)],
            'click_from' => ['nullable', 'date'],
            'click_to' => ['nullable', 'date'],
            'click_from_hour' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}-\d{2} \d{2}(:\d{2})?$/'],
            'click_to_hour' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}-\d{2} \d{2}(:\d{2})?$/'],
            'sort_by' => ['nullable', 'string', Rule::in($allowedSort)],
            'sort_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in($allowedPerPage)],
            'page' => ['nullable', 'integer', 'min:1'],
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', Rule::in($allowedPerPage)],
            'search.value' => ['nullable', 'string', 'max:120'],
            'order.0.column' => ['nullable', 'integer', 'min:0'],
            'order.0.dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'columns' => ['nullable', 'array'],
            'columns.*.data' => ['nullable', 'string'],
        ]);

        $validated = $validator->validate();

        $timeStart = $this->normalizeString(data_get($validated, 'time_start'));
        $timeEnd = $this->normalizeString(data_get($validated, 'time_end'));

        if ($timeStart !== null && $timeEnd !== null && strcmp($timeStart, $timeEnd) > 0) {
            throw ValidationException::withMessages([
                'time_start' => ['O horario inicial nao pode ser maior que o horario final.'],
            ]);
        }

        $clickWindow = (string) data_get($validated, 'click_window', '24h');
        $clickFrom = $this->normalizeString(data_get($validated, 'click_from'));
        $clickTo = $this->normalizeString(data_get($validated, 'click_to'));
        $clickFromHour = $this->normalizeString(data_get($validated, 'click_from_hour'));
        $clickToHour = $this->normalizeString(data_get($validated, 'click_to_hour'));

        if (($clickFromHour === null) xor ($clickToHour === null)) {
            throw ValidationException::withMessages([
                'click_from_hour' => ['click_from_hour e click_to_hour devem ser informados em conjunto.'],
            ]);
        }

        $hasDateRange = $clickFrom !== null && $clickTo !== null;
        $hasHourRange = $clickFromHour !== null && $clickToHour !== null;

        if ($hasHourRange && strcmp((string) $clickFromHour, (string) $clickToHour) > 0) {
            throw ValidationException::withMessages([
                'click_from_hour' => ['O periodo de clique deve ter inicio menor ou igual ao fim.'],
            ]);
        }

        if ($clickWindow === 'custom' && ! $hasDateRange && ! $hasHourRange) {
            throw ValidationException::withMessages([
                'click_window' => ['Para janela custom, informe click_from/click_to ou click_from_hour/click_to_hour.'],
            ]);
        }

        $isDataTable = array_key_exists('draw', $rawFilters)
            || array_key_exists('start', $rawFilters)
            || array_key_exists('length', $rawFilters);

        $sortBy = (string) data_get($validated, 'sort_by', (string) data_get($contract, 'sorting.default_by', 'weekday'));
        $sortDirection = (string) data_get($validated, 'sort_dir', (string) data_get($contract, 'sorting.default_direction', 'asc'));
        $perPage = (int) data_get($validated, 'per_page', (int) data_get($contract, 'pagination.default_per_page', 20));
        $page = (int) data_get($validated, 'page', 1);

        if ($isDataTable) {
            $perPage = (int) data_get($validated, 'length', $perPage);
            $start = (int) data_get($validated, 'start', 0);
            $page = (int) floor($start / max(1, $perPage)) + 1;

            $orderIndex = data_get($validated, 'order.0.column');
            $orderDir = data_get($validated, 'order.0.dir');
            $columnData = data_get($validated, 'columns.'.$orderIndex.'.data');

            if (is_string($columnData) && in_array($columnData, $allowedSort, true)) {
                $sortBy = $columnData;
            }

            if (is_string($orderDir) && in_array($orderDir, ['asc', 'desc'], true)) {
                $sortDirection = $orderDir;
            }
        }

        $globalSearch = $this->normalizeString(data_get($validated, 'search.value'));
        $searchName = $this->normalizeString(data_get($validated, 'search_name'));
        if ($searchName === null && $globalSearch !== null) {
            $searchName = $globalSearch;
        }

        $clickBlock = $this->normalizeString(data_get($validated, 'click_block'));
        if ($clickWindow === 'custom' && ($clickBlock === null || $clickBlock === 'all')) {
            $clickBlock = 'accessed';
        }

        return new NaVirtualMeetingAnalysisFiltersData(
            searchName: $searchName,
            weekday: $this->normalizeString(data_get($validated, 'weekday')),
            timeStart: $timeStart,
            timeEnd: $timeEnd,
            meetingPlatform: $this->normalizeString(data_get($validated, 'meeting_platform')),
            isOpen: $this->normalizeBool(data_get($validated, 'is_open')),
            isStudy: $this->normalizeBool(data_get($validated, 'is_study')),
            isLgbt: $this->normalizeBool(data_get($validated, 'is_lgbt')),
            isWomen: $this->normalizeBool(data_get($validated, 'is_women')),
            isHybrid: $this->normalizeBool(data_get($validated, 'is_hybrid')),
            isActive: $this->normalizeBool(data_get($validated, 'is_active')),
            clickBlock: $clickBlock,
            clickWindow: $clickWindow,
            clickFrom: $clickFrom,
            clickTo: $clickTo,
            clickFromHour: $clickFromHour,
            clickToHour: $clickToHour,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            perPage: $perPage,
            page: $page,
            isDataTable: $isDataTable,
            draw: data_get($validated, 'draw') !== null ? (int) data_get($validated, 'draw') : null,
        );
    }

    public function buildFilteredQuery(NaVirtualMeetingAnalysisFiltersData $filters): Builder
    {
        $clicksByRowIdSub = $this->buildClickStatsByRowIdSubquery($filters);

        return VirtualMeeting::query()
            ->leftJoinSub($clicksByRowIdSub, 'clicks_by_rowid', function ($join): void {
                $join->on('clicks_by_rowid.meeting_row_id', '=', 'virtual_meetings.id');
            })
            ->select('virtual_meetings.*')
            ->selectRaw($this->clickBucketExpression().' as click_bucket')
            ->selectRaw($this->clickBucketSortRankExpression().' as click_bucket_sort_rank')
            ->selectRaw($this->clicksTotalExpression().' as clicks_total')
            ->selectRaw($this->clicksRunningExpression().' as clicks_running')
            ->selectRaw($this->clicksStartingSoonExpression().' as clicks_starting_soon')
            ->selectRaw($this->clicksUpcomingExpression().' as clicks_upcoming')
            ->selectRaw('clicks_by_rowid.last_clicked_at as last_clicked_at')
            ->when($filters->searchName !== null, function (Builder $query) use ($filters): Builder {
                $search = '%'.$filters->searchName.'%';

                return $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('virtual_meetings.name', 'like', $search)
                        ->orWhere('virtual_meetings.meeting_id', 'like', $search)
                        ->orWhere('virtual_meetings.weekday', 'like', $search)
                        ->orWhereRaw($this->clickBucketExpression().' like ?', [$search]);
                });
            })
            ->when($filters->weekday !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.weekday', $filters->weekday))
            ->when($filters->timeStart !== null, fn (Builder $query): Builder => $query->whereTime('virtual_meetings.start_time', '>=', $filters->timeStart))
            ->when($filters->timeEnd !== null, fn (Builder $query): Builder => $query->whereTime('virtual_meetings.start_time', '<=', $filters->timeEnd))
            ->when($filters->meetingPlatform !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.meeting_platform', $filters->meetingPlatform))
            ->when($filters->isOpen !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.is_open', $filters->isOpen))
            ->when($filters->isStudy !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.is_study', $filters->isStudy))
            ->when($filters->isLgbt !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.is_lgbt', $filters->isLgbt))
            ->when($filters->isWomen !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.is_women', $filters->isWomen))
            ->when($filters->isHybrid !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.is_hybrid', $filters->isHybrid))
            ->when($filters->isActive !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.is_active', $filters->isActive))
            ->when($filters->clickBlock === 'accessed', fn (Builder $query): Builder => $query->whereRaw($this->clicksTotalExpression().' > 0'))
            ->when($filters->clickBlock === 'running', fn (Builder $query): Builder => $query->whereRaw($this->clicksRunningExpression().' > 0'))
            ->when($filters->clickBlock === 'starting_soon', fn (Builder $query): Builder => $query->whereRaw($this->clicksStartingSoonExpression().' > 0'))
            ->when($filters->clickBlock === 'upcoming', fn (Builder $query): Builder => $query->whereRaw($this->clicksUpcomingExpression().' > 0'));
    }
    public function applySorting(Builder $query, NaVirtualMeetingAnalysisFiltersData $filters): Builder
    {
        if ($filters->sortBy === 'click_bucket') {
            return $query
                ->orderByRaw($this->clickBucketSortRankExpression().' '.$filters->sortDirection)
                ->orderBy('virtual_meetings.name', 'asc');
        }

        $column = in_array($filters->sortBy, ['clicks_total', 'clicks_running', 'clicks_starting_soon', 'clicks_upcoming'], true)
            ? $filters->sortBy
            : 'virtual_meetings.'.$filters->sortBy;

        return $query
            ->orderBy($column, $filters->sortDirection)
            ->orderBy('virtual_meetings.name', 'asc');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(Builder $filteredQuery): array
    {
        $total = (clone $filteredQuery)->count();
        $active = (clone $filteredQuery)->where('virtual_meetings.is_active', true)->count();

        $weekdayDistribution = (clone $filteredQuery)
            ->select([])
            ->selectRaw('COALESCE(virtual_meetings.weekday, "nao_informado") as label, COUNT(*) as total')
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->all();

        $platformDistribution = (clone $filteredQuery)
            ->select([])
            ->selectRaw('COALESCE(virtual_meetings.meeting_platform, "nao_informado") as label, COUNT(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->all();

        return [
            'total_filtered' => (int) $total,
            'active_count' => (int) $active,
            'inactive_count' => max(0, (int) $total - (int) $active),
            'weekday_distribution' => $weekdayDistribution,
            'platform_distribution' => $platformDistribution,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function transformRows(LengthAwarePaginator $paginator): array
    {
        return collect($paginator->items())
            ->map(fn (VirtualMeeting $meeting): array => $this->transformMeetingRow($meeting))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformMeetingRow(VirtualMeeting $meeting): array
    {
        $name = (string) $meeting->name;
        $isOpen = (bool) $meeting->is_open;
        $isStudy = (bool) $meeting->is_study;

        return [
            'name' => $name,
            'name_clean' => $this->cleanMeetingName($name),
            'meeting_platform' => $meeting->meeting_platform,
            'meeting_id' => $meeting->meeting_id,
            'weekday' => $meeting->weekday,
            'hora_clique' => $this->formatClickHour($meeting->last_clicked_at),
            'start_time' => $this->formatTime($meeting->start_time),
            'start_hour' => $this->formatStartHour($meeting->start_time),
            'end_time' => $this->formatTime($meeting->end_time),
            'duration_minutes' => $meeting->duration_minutes,
            'meeting_format' => $this->resolveMeetingFormat($isOpen, $isStudy),
            'is_open' => $isOpen,
            'is_study' => $isStudy,
            'is_lgbt' => (bool) $meeting->is_lgbt,
            'is_women' => (bool) $meeting->is_women,
            'is_hybrid' => (bool) $meeting->is_hybrid,
            'is_active' => (bool) $meeting->is_active,
            'clicks_total' => (int) ($meeting->clicks_total ?? 0),
            'clicks_running' => (int) ($meeting->clicks_running ?? 0),
            'clicks_starting_soon' => (int) ($meeting->clicks_starting_soon ?? 0),
            'clicks_upcoming' => (int) ($meeting->clicks_upcoming ?? 0),
            'click_bucket' => (string) ($meeting->click_bucket ?? 'sem_clique'),
        ];
    }

    private function cleanMeetingName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return $trimmed;
        }

        $clean = preg_replace('/^grupo\s+/iu', '', $trimmed);

        return is_string($clean) && trim($clean) !== '' ? trim($clean) : $trimmed;
    }

    private function formatStartHour(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $parts = explode(':', $value);
        if ($parts === [] || ! isset($parts[0])) {
            return null;
        }

        return sprintf('%02d:00', (int) $parts[0]);
    }

    private function resolveMeetingFormat(bool $isOpen, bool $isStudy): string
    {
        if ($isOpen) {
            return 'aberta';
        }

        return $isStudy ? 'fechada (estudo)' : 'fechada';
    }

    private function toCsvBoolLabel(bool $value): string
    {
        return $value ? 'Sim' : 'Nao';
    }

    private function buildClickStatsByRowIdSubquery(NaVirtualMeetingAnalysisFiltersData $filters): Builder
    {
        $meetingRowIdExpr = $this->meetingRowIdExpression();
        [$fromAt, $toAt] = $this->resolveClickWindowRange($filters);

        return MetricPageView::query()
            ->from('metric_page_views as mpv')
            ->selectRaw($meetingRowIdExpr.' as meeting_row_id')
            ->selectRaw('COUNT(*) as clicks_total')
            ->selectRaw("SUM(CASE WHEN mpv.category = 'running' THEN 1 ELSE 0 END) as clicks_running")
            ->selectRaw("SUM(CASE WHEN mpv.category = 'starting_soon' THEN 1 ELSE 0 END) as clicks_starting_soon")
            ->selectRaw("SUM(CASE WHEN mpv.category = 'upcoming' THEN 1 ELSE 0 END) as clicks_upcoming")
            ->selectRaw('MAX(mpv.occurred_at) as last_clicked_at')
            ->where('mpv.event_type', 'category_click')
            ->whereNotNull('mpv.context')
            ->whereRaw($meetingRowIdExpr.' IS NOT NULL')
            ->when($fromAt !== null, fn (Builder $query): Builder => $query->where('mpv.occurred_at', '>=', $fromAt))
            ->when($toAt !== null, fn (Builder $query): Builder => $query->where('mpv.occurred_at', '<=', $toAt))
            ->groupBy('meeting_row_id');
    }
    private function clicksTotalExpression(): string
    {
        return 'COALESCE(clicks_by_rowid.clicks_total, 0)';
    }

    private function clicksRunningExpression(): string
    {
        return 'COALESCE(clicks_by_rowid.clicks_running, 0)';
    }

    private function clicksStartingSoonExpression(): string
    {
        return 'COALESCE(clicks_by_rowid.clicks_starting_soon, 0)';
    }

    private function clicksUpcomingExpression(): string
    {
        return 'COALESCE(clicks_by_rowid.clicks_upcoming, 0)';
    }

    private function clickBucketExpression(): string
    {
        $total = $this->clicksTotalExpression();
        $running = $this->clicksRunningExpression();
        $startingSoon = $this->clicksStartingSoonExpression();
        $upcoming = $this->clicksUpcomingExpression();

        return "CASE
            WHEN {$total} = 0 THEN 'sem_clique'
            WHEN {$running} >= {$startingSoon}
                AND {$running} >= {$upcoming} THEN 'andamento'
            WHEN {$startingSoon} >= {$running}
                AND {$startingSoon} >= {$upcoming} THEN 'em_breve'
            ELSE 'proxima'
        END";
    }

    private function clickBucketSortRankExpression(): string
    {
        $total = $this->clicksTotalExpression();
        $running = $this->clicksRunningExpression();
        $startingSoon = $this->clicksStartingSoonExpression();
        $upcoming = $this->clicksUpcomingExpression();

        return "CASE
            WHEN {$total} = 0 THEN 4
            WHEN {$running} >= {$startingSoon}
                AND {$running} >= {$upcoming} THEN 1
            WHEN {$startingSoon} >= {$running}
                AND {$startingSoon} >= {$upcoming} THEN 2
            ELSE 3
        END";
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveClickWindowRange(NaVirtualMeetingAnalysisFiltersData $filters): array
    {
        return match ($filters->clickWindow) {
            '7d' => [now()->subDays(7), now()],
            '30d' => [now()->subDays(30), now()],
            'custom' => [
                $this->resolveCustomFrom($filters),
                $this->resolveCustomTo($filters),
            ],
            default => [now()->subDay(), now()],
        };
    }

    private function resolveCustomFrom(NaVirtualMeetingAnalysisFiltersData $filters): ?Carbon
    {
        if ($filters->clickFromHour !== null) {
            return $this->parseCustomDateTime($filters->clickFromHour, 'from');
        }

        return $filters->clickFrom !== null ? Carbon::parse($filters->clickFrom)->startOfDay() : null;
    }

    private function resolveCustomTo(NaVirtualMeetingAnalysisFiltersData $filters): ?Carbon
    {
        if ($filters->clickToHour !== null) {
            return $this->parseCustomDateTime($filters->clickToHour, 'to');
        }

        return $filters->clickTo !== null ? Carbon::parse($filters->clickTo)->endOfDay() : null;
    }    private function parseCustomDateTime(string $value, string $boundary): Carbon
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $hasMinutes = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1;
        $format = $hasMinutes ? 'Y-m-d H:i' : 'Y-m-d H';
        $parsed = Carbon::createFromFormat($format, $value, $timezone);

        if ($parsed === false) {
            throw ValidationException::withMessages([
                'click_from_hour' => ['Formato invalido para click_from_hour/click_to_hour.'],
            ]);
        }

        if ($hasMinutes) {
            return $parsed;
        }

        return $boundary === 'to' ? $parsed->endOfHour() : $parsed->startOfHour();
    }

    private function meetingRowIdExpression(): string
    {
        $driver = (string) config('database.default');
        $connectionDriver = (string) config('database.connections.'.$driver.'.driver', $driver);

        if ($connectionDriver === 'mysql') {
            return "CAST(JSON_UNQUOTE(JSON_EXTRACT(mpv.context, '$.meeting_row_id')) AS UNSIGNED)";
        }

        return "CAST(json_extract(mpv.context, '$.meeting_row_id') AS INTEGER)";
    }

    private function formatClickHour(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $timezone = (string) config('app.timezone', 'UTC');
        $at = $value instanceof Carbon ? $value : Carbon::parse((string) $value);
        $at = $at->copy()->timezone($timezone);

        $labels = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab'];
        $weekday = $labels[$at->dayOfWeek] ?? '---';

        return $weekday.' | '.$at->format('H:i');
    }

    private function formatTime(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $parts = explode(':', $value);
        if (count($parts) < 2) {
            return null;
        }

        return sprintf('%02d:%02d', (int) $parts[0], (int) $parts[1]);
    }

    private function normalizeString(mixed $value): ?string{
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $normalized = mb_strtolower(trim($value));

            if (in_array($normalized, ['1', 'true'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false'], true)) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $contract
     * @return array<string, mixed>
     */
    private function performanceStrategy(array $contract): array
    {
        $allowedPerPage = array_values(array_unique(array_merge(
            array_map('intval', (array) data_get($contract, 'pagination.allowed_per_page', [20, 50, 100])),
            [10]
        )));
        sort($allowedPerPage);

        return [
            'allowed_per_page' => $allowedPerPage,
            'max_per_page' => max($allowedPerPage),
            'recommended_indexes' => [
                ['is_active', 'weekday', 'start_time'],
                ['meeting_platform', 'is_active'],
                ['event_type', 'category', 'occurred_at'],
            ],
        ];
    }
}










