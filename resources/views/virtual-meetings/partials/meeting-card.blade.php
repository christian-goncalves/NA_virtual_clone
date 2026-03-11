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
    $timeRange = ($startAt ? $startAt->format('H:i') : '--:--') . ' - ' . ($endAt ? $endAt->format('H:i') : '--:--');

    $typeDescription = null;
    if (is_string($typeLabel) && trim($typeLabel) !== '') {
        $typeDescription = data_get($groupedBadges ?? [], Str::lower(Str::ascii($typeLabel)));
    }
@endphp

<article class="rounded-2xl border border-cyan-700/40 bg-gradient-to-br from-cyan-900/25 to-slate-900/80 p-5 shadow-lg shadow-cyan-900/20">
    <div class="flex items-start justify-between gap-4">
        <h3 class="text-lg font-semibold text-white">{{ $name }}</h3>
        <span class="shrink-0 rounded-full border border-cyan-500/40 bg-cyan-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-200">
            Em andamento
        </span>
    </div>

    <p class="mt-3 text-sm text-slate-300">{{ $timeRange }}</p>
    <p class="mt-1 text-sm text-slate-300">{{ ucfirst($platform) }}</p>

    @if ($typeLabel)
        <p class="mt-3 text-xs font-medium text-amber-200">
            Tipo: {{ $typeLabel }}@if ($typeDescription) - {{ $typeDescription }}@endif
        </p>
    @endif

    @if (!empty($formats))
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($formats as $format)
                @if (is_string($format) && trim($format) !== '')
                    <span class="rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-xs text-slate-200">{{ $format }}</span>
                @endif
            @endforeach
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between gap-3">
        <p class="text-sm font-medium text-cyan-100">{{ $statusText ?: 'Status indisponivel' }}</p>
        @if ($meetingUrl)
            <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-cyan-500 px-3 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400">
                Entrar
            </a>
        @else
            <span class="inline-flex items-center rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-400">Link indisponivel</span>
        @endif
    </div>
</article>