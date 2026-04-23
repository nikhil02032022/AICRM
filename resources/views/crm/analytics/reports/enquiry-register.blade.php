{{-- BRD: CRM-AR-009 — Enquiry Register Report: full lead list with filters and pagination --}}
<x-layouts.crm title="Enquiry Register">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <span>Reports</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">Enquiry Register</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Enquiry Register</h1>
        <p class="mt-1 text-sm text-gray-500">All enquiries received in the selected period with current status and counsellor assignment.</p>
    </x-slot:header>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('crm.analytics.reports.enquiry-register') }}"
          class="mb-6 card p-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">

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

            {{-- Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Campus (hidden for campus-scoped managers) --}}
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
            <a href="{{ route('crm.analytics.reports.enquiry-register') }}" class="btn-ghost-sm">Reset</a>
        </div>
    </form>

    {{-- Results Header --}}
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-600">
            <span class="font-semibold text-gray-900 tabular-nums">{{ number_format($leads->total()) }}</span>
            {{ Str::plural('enquiry', $leads->total()) }} found
            &middot;
            {{ \Carbon\Carbon::parse($filters['from'])->format('d M Y') }}
            –
            {{ \Carbon\Carbon::parse($filters['to'])->format('d M Y') }}
        </p>

        {{-- Export buttons (AR-019) --}}
        @can('crm.reports.export')
        <div class="flex items-center gap-2">
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'enquiry-register']) . '?' . http_build_query(array_filter($filters) + ['format' => 'excel']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
               title="Export to Excel">
                <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Excel
            </a>
            <a href="{{ route('crm.analytics.reports.export', ['report' => 'enquiry-register']) . '?' . http_build_query(array_filter($filters) + ['format' => 'pdf']) }}"
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

    {{-- Leads Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">#</th>
                        <th class="table-th">Name</th>
                        <th class="table-th">Contact</th>
                        <th class="table-th">Source</th>
                        @if(!$scope['campus_id'] && $campuses->count() > 1)
                        <th class="table-th">Campus</th>
                        @endif
                        <th class="table-th">Programme</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Counsellor</th>
                        <th class="table-th whitespace-nowrap">Enquiry Date</th>
                        <th class="table-th-center">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($leads as $index => $lead)
                    @php
                        $primaryProgramme = $lead->programmeInterests->first();
                        $rowNum = ($leads->currentPage() - 1) * $leads->perPage() + $index + 1;
                    @endphp
                    <tr class="hover:bg-gray-50">

                        {{-- Row number --}}
                        <td class="table-td text-gray-400 tabular-nums text-xs">{{ $rowNum }}</td>

                        {{-- Name --}}
                        <td class="table-td">
                            <div class="font-medium text-gray-900">
                                {{ $lead->first_name }} {{ $lead->last_name }}
                            </div>
                            @if($lead->city)
                                <div class="text-xs text-gray-400 mt-0.5">{{ $lead->city }}@if($lead->state), {{ $lead->state }}@endif</div>
                            @endif
                        </td>

                        {{-- Contact --}}
                        <td class="table-td">
                            @if($lead->mobile)
                                <div class="font-mono text-xs text-gray-700">{{ $lead->mobile }}</div>
                            @endif
                            @if($lead->email)
                                <div class="text-xs text-gray-400 mt-0.5 truncate max-w-[14rem]">{{ $lead->email }}</div>
                            @endif
                        </td>

                        {{-- Source --}}
                        <td class="table-td">
                            @if($lead->source)
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600">
                                    {{ $lead->source->label() }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Campus (only shown when not already scoped) --}}
                        @if(!$scope['campus_id'] && $campuses->count() > 1)
                        <td class="table-td text-gray-600 text-xs">
                            {{ $lead->campus?->name ?? '—' }}
                        </td>
                        @endif

                        {{-- Primary Programme --}}
                        <td class="table-td text-gray-700 text-xs">
                            {{ $primaryProgramme?->name ?? '—' }}
                        </td>

                        {{-- Status badge --}}
                        <td class="table-td">
                            @if($lead->status)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $lead->status->badgeClass() }}">
                                    {{ $lead->status->label() }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Counsellor --}}
                        <td class="table-td text-gray-600 text-xs whitespace-nowrap">
                            {{ $lead->assignedCounsellor?->name ?? '—' }}
                        </td>

                        {{-- Enquiry date --}}
                        <td class="table-td text-gray-500 tabular-nums whitespace-nowrap text-xs">
                            {{ $lead->created_at->format('d M Y') }}
                            <div class="text-gray-400">{{ $lead->created_at->format('H:i') }}</div>
                        </td>

                        {{-- View link --}}
                        <td class="table-td-center">
                            @can('crm.leads.view')
                            <a href="{{ route('crm.leads.show', $lead->uuid) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-xs font-medium hover:underline">
                                View →
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="table-td text-center text-gray-400 py-16">
                            <svg class="mx-auto mb-3 h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                            </svg>
                            No enquiries found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($leads->hasPages())
        <div class="border-t border-gray-100 px-5 py-3">
            {{ $leads->links() }}
        </div>
        @endif
    </div>
</x-layouts.crm>
