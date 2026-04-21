<div class="space-y-4">

    {{-- View toggle --}}
    <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-gray-700">View:</span>
        <div class="inline-flex rounded-md shadow-sm" role="group">
            @foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $key => $label)
            <button type="button" wire:click="setView('{{ $key }}')"
                class="px-4 py-2 text-sm font-medium border transition
                    {{ $viewType === $key
                        ? 'bg-indigo-600 text-white border-indigo-600 z-10'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}
                    {{ $key === 'day' ? 'rounded-l-md' : ($key === 'month' ? 'rounded-r-md' : '') }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div wire:loading class="ml-2 h-4 w-4 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent"></div>
    </div>

    {{-- Priority legend --}}
    <div class="flex items-center gap-4 text-xs text-gray-500">
        <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>Urgent</span>
        <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-orange-400"></span>High</span>
        <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-blue-400"></span>Normal</span>
        <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-full bg-gray-300"></span>Low</span>
    </div>

    {{-- FullCalendar mount point — wire:ignore prevents Livewire re-rendering the JS-managed DOM --}}
    <div wire:ignore class="bg-white rounded-xl border border-gray-200 p-4">
        <div id="task-calendar" style="min-height: 600px;"></div>
    </div>

</div>

@push('scripts')
{{-- FullCalendar v6 via CDN --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener('livewire:initialized', function () {
    const calendarEl = document.getElementById('task-calendar');
    if (!calendarEl) return;

    const componentId = calendarEl.closest('[wire\\:id]')?.getAttribute('wire:id');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: '{{ $viewType === "day" ? "timeGridDay" : ($viewType === "month" ? "dayGridMonth" : "timeGridWeek") }}',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '',
        },
        events: function (info, successCallback, failureCallback) {
            const component = componentId ? window.Livewire.find(componentId) : null;
            if (!component) { failureCallback(); return; }
            component.call('getEvents', info.startStr, info.endStr)
                .then(events => successCallback(events))
                .catch(() => failureCallback());
        },
        eventClick: function (info) {
            if (info.event.extendedProps.taskUuid) {
                window.location.href = '/tasks/' + info.event.extendedProps.taskUuid + '/edit';
            }
        },
        height: 'auto',
    });

    calendar.render();

    // Re-render calendar when Livewire dispatches a view-changed event
    window.addEventListener('calendar-view-changed', function (e) {
        const viewType = e.detail.viewType ?? e.detail[0];
        const fcView = viewType === 'day' ? 'timeGridDay' : (viewType === 'month' ? 'dayGridMonth' : 'timeGridWeek');
        if (calendar.view.type !== fcView) {
            calendar.changeView(fcView);
        }
        calendar.refetchEvents();
    });
});
</script>
@endpush
