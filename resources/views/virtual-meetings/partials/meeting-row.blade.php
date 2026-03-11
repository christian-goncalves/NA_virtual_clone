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
@endphp

<article class="rounded-xl border border-slate-800 bg-slate-900/75 p-4 transition hover:border-slate-700">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-cyan-300">{{ $timeRange }}</p>
            <h4 class="mt-1 truncate text-base font-semibold text-white">{{ $name }}</h4>
            <p class="mt-1 text-sm text-slate-300">{{ ucfirst($platform) }}</p>
            @if ($typeLabel)
                <p class="mt-1 text-xs text-amber-200">{{ $typeLabel }}@if ($typeDescription) - {{ $typeDescription }}@endif</p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <span class="text-sm text-slate-300">{{ $statusText ?: 'Horario a confirmar' }}</span>
            @if ($meetingUrl)
                <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-slate-950 transition hover:bg-orange-400">
                    Entrar
                </a>
            @else
                <span class="inline-flex items-center rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-400">Sem link</span>
            @endif
        </div>
    </div>
</article>