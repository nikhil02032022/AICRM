<x-layouts.crm title="Daily Priority Leads">
    <x-slot:header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Daily Priority Leads</h1>
                <p class="mt-1 text-sm text-gray-500">AI-ranked leads for your follow-up queue (score, inactivity, conversion probability).</p>
            </div>
            <form action="{{ route('crm.scoring.priority-leads.generate') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <input type="date" name="for_date" value="{{ $forDate }}" class="input-field w-44 text-sm" aria-label="Generate date">
                <button type="submit" class="btn-primary-sm">Generate List</button>
            </form>
        </div>
    </x-slot:header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-xs text-indigo-700">
            Showing priority list for: <span class="font-semibold">{{ \Illuminate\Support\Carbon::parse($forDate)->format('d M Y') }}</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Rank</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lead</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Priority Score</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">Reasoning</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($entries as $entry)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                    {{ $entry['priority_rank'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $entry['lead_name'] ?? 'Lead' }}</td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-mono font-semibold text-gray-800">{{ $entry['priority_score'] }}/100</span>
                            </td>
                            <td class="px-4 py-3 text-xs leading-relaxed text-gray-600">{{ $entry['reasoning'] }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('crm.leads.show', $entry['lead_uuid']) }}" class="btn-secondary-sm">Open Lead</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                No priority list records available for this date. Click <span class="font-semibold">Generate List</span> to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.crm>
