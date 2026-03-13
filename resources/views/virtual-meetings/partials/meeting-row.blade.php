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
    $timeRange = ($startAt ? $startAt->format('H:i') : '--:--') . ' - ' . ($endAt ? $endAt->format('H:i') : '--:--');

    $typeDescription = null;
    if (is_string($typeLabel) && trim($typeLabel) !== '') {
        $typeDescription = data_get($groupedBadges ?? [], Str::lower(Str::ascii($typeLabel)));
    }

    $normalizedType = is_string($typeLabel) ? Str::lower(Str::ascii($typeLabel)) : '';
    $typeBadgeClass = str_contains($normalizedType, 'aberta')
        ? 'vm-badge-type-open'
        : (str_contains($normalizedType, 'fechada')
            ? 'vm-badge-type-closed'
            : (str_contains($normalizedType, 'estudo')
                ? 'vm-badge-type-study'
                : 'vm-badge-type-theme'));
@endphp

<article class="vm-card-shell vm-meeting-row">
    <div class="min-w-0 flex-1">
        <p class="vm-time">{{ $timeRange }}</p>
        <h4 class="vm-title vm-title-clamp-2 mt-1">{{ $name }}</h4>
        <p class="vm-meta mt-1">{{ ucfirst($platform) }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            @if ($typeLabel)
                <span class="vm-badge {{ $typeBadgeClass }}">{{ ucfirst($typeLabel) }}</span>
            @endif
            @if ($typeDescription)
                <span class="vm-meta">{{ $typeDescription }}</span>
            @endif
        </div>
    </div>

    <div class="vm-meeting-row-actions shrink-0">
        <span class="vm-status vm-status-truncate truncate">{{ $statusText ?: 'Horario a confirmar' }}</span>
        @if ($meetingUrl)
            <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="vm-btn vm-btn-primary min-w-[7.25rem]">
                Entrar
            </a>
        @else
            <span class="vm-link-disabled">Sem link</span>
        @endif
    </div>
</article>
