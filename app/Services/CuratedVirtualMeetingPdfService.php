<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CuratedVirtualMeetingPdfService
{
    public function __construct(
        private readonly CuratedMeetingJsonSourceService $sourceService,
        private readonly CuratedMeetingPdfLayoutService $layoutService
    ) {}

    /**
     * @throws ValidationException
     */
    public function download(): Response
    {
        $groups = $this->sourceService->loadValidatedGroups();
        $summary = $this->sourceService->lastSummary();
        $layout = $this->layoutService->build($groups);
        $logoDataUri = $this->layoutService->logoDataUri();

        $exportPageWidthPt = (float) data_get($layout, 'page_width_pt', 842);
        $exportPageHeightPt = (float) data_get($layout, 'page_height_pt', 595);

        Cache::put(
            (string) config('na_virtual.curated_groups.export_summary_cache_key', 'na.virtual.curated_groups.last_export_summary'),
            $summary,
            now()->addMinutes(max(1, (int) config('na_virtual.curated_groups.export_summary_ttl_minutes', 180)))
        );

        $pdf = Pdf::loadView('admin.metrics.pdf.curated-meetings-weekly', [
            'groups' => $groups,
            'summary' => $summary,
            'layout' => $layout,
            'logoDataUri' => $logoDataUri,
            'generatedAt' => now(),
            'exportPageWidthPt' => $exportPageWidthPt,
            'exportPageHeightPt' => $exportPageHeightPt,
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
            ->setOption('isRemoteEnabled', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setPaper([
                0,
                0,
                $exportPageWidthPt,
                $exportPageHeightPt,
            ]);

        return $pdf->download('lista-de-grupos-'.now()->format('Ymd-His').'.pdf');
    }
}
