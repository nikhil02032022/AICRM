{{-- BRD: CRM-EC-008 — Counsellor workload overview dashboard --}}
<x-layouts.crm title="Counsellor Workload">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Counsellor Workload</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Real-time active lead distribution across your counselling team.
                </p>
            </div>
            <div class="flex items-center gap-2">
                @can('crm.settings.manage')
                    <a href="{{ route('crm.settings.assignment-config') }}" class="btn-secondary-sm">
                        Assignment Config
                    </a>
                @endcan
                <a href="{{ route('crm.leads.index') }}" class="btn-secondary-sm">Back to Leads</a>
            </div>
        </div>

        <div class="card p-5">
            <livewire:crm.counselling.counsellor-workload-dashboard />
        </div>

    </div>
</x-layouts.crm>
