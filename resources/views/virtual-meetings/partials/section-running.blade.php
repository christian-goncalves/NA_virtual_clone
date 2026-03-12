<div class="rounded-2xl border border-cyan-800/30 bg-slate-900/65 p-5 sm:p-6">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="text-xl font-semibold text-white">Reuniões em andamento</h3>
        <span class="rounded-full bg-cyan-500/15 px-3 py-1 text-sm font-semibold text-cyan-200">{{ $runningCount }}</span>
    </div>

    @if ($runningMeetings->isEmpty())
        <p class="rounded-xl border border-dashed border-slate-700 bg-slate-900/60 px-4 py-6 text-sm text-slate-300">
            Nenhuma reuniao em andamento neste momento.
        </p>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($runningMeetings as $meetingData)
                @include('virtual-meetings.partials.meeting-card', ['meetingData' => $meetingData])
            @endforeach
        </div>
    @endif
</div>

