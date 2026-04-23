{{-- BRD: CRM-AR-012 — Source Effectiveness Report: per-source funnel from lead to enrolment --}}
<x-layouts.crm title="Source Effectiveness Report">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <span>Reports</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">Source Effectiveness</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Source Effectiveness Report</h1>
        <p class="mt-1 text-sm text-gray-500">Per-channel funnel breakdown — leads acquired, applications, offers issued, and enrolments — for the selected period.</p>
    </x-slot:header>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('crm.analytics.reports.source-effectiveness') }}"
          class="mb-6 card p-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">

            {{-- Date From --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" value="{{ $filters['from'] }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" value="{{ $filters['to'] }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Campus (director/multi-campus only) --}}
            @if($scope['role'] !== 'counsellor' && $campuses->count() > 1 && !$scope['campus_id'])
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Campus</label>
                <select name="campus_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" @selected((int)$filters['campus_id'] === $campus->id)>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

        </div>

        <div class="mt-3 flex items-center gap-2">
            <button type="submit" class="btn-primary-sm">Apply Filters</button>
            <a href="{{ route('crm.analytics.reports.source-effectiveness') }}" class="btn-ghost-sm">Reset</a>
        </div>
    </form>

    {{-- Results Header --}}
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-600">
            <span class="font-semibold text-gray-900 tabular-nums">{{ $rows->count() }}</span>
            {{ Str::plural('source', $rows->count()) }} with activity
            &middot;
            {{ \Carbon\Carbon::parse($filters['from'])->format('d M Y') }}
            –
            {{ \Carbon\Carbon::parse($filters['to'])->format('d M Y') }}
        </p>

        {{-- Export buttons (AR-019) --}}
        @can('crm.reports.export')
        <div class="flex items-center gap-2">
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'source-effectiveness']) . '?' . http_build_query(array_filter($filters) + ['format' => 'excel']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
               title="Export to Excel">
                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Excel
            </a>
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'source-effectiveness']) . '?' . http_build_query(array_filter($filters) + ['format' => 'pdf']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
               title="Export to PDF">
                <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                PDF
            </a>
        </div>
        @endcan
    </div>

    {{-- Source Effectiveness Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">#</th>
                        <th class="table-th">Source / Channel</th>
                        <th class="table-th text-right">Leads</th>
                        <th class="table-th text-right">Applied</th>
                        <th class="table-th text-right">Offered</th>
                        <th class="table-th text-right">Enrolled</th>
                        <th class="table-th text-right whitespace-nowrap">Lead → Apply</th>
                        <th class="table-th text-right whitespace-nowrap">Apply → Enrol</th>
                        <th class="table-th text-right whitespace-nowrap">Overall Rate</th>
                        <th class="table-th-center">ROI Signal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        // Resolve LeadSource labels once — source value may not match an active enum case
                        $sourceLabels = collect(\App\Enums\CRM\LeadSource::cases())->keyBy('value')
                            ->map(fn ($s) => $s->label());
                    @endphp

                    @forelse($rows as $index => $row)
                    @php
                        $leadToApply  = $row->total_leads > 0
                            ? round(($row->applied  / $row->total_leads) * 100, 1) : null;
                        $applyToEnrol = $row->applied > 0
                            ? round(($row->enrolled / $row->applied)      * 100, 1) : null;
                        $overall      = $row->total_leads > 0
                            ? round(($row->enrolled / $row->total_leads)  * 100, 1) : null;

                        // ROI signal: High ≥20%, Medium ≥10%, Low >0%, None = 0
                        $roiBadge = match(true) {
                            $overall === null || $overall == 0 => ['label' => 'No enrolments', 'class' => 'bg-gray-100 text-gray-500'],
                            $overall >= 20                     => ['label' => 'High',           'class' => 'bg-green-100 text-green-800'],
                            $overall >= 10                     => ['label' => 'Medium',         'class' => 'bg-amber-100 text-amber-800'],
                            default                            => ['label' => 'Low',            'class' => 'bg-red-100 text-red-800'],
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">

                        <td class="table-td text-gray-400 tabular-nums text-xs">{{ $index + 1 }}</td>

                        {{-- Source label --}}
                        <td class="table-td">
                            <span class="font-medium text-gray-900">
                                {{ $sourceLabels->get($row->source, ucwords(str_replace('_', ' ', $row->source))) }}
                            </span>
                        </td>

                        {{-- Leads --}}
                        <td class="table-td text-right tabular-nums font-semibold text-gray-900">
                            {{ number_format($row->total_leads) }}
                        </td>

                        {{-- Applied --}}
                        <td class="table-td text-right tabular-nums text-gray-700">
                            {{ number_format($row->applied) }}
                        </td>

                        {{-- Offered --}}
                        <td class="table-td text-right tabular-nums text-gray-700">
                            {{ number_format($row->offered) }}
                        </td>

                        {{-- Enrolled --}}
                        <td class="table-td text-right tabular-nums font-medium text-gray-900">
                            {{ number_format($row->enrolled) }}
                        </td>

                        {{-- Lead → Apply % --}}
                        <td class="table-td text-right tabular-nums">
                            @if($leadToApply !== null)
                                <span class="{{ $leadToApply >= 50 ? 'text-green-700' : ($leadToApply >= 25 ? 'text-amber-700' : 'text-gray-500') }} font-medium">
                                    {{ $leadToApply }}%
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Apply → Enrol % --}}
                        <td class="table-td text-right tabular-nums">
                            @if($applyToEnrol !== null)
                                <span class="{{ $applyToEnrol >= 50 ? 'text-green-700' : ($applyToEnrol >= 25 ? 'text-amber-700' : 'text-gray-500') }} font-medium">
                                    {{ $applyToEnrol }}%
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Overall % --}}
                        <td class="table-td text-right tabular-nums">
                            @if($overall !== null)
                                <span class="{{ $overall >= 20 ? 'text-green-700' : ($overall >= 10 ? 'text-amber-700' : 'text-red-600') }} font-semibold">
                                    {{ $overall }}%
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- ROI Signal badge --}}
                        <td class="table-td-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $roiBadge['class'] }}">
                                {{ $roiBadge['label'] }}
                            </span>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="table-td text-center text-gray-400 py-16">
                            <svg class="mx-auto mb-3 h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/>
                            </svg>
                            No lead data found for the selected period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- Totals footer --}}
                @if($rows->isNotEmpty())
                @php
                    $totLeads    = $rows->sum('total_leads');
                    $totApplied  = $rows->sum('applied');
                    $totOffered  = $rows->sum('offered');
                    $totEnrolled = $rows->sum('enrolled');
                    $totLta      = $totLeads   > 0 ? round(($totApplied  / $totLeads)   * 100, 1) : null;
                    $totAte      = $totApplied > 0 ? round(($totEnrolled / $totApplied) * 100, 1) : null;
                    $totOverall  = $totLeads   > 0 ? round(($totEnrolled / $totLeads)   * 100, 1) : null;
                @endphp
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td class="table-td text-xs text-gray-500 font-medium" colspan="2">
                            All Sources ({{ $rows->count() }})
                        </td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-900">{{ number_format($totLeads) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ number_format($totApplied) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ number_format($totOffered) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-900">{{ number_format($totEnrolled) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ $totLta !== null ? $totLta . '%' : '—' }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ $totAte !== null ? $totAte . '%' : '—' }}</td>
                        <td class="table-td text-right tabular-nums font-semibold {{ $totOverall !== null && $totOverall >= 20 ? 'text-green-700' : 'text-gray-700' }}">{{ $totOverall !== null ? $totOverall . '%' : '—' }}</td>
                        <td class="table-td"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-layouts.crm>
