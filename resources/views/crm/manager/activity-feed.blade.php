<x-layouts.crm title="Team Activity Feed">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Team Activity Feed</h1>
                <p class="mt-1 text-sm text-gray-500">Real-time task activity across your team</p>
            </div>
            <a href="{{ route('crm.manager.team-tasks') }}" class="btn-secondary">Team Tasks</a>
        </div>

        <livewire:crm.tasks.manager.activity-feed />

    </div>
</x-layouts.crm>
