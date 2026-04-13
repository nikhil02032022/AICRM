{{-- BRD: CRM-AI-009 — Anomaly alert dashboard for funnel drop-off monitoring --}}
<x-layouts.crm title="Anomaly Alerts">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Anomaly Alerts</h1>
                <p class="mt-0.5 text-sm text-gray-500">Detect unusual drops in enquiry and application volumes using rolling baseline windows.</p>
            </div>
            <form action="{{ route('crm.scoring.anomaly-alerts.detect') }}" method="POST" class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                @csrf
                <label for="for_date" class="sr-only">For date</label>
                <input id="for_date" type="date" name="for_date" value="{{ $forDate }}" class="input-field text-sm" required>
                <select name="window_days" class="input-field text-sm" aria-label="Window days">
                    <option value="7" selected>7 days</option>
                    <option value="14">14 days</option>
                </select>
                <select name="baseline_days" class="input-field text-sm" aria-label="Baseline days">
                    <option value="28" selected>28 days</option>
                    <option value="56">56 days</option>
                </select>
                <select name="threshold_percent" class="input-field text-sm" aria-label="Threshold percent">
                    <option value="25" selected>25% drop</option>
                    <option value="30">30% drop</option>
                    <option value="40">40% drop</option>
                </select>
                <button type="submit" class="btn-primary-sm min-h-11">Run Detection</button>
            </form>
        </div>

        @php
            $collection = collect($rows->resolve());
            $criticalCount = $collection->where('severity', 'critical')->count();
            $highCount = $collection->where('severity', 'high')->count();
            $mediumCount = $collection->where('severity', 'medium')->count();
        @endphp

        <form method="GET" action="{{ route('crm.scoring.anomaly-alerts') }}" class="flex items-center gap-2">
            <input type="hidden" name="for_date" value="{{ $forDate }}">
            <label for="severity" class="text-xs font-semibold uppercase tracking-wide text-gray-500">Severity Filter</label>
            <select id="severity" name="severity" class="input-field w-44 text-sm" onchange="this.form.submit()">
                <option value="" @selected($severity === '')>All</option>
                <option value="critical" @selected($severity === 'critical')>Critical</option>
                <option value="high" @selected($severity === 'high')>High</option>
                <option value="medium" @selected($severity === 'medium')>Medium</option>
            </select>
        </form>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Critical Alerts</p>
                <p class="mt-2 text-2xl font-bold text-red-900">{{ $criticalCount }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">High Alerts</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">{{ $highCount }}</p>
            </div>
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Medium Alerts</p>
                <p class="mt-2 text-2xl font-bold text-blue-900">{{ $mediumCount }}</p>
            </div>
        </div>

        @if($collection->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
                <p class="text-sm font-semibold text-gray-700">No anomaly alerts detected for {{ $forDate }}.</p>
                <p class="mt-1 text-sm text-gray-500">Run detection to evaluate drop-off patterns against baseline windows.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-gray-900">Detected Alerts</h2>
                    <p class="mt-1 text-xs text-gray-500">Date: {{ $forDate }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Metric</th>
                                <th class="px-4 py-3 text-right">Current</th>
                                <th class="px-4 py-3 text-right">Baseline</th>
                                <th class="px-4 py-3 text-right">Deviation</th>
                                <th class="px-4 py-3">Severity</th>
                                <th class="px-4 py-3">Rationale</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($collection as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ str($row['metric_name'])->replace('_', ' ')->title() }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ $row['current_value'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ $row['baseline_value'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-red-700">{{ $row['deviation_percent'] }}%</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $row['severity'] === 'critical' ? 'bg-red-100 text-red-700' : ($row['severity'] === 'high' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">
                                            {{ strtoupper($row['severity']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $row['rationale'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-layouts.crm>
