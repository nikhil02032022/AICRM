{{-- BRD: CRM-AR-016 — Year-on-Year Comparison Report: current vs previous year KPIs and breakdown --}}
<x-layouts.crm title="Year-on-Year Comparison">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <span>Reports</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">Year-on-Year Comparison</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Year-on-Year Comparison</h1>
        <p class="mt-1 text-sm text-gray-500">
            Comparing {{ $summary->year }} vs {{ $summary->prev_year }} — leads, applications, enrolments, and revenue.
        </p>
    </x-slot:header>

    {{-- KPI Comparison Tiles --}}
    @php
        $trendIcon = fn ($pct) => match (true) {
            $pct === null         => '',
            $pct > 0              => '↑',
            $pct < 0              => '↓',
            default               => '→',
        };
        $trendClass = fn ($pct) => match (true) {
            $pct === null         => 'text-gray-400',
            $pct > 0              => 'text-green-600',
            $pct < 0              => 'text-red-600',
            default               => 'text-gray-500',
        };
    @endphp

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">

        {{-- Leads --}}
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Leads</p>
            <div class="mt-1 flex items-end gap-2">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($summary->leads['current']) }}</p>
                <span class="text-sm {{ $trendClass($summary->leads['pct']) }} font-medium mb-0.5">
                    {{ $trendIcon($summary->leads['pct']) }}
                    @if($summary->leads['pct'] !== null)
                        {{ abs($summary->leads['pct']) }}%
                    @endif
                </span>
            </div>
            <p class="mt-0.5 text-xs text-gray-400">
                {{ $summary->prev_year }}: {{ number_format($summary->leads['previous']) }}
                &bull; Δ {{ $summary->leads['delta'] >= 0 ? '+' : '' }}{{ number_format($summary->leads['delta']) }}
            </p>
        </div>

        {{-- Applications --}}
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Applications</p>
            <div class="mt-1 flex items-end gap-2">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($summary->applied['current']) }}</p>
                <span class="text-sm {{ $trendClass($summary->applied['pct']) }} font-medium mb-0.5">
                    {{ $trendIcon($summary->applied['pct']) }}
                    @if($summary->applied['pct'] !== null)
                        {{ abs($summary->applied['pct']) }}%
                    @endif
                </span>
            </div>
            <p class="mt-0.5 text-xs text-gray-400">
                {{ $summary->prev_year }}: {{ number_format($summary->applied['previous']) }}
                &bull; Δ {{ $summary->applied['delta'] >= 0 ? '+' : '' }}{{ number_format($summary->applied['delta']) }}
            </p>
        </div>

        {{-- Enrolments --}}
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Enrolments</p>
            <div class="mt-1 flex items-end gap-2">
                <p class="text-2xl font-bold text-indigo-600">{{ number_format($summary->enrolled['current']) }}</p>
                <span class="text-sm {{ $trendClass($summary->enrolled['pct']) }} font-medium mb-0.5">
                    {{ $trendIcon($summary->enrolled['pct']) }}
                    @if($summary->enrolled['pct'] !== null)
                        {{ abs($summary->enrolled['pct']) }}%
                    @endif
                </span>
            </div>
            <p class="mt-0.5 text-xs text-gray-400">
                {{ $summary->prev_year }}: {{ number_format($summary->enrolled['previous']) }}
                &bull; Δ {{ $summary->enrolled['delta'] >= 0 ? '+' : '' }}{{ number_format($summary->enrolled['delta']) }}
            </p>
        </div>

        {{-- Revenue --}}
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Revenue</p>
            <div class="mt-1 flex items-end gap-2">
                <p class="text-2xl font-bold text-green-600">₹{{ number_format($summary->revenue['current'], 0) }}</p>
                <span class="text-sm {{ $trendClass($summary->revenue['pct']) }} font-medium mb-0.5">
                    {{ $trendIcon($summary->revenue['pct']) }}
                    @if($summary->revenue['pct'] !== null)
                        {{ abs($summary->revenue['pct']) }}%
                    @endif
                </span>
            </div>
            <p class="mt-0.5 text-xs text-gray-400">
                {{ $summary->prev_year }}: ₹{{ number_format($summary->revenue['previous'], 0) }}
            </p>
        </div>
    </div>

    {{-- Filter / Group-by Form --}}
    <form method="GET" action="{{ route('crm.analytics.reports.year-on-year') }}"
          class="mb-6 card p-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Compare Year</label>
                <select name="year"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected((string)$filters['year'] === (string)$y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Group By</label>
                <select name="group_by"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="programme" @selected($filters['group_by'] === 'programme')>Programme</option>
                    <option value="source"    @selected($filters['group_by'] === 'source')>Lead Source</option>
                    <option value="campus"    @selected($filters['group_by'] === 'campus')>Campus</option>
                </select>
            </div>

            @if(!$scope['campus_id'])
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Campus</label>
                <select name="campus_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" @selected((string)$filters['campus_id'] === (string)$campus->id)>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors">
                    Apply
                </button>
                <a href="{{ route('crm.analytics.reports.year-on-year') }}"
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Reset
                </a>
                {{-- AR-019 export placeholders --}}
                <div class="ml-auto flex gap-2">
                    <button type="button" disabled
                            class="px-3 py-2 border border-gray-300 text-gray-400 text-sm rounded-lg cursor-not-allowed flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Excel
                    </button>
                    <button type="button" disabled
                            class="px-3 py-2 border border-gray-300 text-gray-400 text-sm rounded-lg cursor-not-allowed flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        PDF
                    </button>
                </div>
            </div>

        </div>
    </form>

    {{-- Breakdown Table --}}
    @php
        $groupLabel = match($filters['group_by']) {
            'source' => 'Lead Source',
            'campus' => 'Campus',
            default  => 'Programme',
        };
    @endphp

    @if($breakdown->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-gray-500 text-sm">No data found for {{ $summary->year }} or {{ $summary->prev_year }}.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $groupLabel }}</th>
                            {{-- Leads --}}
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $summary->prev_year }} Leads</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $summary->year }} Leads</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Δ Leads</th>
                            {{-- Applications --}}
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $summary->prev_year }} Apps</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $summary->year }} Apps</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Δ Apps</th>
                            {{-- Enrolments --}}
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $summary->prev_year }} Enrolled</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $summary->year }} Enrolled</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Δ Enrolled</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($breakdown as $row)
                            @php
                                $dLeads   = (int) $row->current_leads    - (int) $row->prev_leads;
                                $dApplied = (int) $row->current_applied  - (int) $row->prev_applied;
                                $dEnrolled= (int) $row->current_enrolled - (int) $row->prev_enrolled;

                                $deltaClass = fn ($d) => $d > 0
                                    ? 'text-green-600 font-medium'
                                    : ($d < 0 ? 'text-red-600 font-medium' : 'text-gray-400');
                                $deltaFmt   = fn ($d) => ($d >= 0 ? '+' : '') . number_format($d);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    @if($filters['group_by'] === 'source')
                                        @php
                                            $sourceCase = \App\Enums\CRM\LeadSource::tryFrom($row->label_key);
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                            {{ $sourceCase?->label() ?? $row->label }}
                                        </span>
                                    @else
                                        {{ $row->label }}
                                    @endif
                                </td>
                                {{-- Leads --}}
                                <td class="px-3 py-3 text-right text-gray-500 tabular-nums">{{ number_format((int) $row->prev_leads) }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ number_format((int) $row->current_leads) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums {{ $deltaClass($dLeads) }}">{{ $deltaFmt($dLeads) }}</td>
                                {{-- Applications --}}
                                <td class="px-3 py-3 text-right text-gray-500 tabular-nums">{{ number_format((int) $row->prev_applied) }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ number_format((int) $row->current_applied) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums {{ $deltaClass($dApplied) }}">{{ $deltaFmt($dApplied) }}</td>
                                {{-- Enrolments --}}
                                <td class="px-3 py-3 text-right text-gray-500 tabular-nums">{{ number_format((int) $row->prev_enrolled) }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ number_format((int) $row->current_enrolled) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums {{ $deltaClass($dEnrolled) }}">{{ $deltaFmt($dEnrolled) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    {{-- Totals Footer --}}
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td class="px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wide">Total</td>
                            <td class="px-3 py-3 text-right font-semibold text-gray-700 tabular-nums">{{ number_format($summary->leads['previous']) }}</td>
                            <td class="px-3 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ number_format($summary->leads['current']) }}</td>
                            <td class="px-3 py-3 text-right font-semibold tabular-nums {{ $summary->leads['delta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ ($summary->leads['delta'] >= 0 ? '+' : '') . number_format($summary->leads['delta']) }}
                            </td>
                            <td class="px-3 py-3 text-right font-semibold text-gray-700 tabular-nums">{{ number_format($summary->applied['previous']) }}</td>
                            <td class="px-3 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ number_format($summary->applied['current']) }}</td>
                            <td class="px-3 py-3 text-right font-semibold tabular-nums {{ $summary->applied['delta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ ($summary->applied['delta'] >= 0 ? '+' : '') . number_format($summary->applied['delta']) }}
                            </td>
                            <td class="px-3 py-3 text-right font-semibold text-gray-700 tabular-nums">{{ number_format($summary->enrolled['previous']) }}</td>
                            <td class="px-3 py-3 text-right font-semibold text-gray-900 tabular-nums">{{ number_format($summary->enrolled['current']) }}</td>
                            <td class="px-3 py-3 text-right font-semibold tabular-nums {{ $summary->enrolled['delta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ ($summary->enrolled['delta'] >= 0 ? '+' : '') . number_format($summary->enrolled['delta']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <p class="mt-2 text-xs text-gray-400 text-right">
            {{ $breakdown->count() }} {{ $groupLabel === 'Lead Source' ? 'sources' : strtolower($groupLabel) . 's' }} shown
        </p>
    @endif
</x-layouts.crm>
