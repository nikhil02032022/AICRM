<div wire:poll.30s>

    {{-- Search bar --}}
    <div class="relative mb-4">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input wire:model.live.debounce.400ms="search" type="search" placeholder="Search tasks..."
            class="input-field pl-10 pr-4" aria-label="Search tasks">
    </div>

    {{-- Filter chips + dropdowns --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        {{-- Priority quick-filter chips --}}
        <button wire:click="$set('filterPriority', '')"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors
                {{ $filterPriority === '' ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            All Priorities
        </button>
        <button wire:click="$set('filterPriority', 'urgent')"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors
                {{ $filterPriority === 'urgent' ? 'border-red-300 bg-red-50 text-red-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            🔴 Urgent
        </button>
        <button wire:click="$set('filterPriority', 'high')"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors
                {{ $filterPriority === 'high' ? 'border-orange-300 bg-orange-50 text-orange-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            🟠 High
        </button>
        <button wire:click="$set('filterPriority', 'normal')"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors
                {{ $filterPriority === 'normal' ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            🔵 Normal
        </button>

        {{-- Divider --}}
        <div class="mx-1 h-5 w-px bg-gray-200"></div>

        {{-- Status filter --}}
        <select wire:model.live="filterStatus"
            class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 focus:border-primary-400 focus:outline-none focus:ring-1 focus:ring-primary-400 cursor-pointer"
            aria-label="Filter by status">
            <option value="">All Statuses</option>
            @foreach ($this->statuses as $status)
            <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>

        {{-- Type filter --}}
        <select wire:model.live="filterType"
            class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 focus:border-primary-400 focus:outline-none focus:ring-1 focus:ring-primary-400 cursor-pointer"
            aria-label="Filter by type">
            <option value="">All Types</option>
            @foreach ($this->types as $type)
            <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>

        {{-- Date range --}}
        <input type="date" wire:model.live="filterDateFrom"
            class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 focus:border-primary-400 focus:outline-none focus:ring-1 focus:ring-primary-400 cursor-pointer"
            aria-label="From date">
        <input type="date" wire:model.live="filterDateTo"
            class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 focus:border-primary-400 focus:outline-none focus:ring-1 focus:ring-primary-400 cursor-pointer"
            aria-label="To date">
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="table-th">Task</th>
                        <th scope="col" class="table-th">Type</th>
                        <th scope="col" class="table-th">Priority</th>
                        <th scope="col" class="table-th">Status</th>
                        <th scope="col" class="table-th">Due Date</th>
                        <th scope="col" class="table-th">Assignee</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap bg-gray-50">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($this->tasks as $task)
                    <tr @class([
                        'transition-colors duration-100',
                        'hover:bg-gray-50' => ! $task->isOverdue(),
                        'bg-red-50 hover:bg-red-50 border-l-4 border-l-red-400' => $task->isOverdue(),
                    ])>

                        {{-- Task: title + lead --}}
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $task->title }}</p>
                            @if ($task->lead)
                            <p class="mt-0.5 font-mono text-xs text-gray-400">
                                {{ $task->lead->first_name }} {{ $task->lead->last_name }}
                            </p>
                            @endif
                        </td>

                        {{-- Type --}}
                        <td class="table-td">
                            @if ($task->type)
                            <span class="badge badge-gray">{{ $task->type->label() }}</span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Priority --}}
                        <td class="table-td">
                            @if ($task->priority)
                            <span class="badge
                                @switch($task->priority?->value)
                                    @case('urgent') badge-red @break
                                    @case('high') badge-orange @break
                                    @case('normal') badge-blue @break
                                    @default badge-gray
                                @endswitch
                            ">{{ $task->priority->label() }}</span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="table-td">
                            @if ($task->status)
                            <span class="badge {{ $task->status->tailwindBadgeClass() }}">
                                {{ $task->status->label() }}
                            </span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Due Date --}}
                        <td class="table-td">
                            @if ($task->due_at)
                            <div>
                                <span class="{{ $task->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-500' }} text-xs">
                                    {{ $task->due_at->diffForHumans() }}
                                </span>
                                @if ($task->isOverdue())
                                <span class="mt-0.5 inline-flex items-center rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-red-700">
                                    Overdue
                                </span>
                                @endif
                            </div>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Assignee --}}
                        <td class="table-td">
                            @if ($task->assignee)
                            {{ $task->assignee->name }}
                            @else
                            <span class="text-xs font-medium text-red-400">Unassigned</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                @if (! $task->status?->isTerminal())
                                <a href="{{ route('crm.tasks.complete', $task->uuid) }}"
                                    class="btn-ghost-sm text-green-700 hover:bg-green-50"
                                    aria-label="Complete {{ $task->title }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Complete
                                </a>
                                @endif
                                <a href="{{ route('crm.tasks.edit', $task->uuid) }}"
                                    class="btn-ghost-sm"
                                    aria-label="Edit {{ $task->title }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center">
                            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-500">No tasks found</p>
                            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters or create a new task.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        @if ($this->tasks->hasPages())
        <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3">
            <span class="text-xs text-gray-500">
                Showing {{ $this->tasks->firstItem() }}–{{ $this->tasks->lastItem() }}
                of {{ number_format($this->tasks->total()) }} tasks
            </span>
            <div class="flex gap-2">
                @if ($this->tasks->onFirstPage())
                    <span class="btn-secondary-sm cursor-not-allowed opacity-50">← Prev</span>
                @else
                    <button wire:click="previousPage" class="btn-secondary-sm">← Prev</button>
                @endif
                @if ($this->tasks->hasMorePages())
                    <button wire:click="nextPage" class="btn-primary-sm">Next →</button>
                @else
                    <span class="btn-primary-sm cursor-not-allowed opacity-50">Next →</span>
                @endif
            </div>
        </div>
        @else
        <div class="border-t border-gray-100 px-4 py-3">
            <span class="text-xs text-gray-500">{{ $this->tasks->total() }} task{{ $this->tasks->total() !== 1 ? 's' : '' }}</span>
        </div>
        @endif
    </div>

    {{-- Loading overlay --}}
    <div wire:loading.flex class="fixed inset-0 z-50 items-center justify-center bg-white/50">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-600 border-t-transparent"></div>
    </div>

</div>
