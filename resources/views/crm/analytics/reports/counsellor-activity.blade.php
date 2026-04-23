{{-- BRD: CRM-AR-010 — Counsellor Activity Report: per-counsellor summary of leads, tasks, calls, sessions --}}
<x-layouts.crm title="Counsellor Activity Report">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <span>Reports</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">Counsellor Activity</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Counsellor Activity Report</h1>
        <p class="mt-1 text-sm text-gray-500">Per-counsellor summary of leads assigned, tasks completed, calls made and sessions held in the selected period.</p>
    </x-slot:header>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('crm.analytics.reports.counsellor-activity') }}"
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
            @if($scope['role'] !== 'counsellor' && $counsellors->isNotEmpty())
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
            <a href="{{ route('crm.analytics.reports.counsellor-activity') }}" class="btn-ghost-sm">Reset</a>
        </div>
    </form>

    {{-- Results Header --}}
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-600">
            <span class="font-semibold text-gray-900 tabular-nums">{{ $rows->count() }}</span>
            {{ Str::plural('counsellor', $rows->count()) }}
            &middot;
            {{ \Carbon\Carbon::parse($filters['from'])->format('d M Y') }}
            –
            {{ \Carbon\Carbon::parse($filters['to'])->format('d M Y') }}
        </p>

        {{-- Export buttons (AR-019) --}}
        @can('crm.reports.export')
        <div class="flex items-center gap-2">
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'counsellor-activity']) . '?' . http_build_query(array_filter($filters) + ['format' => 'excel']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
               title="Export to Excel">
                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Excel
            </a>
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'counsellor-activity']) . '?' . http_build_query(array_filter($filters) + ['format' => 'pdf']) }}"
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

    {{-- Activity Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">#</th>
                        <th class="table-th">Counsellor</th>
                        @if(!$scope['campus_id'] && $campuses->count() > 1)
                        <th class="table-th">Campus</th>
                        @endif
                        <th class="table-th text-right">New Leads</th>
                        <th class="table-th text-right">Converted</th>
                        <th class="table-th text-right">Conv. Rate</th>
                        <th class="table-th text-right">Tasks Done</th>
                        <th class="table-th text-right">Overdue</th>
                        <th class="table-th text-right">Calls Made</th>
                        <th class="table-th text-right">Sessions</th>
                        <th class="table-th-center">Performance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rows as $index => $row)
                    @php
                        $convRate = $row->new_leads > 0
                            ? round(($row->converted_leads / $row->new_leads) * 100, 1)
                            : null;
                        $perfBadge = match(true) {
                            $convRate === null         => ['label' => 'No data',  'class' => 'bg-gray-100 text-gray-500'],
                            $convRate >= 30            => ['label' => 'High',     'class' => 'bg-green-100 text-green-800'],
                            $convRate >= 15            => ['label' => 'Medium',   'class' => 'bg-amber-100 text-amber-800'],
                            default                    => ['label' => 'Low',      'class' => 'bg-red-100 text-red-800'],
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">

                        <td class="table-td text-gray-400 tabular-nums text-xs">{{ $index + 1 }}</td>

                        {{-- Counsellor name --}}
                        <td class="table-td">
                            <div class="font-medium text-gray-900">{{ $row->name }}</div>
                        </td>

                        {{-- Campus (only when not scoped) --}}
                        @if(!$scope['campus_id'] && $campuses->count() > 1)
                        <td class="table-td text-gray-600 text-xs">
                            {{ $row->campus?->name ?? '—' }}
                        </td>
                        @endif

                        {{-- New Leads --}}
                        <td class="table-td text-right tabular-nums font-medium text-gray-900">
                            {{ number_format($row->new_leads) }}
                        </td>

                        {{-- Converted --}}
                        <td class="table-td text-right tabular-nums text-gray-700">
                            {{ number_format($row->converted_leads) }}
                        </td>

                        {{-- Conversion Rate --}}
                        <td class="table-td text-right tabular-nums">
                            @if($convRate !== null)
                                <span class="font-medium {{ $convRate >= 30 ? 'text-green-700' : ($convRate >= 15 ? 'text-amber-700' : 'text-red-600') }}">
                                    {{ $convRate }}%
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Tasks Completed --}}
                        <td class="table-td text-right tabular-nums text-gray-700">
                            {{ number_format($row->tasks_completed) }}
                        </td>

                        {{-- Overdue Tasks --}}
                        <td class="table-td text-right tabular-nums">
                            @if($row->tasks_overdue > 0)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                    {{ $row->tasks_overdue }}
                                </span>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>

                        {{-- Calls Made --}}
                        <td class="table-td text-right tabular-nums text-gray-700">
                            {{ number_format($row->calls_made) }}
                        </td>

                        {{-- Sessions Completed --}}
                        <td class="table-td text-right tabular-nums text-gray-700">
                            {{ number_format($row->sessions_completed) }}
                        </td>

                        {{-- Performance Badge --}}
                        <td class="table-td-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $perfBadge['class'] }}">
                                {{ $perfBadge['label'] }}
                            </span>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="table-td text-center text-gray-400 py-16">
                            <svg class="mx-auto mb-3 h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            No counsellors found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- Totals footer --}}
                @if($rows->isNotEmpty())
                @php
                    $totalNewLeads   = $rows->sum('new_leads');
                    $totalConverted  = $rows->sum('converted_leads');
                    $totalConvRate   = $totalNewLeads > 0 ? round(($totalConverted / $totalNewLeads) * 100, 1) : null;
                    $totalTasksDone  = $rows->sum('tasks_completed');
                    $totalOverdue    = $rows->sum('tasks_overdue');
                    $totalCalls      = $rows->sum('calls_made');
                    $totalSessions   = $rows->sum('sessions_completed');
                @endphp
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td class="table-td text-xs text-gray-500 font-medium" colspan="{{ (!$scope['campus_id'] && $campuses->count() > 1) ? 2 : 1 }}">
                            Totals ({{ $rows->count() }} counsellors)
                        </td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-900">{{ number_format($totalNewLeads) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ number_format($totalConverted) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold {{ $totalConvRate !== null && $totalConvRate >= 30 ? 'text-green-700' : ($totalConvRate !== null && $totalConvRate >= 15 ? 'text-amber-700' : 'text-red-600') }}">
                            {{ $totalConvRate !== null ? $totalConvRate . '%' : '—' }}
                        </td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ number_format($totalTasksDone) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold {{ $totalOverdue > 0 ? 'text-red-700' : 'text-gray-400' }}">{{ number_format($totalOverdue) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ number_format($totalCalls) }}</td>
                        <td class="table-td text-right tabular-nums font-semibold text-gray-700">{{ number_format($totalSessions) }}</td>
                        <td class="table-td"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-layouts.crm>
