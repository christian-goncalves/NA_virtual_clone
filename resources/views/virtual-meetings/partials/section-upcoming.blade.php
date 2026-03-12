<div class="rounded-2xl border border-slate-700 bg-slate-900/65 p-5 sm:p-6">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="text-xl font-semibold text-white">Próximas reuniões</h3>
        <span class="rounded-full bg-slate-700 px-3 py-1 text-sm font-semibold text-slate-200">{{ $upcomingCount }}</span>
    </div>

    @if ($upcomingMeetings->isEmpty())
        <p class="rounded-xl border border-dashed border-slate-700 bg-slate-900/60 px-4 py-6 text-sm text-slate-300">
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

