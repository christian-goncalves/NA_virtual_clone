@php
    $now = \Illuminate\Support\Carbon::now();
    $startWindow = $now->copy()->addMinutes(60);
    $endWindow = $now->copy()->addMinutes(360);

    $windowUpcomingMeetings = $upcomingMeetings
        ->filter(function ($meetingData) use ($startWindow, $endWindow) {
            $startAt = data_get($meetingData, 'start_at');

            if (!$startAt) {
                return false;
            }

            if (!$startAt instanceof \Illuminate\Support\Carbon) {
                $startAt = \Illuminate\Support\Carbon::parse($startAt);
            }

            return $startAt->gt($startWindow) && $startAt->lte($endWindow);
        })
        ->values();

    $initialLimit = 10;
    $visibleUpcomingMeetings = $windowUpcomingMeetings->take($initialLimit);
    $extraUpcomingMeetings = $windowUpcomingMeetings->slice($initialLimit)->values();
    $hasMoreUpcoming = $extraUpcomingMeetings->isNotEmpty();
    $windowUpcomingCount = $windowUpcomingMeetings->count();
@endphp

<div class="vm-section-shell p-5 sm:p-6">
    <div class="vm-section-header">
        <h3 class="vm-section-title"><i class="fa-regular fa-clock mr-1 text-[0.8rem] text-[hsl(var(--na-blue))]" aria-hidden="true"></i>Próximas reuniões</h3>
        <span class="vm-counter-badge vm-counter-badge-upcoming">{{ $windowUpcomingCount }} reuniões</span>
    </div>

    @if (!empty($groupedBadges))
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-[hsl(var(--muted-foreground))]">Tipos:</span>
            @foreach ($groupedBadges as $badgeLabel => $badgeDescription)
                @php
                    $normalizedBadge = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $badgeLabel));
                    $badgeClass = str_contains($normalizedBadge, 'aberta') ? 'vm-badge-type-open' : (str_contains($normalizedBadge, 'fechada') ? 'vm-badge-type-closed' : (str_contains($normalizedBadge, 'estudo') ? 'vm-badge-type-study' : 'vm-badge-type-theme'));
                @endphp
                @include('virtual-meetings.partials.type-badge', ['badgeClass' => $badgeClass, 'badgeLabel' => ucfirst($badgeLabel), 'badgeDescription' => $badgeDescription, 'badgeDescriptionExplicit' => true])
            @endforeach
        </div>
    @endif

    @if ($windowUpcomingMeetings->isEmpty())
        <p class="vm-empty-state px-4 py-6">
            Nenhuma reuniao futura encontrada no momento.
        </p>
    @else
        <div class="space-y-3">
            @foreach ($visibleUpcomingMeetings as $meetingData)
                @include('virtual-meetings.partials.meeting-row', ['meetingData' => $meetingData, 'sourceSection' => 'upcoming'])
            @endforeach

            @if ($hasMoreUpcoming)
                <div id="upcoming-extra-list" class="hidden space-y-3" data-upcoming-extra>
                    @foreach ($extraUpcomingMeetings as $meetingData)
                        @include('virtual-meetings.partials.meeting-row', ['meetingData' => $meetingData, 'sourceSection' => 'upcoming'])
                    @endforeach

                    <div class="pt-2 text-center">
                        <button type="button" class="vm-btn vm-btn-secondary" data-upcoming-toggle-collapse>
                            Ver menos
                        </button>
                    </div>
                </div>

                <div class="pt-2 text-center" data-upcoming-toggle-expand-wrap>
                    <button type="button" class="vm-btn vm-btn-secondary" data-upcoming-toggle-expand>
                        Ver mais
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>

@if ($hasMoreUpcoming)
    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const extraList = document.querySelector('[data-upcoming-extra]');
                const expandWrap = document.querySelector('[data-upcoming-toggle-expand-wrap]');
                const expandBtn = document.querySelector('[data-upcoming-toggle-expand]');
                const collapseBtn = document.querySelector('[data-upcoming-toggle-collapse]');

                if (!extraList || !expandWrap || !expandBtn || !collapseBtn) {
                    return;
                }

                expandBtn.addEventListener('click', function () {
                    extraList.classList.remove('hidden');
                    expandWrap.classList.add('hidden');
                });

                collapseBtn.addEventListener('click', function () {
                    extraList.classList.add('hidden');
                    expandWrap.classList.remove('hidden');
                });
            });
        </script>
    @endonce
@endif
