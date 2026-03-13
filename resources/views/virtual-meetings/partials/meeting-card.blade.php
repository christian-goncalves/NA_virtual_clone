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
    $formats = is_array($meeting?->format_labels) ? $meeting->format_labels : [];
    $endsInMinutes = data_get($meetingData, 'ends_in_minutes');
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

    $statusClass = (is_numeric($endsInMinutes) && (int) $endsInMinutes <= 30)
        ? 'vm-status-warning'
        : 'vm-status-neutral';
@endphp

<article class="vm-card-shell vm-meeting-card">
    <div class="flex items-start justify-between gap-3">
        <h3 class="vm-title vm-title-clamp-2">{{ $name }}</h3>
        <span class="vm-badge vm-badge-status shrink-0 uppercase tracking-wide">
            Em andamento
        </span>
    </div>

    <div class="space-y-1">
        <p class="vm-time">{{ $timeRange }}</p>
        <p class="vm-meta">{{ ucfirst($platform) }}</p>
    </div>

    @if ($typeLabel)
        <div class="flex flex-wrap items-center gap-2">
            <span class="vm-badge {{ $typeBadgeClass }}">{{ ucfirst($typeLabel) }}</span>
            @if ($typeDescription)
                <span class="vm-meta">{{ $typeDescription }}</span>
            @endif
        </div>
    @endif

    @if (!empty($formats))
        <div class="flex flex-wrap gap-2">
            @foreach ($formats as $format)
                @if (is_string($format) && trim($format) !== '')
                    @php
                        $normalizedFormat = Str::lower(Str::ascii($format));
                        $formatBadgeClass = str_contains($normalizedFormat, 'aberta')
                            ? 'vm-badge-type-open'
                            : (str_contains($normalizedFormat, 'fechada')
                                ? 'vm-badge-type-closed'
                                : (str_contains($normalizedFormat, 'estudo')
                                    ? 'vm-badge-type-study'
                                    : (str_contains($normalizedFormat, 'tematica') ? 'vm-badge-type-theme' : 'vm-format-badge')));
                    @endphp
                    <span class="{{ $formatBadgeClass }}">{{ $format }}</span>
                @endif
            @endforeach
        </div>
    @endif

    <div class="mt-auto flex items-center justify-between gap-3 pt-2">
        <p class="vm-status {{ $statusClass }} vm-status-truncate truncate">{{ $statusText ?: 'Status indisponivel' }}</p>
        @if ($meetingUrl)
            <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="vm-btn vm-btn-primary min-w-[7.5rem]">
                Entrar
            </a>
        @else
            <span class="vm-link-disabled">Link indisponivel</span>
        @endif
    </div>
</article>
