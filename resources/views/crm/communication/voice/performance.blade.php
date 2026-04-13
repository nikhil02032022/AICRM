{{-- BRD: CRM-TC-007 — Call centre performance dashboard --}}
<x-layouts.crm title="Call Centre Performance">
    <div class="space-y-6">
        {{-- Page header with date filter --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Call Centre Performance</h1>
                <p class="mt-0.5 text-sm text-gray-500">Real-time agent performance, call volume trends, and conversion rates for data-driven telecalling management.</p>
            </div>
            <form action="{{ route('crm.communication.voice.performance') }}" method="GET" class="flex items-center gap-2">
                <label for="from_date" class="sr-only">From Date</label>
                <input id="from_date" type="date" name="from_date" value="{{ $fromDate }}" class="input-field w-36 text-sm" required>
                <span class="text-sm font-medium text-gray-500">to</span>
                <label for="to_date" class="sr-only">To Date</label>
                <input id="to_date" type="date" name="to_date" value="{{ $toDate }}" class="input-field w-36 text-sm" required>
                <button type="submit" class="btn-primary-sm min-h-11">Filter</button>
            </form>
        </div>

        @php
            $summary = $report['summary'];
            $perAgent = $report['per_agent'];
            $totalCalls = (int) $summary['total_calls_made'];
            $totalConnects = (int) $summary['total_connects'];
            $totalTalkTime = (int) $summary['total_talk_time_seconds'];
            $totalConversions = (int) $summary['total_conversions'];
            $agentCount = (int) $summary['agent_count'];
            
            $avgTalkTimeFormatted = $totalConnects > 0 ? gmdate('i:s', (int) ($totalTalkTime / $totalConnects)) : '0:00';
            $connectRatePercent = $totalCalls > 0 ? round(($totalConnects / $totalCalls) * 100, 1) : 0.0;
            $conversionRatePercent = $totalConnects > 0 ? round(($totalConversions / $totalConnects) * 100, 1) : 0.0;
        @endphp

        {{-- Summary metrics --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Total Calls Made</p>
                <p class="mt-2 text-3xl font-bold text-blue-900">{{ number_format($totalCalls) }}</p>
                <p class="mt-1 text-xs text-blue-600">by {{ $agentCount }} agent{{ $agentCount !== 1 ? 's' : '' }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Connect Rate</p>
                <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $connectRatePercent }}%</p>
                <p class="mt-1 text-xs text-emerald-600">{{ number_format($totalConnects) }} connects</p>
            </div>
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Avg Talk Time</p>
                <p class="mt-2 text-3xl font-bold text-violet-900">{{ $avgTalkTimeFormatted }}</p>
                <p class="mt-1 text-xs text-violet-600">{{ gmdate('H:i:s', $totalTalkTime) }} total</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Conversion Rate</p>
                <p class="mt-2 text-3xl font-bold text-amber-900">{{ $conversionRatePercent }}%</p>
                <p class="mt-1 text-xs text-amber-600">{{ number_format($totalConversions) }} conversions</p>
            </div>
        </div>

        @if(empty($perAgent))
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
                <p class="text-sm font-semibold text-gray-700">No call activity recorded for the selected period.</p>
                <p class="mt-1 text-sm text-gray-500">Adjust the date filter or check that dialler sessions have been launched.</p>
            </div>
        @else
            {{-- Charts section --}}
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 class="text-sm font-bold text-gray-900">Calls Made by Agent</h2>
                    <div class="relative mt-4 h-72 w-full">
                        <canvas id="agentCallsChart" role="img" aria-label="Bar chart showing calls made per agent"></canvas>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-gray-900">Daily Call Volume Trend</h2>
                    <div class="relative mt-4 h-72 w-full">
                        <canvas id="volumeTrendChart" role="img" aria-label="Line chart showing daily call volume trend"></canvas>
                    </div>
                </div>
            </div>

            {{-- Performance table --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-gray-900">Agent Performance Breakdown</h2>
                    <p class="mt-1 text-xs text-gray-500">Period: {{ $fromDate }} to {{ $toDate }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Agent</th>
                                <th class="px-4 py-3 text-right">Calls</th>
                                <th class="px-4 py-3 text-right">Connects</th>
                                <th class="px-4 py-3 text-right">Connect%</th>
                                <th class="px-4 py-3 text-right">Avg Talk</th>
                                <th class="px-4 py-3 text-right">Total Talk</th>
                                <th class="px-4 py-3 text-right">Conversions</th>
                                <th class="px-4 py-3 text-right">Conv%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($perAgent as $row)
                                @php
                                    $callsMade = (int) $row['calls_made'];
                                    $connects = (int) $row['connects'];
                                    $conversions = (int) $row['conversions'];
                                    $connectRate = (float) $row['connect_rate_percent'];
                                    $conversionRate = (float) $row['conversion_rate_percent'];
                                    $avgTalkTime = (int) $row['avg_talk_time_seconds'];
                                    $totalTalkTime = (int) $row['total_talk_time_seconds'];
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $row['agent_name'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-blue-700">{{ number_format($callsMade) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ number_format($connects) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $connectRate >= 60 ? 'bg-emerald-100 text-emerald-700' : ($connectRate >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $connectRate }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ gmdate('i:s', $avgTalkTime) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 text-xs">{{ gmdate('H:i:s', $totalTalkTime) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-amber-700">{{ number_format($conversions) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $conversionRate >= 15 ? 'bg-emerald-100 text-emerald-700' : ($conversionRate >= 8 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700') }}">
                                            {{ $conversionRate }}%
                                        </span>
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
        @if(!empty($perAgent))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const perAgent = @json($perAgent);
                    const volumeTrendData = @json($volumeTrend);

                    // Chart 1: Calls Made by Agent (Bar Chart)
                    const agentLabels = perAgent.map((row) => row.agent_name);
                    const callsMadeData = perAgent.map((row) => row.calls_made);
                    const connectsData = perAgent.map((row) => row.connects);

                    new Chart(document.getElementById('agentCallsChart'), {
                        type: 'bar',
                        data: {
                            labels: agentLabels,
                            datasets: [
                                {
                                    label: 'Calls Made',
                                    data: callsMadeData,
                                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                    borderColor: 'rgba(59, 130, 246, 1)',
                                    borderWidth: 1.5,
                                },
                                {
                                    label: 'Connects',
                                    data: connectsData,
                                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                                    borderColor: 'rgba(16, 185, 129, 1)',
                                    borderWidth: 1.5,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                    },
                                },
                            },
                        },
                    });

                    // Chart 2: Daily Call Volume Trend (Line Chart)
                    const trendLabels = volumeTrendData.map((row) => row.date);
                    const trendCallsData = volumeTrendData.map((row) => row.calls);
                    const trendConnectsData = volumeTrendData.map((row) => row.connects);

                    new Chart(document.getElementById('volumeTrendChart'), {
                        type: 'line',
                        data: {
                            labels: trendLabels,
                            datasets: [
                                {
                                    label: 'Calls',
                                    data: trendCallsData,
                                    borderColor: 'rgba(99, 102, 241, 1)',
                                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.3,
                                },
                                {
                                    label: 'Connects',
                                    data: trendConnectsData,
                                    borderColor: 'rgba(16, 185, 129, 1)',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.3,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                    },
                                },
                            },
                        },
                    });
                });
            </script>
        @endif
    @endpush
</x-layouts.crm>
