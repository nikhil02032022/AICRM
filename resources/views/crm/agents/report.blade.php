{{-- BRD: CRM-AG-007 — Agent performance report --}}
<x-layouts.crm title="Agent Performance Report">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Agent Performance Report</h1>
        <p class="mt-1 text-sm text-gray-500">Leads submitted, conversions, revenue generated, and commissions earned.</p>
    </x-slot:header>

    {{-- Filters --}}
    <form method="GET" class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Agent</label>
            <select name="agent_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-48">
                <option value="">All Agents</option>
                @foreach($agents as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['agent_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Apply</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Agent</th>
                    <th class="px-4 py-3 text-right">Leads</th>
                    <th class="px-4 py-3 text-right">Conversions</th>
                    <th class="px-4 py-3 text-right">Rate</th>
                    <th class="px-4 py-3 text-right">Revenue (₹)</th>
                    <th class="px-4 py-3 text-right">Total Commission (₹)</th>
                    <th class="px-4 py-3 text-right">Paid (₹)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $row['agent']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $row['agent']->email }}</div>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($row['total_leads']) }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($row['total_conversions']) }}</td>
                    <td class="px-4 py-3 text-right">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $row['conversion_rate'] >= 20 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $row['conversion_rate'] }}%
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($row['total_revenue'], 2) }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($row['total_accrued_commission'], 2) }}</td>
                    <td class="px-4 py-3 text-right text-green-700 font-medium">{{ number_format($row['paid_commission'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">No data for the selected filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.crm>
