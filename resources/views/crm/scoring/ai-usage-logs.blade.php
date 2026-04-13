{{-- BRD: CRM-AI-012 — AI usage audit dashboard for DPDP and operational traceability --}}
<x-layouts.crm title="AI Usage Logs">
    <div class="space-y-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">AI Usage Logs</h1>
                <p class="mt-0.5 text-sm text-gray-500">Immutable AI activity records for audit and DPDP compliance review.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('crm.scoring.ai-usage-logs') }}" class="grid gap-2 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-5">
            <div>
                <label for="from_date" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">From</label>
                <input id="from_date" type="date" name="from_date" value="{{ $fromDate }}" class="input-field text-sm">
            </div>
            <div>
                <label for="to_date" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">To</label>
                <input id="to_date" type="date" name="to_date" value="{{ $toDate }}" class="input-field text-sm">
            </div>
            <div>
                <label for="feature_key" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Feature</label>
                <input id="feature_key" type="text" name="feature_key" value="{{ $featureKey }}" class="input-field text-sm" placeholder="next_best_action">
            </div>
            <div>
                <label for="action" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Action</label>
                <input id="action" type="text" name="action" value="{{ $action }}" class="input-field text-sm" placeholder="accepted">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary-sm w-full justify-center">Apply Filters</button>
            </div>
        </form>

        @php
            $collection = collect($rows->resolve());
        @endphp

        @if($collection->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center shadow-sm">
                <p class="text-sm font-semibold text-gray-700">No AI usage logs found for the selected filters.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Occurred</th>
                                <th class="px-4 py-3">Feature</th>
                                <th class="px-4 py-3">Action</th>
                                <th class="px-4 py-3">Lead</th>
                                <th class="px-4 py-3">Actor</th>
                                <th class="px-4 py-3">Context</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($collection as $row)
                                <tr>
                                    <td class="px-4 py-3 text-xs text-gray-700">{{ \Illuminate\Support\Carbon::parse($row['occurred_at'])->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ str_replace('_', ' ', $row['feature_key']) }}</td>
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">{{ strtoupper($row['action']) }}</span></td>
                                    <td class="px-4 py-3 text-xs text-gray-700">{{ $row['lead_uuid'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-700">{{ $row['actor_name'] ?? 'System' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ json_encode($row['context']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-layouts.crm>
