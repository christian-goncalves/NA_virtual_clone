<?php

namespace App\Console\Commands;

use App\Services\NaVirtualMeetingSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncNaVirtualMeetingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'na:sync-virtual-meetings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza reuniões virtuais de NA da fonte oficial para o banco local.';

    /**
     * Execute the console command.
     */
    public function handle(NaVirtualMeetingSyncService $syncService): int
    {
        $this->info('Iniciando sincronização de reuniões virtuais...');

        try {
            $result = $syncService->sync();
        } catch (Throwable $e) {
            report($e);
            $this->error('Falha na sincronização: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Resumo:');
        $this->line('Fonte: '.(string) $result['source_url']);
        $this->line('Total encontrado: '.(int) $result['total_found']);
        $this->line('Total criado: '.(int) $result['total_created']);
        $this->line('Total atualizado: '.(int) $result['total_updated']);
        $this->line('Total inativado: '.(int) $result['total_inactivated']);
        $this->newLine();
        $this->info('Sincronização concluída.');

        return self::SUCCESS;
    }
}
