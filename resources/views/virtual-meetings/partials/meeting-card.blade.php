@php
    use App\Support\VirtualMeetingMetaFormatter;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $meeting = data_get($meetingData, 'meeting');
    $startAt = data_get($meetingData, 'start_at');
    $endAt = data_get($meetingData, 'end_at');
    $statusText = data_get($meetingData, 'status_text');

    $name = $meeting?->name ?: 'Grupo sem nome';
    $platform = $meeting?->meeting_platform ?: 'Plataforma nao informada';
    $meetingUrl = $meeting?->meeting_url;
    $meetingId = data_get($meetingData, 'meeting.meeting_id');
    $meetingPassword = data_get($meetingData, 'meeting.meeting_password');
    $typeLabel = $meeting?->type_label;
    $formatLabels = is_array($meeting?->format_labels) ? $meeting->format_labels : [];
    $isStudyMeeting = (bool) data_get($meeting, 'is_study', false);
    $isOpenMeeting = (bool) data_get($meeting, 'is_open', false);
    $endsInMinutes = data_get($meetingData, 'ends_in_minutes');

    $displayMeetingId = is_string($meetingId) ? trim($meetingId) : null;
    $displayMeetingPassword = is_string($meetingPassword) ? trim($meetingPassword) : null;

    $startAtCarbon = null;
    if ($startAt instanceof Carbon) {
        $startAtCarbon = $startAt;
    } elseif ($startAt) {
        $startAtCarbon = Carbon::parse($startAt);
    }

    $minutesFromStart = null;
    if ($startAtCarbon instanceof Carbon) {
        $nowAtMeetingTz = Carbon::now($startAtCarbon->getTimezone());
        $startMinutesOfDay = ((int) $startAtCarbon->format('H') * 60) + (int) $startAtCarbon->format('i');
        $nowMinutesOfDay = ((int) $nowAtMeetingTz->format('H') * 60) + (int) $nowAtMeetingTz->format('i');

        $minutesFromStart = $nowMinutesOfDay - $startMinutesOfDay;

        if ($minutesFromStart < 0) {
            $minutesFromStart += 1440;
        }
    }

    $justStarted = is_int($minutesFromStart)
        && $minutesFromStart >= 0
        && $minutesFromStart <= 15;

    $runningBadgeText = $justStarted ? 'Começando Agora' : 'Em andamento';
    $runningBadgeClass = $justStarted ? 'bg-[#ef4444] text-white' : 'vm-badge-status';
    $runningBadgeTextClass = $justStarted ? '' : 'uppercase tracking-wide';
    $cardToneClass = $justStarted
        ? 'border-[#ef4444]/35 border-l-[#ef4444] shadow-[0_8px_24px_rgba(239,68,68,0.18)]'
        : 'border-blue-100 border-l-[hsl(var(--na-blue))] hover:shadow-lg';
    $ctaClass = $justStarted ? 'bg-[#ef4444] hover:bg-[#dc2626] text-white' : 'vm-btn-primary';
    $ctaLabel = $justStarted ? 'Entrar Agora' : 'Entrar';

    $timeRange = ($startAt ? $startAt->format('H:i') : '--:--') . ' - ' . ($endAt ? $endAt->format('H:i') : '--:--');

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

    $statusClass = (is_numeric($endsInMinutes) && (int) $endsInMinutes <= 30)
        ? 'vm-status-warning'
        : 'vm-status-neutral';

    $metaLine = VirtualMeetingMetaFormatter::buildMetaLine($meetingData);
    $sourceSection = isset($sourceSection) && is_string($sourceSection) ? $sourceSection : 'running';

    $shareText = is_string($meetingUrl) && trim($meetingUrl) !== ''
        ? trim($meetingUrl)
        : implode(' | ', array_values(array_filter([
            $name,
            $timeRange,
            ucfirst($platform),
            is_string($displayMeetingId) && trim($displayMeetingId) !== '' ? 'ID: ' . trim($displayMeetingId) : null,
            is_string($displayMeetingPassword) && trim($displayMeetingPassword) !== '' ? 'Senha: ' . trim($displayMeetingPassword) : null,
        ])));
@endphp

<article class="vm-card-shell vm-meeting-card w-full min-h-[215px] h-full rounded-xl border border-l-4 p-4 transition-all {{ $cardToneClass }}">
    <div class="vm-running-top gap-2">
        <span class="vm-badge {{ $runningBadgeClass }} shrink-0 {{ $runningBadgeTextClass }}">
            @if ($justStarted)
                <span class="h-2 w-2 rounded-full bg-white/85"></span>
            @endif
            {{ $runningBadgeText }}
        </span>
        <span class="vm-status {{ $statusClass }} vm-status-truncate truncate text-[11px] font-bold">{{ $statusText ?: 'Status indisponivel' }}</span>
    </div>

    <h3 class="vm-title vm-title-clamp-2 text-sm leading-snug">{{ $name }}</h3>

    @if ($typeBadgeLabel)
        <div class="flex flex-wrap items-center gap-1.5">
            @include('virtual-meetings.partials.type-badge', ['badgeClass' => $typeBadgeClass, 'badgeLabel' => $typeBadgeLabel, 'badgeDescription' => '', 'badgeDescriptionExplicit' => false])
        </div>
    @endif

    <div class="space-y-1">
        <p class="vm-time">{{ $timeRange }}</p>
        <p class="vm-meta truncate">{{ $metaLine }}</p>
    </div>

    <div class="vm-card-actions mt-auto pt-1">
        @if ($meetingUrl)
            <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="vm-btn vm-card-cta-main py-2.5 text-xs {{ $ctaClass }}" data-metrics-event="category_click" data-source-section="{{ $sourceSection }}" data-meeting-name="{{ $name }}" data-metrics-route="{{ request()->path() }}">
                <i class="fa-solid fa-arrow-right-to-bracket text-[0.72rem]" aria-hidden="true"></i>
                {{ $ctaLabel }}
            </a>
        @else
            <span class="vm-link-disabled">Link indisponivel</span>
        @endif
        <button
            type="button"
            class="vm-card-aux-btn"
            aria-label="Compartilhar reuniao"
            data-vm-share-button
            data-share-title="{{ $name }}"
            data-share-text="{{ $shareText }}"
            data-share-url="{{ $meetingUrl ?? '' }}"
        >
            <i class="fa-solid fa-share-nodes text-[0.78rem]" aria-hidden="true"></i>
        </button>
    </div>
</article>
