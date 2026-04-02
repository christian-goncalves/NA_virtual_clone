<?php

namespace App\Services;

class CuratedMeetingPdfLayoutService
{
    private const PT_PER_PX = 72 / 96;
    private const PX_PER_PT = 96 / 72;

    private const MARGIN_TOP_PT = 16;
    private const MARGIN_RIGHT_PT = 14;
    private const MARGIN_BOTTOM_PT = 16;
    private const MARGIN_LEFT_PT = 14;

    private const HEADER_HEIGHT_PX = 56.89;
    private const HEADER_LEFT_PADDING_X_PX = 16.0;
    private const HEADER_RIGHT_PADDING_RIGHT_PX = 13.3333;
    private const HEADER_BOTTOM_GAP_PT = 0;

    private const LEGEND_TOP_GAP_PT = 0;
    private const LEGEND_HEIGHT_PT = 14;

    private const COL_GROUP_PX = 178.96;
    private const COL_LINK_PX = 90.31;
    private const COL_DAY_PX = 54.24;
    private const WEEKDAY_COUNT = 7;

    private const TABLE_HEADER_HEIGHT_PX = 20.44;
    private const CELL_PADDING_PX = 4;
    private const ROW_BASE_HEIGHT_PT = 26;
    private const SLOT_EXTRA_HEIGHT_PT = 10;

    private const MIN_PAGE_WIDTH_PT = 842;
    private const MIN_PAGE_HEIGHT_PT = 595;
    private const MAX_PAGE_HEIGHT_PT = 2200;

    private const LOGO_HEIGHT_PT = 34;

    /**
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<string, mixed>
     */
    public function build(array $groups): array
    {
        $headerHeightPt = self::HEADER_HEIGHT_PX * self::PT_PER_PX;
        $headerLeftPaddingXPt = self::HEADER_LEFT_PADDING_X_PX * self::PT_PER_PX;
        $headerRightPaddingRightPt = self::HEADER_RIGHT_PADDING_RIGHT_PX * self::PT_PER_PX;
        $tableHeaderHeightPt = self::TABLE_HEADER_HEIGHT_PX * self::PT_PER_PX;
        $cellPaddingPt = self::CELL_PADDING_PX * self::PT_PER_PX;

        $colGroupPt = self::COL_GROUP_PX * self::PT_PER_PX;
        $colLinkPt = self::COL_LINK_PX * self::PT_PER_PX;
        $colDayPt = self::COL_DAY_PX * self::PT_PER_PX;

        $tableWidthPt = $colGroupPt + $colLinkPt + ($colDayPt * self::WEEKDAY_COUNT);
        $contentWidthPt = $tableWidthPt;

        $rowHeights = [];
        $tableBodyHeightPt = 0;

        foreach ($groups as $index => $group) {
            $schedule = is_array(data_get($group, 'schedule')) ? (array) data_get($group, 'schedule') : [];
            $maxSlots = 1;
            foreach (['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'] as $weekday) {
                $entries = data_get($schedule, $weekday, []);
                $count = is_array($entries) ? count($entries) : 0;
                if ($count > $maxSlots) {
                    $maxSlots = $count;
                }
            }

            $rowHeight = self::ROW_BASE_HEIGHT_PT + max(0, $maxSlots - 1) * self::SLOT_EXTRA_HEIGHT_PT;
            $rowHeights[$index] = $rowHeight;
            $tableBodyHeightPt += $rowHeight;
        }

        $tableHeightPt = $tableHeaderHeightPt + $tableBodyHeightPt;
        $contentHeightPt = $headerHeightPt + self::HEADER_BOTTOM_GAP_PT + $tableHeightPt + self::LEGEND_TOP_GAP_PT + self::LEGEND_HEIGHT_PT;

        $pageWidthPt = max(self::MIN_PAGE_WIDTH_PT, (int) ceil($tableWidthPt + self::MARGIN_LEFT_PT + self::MARGIN_RIGHT_PT));
        $pageHeightPt = max(self::MIN_PAGE_HEIGHT_PT, (int) ceil($contentHeightPt + self::MARGIN_TOP_PT + self::MARGIN_BOTTOM_PT));
        $pageHeightPt = min($pageHeightPt, self::MAX_PAGE_HEIGHT_PT);

        return [
            'margins' => [
                'top_pt' => self::MARGIN_TOP_PT,
                'right_pt' => self::MARGIN_RIGHT_PT,
                'bottom_pt' => self::MARGIN_BOTTOM_PT,
                'left_pt' => self::MARGIN_LEFT_PT,
            ],
            'page_width_pt' => $pageWidthPt,
            'page_height_pt' => $pageHeightPt,
            'preview_width_px' => (int) ceil($pageWidthPt * self::PX_PER_PT),
            'preview_height_px' => (int) ceil($pageHeightPt * self::PX_PER_PT),
            'content_width_pt' => $contentWidthPt,
            'table_width_pt' => $tableWidthPt,
            'header_height_pt' => $headerHeightPt,
            'header_bottom_gap_pt' => self::HEADER_BOTTOM_GAP_PT,
            'header_left_padding_x_pt' => $headerLeftPaddingXPt,
            'header_right_padding_right_pt' => $headerRightPaddingRightPt,
            'legend_top_gap_pt' => self::LEGEND_TOP_GAP_PT,
            'legend_height_pt' => self::LEGEND_HEIGHT_PT,
            'logo_height_pt' => self::LOGO_HEIGHT_PT,
            'col_group_pt' => $colGroupPt,
            'col_link_pt' => $colLinkPt,
            'col_day_pt' => $colDayPt,
            'table_header_height_pt' => $tableHeaderHeightPt,
            'cell_padding_pt' => $cellPaddingPt,
            'row_heights_pt' => $rowHeights,
        ];
    }

    public function logoDataUri(): ?string
    {
        $path = public_path('images/NALogoBrancaSemFundo (1).png');
        if (! is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($path) : null;
        $mime = is_string($mime) && $mime !== '' ? $mime : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }
}
