<x-layouts.crm title="Team Task Overview">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Team Task Overview</h1>
                <p class="mt-1 text-sm text-gray-500">All counsellor tasks across your institution</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('crm.manager.activity-feed') }}" class="btn-secondary">Activity Feed</a>
                @can('crm.tasks.bulk-assign')
                <a href="{{ route('crm.tasks.bulk-reassign') }}" class="btn-secondary">Bulk Reassign</a>
                @endcan
            </div>
        </div>

        @if (session('success'))
        <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-200">
            {{ session('success') }}
        </div>
        @endif

        {{-- Task table rendered by Livewire TaskList in manager mode --}}
        <livewire:crm.tasks.task-list />

    </div>
</x-layouts.crm>
