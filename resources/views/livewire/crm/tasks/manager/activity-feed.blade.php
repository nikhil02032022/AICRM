<div class="space-y-4" wire:poll.60s>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Counsellor (User ID)</label>
                <input type="number" wire:model.live="filterCounsellor" placeholder="All"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm w-28">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" wire:model.live="filterDateFrom"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" wire:model.live="filterDateTo"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div wire:loading class="self-end mb-1 h-4 w-4 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent"></div>
        </div>
    </div>

    {{-- Feed --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        @if ($this->activities->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg class="h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-gray-500">No activity in the selected period.</p>
        </div>
        @else
        <ul class="divide-y divide-gray-100">
            @foreach ($this->activities as $activity)
            <li class="flex items-start gap-4 px-5 py-4">

                {{-- Avatar initials --}}
                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700">
                    {{ strtoupper(substr($activity->performer?->name ?? 'S', 0, 1)) }}
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800">
                        <span class="font-medium">{{ $activity->performer?->name ?? 'System' }}</span>
                        {{ match($activity->type) {
                            'task_created'   => 'created a task',
                            'task_completed' => 'completed a task',
                            'task_updated'   => 'updated a task',
                            default          => $activity->type,
                        } }}
                        @if (isset($activity->metadata['task_uuid']))
                        — <a href="{{ route('crm.tasks.edit', $activity->metadata['task_uuid']) }}" class="text-indigo-600 hover:underline">view task</a>
                        @endif
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                </div>

            </li>
            @endforeach
        </ul>

        @if ($this->activities->hasPages())
        <div class="border-t border-gray-100 px-5 py-3">
            {{ $this->activities->links() }}
        </div>
        @endif
        @endif

    </div>

</div>
