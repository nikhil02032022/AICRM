<x-layouts.crm title="My Tasks">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Tasks</h1>
                <p class="mt-1 text-sm text-gray-500">Follow-up activities assigned to you</p>
            </div>
            <div class="flex items-center gap-2">
                @can('crm.tasks.calendar')
                <a href="{{ route('crm.tasks.calendar') }}" class="btn-secondary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Calendar
                </a>
                @endcan
                @can('crm.tasks.create')
                <a href="{{ route('crm.tasks.create') }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Task
                </a>
                @endcan
            </div>
        </div>

        @if (session('success'))
        <div x-data="{ show: true }" x-show="show"
            x-init="setTimeout(() => show = false, 4500)"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            role="alert" aria-live="polite"
            class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800 shadow-sm">
            <svg class="h-5 w-5 flex-shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <span class="flex-1 font-medium">{{ session('success') }}</span>
            <button type="button" @click="show = false" aria-label="Dismiss" class="cursor-pointer text-green-600 transition-colors hover:text-green-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endif

        {{-- Reactive task list --}}
        <livewire:crm.tasks.task-list />

    </div>
</x-layouts.crm>
