<?php

namespace App\Jobs;

use App\Services\NaVirtualMeetingSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncNaVirtualMeetingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(NaVirtualMeetingSyncService $syncService): void
    {
        Log::info('SyncNaVirtualMeetingsJob iniciado.');

        try {
            $result = $syncService->sync();

            Log::info('SyncNaVirtualMeetingsJob finalizado com sucesso.', $result);
        } catch (Throwable $e) {
            Log::error('SyncNaVirtualMeetingsJob falhou.', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

