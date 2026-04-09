{{-- BRD: CRM-LQ-008 — Source quality report: avg score + conversion rate by lead source --}}
<x-layouts.crm title="Source Quality Report">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Source Quality Report</h1>
                <p class="mt-0.5 text-sm text-gray-500">Average lead score and conversion rate by acquisition channel.</p>
            </div>
            <div class="flex items-center gap-2">
                @can('update', \App\Models\CRM\InstitutionScoringConfig::class)
                <a href="{{ route('crm.scoring.config') }}" class="btn-secondary-sm">
                    Configure Scoring →
                </a>
                @endcan
                <a href="{{ route('crm.leads.index') }}" class="btn-secondary-sm">Back to Leads</a>
            </div>
        </div>

        @if($report->isEmpty())
        <div class="card flex flex-col items-center justify-center py-20">
            <svg class="mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
            </svg>
            <p class="text-sm font-medium text-gray-400">No leads found. Create some leads to see source analytics.</p>
        </div>
        @else

        {{-- KPI tiles --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @php
                $topSource  = $report->first();
                $totalLeads = $report->sum('total');
                $avgScore   = $report->isNotEmpty() ? round($report->avg('avg_score'), 1) : 0;
                $totalConv  = $report->sum('converted');
                $overallConvRate = $totalLeads > 0 ? round(($totalConv / $totalLeads) * 100, 1) : 0;
            @endphp
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="h-[3px] bg-indigo-500"></div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Leads</p>
                    <p class="mt-2 text-2xl font-bold leading-none text-gray-900">{{ number_format($totalLeads) }}</p>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="h-[3px] bg-violet-500"></div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Average Score</p>
                    <p class="mt-2 text-2xl font-bold leading-none text-violet-600">{{ $avgScore }}</p>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="h-[3px] bg-green-500"></div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Converted</p>
                    <p class="mt-2 text-2xl font-bold leading-none text-green-600">{{ number_format($totalConv) }}</p>
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="h-[3px] bg-emerald-500"></div>
                <div class="px-4 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Overall Conv. Rate</p>
                    <p class="mt-2 text-2xl font-bold leading-none text-emerald-600">{{ $overallConvRate }}%</p>
                </div>
            </div>
        </div>

        {{-- Charts + Table grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Bar chart: Avg Score by Source --}}
            <div class="card p-5 lg:col-span-2">
                <h2 class="mb-4 text-sm font-bold text-gray-900">Average Lead Score by Source</h2>
                <div style="position:relative;height:280px">
                    <canvas id="scoreBySourceChart" aria-label="Bar chart showing average lead score per source"></canvas>
                </div>
            </div>

            {{-- Pie chart: Lead distribution --}}
            <div class="card p-5">
                <h2 class="mb-4 text-sm font-bold text-gray-900">Lead Distribution by Source</h2>
                <div style="position:relative;height:280px">
                    <canvas id="sourceDistributionChart" aria-label="Pie chart showing lead distribution per source"></canvas>
                </div>
            </div>

        </div>

        {{-- Data table --}}
        <div class="card overflow-hidden p-0">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-bold text-gray-900">Detailed Breakdown by Source</h2>
                <p class="mt-0.5 text-xs text-gray-500">Sorted by average lead score (highest first).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Source Channel</th>
                            <th class="px-6 py-3 text-right">Total Leads</th>
                            <th class="px-6 py-3 text-right">Avg Score</th>
                            <th class="px-6 py-3 text-right">Converted</th>
                            <th class="px-6 py-3 text-right">Conversion Rate</th>
                            <th class="px-6 py-3">Quality Tier</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($report as $i => $row)
                        @php
                            $tier = match(true) {
                                $row->avg_score >= 70 => ['label' => 'Premium', 'class' => 'badge-green'],
                                $row->avg_score >= 50 => ['label' => 'Good', 'class' => 'badge-blue'],
                                $row->avg_score >= 30 => ['label' => 'Average', 'class' => 'badge-orange'],
                                default               => ['label' => 'Low', 'class' => 'badge-gray'],
                            };
                            $scoreColor = match(true) {
                                $row->avg_score >= 70 => 'text-green-600',
                                $row->avg_score >= 50 => 'text-blue-600',
                                $row->avg_score >= 30 => 'text-orange-500',
                                default               => 'text-gray-400',
                            };
                            $sourceLabel = '';
                            try {
                                $sourceLabel = \App\Enums\CRM\LeadSource::from($row->source)->label();
                            } catch (\Throwable) {
                                $sourceLabel = ucwords(str_replace('_', ' ', $row->source));
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors duration-100">
                            <td class="px-6 py-3.5 text-xs font-mono text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                    {{ $sourceLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-semibold text-gray-900">{{ number_format($row->total) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="font-bold tabular-nums {{ $scoreColor }}">{{ $row->avg_score }}</span>
                                <span class="text-xs text-gray-400"> / 100</span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-semibold text-gray-900">{{ number_format($row->converted) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <span class="font-bold tabular-nums {{ $row->conversion_rate >= 30 ? 'text-green-600' : ($row->conversion_rate >= 10 ? 'text-blue-600' : 'text-gray-400') }}">
                                    {{ $row->conversion_rate }}%
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="badge {{ $tier['class'] }}">{{ $tier['label'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
    @if(!$report->isEmpty())
    document.addEventListener('DOMContentLoaded', function () {
        const labels  = @json($report->pluck('source')->map(fn($s) => ucwords(str_replace('_', ' ', $s))));
        const scores  = @json($report->pluck('avg_score'));
        const totals  = @json($report->pluck('total'));
        const palette = ['#6366F1','#8B5CF6','#3B82F6','#10B981','#F59E0B','#EF4444','#EC4899','#14B8A6','#F97316','#84CC16','#06B6D4','#A855F7','#0EA5E9','#22C55E'];

        // Bar chart — avg score by source
        new Chart(document.getElementById('scoreBySourceChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg Lead Score',
                    data: scores,
                    backgroundColor: palette.slice(0, labels.length).map(c => c + 'CC'),
                    borderColor: palette.slice(0, labels.length),
                    borderWidth: 2,
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` Avg Score: ${ctx.raw} / 100`
                        }
                    }
                },
                scales: {
                    y: { min: 0, max: 100, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                },
            },
        });

        // Pie chart — lead distribution
        new Chart(document.getElementById('sourceDistributionChart'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: totals,
                    backgroundColor: palette.slice(0, labels.length).map(c => c + 'CC'),
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, boxWidth: 10, padding: 12 },
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.raw} leads`
                        }
                    }
                },
            },
        });
    });
    @endif
    </script>
    @endpush
</x-layouts.crm>
