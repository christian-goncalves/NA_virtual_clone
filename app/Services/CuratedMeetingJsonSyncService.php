<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CuratedMeetingJsonSyncService
{
    public function __construct(private readonly CuratedVirtualMeetingListService $listService) {}

    /**
     * @return array{written:int,total:int,conflicts:list<array<string,mixed>>}
     *
     * @throws ValidationException
     */
    public function syncFromSheetToJson(): array
    {
        $groups = $this->listService->loadValidatedGroups();
        $summary = $this->listService->lastSummary();

        $conflicts = data_get($summary, 'conflicts', []);
        $conflictsCount = (int) data_get($summary, 'conflicts_count', 0);

        if ($conflictsCount > 0) {
            Log::warning('json_sync_aborted_conflicts', [
                'conflicts_count' => $conflictsCount,
                'conflicts' => $conflicts,
            ]);

            throw ValidationException::withMessages([
                'sheet' => ['Sincronizacao abortada: existem conflitos de resolucao entre planilha e base.'],
            ]);
        }

        $path = (string) config('na_virtual.curated_groups.json_path', resource_path('data/curated-meeting-groups.json'));

        if ($path === '') {
            throw ValidationException::withMessages([
                'json' => ['Config de caminho do JSON nao definida.'],
            ]);
        }

        $directory = dirname($path);
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw ValidationException::withMessages([
                'json' => ['Nao foi possivel criar diretorio para gravar JSON.'],
            ]);
        }

        $jsonGroups = array_map(function (array $group): array {
            return [
                'meeting_id' => (string) data_get($group, 'meeting_id', '-'),
                'group_name' => (string) data_get($group, 'group_name', ''),
                'link_url' => (string) data_get($group, 'link_url', '#'),
                'schedule' => (array) data_get($group, 'schedule', []),
            ];
        }, $groups);

        $payload = json_encode($jsonGroups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($payload)) {
            throw ValidationException::withMessages([
                'json' => ['Falha ao serializar JSON da curadoria.'],
            ]);
        }

        $tempPath = $path.'.tmp';
        $bytes = @file_put_contents($tempPath, $payload.PHP_EOL, LOCK_EX);
        if ($bytes === false) {
            throw ValidationException::withMessages([
                'json' => ['Falha ao escrever arquivo JSON temporario.'],
            ]);
        }

        if (! @rename($tempPath, $path)) {
            @unlink($tempPath);
            throw ValidationException::withMessages([
                'json' => ['Falha ao concluir escrita atomica do JSON.'],
            ]);
        }

        $syncSummary = array_merge($summary, [
            'source' => 'sheet_db_sync',
            'json_path' => $path,
            'synced_at' => now()->toIso8601String(),
        ]);

        Cache::put(
            (string) config('na_virtual.curated_groups.export_summary_cache_key', 'na.virtual.curated_groups.last_export_summary'),
            $syncSummary,
            now()->addMinutes(max(1, (int) config('na_virtual.curated_groups.export_summary_ttl_minutes', 180)))
        );

        return [
            'written' => count($jsonGroups),
            'total' => (int) data_get($summary, 'total_sheet_valid_pairs', count($jsonGroups)),
            'conflicts' => is_array($conflicts) ? $conflicts : [],
        ];
    }
}