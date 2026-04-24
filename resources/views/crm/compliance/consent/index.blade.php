<x-layouts.crm title="Consent Records">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Consent Records</h1>
                <p class="mt-1 text-sm text-gray-500">DPDP Act 2023 — consent captured at lead creation</p>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm mb-4">{{ session('success') }}</div>
        @endif

        {{-- Filter Bar --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('crm.compliance.consent.index') }}" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Lead ID</label>
                    <input type="text" name="lead_id" value="{{ request('lead_id') }}"
                        placeholder="Lead ID"
                        class="block w-36 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Consent Type</label>
                    <select name="type" class="block w-52 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        <option value="marketing_communication" @selected(request('type') === 'marketing_communication')>Marketing Communication</option>
                        <option value="data_processing" @selected(request('type') === 'data_processing')>Data Processing</option>
                        <option value="call_recording" @selected(request('type') === 'call_recording')>Call Recording</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="block w-40 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="block w-40 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="{{ route('crm.compliance.consent.index') }}" class="btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="table-th">Lead Name</th>
                            <th class="table-th">Consent Type</th>
                            <th class="table-th">IP Address</th>
                            <th class="table-th">Form Version</th>
                            <th class="table-th">Consented At</th>
                            <th class="table-th text-center">Opt-Out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($records as $record)
                            <tr class="hover:bg-gray-50/70 transition-colors duration-100">
                                <td class="table-td">
                                    <a href="{{ route('crm.leads.show', $record->lead) }}"
                                        class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                        {{ $record->lead?->full_name ?? 'Lead #'.$record->lead_id }}
                                    </a>
                                </td>
                                <td class="table-td">
                                    <span class="capitalize text-sm text-gray-700">{{ str_replace('_', ' ', $record->consent_type) }}</span>
                                </td>
                                <td class="table-td font-mono text-xs text-gray-600">{{ $record->ip_address ?? '—' }}</td>
                                <td class="table-td text-sm text-gray-600">{{ $record->form_version ?? '—' }}</td>
                                <td class="table-td text-sm text-gray-600">
                                    {{ $record->consented_at ? $record->consented_at->format('d M Y, H:i') : '—' }}
                                </td>
                                <td class="table-td text-center">
                                    @if($record->opt_out_at)
                                        <span class="badge-red">Opted Out</span>
                                    @else
                                        <span class="badge-green">Active</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">No consent records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($records->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">{{ $records->withQueryString()->links() }}</div>
            @endif
        </div>

    </div>
</x-layouts.crm>
