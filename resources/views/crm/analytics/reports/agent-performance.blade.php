{{-- BRD: CRM-AR-017 — Agent Performance Report: referred leads, funnel conversion, and commission summary --}}
<x-layouts.crm title="Agent Performance Report">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <span>Reports</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">Agent Performance</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Agent Performance Report</h1>
        <p class="mt-1 text-sm text-gray-500">
            Referred leads, funnel conversion, and commission accruals for channel partners — {{ \Carbon\Carbon::parse($filters['from'])->format('d M Y') }} to {{ \Carbon\Carbon::parse($filters['to'])->format('d M Y') }}.
        </p>
    </x-slot:header>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('crm.analytics.reports.agent-performance') }}"
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

            {{-- Agent Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Agent Status</label>
                <select name="agent_status"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach($agentStatuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['agent_status'] === $status->value)>
                            {{ $status->label() }}
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

        </div>

        <div class="mt-3 flex items-center gap-2">
            <button type="submit" class="btn-primary-sm">Apply Filters</button>
            <a href="{{ route('crm.analytics.reports.agent-performance') }}" class="btn-ghost-sm">Reset</a>
        </div>
    </form>

    {{-- Summary Tiles --}}
    @php
        $totLeads     = $rows->sum('leads_referred');
        $totApplied   = $rows->sum('applied');
        $totEnrolled  = $rows->sum('enrolled');
        $totAccrued   = $rows->sum('commission_accrued');
        $totPaid      = $rows->sum('commission_paid');
    @endphp

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">

        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Agents</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($rows->count()) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">in selected period</p>
        </div>

        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Leads Referred</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($totLeads) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">
                @if($totLeads > 0)
                    {{ number_format($totEnrolled) }} enrolled ({{ round(($totEnrolled / $totLeads) * 100, 1) }}%)
                @else
                    no leads in period
                @endif
            </p>
        </div>

        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Commission Accrued</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($totAccrued, 2) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">pending + approved</p>
        </div>

        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Commission Paid</p>
            <p class="mt-1 text-2xl font-bold text-green-700 tabular-nums">{{ number_format($totPaid, 2) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">
                @if($totAccrued > 0)
                    {{ round(($totPaid / $totAccrued) * 100, 1) }}% of accrued
                @else
                    —
                @endif
            </p>
        </div>

    </div>

    {{-- Results Header --}}
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-600">
            <span class="font-semibold text-gray-900 tabular-nums">{{ $rows->count() }}</span>
            {{ Str::plural('agent', $rows->count()) }}
            &middot;
            {{ \Carbon\Carbon::parse($filters['from'])->format('d M Y') }}
            –
            {{ \Carbon\Carbon::parse($filters['to'])->format('d M Y') }}
        </p>

        {{-- Export buttons (AR-019) --}}
        @can('crm.reports.export')
        <div class="flex items-center gap-2">
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'agent-performance']) . '?' . http_build_query(array_filter($filters) + ['format' => 'excel']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
               title="Export to Excel">
                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Excel
            </a>
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'agent-performance']) . '?' . http_build_query(array_filter($filters) + ['format' => 'pdf']) }}"
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

    {{-- Agent Performance Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">#</th>
                        <th class="table-th">Agent</th>
                        <th class="table-th-center">Status</th>
                        <th class="table-th text-right">Leads Referred</th>
                        <th class="table-th text-right">Applied</th>
                        <th class="table-th text-right">Enrolled</th>
                        <th class="table-th text-right whitespace-nowrap">Refer → Enrol</th>
                        <th class="table-th text-right whitespace-nowrap">Commission Accrued</th>
                        <th class="table-th text-right whitespace-nowrap">Commission Paid</th>
                        <th class="table-th-center">Pay Rate</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                    @forelse($rows as $index => $agent)
                    @php
                        $convRate = $agent->leads_referred > 0
                            ? round(($agent->enrolled / $agent->leads_referred) * 100, 1)
                            : null;

                        $payRate = $agent->commission_accrued > 0
                            ? round(($agent->commission_paid / $agent->commission_accrued) * 100, 1)
                            : null;

                        $statusBadge = match ($agent->status->value ?? $agent->status) {
                            'active'    => 'bg-green-100 text-green-800',
                            'inactive'  => 'bg-gray-100 text-gray-600',
                            'suspended' => 'bg-red-100 text-red-700',
                            default     => 'bg-gray-100 text-gray-500',
                        };
                        $statusLabel = $agent->status instanceof \App\Enums\CRM\Agents\AgentStatus
                            ? $agent->status->label()
                            : ucfirst($agent->status);
                    @endphp
                    <tr class="hover:bg-gray-50">

                        <td class="table-td text-gray-400 tabular-nums text-xs">{{ $index + 1 }}</td>

                        {{-- Agent name + email --}}
                        <td class="table-td">
                            <p class="font-medium text-gray-900">{{ $agent->name }}</p>
                            <p class="text-xs text-gray-400">{{ $agent->email }}</p>
                        </td>

                        {{-- Status badge --}}
                        <td class="table-td-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        {{-- Leads Referred --}}
                        <td class="table-td text-right tabular-nums font-semibold text-gray-900">
                            {{ number_format($agent->leads_referred) }}
                        </td>

                        {{-- Applied --}}
                        <td class="table-td text-right tabular-nums text-gray-700">
                            {{ number_format($agent->applied) }}
                        </td>

                        {{-- Enrolled --}}
                        <td class="table-td text-right tabular-nums font-medium text-gray-900">
                            {{ number_format($agent->enrolled) }}
                        </td>

                        {{-- Refer → Enrol % --}}
                        <td class="table-td text-right tabular-nums">
                            @if($convRate !== null)
                                <span class="{{ $convRate >= 20 ? 'text-green-700' : ($convRate >= 10 ? 'text-amber-700' : 'text-gray-500') }} font-medium">
                                    {{ $convRate }}%
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Commission Accrued --}}
                        <td class="table-td text-right tabular-nums text-gray-700">
                            @if($agent->commission_accrued > 0)
                                {{ number_format($agent->commission_accrued, 2) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Commission Paid --}}
                        <td class="table-td text-right tabular-nums">
                            @if($agent->commission_paid > 0)
                                <span class="font-medium text-green-700">{{ number_format($agent->commission_paid, 2) }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Pay Rate badge --}}
                        <td class="table-td-center">
                            @if($payRate !== null)
                                @php
                                    $payClass = match(true) {
                                        $payRate >= 100 => 'bg-green-100 text-green-800',
                                        $payRate >= 50  => 'bg-blue-100 text-blue-800',
                                        default         => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $payClass }}">
                                    {{ $payRate }}%
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="table-td text-center text-gray-400 py-16">
                            <svg class="mx-auto mb-3 h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                            </svg>
                            No agents found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- Totals footer --}}
                @if($rows->isNotEmpty())
                @php
                    $totConv    = $totLeads   > 0 ? round(($totEnrolled / $totLeads)   * 100, 1) : null;
                    $totPayRate = $totAccrued  > 0 ? round(($totPaid    / $totAccrued) * 100, 1) : null;
                @endphp
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td class="table-td text-xs text-gray-500 font-medium" colspan="3">
                            All Agents ({{ $rows->count() }})
                        </td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-900">{{ number_format($totLeads) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ number_format($totApplied) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-900">{{ number_format($totEnrolled) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">
                            {{ $totConv !== null ? $totConv . '%' : '—' }}
                        </td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">
                            {{ $totAccrued > 0 ? number_format($totAccrued, 2) : '—' }}
                        </td>
                        <td class="table-td text-right tabular-nums font-semibold text-green-700">
                            {{ $totPaid > 0 ? number_format($totPaid, 2) : '—' }}
                        </td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">
                            {{ $totPayRate !== null ? $totPayRate . '%' : '—' }}
                        </td>
                    </tr>
                </tfoot>
                @endif

            </table>
        </div>
    </div>
</x-layouts.crm>
