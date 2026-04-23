{{-- BRD: CRM-AR-003 — Marketing campaign dashboard: spend vs leads, CPL, CPE, channel ROI --}}
<x-layouts.crm title="Marketing Dashboard">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Marketing Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Campaign spend, cost-per-lead, cost-per-enrolment, and channel ROI.</p>
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
        <a href="{{ route('crm.analytics.dashboards.marketing') }}" class="btn-ghost-sm">Clear</a>
    </form>

    {{-- Summary KPI Tiles --}}
    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Period Summary</h2>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-8">
        @foreach([
            ['label' => 'Total Spend',     'value' => '₹' . number_format($kpis['total_spend'], 0),     'color' => 'text-red-600'],
            ['label' => 'Total Leads',     'value' => number_format($kpis['total_leads']),               'color' => 'text-gray-900'],
            ['label' => 'Enrolments',      'value' => number_format($kpis['total_enrolments']),          'color' => 'text-green-600'],
            ['label' => 'Revenue',         'value' => '₹' . number_format($kpis['total_revenue'], 0),   'color' => 'text-indigo-600'],
            ['label' => 'CPL',             'value' => '₹' . number_format($kpis['cpl'], 0),             'color' => 'text-orange-600'],
            ['label' => 'CPE',             'value' => '₹' . number_format($kpis['cpe'], 0),             'color' => 'text-purple-600'],
        ] as $tile)
        <div class="card p-4">
            <p class="text-xs text-gray-500 font-medium">{{ $tile['label'] }}</p>
            <p class="mt-1 text-2xl font-bold {{ $tile['color'] }}">{{ $tile['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Spend vs Leads Trend Chart --}}
    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">12-Month Spend vs Leads Trend</h2>
    <div class="card p-5 mb-8">
        <canvas id="marketingTrendChart" height="90"></canvas>
    </div>

    {{-- Channel ROI Breakdown --}}
    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Channel Breakdown</h2>
    <div class="card overflow-hidden mb-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="table-th">Channel</th>
                    <th class="table-th-right">Spend (₹)</th>
                    <th class="table-th-center">Leads</th>
                    <th class="table-th-center">Enrolments</th>
                    <th class="table-th-right">Revenue (₹)</th>
                    <th class="table-th-right">CPL (₹)</th>
                    <th class="table-th-right">CPE (₹)</th>
                    <th class="table-th-center">ROI</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($byChannel as $row)
                <tr class="hover:bg-gray-50">
                    <td class="table-td font-medium">
                        {{ \App\Enums\CRM\LeadSource::tryFrom($row->source)?->label() ?? ucwords(str_replace('_', ' ', $row->source)) }}
                    </td>
                    <td class="table-td-right tabular-nums">{{ number_format($row->total_spend, 0) }}</td>
                    <td class="table-td-center tabular-nums">{{ number_format($row->total_leads) }}</td>
                    <td class="table-td-center tabular-nums">{{ number_format($row->total_enrolments) }}</td>
                    <td class="table-td-right tabular-nums">{{ number_format($row->total_revenue, 0) }}</td>
                    <td class="table-td-right tabular-nums">
                        {{ $row->cpl > 0 ? number_format($row->cpl, 0) : '—' }}
                    </td>
                    <td class="table-td-right tabular-nums">
                        {{ $row->cpe > 0 ? number_format($row->cpe, 0) : '—' }}
                    </td>
                    <td class="table-td-center">
                        @if($row->roi === null)
                            <span class="text-gray-400 text-xs">No spend</span>
                        @elseif($row->roi >= 100)
                            <span class="badge-success">{{ $row->roi }}%</span>
                        @elseif($row->roi >= 0)
                            <span class="badge-warning">{{ $row->roi }}%</span>
                        @else
                            <span class="badge-danger">{{ $row->roi }}%</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="table-td text-center text-gray-400">No campaign data for selected period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const trend = @json($trend);
        const labels = trend.map(t => t.month);
        const spendData = trend.map(t => t.total_spend);
        const leadData  = trend.map(t => t.total_leads);

        new Chart(document.getElementById('marketingTrendChart'), {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Spend (₹)',
                        data: spendData,
                        backgroundColor: 'rgba(239, 68, 68, 0.18)',
                        borderColor: 'rgba(239, 68, 68, 0.9)',
                        borderWidth: 1.5,
                        yAxisID: 'ySpend',
                        order: 2,
                    },
                    {
                        label: 'Leads',
                        data: leadData,
                        type: 'line',
                        borderColor: 'rgba(99, 102, 241, 0.9)',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'yLeads',
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                if (ctx.dataset.label === 'Spend (₹)') {
                                    return ` ₹${ctx.parsed.y.toLocaleString('en-IN')}`;
                                }
                                return ` ${ctx.parsed.y.toLocaleString()} leads`;
                            },
                        },
                    },
                },
                scales: {
                    ySpend: {
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            callback: v => '₹' + v.toLocaleString('en-IN'),
                            font: { size: 11 },
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                    yLeads: {
                        type: 'linear',
                        position: 'right',
                        ticks: { font: { size: 11 } },
                        grid: { drawOnChartArea: false },
                    },
                },
            },
        });
    })();
    </script>
    @endpush
</x-layouts.crm>
