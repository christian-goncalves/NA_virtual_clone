<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CuratedMeetingJsonSourceService;
use App\Services\CuratedMeetingPdfLayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class MeetingPdfPreviewController extends Controller
{
    public function __invoke(CuratedMeetingJsonSourceService $sourceService, CuratedMeetingPdfLayoutService $layoutService): Response|RedirectResponse
    {
        try {
            $groups = $sourceService->loadValidatedGroups();
            $summary = $sourceService->lastSummary();
            $layout = $layoutService->build($groups);
            $logoDataUri = $layoutService->logoDataUri();
        } catch (ValidationException $exception) {
            report($exception);

            return redirect()
                ->route('admin.metrics.meetings.index')
                ->with('meeting_pdf_error', 'Falha ao carregar preview: JSON de curadoria invalido.');
        }

        return response()
            ->view('admin.metrics.meeting-pdf-preview', [
                'groups' => $groups,
                'summary' => $summary,
                'layout' => $layout,
                'logoDataUri' => $logoDataUri,
                'generatedAt' => now(),
                'weekdayColumns' => [
                    'segunda' => '2ª',
                    'terca' => '3ª',
                    'quarta' => '4ª',
                    'quinta' => '5ª',
                    'sexta' => '6ª',
                    'sabado' => 'SÁB',
                    'domingo' => 'DOM',
                ],
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}