{{-- BRD: CRM-AR-006 — Director/management executive dashboard: KPI tiles, trend, top programmes, campus breakdown --}}
<x-layouts.crm title="Executive Dashboard">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Executive Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Institution-wide KPIs, trend analysis, and performance summary for management.</p>
    </x-slot:header>

    {{-- Date Range Filter --}}
    <form method="GET" class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end">
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
        <a href="{{ route('crm.analytics.dashboards.executive') }}" class="btn-ghost-sm">Clear</a>
    </form>

    {{-- KPI Tiles with Trend Indicators — values link to drill-down (BRD: CRM-AR-008) --}}
    @php $drillableMetrics = ['leads', 'applications', 'offers', 'enrolments']; @endphp
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-8">
        @foreach($kpis as $key => $kpi)
        @php
            $drillUrl = in_array($key, $drillableMetrics)
                ? route('crm.analytics.drill-down.leads', ['from' => $filters['from'], 'to' => $filters['to'], 'metric' => $key])
                : null;
        @endphp
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide truncate">{{ $kpi['label'] }}</p>
            @php
                $displayValue = $kpi['is_currency']
                    ? '₹' . number_format($kpi['value'], 0)
                    : ($kpi['is_rate'] ? $kpi['value'] . '%' : number_format($kpi['value']));
            @endphp
            @if($drillUrl)
                <a href="{{ $drillUrl }}"
                   class="mt-2 block text-2xl font-bold text-gray-900 hover:text-indigo-600 transition-colors tabular-nums leading-tight">
                    {{ $displayValue }}
                </a>
            @else
                <p class="mt-2 text-2xl font-bold text-gray-900 tabular-nums leading-tight">{{ $displayValue }}</p>
            @endif
            <div class="mt-1 flex items-center gap-1 text-xs">
                @if($kpi['delta_pct'] === null)
                    <span class="text-gray-400">No prior data</span>
                @elseif($kpi['trend'] === 'up')
                    <svg class="h-3.5 w-3.5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    <span class="text-green-600 font-medium">
                        {{ $kpi['is_rate'] ? abs($kpi['delta_pct']) . ' pp' : abs($kpi['delta_pct']) . '%' }}
                    </span>
                @else
                    <svg class="h-3.5 w-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    <span class="text-red-500 font-medium">
                        {{ $kpi['is_rate'] ? abs($kpi['delta_pct']) . ' pp' : abs($kpi['delta_pct']) . '%' }}
                    </span>
                @endif
                @if($kpi['delta_pct'] !== null)
                    <span class="text-gray-400">vs prior period</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- BRD: CRM-AL-004 — Alumni NPS Score Card --}}
    @if($npsLatest)
    @php
        $npsColour = $npsLatest->nps_score > 50
            ? 'text-green-600'
            : ($npsLatest->nps_score >= 0 ? 'text-yellow-500' : 'text-red-500');
        $npsBg = $npsLatest->nps_score > 50
            ? 'bg-green-50 border-green-200'
            : ($npsLatest->nps_score >= 0 ? 'bg-amber-50 border-amber-200' : 'bg-red-50 border-red-200');
    @endphp
    <div class="mb-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- NPS Score Tile --}}
            <div class="rounded-xl border {{ $npsBg }} p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Alumni NPS Score</p>
                <p class="mt-2 text-4xl font-bold tabular-nums {{ $npsColour }}">{{ $npsLatest->scoreLabel() }}</p>
                <p class="mt-1 text-xs text-gray-500">
                    Promoters {{ $npsLatest->promoters_pct }}% &middot;
                    Detractors {{ $npsLatest->detractors_pct }}%
                </p>
                <p class="mt-1 text-xs text-gray-400">
                    Survey date: {{ $npsLatest->survey_date->format('d M Y') }}
                    &nbsp;&bull;&nbsp;
                    {{ $npsLatest->source->label() }}
                </p>
            </div>

            {{-- NPS Trend Sparkline (last 12 months) --}}
            <div class="col-span-1 sm:col-span-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">NPS Trend (12 months)</p>
                @if($npsTrend->count() > 1)
                    <div class="relative h-20">
                        <canvas id="npsSparkline"></canvas>
                    </div>
                @else
                    <p class="text-sm text-gray-400 py-4">Not enough data points for a trend chart yet.</p>
                @endif
                <div class="mt-2 flex items-center justify-end">
                    @can('alumni.nps.manage')
                    <a href="{{ route('crm.admin.nps.index') }}" class="text-xs text-indigo-600 hover:underline">View all NPS data →</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 12-Month Trend Chart --}}
    <div class="card p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">12-Month Trend</h2>
        <div class="relative h-72">
            <canvas id="trendChart"></canvas>
        </div>
        <p class="mt-2 text-xs text-gray-400">Based on monthly snapshot data. Current month updates nightly.</p>
    </div>

    {{-- Bottom Grid: Top Programmes + Campus Breakdown --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Top 5 Programmes --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Top Programmes by Enrolment</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Programme</th>
                        <th class="table-th-center">Leads</th>
                        <th class="table-th-center">Enrolled</th>
                        <th class="table-th-center">Conv %</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($topProgrammes as $row)
                    @php $convRate = $row->total_leads > 0 ? round(($row->total_enrolments / $row->total_leads) * 100, 1) : 0; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="table-td">
                            <div class="font-medium text-gray-900">{{ $row->programme }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $row->code }}</div>
                        </td>
                        <td class="table-td-center tabular-nums">{{ number_format($row->total_leads) }}</td>
                        <td class="table-td-center tabular-nums font-semibold text-green-700">{{ number_format($row->total_enrolments) }}</td>
                        <td class="table-td-center">
                            @if($convRate >= 30)
                                <span class="badge-success">{{ $convRate }}%</span>
                            @elseif($convRate >= 10)
                                <span class="badge-warning">{{ $convRate }}%</span>
                            @else
                                <span class="badge-danger">{{ $convRate }}%</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="table-td text-center text-gray-400">No enrolment data for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Campus Breakdown --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Campus Breakdown</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Campus</th>
                        <th class="table-th-center">Leads</th>
                        <th class="table-th-center">Applied</th>
                        <th class="table-th-center">Enrolled</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($campusBreakdown as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td font-medium text-gray-900">{{ $row->campus }}</td>
                        <td class="table-td-center tabular-nums">{{ number_format($row->total_leads) }}</td>
                        <td class="table-td-center tabular-nums">{{ number_format($row->total_applications) }}</td>
                        <td class="table-td-center tabular-nums font-semibold text-green-700">{{ number_format($row->total_enrolments) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="table-td text-center text-gray-400">No campus data available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    @if($npsLatest && $npsTrend->count() > 1)
    (function () {
        const npsTrend = @json($npsTrend->map(fn($s) => ['date' => $s->survey_date->format('M y'), 'score' => $s->nps_score]));
        new Chart(document.getElementById('npsSparkline'), {
            type: 'line',
            data: {
                labels: npsTrend.map(t => t.date),
                datasets: [{
                    data: npsTrend.map(t => t.score),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' NPS: ' + ctx.parsed.y } } },
                scales: {
                    y: { ticks: { font: { size: 10 }, precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { ticks: { font: { size: 10 } } },
                },
            },
        });
    })();
    @endif
    (function () {
        const trend = @json($trend);
        const labels = trend.map(t => {
            const [y, m] = t.month.split('-');
            return new Date(y, m - 1).toLocaleDateString('en-IN', { month: 'short', year: '2-digit' });
        });

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Leads',
                        data: trend.map(t => t.leads),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Enrolments',
                        data: trend.map(t => t.enrolments),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Revenue (₹)',
                        data: trend.map(t => t.revenue),
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        borderDash: [4, 3],
                        tension: 0.3,
                        pointRadius: 3,
                        yAxisID: 'y2',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 12 } } },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const v = ctx.parsed.y;
                                return ctx.dataset.yAxisID === 'y2'
                                    ? ` Revenue: ₹${v.toLocaleString('en-IN')}`
                                    : ` ${ctx.dataset.label}: ${v.toLocaleString()}`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: { precision: 0, font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                    y2: {
                        beginAtZero: true,
                        position: 'right',
                        ticks: {
                            font: { size: 11 },
                            callback: v => '₹' + Number(v).toLocaleString('en-IN'),
                        },
                        grid: { drawOnChartArea: false },
                    },
                    x: { ticks: { font: { size: 11 } } },
                },
            },
        });
    })();
    </script>
    @endpush
</x-layouts.crm>
