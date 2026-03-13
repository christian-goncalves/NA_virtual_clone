<div class="vm-section-shell p-5 sm:p-6">
    <div class="vm-section-header">
        <h3 class="vm-section-title"><i class="fa-regular fa-clock mr-1 text-[0.8rem] text-[hsl(var(--na-blue))]" aria-hidden="true"></i>Próximas reuniões</h3>
        <span class="vm-counter-badge vm-counter-badge-upcoming">{{ $upcomingCount }} reuniões</span>
    </div>

    @if (!empty($groupedBadges))
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-[hsl(var(--muted-foreground))]">Tipos:</span>
            @foreach ($groupedBadges as $badgeLabel => $badgeDescription)
                @php
                    $normalizedBadge = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $badgeLabel));
                    $badgeClass = str_contains($normalizedBadge, 'aberta') ? 'vm-badge-type-open' : (str_contains($normalizedBadge, 'fechada') ? 'vm-badge-type-closed' : (str_contains($normalizedBadge, 'estudo') ? 'vm-badge-type-study' : 'vm-badge-type-theme'));
                @endphp
                <span class="vm-badge {{ $badgeClass }}">{{ ucfirst($badgeLabel) }} - {{ $badgeDescription }}</span>
            @endforeach
        </div>
    @endif

    @if ($upcomingMeetings->isEmpty())
        <p class="vm-empty-state px-4 py-6">
            Nenhuma reuniao futura encontrada no momento.
        </p>
    @else
        <div class="space-y-3">
            @foreach ($upcomingMeetings as $meetingData)
                @include('virtual-meetings.partials.meeting-row', ['meetingData' => $meetingData])
            @endforeach
        </div>
    @endif
</div>
