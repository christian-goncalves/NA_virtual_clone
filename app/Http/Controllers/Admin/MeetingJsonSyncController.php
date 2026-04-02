<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CuratedMeetingJsonSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class MeetingJsonSyncController extends Controller
{
    public function __invoke(CuratedMeetingJsonSyncService $syncService): RedirectResponse
    {
        try {
            $result = $syncService->syncFromSheetToJson();

            return redirect()
                ->route('admin.metrics.meetings.index')
                ->with('meeting_json_sync_success', sprintf(
                    'JSON atualizado com sucesso: %d/%d grupos gravados.',
                    (int) data_get($result, 'written', 0),
                    (int) data_get($result, 'total', 0)
                ));
        } catch (ValidationException $exception) {
            report($exception);

            return redirect()
                ->route('admin.metrics.meetings.index')
                ->with('meeting_pdf_error', (string) data_get($exception->errors(), 'sheet.0', data_get($exception->errors(), 'json.0', 'Falha ao sincronizar JSON da planilha.')));
        } catch (Throwable $exception) {
            Log::error('json_sync_failed', [
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            return redirect()
                ->route('admin.metrics.meetings.index')
                ->with('meeting_pdf_error', 'Falha inesperada ao sincronizar JSON.');
        }
    }
}