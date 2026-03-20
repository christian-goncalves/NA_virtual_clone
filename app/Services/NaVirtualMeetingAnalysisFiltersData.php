<?php

namespace App\Services;

class NaVirtualMeetingAnalysisFiltersData
{
    public function __construct(
        public readonly ?string $searchName,
        public readonly ?string $weekday,
        public readonly ?string $timeStart,
        public readonly ?string $timeEnd,
        public readonly ?string $meetingPlatform,
        public readonly ?bool $isOpen,
        public readonly ?bool $isStudy,
        public readonly ?bool $isLgbt,
        public readonly ?bool $isWomen,
        public readonly ?bool $isHybrid,
        public readonly ?bool $isActive,
        public readonly ?string $clickBlock,
        public readonly string $clickWindow,
        public readonly ?string $clickFrom,
        public readonly ?string $clickTo,
        public readonly string $sortBy,
        public readonly string $sortDirection,
        public readonly int $perPage,
        public readonly int $page,
        public readonly bool $isDataTable,
        public readonly ?int $draw,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search_name' => $this->searchName,
            'weekday' => $this->weekday,
            'time_start' => $this->timeStart,
            'time_end' => $this->timeEnd,
            'meeting_platform' => $this->meetingPlatform,
            'is_open' => $this->isOpen,
            'is_study' => $this->isStudy,
            'is_lgbt' => $this->isLgbt,
            'is_women' => $this->isWomen,
            'is_hybrid' => $this->isHybrid,
            'is_active' => $this->isActive,
            'click_block' => $this->clickBlock,
            'click_window' => $this->clickWindow,
            'click_from' => $this->clickFrom,
            'click_to' => $this->clickTo,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDirection,
            'per_page' => $this->perPage,
            'page' => $this->page,
            'is_datatable' => $this->isDataTable,
            'draw' => $this->draw,
        ];
    }
}
