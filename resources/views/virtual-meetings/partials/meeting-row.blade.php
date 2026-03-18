@php
    use Illuminate\Support\Str;

    $meeting = data_get($meetingData, 'meeting');
    $startAt = data_get($meetingData, 'start_at');
    $endAt = data_get($meetingData, 'end_at');
    $statusText = data_get($meetingData, 'status_text');

    $name = $meeting?->name ?: 'Grupo sem nome';
    $platform = $meeting?->meeting_platform ?: 'Plataforma nao informada';
    $meetingUrl = $meeting?->meeting_url;
    $typeLabel = $meeting?->type_label;
    $formatLabels = is_array($meeting?->format_labels) ? $meeting->format_labels : [];
    $isStudyMeeting = (bool) data_get($meeting, 'is_study', false);
    $isOpenMeeting = (bool) data_get($meeting, 'is_open', false);
    $timeRange = ($startAt ? $startAt->format('H:i') : '--:--') . ' - ' . ($endAt ? $endAt->format('H:i') : '--:--');
    $sourceSection = isset($sourceSection) && is_string($sourceSection) ? $sourceSection : 'unknown';

    $normalizedType = is_string($typeLabel) ? Str::lower(Str::ascii($typeLabel)) : '';
    $normalizedFormats = collect($formatLabels)
        ->filter(fn ($format) => is_string($format) && trim($format) !== '')
        ->map(fn ($format) => Str::lower(Str::ascii($format)))
        ->values()
        ->all();

    $typeBadgeClass = null;
    $typeBadgeLabel = null;
    if (str_contains($normalizedType, 'estudo') || in_array('estudo', $normalizedFormats, true) || $isStudyMeeting) {
        $typeBadgeClass = 'vm-badge-type-study';
        $typeBadgeLabel = 'Estudo';
    } elseif (str_contains($normalizedType, 'fechada') || in_array('fechada', $normalizedFormats, true) || in_array('fechado', $normalizedFormats, true)) {
        $typeBadgeClass = 'vm-badge-type-closed';
        $typeBadgeLabel = 'Fechada';
    } elseif (str_contains($normalizedType, 'aberta') || in_array('aberta', $normalizedFormats, true) || in_array('aberto', $normalizedFormats, true) || $isOpenMeeting) {
        $typeBadgeClass = 'vm-badge-type-open';
        $typeBadgeLabel = 'Aberta';
    } elseif ($normalizedType === '') {
        $typeBadgeClass = 'vm-badge-type-closed';
        $typeBadgeLabel = 'Fechada';
    }
@endphp

<article class="vm-card-shell vm-meeting-row">
    <div class="min-w-0 flex-1">
        <p class="vm-time">{{ $timeRange }}</p>
        <h4 class="vm-title vm-title-clamp-2 mt-1">{{ $name }}</h4>
        <p class="vm-meta mt-1">{{ ucfirst($platform) }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            @if ($typeBadgeLabel)
                @include('virtual-meetings.partials.type-badge', ['badgeClass' => $typeBadgeClass, 'badgeLabel' => $typeBadgeLabel, 'badgeDescription' => '', 'badgeDescriptionExplicit' => false])
            @endif
        </div>
    </div>

    <div class="vm-meeting-row-actions shrink-0">
        <span class="vm-status vm-status-truncate truncate">{{ $statusText ?: 'Horario a confirmar' }}</span>
        @if ($meetingUrl)
            <a
                href="{{ $meetingUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="vm-btn vm-btn-primary min-w-[7.25rem]"
                data-metrics-event="category_click"
                data-source-section="{{ $sourceSection }}"
                data-meeting-name="{{ $name }}"
                data-metrics-route="{{ request()->path() }}"
            >
                <i class="fa-solid fa-arrow-right-to-bracket text-[0.72rem]" aria-hidden="true"></i>
                Entrar
            </a>
        @else
            <span class="vm-link-disabled">Sem link</span>
        @endif
    </div>
</article>
