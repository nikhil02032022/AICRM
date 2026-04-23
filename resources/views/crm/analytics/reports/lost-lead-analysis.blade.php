{{-- BRD: CRM-AR-013 — Lost Lead Analysis Report: lost leads by reason, source, counsellor and days-to-loss --}}
<x-layouts.crm title="Lost Lead Analysis Report">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <span>Reports</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">Lost Lead Analysis</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Lost Lead Analysis Report</h1>
        <p class="mt-1 text-sm text-gray-500">Leads marked as Lost in the selected period — broken down by reason, source, and counsellor.</p>
    </x-slot:header>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('crm.analytics.reports.lost-lead-analysis') }}"
          class="mb-6 card p-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">

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

            {{-- Lost Reason --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Lost Reason</label>
                <select name="lost_reason"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Reasons</option>
                    @foreach($lostReasons as $reason)
                        <option value="{{ $reason->value }}" @selected($filters['lost_reason'] === $reason->value)>
                            {{ $reason->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Source --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Source</label>
                <select name="source"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Sources</option>
                    @foreach($sources as $source)
                        <option value="{{ $source->value }}" @selected($filters['source'] === $source->value)>
                            {{ $source->label() }}
                        </option>
                    @endforeach
                </select>
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

            {{-- Counsellor (manager/director only) --}}
            @if($scope['role'] !== 'counsellor' && $counsellors->isNotEmpty() && !$scope['counsellor_ids'])
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Counsellor</label>
                <select name="counsellor_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Counsellors</option>
                    @foreach($counsellors as $counsellor)
                        <option value="{{ $counsellor->id }}" @selected((int)$filters['counsellor_id'] === $counsellor->id)>
                            {{ $counsellor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

        </div>

        <div class="mt-3 flex items-center gap-2">
            <button type="submit" class="btn-primary-sm">Apply Filters</button>
            <a href="{{ route('crm.analytics.reports.lost-lead-analysis') }}" class="btn-ghost-sm">Reset</a>
        </div>
    </form>

    {{-- Reason Summary --}}
    @if($reasonSummary->isNotEmpty())
    @php
        $totalLost = $reasonSummary->sum('total');
    @endphp
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Loss Reason Breakdown</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($reasonSummary as $row)
            @php
                $pct = $totalLost > 0 ? round(($row->total / $totalLost) * 100, 1) : 0;
                $lostReasonEnum = \App\Enums\CRM\LostReason::tryFrom($row->lost_reason);
                $label = $lostReasonEnum?->label() ?? ucwords(str_replace('_', ' ', $row->lost_reason));
                $isActiveFilter = $filters['lost_reason'] === $row->lost_reason;
            @endphp
            <a href="{{ route('crm.analytics.reports.lost-lead-analysis', array_merge(request()->query(), ['lost_reason' => $isActiveFilter ? null : $row->lost_reason])) }}"
               class="card p-3 hover:shadow-md transition-shadow {{ $isActiveFilter ? 'ring-2 ring-indigo-500' : '' }}">
                <p class="text-xs text-gray-500 mb-1">{{ $label }}</p>
                <div class="flex items-end justify-between">
                    <span class="text-xl font-bold text-gray-900 tabular-nums">{{ number_format($row->total) }}</span>
                    <span class="text-sm font-medium {{ $pct >= 30 ? 'text-red-600' : ($pct >= 15 ? 'text-amber-600' : 'text-gray-500') }}">
                        {{ $pct }}%
                    </span>
                </div>
                <div class="mt-2 h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-1.5 rounded-full {{ $pct >= 30 ? 'bg-red-400' : ($pct >= 15 ? 'bg-amber-400' : 'bg-gray-300') }}"
                         style="width: {{ $pct }}%"></div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Results Header --}}
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-600">
            <span class="font-semibold text-gray-900 tabular-nums">{{ $leads->total() }}</span>
            {{ Str::plural('lost lead', $leads->total()) }}
            &middot;
            {{ \Carbon\Carbon::parse($filters['from'])->format('d M Y') }}
            –
            {{ \Carbon\Carbon::parse($filters['to'])->format('d M Y') }}
            @if($filters['lost_reason'])
                &middot; Filtered: {{ \App\Enums\CRM\LostReason::tryFrom($filters['lost_reason'])?->label() ?? $filters['lost_reason'] }}
            @endif
        </p>

        {{-- Export buttons (AR-019) --}}
        @can('crm.reports.export')
        <div class="flex items-center gap-2">
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'lost-lead-analysis']) . '?' . http_build_query(array_filter($filters) + ['format' => 'excel']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
               title="Export to Excel">
                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Excel
            </a>
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'lost-lead-analysis']) . '?' . http_build_query(array_filter($filters) + ['format' => 'pdf']) }}"
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

    {{-- Lost Leads Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">#</th>
                        <th class="table-th">Lead</th>
                        <th class="table-th">Source</th>
                        <th class="table-th">Campus</th>
                        <th class="table-th">Counsellor</th>
                        <th class="table-th">Lost Reason</th>
                        <th class="table-th whitespace-nowrap">Enquiry Date</th>
                        <th class="table-th whitespace-nowrap">Lost Date</th>
                        <th class="table-th text-right whitespace-nowrap">Days to Loss</th>
                        <th class="table-th"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leads as $index => $lead)
                    @php
                        $daysToLoss = $lead->status_changed_at && $lead->created_at
                            ? (int) $lead->created_at->diffInDays($lead->status_changed_at)
                            : null;
                        $primaryProgramme = $lead->programmeInterests->first();

                        // Lost reason badge colour
                        $reasonBadge = match($lead->lost_reason?->value) {
                            'no_response'          => 'bg-gray-100 text-gray-700',
                            'not_interested'       => 'bg-red-100 text-red-800',
                            'joined_competitor'    => 'bg-orange-100 text-orange-800',
                            'financial_constraint' => 'bg-amber-100 text-amber-800',
                            'personal_reason'      => 'bg-yellow-100 text-yellow-800',
                            'programme_not_suited' => 'bg-purple-100 text-purple-800',
                            'deferred_next_cycle'  => 'bg-blue-100 text-blue-800',
                            default                => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">

                        <td class="table-td text-gray-400 tabular-nums text-xs">
                            {{ ($leads->currentPage() - 1) * $leads->perPage() + $index + 1 }}
                        </td>

                        {{-- Lead name + contact --}}
                        <td class="table-td">
                            <p class="font-medium text-gray-900">{{ $lead->first_name }} {{ $lead->last_name }}</p>
                            @if($primaryProgramme)
                                <p class="text-xs text-gray-400 mt-0.5 truncate max-w-[180px]">{{ $primaryProgramme->name }}</p>
                            @endif
                        </td>

                        {{-- Source pill --}}
                        <td class="table-td">
                            @if($lead->source)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                    {{ $lead->source->label() }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Campus --}}
                        <td class="table-td text-gray-700 text-xs">
                            {{ $lead->campus?->name ?? '—' }}
                        </td>

                        {{-- Counsellor --}}
                        <td class="table-td text-gray-700 text-xs">
                            {{ $lead->assignedCounsellor?->name ?? '—' }}
                        </td>

                        {{-- Lost Reason --}}
                        <td class="table-td">
                            @if($lead->lost_reason)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $reasonBadge }}">
                                    {{ $lead->lost_reason->label() }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Enquiry Date --}}
                        <td class="table-td text-gray-600 text-xs whitespace-nowrap tabular-nums">
                            {{ $lead->created_at->format('d M Y') }}
                        </td>

                        {{-- Lost Date --}}
                        <td class="table-td text-gray-600 text-xs whitespace-nowrap tabular-nums">
                            {{ $lead->status_changed_at?->format('d M Y') ?? '—' }}
                        </td>

                        {{-- Days to Loss --}}
                        <td class="table-td text-right tabular-nums">
                            @if($daysToLoss !== null)
                                <span class="{{ $daysToLoss <= 7 ? 'text-red-600 font-semibold' : ($daysToLoss <= 30 ? 'text-amber-600' : 'text-gray-500') }}">
                                    {{ $daysToLoss }}d
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- View link --}}
                        <td class="table-td text-right">
                            @can('crm.leads.view')
                            <a href="{{ route('crm.leads.show', $lead->uuid) }}"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                                View &rarr;
                            </a>
                            @endcan
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="table-td text-center text-gray-400 py-16">
                            <svg class="mx-auto mb-3 h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            No lost leads found for the selected period and filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($leads->hasPages())
        <div class="border-t border-gray-200 px-4 py-3">
            {{ $leads->links() }}
        </div>
        @endif
    </div>
</x-layouts.crm>
