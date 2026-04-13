{{-- BRD: CRM-AI-008 — Programme-level monthly enrolment forecast dashboard --}}
<x-layouts.crm title="Enrolment Forecasts">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Enrolment Forecasts</h1>
                <p class="mt-0.5 text-sm text-gray-500">AI-assisted forecast snapshots by programme with confidence signals for admission planning.</p>
            </div>
            <form action="{{ route('crm.scoring.enrolment-forecasts.generate') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <label for="for_month" class="sr-only">Forecast Month</label>
                <input id="for_month" type="month" name="for_month" value="{{ $forMonth }}" class="input-field w-40 text-sm" required>
                <button type="submit" class="btn-primary-sm min-h-11">Generate Forecast</button>
            </form>
        </div>

        @php
            $collection = collect($rows->resolve());
            $totalForecast = (int) $collection->sum('forecast_count');
            $averageConfidence = $collection->isNotEmpty() ? round((float) $collection->avg('confidence_score'), 1) : 0.0;
            $topProgramme = (string) ($collection->first()['programme_name'] ?? 'N/A');
        @endphp

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Total Forecasted Enrolments</p>
                <p class="mt-2 text-3xl font-bold text-indigo-900">{{ number_format($totalForecast) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Average Confidence</p>
                <p class="mt-2 text-3xl font-bold text-emerald-900">{{ $averageConfidence }}%</p>
            </div>
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Top Programme</p>
                <p class="mt-2 text-xl font-bold text-violet-900">{{ $topProgramme }}</p>
            </div>
        </div>

        @if($collection->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
                <p class="text-sm font-semibold text-gray-700">No forecast snapshots for {{ $forMonth }}.</p>
                <p class="mt-1 text-sm text-gray-500">Generate forecasts to view projected enrolments and confidence indicators.</p>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 class="text-sm font-bold text-gray-900">Forecast Count by Programme</h2>
                    <div class="relative mt-4 h-72 w-full">
                        <canvas id="forecastCountChart" role="img" aria-label="Bar chart showing forecasted enrolments per programme"></canvas>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold text-gray-900">Confidence Distribution</h2>
                    <div class="relative mt-4 h-72 w-full">
                        <canvas id="forecastConfidenceChart" role="img" aria-label="Doughnut chart showing confidence score distribution"></canvas>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-gray-900">Forecast Breakdown</h2>
                    <p class="mt-1 text-xs text-gray-500">Month: {{ $forMonth }} · Model: a2a-forecast-rules-v1</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Programme</th>
                                <th class="px-4 py-3 text-right">Forecast</th>
                                <th class="px-4 py-3 text-right">Confidence</th>
                                <th class="px-4 py-3">Inputs</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($collection as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $row['programme_name'] ?? 'Unmapped Programme' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-indigo-700">{{ $row['forecast_count'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ (int) $row['confidence_score'] >= 75 ? 'bg-emerald-100 text-emerald-700' : ((int) $row['confidence_score'] >= 55 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $row['confidence_score'] }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        Pipeline: {{ $row['inputs']['pipeline_ready'] ?? 0 }},
                                        Enrolled: {{ $row['inputs']['enrolled'] ?? 0 }},
                                        Momentum: {{ $row['inputs']['momentum'] ?? 1.0 }}
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
        @if($collection->isNotEmpty())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const rows = @json($collection->values());
                    const labels = rows.map((row) => row.programme_name || 'Unmapped');
                    const forecasts = rows.map((row) => row.forecast_count);
                    const confidence = rows.map((row) => row.confidence_score);
                    const palette = ['#4f46e5', '#0ea5e9', '#14b8a6', '#84cc16', '#f59e0b', '#f97316', '#ef4444', '#a855f7'];

                    new Chart(document.getElementById('forecastCountChart'), {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Forecasted Enrolments',
                                data: forecasts,
                                backgroundColor: palette.map((c) => c + 'CC'),
                                borderColor: palette,
                                borderWidth: 1.5,
                                borderRadius: 6,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                                x: { grid: { display: false } },
                            },
                        },
                    });

                    new Chart(document.getElementById('forecastConfidenceChart'), {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: confidence,
                                backgroundColor: palette.map((c) => c + 'CC'),
                                borderColor: '#ffffff',
                                borderWidth: 2,
                                hoverOffset: 4,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '58%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { boxWidth: 10, font: { size: 11 } },
                                },
                            },
                        },
                    });
                });
            </script>
        @endif
    @endpush
</x-layouts.crm>
