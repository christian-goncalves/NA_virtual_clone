@php
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
    $endsInMinutes = data_get($meetingData, 'ends_in_minutes');
    $timeRange = ($startAt ? $startAt->format('H:i') : '--:--') . ' - ' . ($endAt ? $endAt->format('H:i') : '--:--');

    $normalizedType = is_string($typeLabel) ? Str::lower(Str::ascii($typeLabel)) : '';
    $normalizedFormats = collect($formatLabels)
        ->filter(fn ($format) => is_string($format) && trim($format) !== '')
        ->map(fn ($format) => Str::lower(Str::ascii($format)))
        ->values()
        ->all();
    $typeBadgeClass = null;
    $typeBadgeLabel = null;
    if (str_contains($normalizedType, 'estudo') || in_array('estudo', $normalizedFormats, true) || (bool) $meeting?->is_study) {
        $typeBadgeClass = 'vm-badge-type-study';
        $typeBadgeLabel = 'Estudo';
    } elseif (str_contains($normalizedType, 'fechada') || in_array('fechada', $normalizedFormats, true) || in_array('fechado', $normalizedFormats, true)) {
        $typeBadgeClass = 'vm-badge-type-closed';
        $typeBadgeLabel = 'Fechada';
    } elseif (str_contains($normalizedType, 'aberta') || in_array('aberta', $normalizedFormats, true) || in_array('aberto', $normalizedFormats, true) || (bool) $meeting?->is_open) {
        $typeBadgeClass = 'vm-badge-type-open';
        $typeBadgeLabel = 'Aberta';
    } elseif ($normalizedType === '') {
        $typeBadgeClass = 'vm-badge-type-closed';
        $typeBadgeLabel = 'Fechada';
    }

    $statusClass = (is_numeric($endsInMinutes) && (int) $endsInMinutes <= 30)
        ? 'vm-status-warning'
        : 'vm-status-neutral';

    $metaParts = [];
    if (is_string($platform) && trim($platform) !== '') {
        $metaParts[] = ucfirst($platform);
    }
    if (is_string($meetingId) && trim($meetingId) !== '') {
        $metaParts[] = 'ID: ' . trim($meetingId);
    }
    if (is_string($meetingPassword) && trim($meetingPassword) !== '') {
        $metaParts[] = 'Senha: ' . trim($meetingPassword);
    }
    $metaLine = $metaParts !== [] ? implode(' · ', $metaParts) : 'Plataforma nao informada';

    $shareText = is_string($meetingUrl) && trim($meetingUrl) !== ''
        ? trim($meetingUrl)
        : implode(' | ', array_values(array_filter([
            $name,
            $timeRange,
            ucfirst($platform),
            is_string($meetingId) && trim($meetingId) !== '' ? 'ID: ' . trim($meetingId) : null,
            is_string($meetingPassword) && trim($meetingPassword) !== '' ? 'Senha: ' . trim($meetingPassword) : null,
        ])));
@endphp

<article class="vm-card-shell vm-meeting-card w-full min-h-[215px] h-full rounded-xl border border-blue-100 border-l-4 border-l-[hsl(var(--na-blue))] p-4 transition-all hover:shadow-lg">
    <div class="vm-running-top gap-2">
        <span class="vm-badge vm-badge-status shrink-0 uppercase tracking-wide">
            Em andamento
        </span>
        <span class="vm-status {{ $statusClass }} vm-status-truncate truncate text-[11px] font-bold">{{ $statusText ?: 'Status indisponivel' }}</span>
    </div>

    <h3 class="vm-title vm-title-clamp-2 text-sm leading-snug">{{ $name }}</h3>

    @if ($typeBadgeLabel)
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="vm-badge {{ $typeBadgeClass }}">{{ $typeBadgeLabel }}</span>
        </div>
    @endif

    <div class="space-y-1">
        <p class="vm-time">{{ $timeRange }}</p>
        <p class="vm-meta truncate">{{ $metaLine }}</p>
    </div>

    <div class="vm-card-actions mt-auto pt-1">
        @if ($meetingUrl)
            <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="vm-btn vm-btn-primary vm-card-cta-main py-2.5 text-xs">
                <i class="fa-solid fa-arrow-right-to-bracket text-[0.72rem]" aria-hidden="true"></i>
                Entrar
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
