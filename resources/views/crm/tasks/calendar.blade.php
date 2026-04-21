<x-layouts.crm title="Task Calendar">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Task Calendar</h1>
                <p class="mt-1 text-sm text-gray-500">Visual overview of your scheduled follow-ups</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('crm.tasks.index') }}" class="btn-secondary">List View</a>
                @can('crm.tasks.create')
                <a href="{{ route('crm.tasks.create') }}" class="btn-primary">New Task</a>
                @endcan
            </div>
        </div>

        <livewire:crm.tasks.task-calendar />

    </div>
</x-layouts.crm>
