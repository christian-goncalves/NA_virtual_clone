<div class="rounded-2xl border border-orange-800/30 bg-slate-900/65 p-5 sm:p-6">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="text-xl font-semibold text-white">Iniciando em breve</h3>
        <span class="rounded-full bg-orange-500/15 px-3 py-1 text-sm font-semibold text-orange-200">{{ $startingSoonCount }}</span>
    </div>

    @if ($startingSoonMeetings->isEmpty())
        <p class="rounded-xl border border-dashed border-slate-700 bg-slate-900/60 px-4 py-6 text-sm text-slate-300">
            Nenhuma reuniao iniciando na janela de tempo atual.
        </p>
    @else
        <div class="space-y-3">
            @foreach ($startingSoonMeetings as $meetingData)
                @include('virtual-meetings.partials.meeting-row', ['meetingData' => $meetingData])
            @endforeach
        </div>
    @endif
</div>

