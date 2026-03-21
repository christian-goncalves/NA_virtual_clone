<?php

namespace App\Console\Commands;

use App\Services\NaVirtualMeetingAnalysisDiagnosticsService;
use Illuminate\Console\Command;
use Throwable;

class DiagnoseNaMeetingAnalysisCommand extends Command
{
    protected $signature = 'na:diagnose-meeting-analysis {--sample=10 : Quantidade de eventos category_click para amostra} {--json : Imprime payload completo em JSON}';

    protected $description = 'Executa diagnostico somente leitura da analise de reunioes (/admin/metricas/reunioes).';

    public function handle(NaVirtualMeetingAnalysisDiagnosticsService $service): int
    {
        $sample = (int) $this->option('sample');

        try {
            $result = $service->run($sample);
        } catch (Throwable $e) {
            report($e);
            $this->error('Falha ao executar diagnostico: '.$e->getMessage());

            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Diagnostico de Meeting Analysis (somente leitura)');
        $this->line('Gerado em: '.(string) data_get($result, 'generated_at'));
        $this->newLine();

        $this->line('Runtime:');
        $this->line('- env: '.(string) data_get($result, 'runtime.app_env'));
        $this->line('- timezone: '.(string) data_get($result, 'runtime.app_timezone'));
        $this->line('- metrics_enabled: '.($this->toYesNo((bool) data_get($result, 'runtime.metrics_enabled', false))));
        $this->line('- meeting_analysis_enabled: '.($this->toYesNo((bool) data_get($result, 'runtime.meeting_analysis_enabled', false))));

        $this->newLine();
        $this->line('Migrations metricas ausentes: '.count((array) data_get($result, 'migrations.missing_metric_migrations', [])));
        foreach ((array) data_get($result, 'migrations.missing_metric_migrations', []) as $item) {
            $this->line('  - '.$item);
        }

        $this->newLine();
        $this->line('Event types 24h: '.json_encode((array) data_get($result, 'metrics.event_type_counts.24h', []), JSON_UNESCAPED_UNICODE));
        $this->line('Event types 7d: '.json_encode((array) data_get($result, 'metrics.event_type_counts.7d', []), JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->line('Qualidade category_click 24h:');
        $this->line('- total: '.(int) data_get($result, 'metrics.category_click_quality_24h.total', 0));
        $this->line('- with_meeting_row_id: '.(int) data_get($result, 'metrics.category_click_quality_24h.with_meeting_row_id', 0));
        $this->line('- without_meeting_row_id: '.(int) data_get($result, 'metrics.category_click_quality_24h.without_meeting_row_id', 0));
        $this->line('- with_matching_virtual_meeting: '.(int) data_get($result, 'metrics.category_click_quality_24h.with_matching_virtual_meeting', 0));

        $this->newLine();
        $this->line('Comparacao API (service):');
        $this->line('- all_24h.records_filtered: '.(int) data_get($result, 'api_comparison.all_24h.records_filtered', 0));
        $this->line('- accessed_24h.records_filtered: '.(int) data_get($result, 'api_comparison.accessed_24h.records_filtered', 0));

        $this->newLine();
        $this->info('Causa primaria sugerida: '.(string) data_get($result, 'decision_matrix.primary_cause'));
        $this->line((string) data_get($result, 'decision_matrix.explanation'));

        return self::SUCCESS;
    }

    private function toYesNo(bool $value): string
    {
        return $value ? 'sim' : 'nao';
    }
}
