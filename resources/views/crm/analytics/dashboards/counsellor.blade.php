{{-- BRD: CRM-AR-002 — Counsellor performance dashboard: own KPIs + team ranking for managers/directors --}}
<x-layouts.crm title="Counsellor Dashboard">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Counsellor Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Your performance metrics for the selected period.</p>
    </x-slot:header>

    {{-- Date Range Filter --}}
    <form method="GET" class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
            <input type="date" name="from" value="{{ $filters['from'] }}"
                   class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
            <input type="date" name="to" value="{{ $filters['to'] }}"
                   class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <button type="submit" class="btn-primary-sm">Apply</button>
        <a href="{{ route('crm.analytics.dashboards.counsellor') }}" class="btn-ghost-sm">Clear</a>
    </form>

    {{-- Own KPI Tiles — lead/converted counts link to drill-down (BRD: CRM-AR-008) --}}
    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">My Performance</h2>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5 mb-8">
        @foreach([
            ['label' => 'My Leads',       'value' => number_format($ownKpis->total_leads),    'metric' => 'leads'],
            ['label' => 'Converted',      'value' => number_format($ownKpis->total_converted), 'metric' => 'enrolments'],
            ['label' => 'Conversion Rate','value' => $ownKpis->conversion_rate . '%',          'metric' => null],
            ['label' => 'Tasks Assigned', 'value' => number_format($ownKpis->total_tasks),      'metric' => null],
            ['label' => 'Tasks Completed','value' => number_format($ownKpis->tasks_completed),  'metric' => null],
        ] as $tile)
        <div class="card p-4">
            <p class="text-xs text-gray-500 font-medium">{{ $tile['label'] }}</p>
            @if($tile['metric'])
                <a href="{{ route('crm.analytics.drill-down.leads', ['from' => $filters['from'], 'to' => $filters['to'], 'metric' => $tile['metric']]) }}"
                   class="mt-1 block text-2xl font-bold text-gray-900 hover:text-indigo-600 transition-colors tabular-nums">
                    {{ $tile['value'] }}
                </a>
            @else
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $tile['value'] }}</p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Avg Response Time --}}
    <div class="card p-4 mb-8 inline-flex items-center gap-3">
        <div class="rounded-full bg-indigo-50 p-3">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500">Avg. First Response Time</p>
            <p class="text-lg font-bold text-gray-900">{{ $ownKpis->avg_response_hours }}h</p>
        </div>
    </div>

    {{-- Team Performance Grid (Manager / Director only) --}}
    @if($isManager && $teamGrid->isNotEmpty())
    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Team Performance Ranking</h2>
    <div class="card overflow-hidden mb-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="table-th">Counsellor</th>
                    <th class="table-th-center">Leads</th>
                    <th class="table-th-center">Converted</th>
                    <th class="table-th-center">Conversion %</th>
                    <th class="table-th-center">Tasks Done</th>
                    <th class="table-th-center">Avg Response</th>
                    <th class="table-th-center">Performance</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($teamGrid as $row)
                <tr class="hover:bg-gray-50">
                    <td class="table-td">
                        {{ optional(\App\Models\User::find($row->counsellor_id))->name ?? 'User #' . $row->counsellor_id }}
                    </td>
                    <td class="table-td-center">{{ number_format($row->total_leads) }}</td>
                    <td class="table-td-center">{{ number_format($row->total_converted) }}</td>
                    <td class="table-td-center font-semibold">{{ $row->conversion_rate }}%</td>
                    <td class="table-td-center">{{ $row->tasks_completed }} / {{ $row->total_tasks }}</td>
                    <td class="table-td-center">{{ $row->avg_response_hours }}h</td>
                    <td class="table-td-center">
                        @if($row->conversion_rate >= 30)
                            <span class="badge-success">High</span>
                        @elseif($row->conversion_rate >= 15)
                            <span class="badge-warning">Medium</span>
                        @else
                            <span class="badge-danger">Low</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="table-td text-center text-gray-400">No data for selected period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

</x-layouts.crm>
