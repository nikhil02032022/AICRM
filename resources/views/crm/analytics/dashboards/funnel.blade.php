{{-- BRD: CRM-AR-004 — Admissions funnel: stage-wise conversion and drop-off visualisation --}}
<x-layouts.crm title="Admissions Funnel">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Admissions Funnel</h1>
        <p class="mt-1 text-sm text-gray-500">Stage-wise lead conversion and drop-off across the admissions pipeline.</p>
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
        <a href="{{ route('crm.analytics.dashboards.funnel') }}" class="btn-ghost-sm">Clear</a>
    </form>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
        {{-- ApexCharts Funnel --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Funnel Visualisation</h2>
            <div id="funnelChart"></div>
        </div>

        {{-- Stage Conversion Table --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Stage Breakdown</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Stage</th>
                        <th class="table-th-center">Leads</th>
                        <th class="table-th-center">Conversion</th>
                        <th class="table-th-center">Drop-off</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($stages as $stage)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td font-medium">{{ $stage['label'] }}</td>
                        <td class="table-td-center tabular-nums font-semibold">{{ number_format($stage['count']) }}</td>
                        <td class="table-td-center">
                            @if($stage['conversion_rate'] === 100.0)
                                <span class="text-gray-400 text-xs">—</span>
                            @elseif($stage['conversion_rate'] >= 60)
                                <span class="badge-success">{{ $stage['conversion_rate'] }}%</span>
                            @elseif($stage['conversion_rate'] >= 30)
                                <span class="badge-warning">{{ $stage['conversion_rate'] }}%</span>
                            @else
                                <span class="badge-danger">{{ $stage['conversion_rate'] }}%</span>
                            @endif
                        </td>
                        <td class="table-td-center tabular-nums text-red-500">
                            @if($stage['drop_off_count'] > 0)
                                {{ number_format($stage['drop_off_count']) }}
                                <span class="text-xs text-gray-400">({{ $stage['drop_off_rate'] }}%)</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Per-Source Funnel Summary --}}
    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Funnel by Acquisition Source</h2>
    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="table-th">Source</th>
                    <th class="table-th-center">Enquiries</th>
                    <th class="table-th-center">Applied</th>
                    <th class="table-th-center">Enrolled</th>
                    <th class="table-th-center">Lead → Enrol %</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($bySource as $row)
                @php
                    $enrolRate = $row->total_leads > 0
                        ? round(($row->total_enrolled / $row->total_leads) * 100, 1)
                        : 0;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="table-td font-medium">
                        {{ \App\Enums\CRM\LeadSource::tryFrom($row->source)?->label() ?? ucwords(str_replace('_', ' ', $row->source)) }}
                    </td>
                    <td class="table-td-center tabular-nums">{{ number_format($row->total_leads) }}</td>
                    <td class="table-td-center tabular-nums">{{ number_format($row->total_applied) }}</td>
                    <td class="table-td-center tabular-nums font-semibold text-green-700">{{ number_format($row->total_enrolled) }}</td>
                    <td class="table-td-center">
                        @if($enrolRate >= 15)
                            <span class="badge-success">{{ $enrolRate }}%</span>
                        @elseif($enrolRate >= 5)
                            <span class="badge-warning">{{ $enrolRate }}%</span>
                        @else
                            <span class="badge-danger">{{ $enrolRate }}%</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="table-td text-center text-gray-400">No lead data for selected period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3/dist/apexcharts.min.js"></script>
    <script>
    (function () {
        const stages = @json($stages);
        const labels = stages.map(s => s.label);
        const counts = stages.map(s => s.count);

        // Colour gradient from indigo (top) to green (bottom)
        const colours = [
            '#6366f1', '#818cf8', '#a5b4fc',
            '#fbbf24', '#f97316', '#10b981', '#059669'
        ].slice(0, labels.length);

        new ApexCharts(document.getElementById('funnelChart'), {
            series: [{ name: 'Leads', data: counts }],
            chart: {
                type: 'bar',
                height: 380,
                toolbar: { show: false },
                animations: { enabled: true, speed: 500 },
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    distributed: true,
                    barHeight: '70%',
                    isFunnel: true,
                    borderRadius: 4,
                },
            },
            colors: colours,
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => {
                    const s = stages[opts.dataPointIndex];
                    return `${s.label}: ${val.toLocaleString()}`;
                },
                style: { fontSize: '12px', fontWeight: 600, colors: ['#1f2937'] },
                dropShadow: { enabled: false },
            },
            xaxis: {
                categories: labels,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: { show: false },
            tooltip: {
                custom: ({ dataPointIndex }) => {
                    const s = stages[dataPointIndex];
                    const conv = s.conversion_rate === 100
                        ? '—'
                        : `${s.conversion_rate}% from previous stage`;
                    const drop = s.drop_off_count > 0
                        ? `${s.drop_off_count.toLocaleString()} dropped (${s.drop_off_rate}%)`
                        : '—';
                    return `<div class="p-3 text-sm">
                        <div class="font-semibold mb-1">${s.label}</div>
                        <div>Leads: <strong>${s.count.toLocaleString()}</strong></div>
                        <div>Conversion: ${conv}</div>
                        <div class="text-red-600">Drop-off: ${drop}</div>
                    </div>`;
                },
            },
            legend: { show: false },
            grid: { show: false },
        }).render();
    })();
    </script>
    @endpush
</x-layouts.crm>
