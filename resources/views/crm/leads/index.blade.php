<x-layouts.crm title="Leads">
    <div class="space-y-6">
        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Leads</h1>
                <p class="mt-1 text-sm text-gray-500">Manage and track all prospective student enquiries.</p>
            </div>
            @can('crm.leads.create')
            <a href="{{ route('crm.leads.create') }}" class="btn-primary">
                + New Lead
            </a>
            @endcan
        </div>

        {{-- Live lead table --}}
        @livewire('crm.lead.lead-table')
    </div>
</x-layouts.crm>
