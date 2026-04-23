{{-- BRD: CRM-AR-001 — Institution admissions dashboard: leads, applications, offers, enrolments, revenue --}}
<x-layouts.crm title="Institution Dashboard">
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
    @endpush

    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Institution Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Admissions overview — leads, applications, offers, enrolments and revenue.</p>
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
        <a href="{{ route('crm.analytics.dashboards.institution') }}" class="btn-ghost-sm">Clear</a>
    </form>

    {{-- KPI Tiles — values link to drill-down (BRD: CRM-AR-008) --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5 mb-6">
        @foreach([
            ['label' => 'Total Leads',   'value' => number_format($kpis['total_leads']),              'metric' => 'leads'],
            ['label' => 'Applications',  'value' => number_format($kpis['total_applications']),       'metric' => 'applications'],
            ['label' => 'Offers Issued', 'value' => number_format($kpis['total_offers']),             'metric' => 'offers'],
            ['label' => 'Enrolments',    'value' => number_format($kpis['total_enrolments']),         'metric' => 'enrolments'],
            ['label' => 'Revenue (₹)',   'value' => '₹ ' . number_format($kpis['total_revenue'], 0), 'metric' => null],
        ] as $tile)
        <div class="card p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $tile['label'] }}</p>
            @if($tile['metric'])
                <a href="{{ route('crm.analytics.drill-down.leads', ['from' => $filters['from'], 'to' => $filters['to'], 'metric' => $tile['metric']]) }}"
                   class="mt-2 block text-2xl font-bold text-gray-900 hover:text-indigo-600 transition-colors tabular-nums">
                    {{ $tile['value'] }}
                </a>
            @else
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $tile['value'] }}</p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Charts Row --}}
    <div class="grid gap-4 lg:grid-cols-2 mb-6">
        {{-- By Programme --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Applications by Programme</h2>
            <div class="relative h-64">
                <canvas id="chartProgramme"></canvas>
            </div>
        </div>

        {{-- By Source --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Leads by Source</h2>
            <div class="relative h-64">
                <canvas id="chartSource"></canvas>
            </div>
        </div>
    </div>

    {{-- Programme Table --}}
    <div class="card mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700">Programme Breakdown</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="table-th">Programme</th>
                        <th class="table-th-center">Applications</th>
                        <th class="table-th-center">Offers</th>
                        <th class="table-th-center">Enrolments</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($byProgramme as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td font-medium text-gray-900">{{ $row->programme }}</td>
                        <td class="table-td-center">
                            <a href="{{ route('crm.analytics.drill-down.leads', ['from' => $filters['from'], 'to' => $filters['to'], 'metric' => 'applications', 'programme_id' => $row->programme_id]) }}"
                               class="text-indigo-600 hover:underline tabular-nums">{{ number_format($row->total_applications) }}</a>
                        </td>
                        <td class="table-td-center">
                            <a href="{{ route('crm.analytics.drill-down.leads', ['from' => $filters['from'], 'to' => $filters['to'], 'metric' => 'offers', 'programme_id' => $row->programme_id]) }}"
                               class="text-indigo-600 hover:underline tabular-nums">{{ number_format($row->total_offers) }}</a>
                        </td>
                        <td class="table-td-center">
                            <a href="{{ route('crm.analytics.drill-down.leads', ['from' => $filters['from'], 'to' => $filters['to'], 'metric' => 'enrolments', 'programme_id' => $row->programme_id]) }}"
                               class="text-indigo-600 hover:underline tabular-nums font-semibold text-green-700">{{ number_format($row->total_enrolments) }}</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">No data for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Programme chart
        const programmeData = @json($byProgramme->map(fn($r) => ['programme' => $r->programme, 'total' => $r->total_applications]));
        if (programmeData.length) {
            new Chart(document.getElementById('chartProgramme'), {
                type: 'bar',
                data: {
                    labels: programmeData.map(r => r.programme),
                    datasets: [{
                        label: 'Applications',
                        data: programmeData.map(r => r.total),
                        backgroundColor: '#6366f1',
                        borderRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }

        // Source chart
        const sourceData = @json($bySource->map(fn($r) => ['source' => $r->source, 'total' => $r->total_leads]));
        const sourceColours = ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#ddd6fe','#ede9fe'];
        if (sourceData.length) {
            new Chart(document.getElementById('chartSource'), {
                type: 'doughnut',
                data: {
                    labels: sourceData.map(r => r.source),
                    datasets: [{
                        data: sourceData.map(r => r.total),
                        backgroundColor: sourceColours.slice(0, sourceData.length),
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right' } },
                },
            });
        }
    });
    </script>
    @endpush
</x-layouts.crm>
