<?php

namespace App\Services;

use App\Models\MetricPageView;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NaVirtualMeetingAnalysisDiagnosticsService
{
    /**
     * @return array<string, mixed>
     */
    public function run(int $sampleLimit = 10): array
    {
        $sampleLimit = max(1, min(50, $sampleLimit));

        $runtime = $this->buildRuntimeSnapshot();
        $migrations = $this->buildMigrationSnapshot();
        $metrics = $this->buildMetricsSnapshot($sampleLimit);
        $apiComparison = $this->buildApiComparisonSnapshot();

        return [
            'phase' => 'read_only',
            'generated_at' => now()->toIso8601String(),
            'runtime' => $runtime,
            'migrations' => $migrations,
            'metrics' => $metrics,
            'api_comparison' => $apiComparison,
            'decision_matrix' => $this->buildDecisionMatrix($metrics, $apiComparison),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuntimeSnapshot(): array
    {
        $manifestPath = public_path('build/manifest.json');
        $assetVersion = null;

        if (is_file($manifestPath)) {
            $raw = @file_get_contents($manifestPath);
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $entry = data_get($decoded, 'resources/js/app.js.file');
                    if (is_string($entry) && trim($entry) !== '') {
                        $assetVersion = $entry;
                    }
                }
            }
        }

        return [
            'app_env' => (string) config('app.env'),
            'app_timezone' => (string) config('app.timezone'),
            'metrics_enabled' => (bool) config('na_virtual.metrics.enabled', true),
            'meeting_analysis_enabled' => (bool) config('na_virtual.metrics.meeting_analysis.enabled', true),
            'metrics_require_https' => (bool) config('na_virtual.metrics.admin.require_https', true),
            'database_default' => (string) config('database.default'),
            'cache_files' => [
                'config' => is_file(base_path('bootstrap/cache/config.php')),
                'routes' => is_file(base_path('bootstrap/cache/routes-v7.php')) || is_file(base_path('bootstrap/cache/routes.php')),
                'events' => is_file(base_path('bootstrap/cache/events.php')),
                'views' => is_dir(storage_path('framework/views')),
            ],
            'assets' => [
                'manifest_exists' => is_file($manifestPath),
                'app_js_build_file' => $assetVersion,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMigrationSnapshot(): array
    {
        $expected = [
            '2026_03_18_160500_create_metric_page_views_table',
            '2026_03_18_160600_create_metric_sync_runs_table',
            '2026_03_18_160700_create_metric_meeting_snapshots_table',
            '2026_03_19_000800_create_metric_request_metrics_table',
            '2026_03_19_000900_create_metric_hourly_aggregates_table',
            '2026_03_20_130000_create_metric_meeting_analysis_presets_table',
            '2026_03_21_120000_add_event_uuid_to_metric_page_views_table',
        ];

        $applied = [];
        if (Schema::hasTable('migrations')) {
            $applied = DB::table('migrations')->pluck('migration')->map(fn ($item): string => (string) $item)->all();
        }

        $missing = array_values(array_diff($expected, $applied));
        sort($missing);

        $tableHealth = [
            'virtual_meetings' => Schema::hasTable('virtual_meetings'),
            'metric_page_views' => Schema::hasTable('metric_page_views'),
            'metric_sync_runs' => Schema::hasTable('metric_sync_runs'),
            'metric_meeting_snapshots' => Schema::hasTable('metric_meeting_snapshots'),
            'metric_request_metrics' => Schema::hasTable('metric_request_metrics'),
            'metric_hourly_aggregates' => Schema::hasTable('metric_hourly_aggregates'),
            'metric_meeting_analysis_presets' => Schema::hasTable('metric_meeting_analysis_presets'),
        ];

        return [
            'expected_metric_migrations' => $expected,
            'missing_metric_migrations' => $missing,
            'tables' => $tableHealth,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetricsSnapshot(int $sampleLimit): array
    {
        $hasPageViews = Schema::hasTable('metric_page_views');
        $hasMeetings = Schema::hasTable('virtual_meetings');

        if (! $hasPageViews || ! $hasMeetings) {
            return [
                'status' => 'insufficient_schema',
                'reason' => 'Tabelas obrigatorias para diagnostico nao encontradas.',
            ];
        }

        $counts24h = $this->eventTypeCounts(now()->subDay());
        $counts7d = $this->eventTypeCounts(now()->subDays(7));

        $meetingRowExpr = $this->meetingRowIdExpression('mpv');
        $categoryClicks24h = MetricPageView::query()
            ->from('metric_page_views as mpv')
            ->where('mpv.event_type', 'category_click')
            ->where('mpv.occurred_at', '>=', now()->subDay());

        $totalCategoryClicks24h = (clone $categoryClicks24h)->count();
        $withMeetingRowId24h = (clone $categoryClicks24h)
            ->whereNotNull('mpv.context')
            ->whereRaw($meetingRowExpr.' IS NOT NULL')
            ->count();

        $withMeetingRowIdMatching24h = (clone $categoryClicks24h)
            ->whereRaw($meetingRowExpr.' IS NOT NULL')
            ->whereExists(function ($query) use ($meetingRowExpr): void {
                $query->selectRaw('1')
                    ->from('virtual_meetings as vm')
                    ->whereRaw('vm.id = '.$meetingRowExpr);
            })
            ->count();

        $withoutMeetingRowId24h = max(0, $totalCategoryClicks24h - $withMeetingRowId24h);
        $withMeetingRowIdWithoutMatch24h = max(0, $withMeetingRowId24h - $withMeetingRowIdMatching24h);

        $samples = MetricPageView::query()
            ->where('event_type', 'category_click')
            ->orderByDesc('occurred_at')
            ->limit($sampleLimit)
            ->get(['occurred_at', 'category', 'route', 'context'])
            ->map(function (MetricPageView $row): array {
                $meetingRowId = data_get($row->context, 'meeting_row_id');

                return [
                    'occurred_at' => optional($row->occurred_at)->toDateTimeString(),
                    'category' => $row->category,
                    'route' => $row->route,
                    'meeting_row_id' => is_numeric($meetingRowId) ? (int) $meetingRowId : null,
                    'source_section' => data_get($row->context, 'source_section'),
                    'meeting_name' => data_get($row->context, 'meeting_name'),
                ];
            })
            ->values()
            ->all();

        return [
            'status' => 'ok',
            'event_type_counts' => [
                '24h' => $counts24h,
                '7d' => $counts7d,
            ],
            'category_click_quality_24h' => [
                'total' => $totalCategoryClicks24h,
                'with_meeting_row_id' => $withMeetingRowId24h,
                'without_meeting_row_id' => $withoutMeetingRowId24h,
                'with_matching_virtual_meeting' => $withMeetingRowIdMatching24h,
                'with_meeting_row_id_without_match' => $withMeetingRowIdWithoutMatch24h,
            ],
            'latest_category_click_samples' => $samples,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildApiComparisonSnapshot(): array
    {
        if (! Schema::hasTable('virtual_meetings')) {
            return [
                'status' => 'insufficient_schema',
                'reason' => 'Tabela virtual_meetings nao encontrada.',
            ];
        }

        $service = app(NaVirtualMeetingAnalysisService::class);
        $baseFilters = [
            'click_window' => '24h',
            'per_page' => 10,
            'page' => 1,
            'sort_by' => 'name',
            'sort_dir' => 'asc',
        ];

        $all = $service->search(array_merge($baseFilters, ['click_block' => 'all']));
        $accessed = $service->search(array_merge($baseFilters, ['click_block' => 'accessed']));

        return [
            'status' => 'ok',
            'all_24h' => [
                'records_filtered' => (int) data_get($all, 'summary.total_filtered', 0),
                'records_returned' => count((array) data_get($all, 'rows', [])),
                'sample_names' => collect((array) data_get($all, 'rows', []))
                    ->take(5)
                    ->pluck('name')
                    ->values()
                    ->all(),
                'applied_filters' => data_get($all, 'applied_filters', []),
            ],
            'accessed_24h' => [
                'records_filtered' => (int) data_get($accessed, 'summary.total_filtered', 0),
                'records_returned' => count((array) data_get($accessed, 'rows', [])),
                'sample_names' => collect((array) data_get($accessed, 'rows', []))
                    ->take(5)
                    ->pluck('name')
                    ->values()
                    ->all(),
                'applied_filters' => data_get($accessed, 'applied_filters', []),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array<string, mixed>  $apiComparison
     * @return array<string, mixed>
     */
    private function buildDecisionMatrix(array $metrics, array $apiComparison): array
    {
        if (data_get($metrics, 'status') !== 'ok' || data_get($apiComparison, 'status') !== 'ok') {
            return [
                'primary_cause' => 'insufficient_schema_or_runtime',
                'explanation' => 'Nao foi possivel avaliar todas as dimensoes por schema/runtimes incompletos.',
            ];
        }

        $categoryTotal24h = (int) data_get($metrics, 'category_click_quality_24h.total', 0);
        $categoryTotal7d = (int) data_get($metrics, 'event_type_counts.7d.category_click', 0);
        $withoutMeetingRowId24h = (int) data_get($metrics, 'category_click_quality_24h.without_meeting_row_id', 0);
        $matching24h = (int) data_get($metrics, 'category_click_quality_24h.with_matching_virtual_meeting', 0);
        $allFiltered = (int) data_get($apiComparison, 'all_24h.records_filtered', 0);
        $accessedFiltered = (int) data_get($apiComparison, 'accessed_24h.records_filtered', 0);

        if ($categoryTotal24h === 0) {
            if ($categoryTotal7d > 0) {
                return [
                    'primary_cause' => 'clicks_outside_24h_window',
                    'explanation' => 'Existem category_click em 7 dias, mas nao nas ultimas 24h.',
                ];
            }

            return [
                'primary_cause' => 'no_category_click_24h',
                'explanation' => 'Nao ha eventos category_click recentes; possivel problema de instrumentacao/transporte.',
            ];
        }

        if ($withoutMeetingRowId24h > 0 && $matching24h === 0) {
            return [
                'primary_cause' => 'category_click_without_meeting_row_id',
                'explanation' => 'Eventos existem, mas sem meeting_row_id utilizavel para correlacao na analise.',
            ];
        }

        if ($matching24h === 0) {
            return [
                'primary_cause' => 'meeting_row_id_without_virtual_meeting_match',
                'explanation' => 'meeting_row_id foi enviado, mas nao encontra correspondencia em virtual_meetings.',
            ];
        }

        if ($allFiltered > 0 && $accessedFiltered === 0) {
            return [
                'primary_cause' => 'api_filter_gap_accessed_vs_all',
                'explanation' => 'A API retorna dados em all_24h, mas nenhum em accessed_24h no momento.',
            ];
        }

        return [
            'primary_cause' => 'healthy_or_intermediate',
            'explanation' => 'Ha cliques correlacionados; revisar browser/network para confirmar fluxo ponta a ponta.',
        ];
    }

    /**
     * @return array<string, int>
     */
    private function eventTypeCounts(\Illuminate\Support\Carbon $from): array
    {
        return MetricPageView::query()
            ->where('occurred_at', '>=', $from)
            ->select('event_type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type')
            ->map(fn ($value): int => (int) $value)
            ->all();
    }

    private function meetingRowIdExpression(string $alias): string
    {
        $driver = (string) config('database.default');
        $connectionDriver = (string) config('database.connections.'.$driver.'.driver', $driver);

        if ($connectionDriver === 'mysql') {
            return "CAST(JSON_UNQUOTE(JSON_EXTRACT({$alias}.context, '$.meeting_row_id')) AS UNSIGNED)";
        }

        return "CAST(json_extract({$alias}.context, '$.meeting_row_id') AS INTEGER)";
    }
}
