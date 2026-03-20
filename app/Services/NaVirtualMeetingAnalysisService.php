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
     * @param  array<string, mixed>  $contract
     *
     * @throws ValidationException
     */
    public function resolveFilters(array $rawFilters, array $contract): NaVirtualMeetingAnalysisFiltersData
    {
        $allowedSort = array_values(array_unique(array_merge(
            (array) data_get($contract, 'sorting.allowed', []),
            ['clicks_total', 'clicks_running', 'clicks_starting_soon', 'clicks_upcoming']
        )));
        $allowedPerPage = array_values(array_unique(array_merge(
            array_map('intval', (array) data_get($contract, 'pagination.allowed_per_page', [20, 50, 100])),
            [10]
        )));
        $allowedWeekdays = ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
        $allowedClickBlocks = ['all', 'running', 'starting_soon', 'upcoming'];
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

        if ($clickWindow === 'custom' && ($clickFrom === null || $clickTo === null)) {
            throw ValidationException::withMessages([
                'click_window' => ['Para janela custom, click_from e click_to sao obrigatorios.'],
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
            clickBlock: $this->normalizeString(data_get($validated, 'click_block')),
            clickWindow: $clickWindow,
            clickFrom: $clickFrom,
            clickTo: $clickTo,
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
        $clicksSub = $this->buildClickStatsSubquery($filters);

        return VirtualMeeting::query()
            ->leftJoinSub($clicksSub, 'clicks', function ($join): void {
                $join->on('clicks.meeting_name', '=', 'virtual_meetings.name');
            })
            ->select('virtual_meetings.*')
            ->selectRaw('COALESCE(clicks.clicks_total, 0) as clicks_total')
            ->selectRaw('COALESCE(clicks.clicks_running, 0) as clicks_running')
            ->selectRaw('COALESCE(clicks.clicks_starting_soon, 0) as clicks_starting_soon')
            ->selectRaw('COALESCE(clicks.clicks_upcoming, 0) as clicks_upcoming')
            ->when($filters->searchName !== null, fn (Builder $query): Builder => $query->where('virtual_meetings.name', 'like', '%'.$filters->searchName.'%'))
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
            ->when($filters->clickBlock === 'all', fn (Builder $query): Builder => $query->whereRaw('COALESCE(clicks.clicks_total, 0) > 0'))
            ->when($filters->clickBlock === 'running', fn (Builder $query): Builder => $query->whereRaw('COALESCE(clicks.clicks_running, 0) > 0'))
            ->when($filters->clickBlock === 'starting_soon', fn (Builder $query): Builder => $query->whereRaw('COALESCE(clicks.clicks_starting_soon, 0) > 0'))
            ->when($filters->clickBlock === 'upcoming', fn (Builder $query): Builder => $query->whereRaw('COALESCE(clicks.clicks_upcoming, 0) > 0'));
    }

    public function applySorting(Builder $query, NaVirtualMeetingAnalysisFiltersData $filters): Builder
    {
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
            ->map(fn (VirtualMeeting $meeting): array => [
                'name' => (string) $meeting->name,
                'meeting_platform' => $meeting->meeting_platform,
                'meeting_id' => $meeting->meeting_id,
                'weekday' => $meeting->weekday,
                'start_time' => $this->formatTime($meeting->start_time),
                'end_time' => $this->formatTime($meeting->end_time),
                'duration_minutes' => $meeting->duration_minutes,
                'is_open' => (bool) $meeting->is_open,
                'is_study' => (bool) $meeting->is_study,
                'is_lgbt' => (bool) $meeting->is_lgbt,
                'is_women' => (bool) $meeting->is_women,
                'is_hybrid' => (bool) $meeting->is_hybrid,
                'is_active' => (bool) $meeting->is_active,
                'clicks_total' => (int) ($meeting->clicks_total ?? 0),
                'clicks_running' => (int) ($meeting->clicks_running ?? 0),
                'clicks_starting_soon' => (int) ($meeting->clicks_starting_soon ?? 0),
                'clicks_upcoming' => (int) ($meeting->clicks_upcoming ?? 0),
            ])
            ->all();
    }

    private function buildClickStatsSubquery(NaVirtualMeetingAnalysisFiltersData $filters): Builder
    {
        $meetingNameExpr = $this->meetingNameExpression();
        [$fromAt, $toAt] = $this->resolveClickWindowRange($filters);

        return MetricPageView::query()
            ->from('metric_page_views as mpv')
            ->selectRaw($meetingNameExpr.' as meeting_name')
            ->selectRaw('COUNT(*) as clicks_total')
            ->selectRaw("SUM(CASE WHEN mpv.category = 'running' THEN 1 ELSE 0 END) as clicks_running")
            ->selectRaw("SUM(CASE WHEN mpv.category = 'starting_soon' THEN 1 ELSE 0 END) as clicks_starting_soon")
            ->selectRaw("SUM(CASE WHEN mpv.category = 'upcoming' THEN 1 ELSE 0 END) as clicks_upcoming")
            ->where('mpv.event_type', 'category_click')
            ->whereNotNull('mpv.context')
            ->whereRaw($meetingNameExpr.' IS NOT NULL')
            ->when($fromAt !== null, fn (Builder $query): Builder => $query->where('mpv.occurred_at', '>=', $fromAt))
            ->when($toAt !== null, fn (Builder $query): Builder => $query->where('mpv.occurred_at', '<=', $toAt))
            ->groupBy('meeting_name');
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
                $filters->clickFrom !== null ? Carbon::parse($filters->clickFrom) : null,
                $filters->clickTo !== null ? Carbon::parse($filters->clickTo) : null,
            ],
            default => [now()->subDay(), now()],
        };
    }

    private function meetingNameExpression(): string
    {
        $driver = (string) config('database.default');
        $connectionDriver = (string) config('database.connections.'.$driver.'.driver', $driver);

        if ($connectionDriver === 'mysql') {
            return "JSON_UNQUOTE(JSON_EXTRACT(mpv.context, '$.meeting_name'))";
        }

        return "json_extract(mpv.context, '$.meeting_name')";
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

    private function normalizeString(mixed $value): ?string
    {
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

