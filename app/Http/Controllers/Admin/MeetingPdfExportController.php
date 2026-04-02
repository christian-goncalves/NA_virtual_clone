<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CuratedVirtualMeetingPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MeetingPdfExportController extends Controller
{
    public function __invoke(CuratedVirtualMeetingPdfService $pdfService): Response|RedirectResponse
    {
        try {
            return $pdfService->download();
        } catch (ValidationException $exception) {
            report($exception);

            return redirect()
                ->route('admin.metrics.meetings.index')
                ->with('meeting_pdf_error', 'Falha ao gerar PDF: JSON de curadoria invalido ou incompleto.');
        } catch (Throwable $exception) {
            Log::error('pdf_generation_failed', [
                'message' => $exception->getMessage(),
            ]);

            report($exception);

            return redirect()
                ->route('admin.metrics.meetings.index')
                ->with('meeting_pdf_error', 'Falha inesperada ao gerar PDF. Tente novamente.');
        }
    }
}

