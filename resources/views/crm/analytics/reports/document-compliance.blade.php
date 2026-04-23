{{-- BRD: CRM-AR-015 — Document Compliance Report: per-application document status breakdown --}}
<x-layouts.crm title="Document Compliance Report">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <span>Reports</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">Document Compliance</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Document Compliance Report</h1>
        <p class="mt-1 text-sm text-gray-500">Document submission and verification status per application for the selected period.</p>
    </x-slot:header>

    {{-- Summary Tiles --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-6">
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Applications</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary->total_applications) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">in period</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Docs</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary->total_docs) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">tracked</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Verified</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ number_format($summary->verified_docs) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">
                @if($summary->total_docs > 0)
                    {{ round(($summary->verified_docs / $summary->total_docs) * 100, 1) }}% compliance
                @else
                    —
                @endif
            </p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending Review</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($summary->pending_docs) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">submitted / under review</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Rejected</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format($summary->rejected_docs) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">need resubmission</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Missing</p>
            <p class="mt-1 text-2xl font-bold text-gray-500">{{ number_format($summary->missing_docs) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">not yet submitted</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('crm.analytics.reports.document-compliance') }}"
          class="mb-6 card p-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" value="{{ $filters['from'] }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" value="{{ $filters['to'] }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Compliance</label>
                <select name="compliance"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Applications</option>
                    <option value="compliant"  @selected($filters['compliance'] === 'compliant')>Fully Compliant</option>
                    <option value="pending"    @selected($filters['compliance'] === 'pending')>Pending Docs</option>
                    <option value="rejected"   @selected($filters['compliance'] === 'rejected')>Has Rejections</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Programme</label>
                <select name="programme_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Programmes</option>
                    @foreach($programmes as $prog)
                        <option value="{{ $prog->id }}" @selected((string)$filters['programme_id'] === (string)$prog->id)>
                            {{ $prog->name }}
                        </option>
                    @endforeach
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

            @if($counsellors->isNotEmpty())
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Counsellor</label>
                <select name="counsellor_id"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Counsellors</option>
                    @foreach($counsellors as $counsellor)
                        <option value="{{ $counsellor->id }}" @selected((string)$filters['counsellor_id'] === (string)$counsellor->id)>
                            {{ $counsellor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

        </div>
        <div class="mt-3 flex items-center gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors">
                Apply Filters
            </button>
            <a href="{{ route('crm.analytics.reports.document-compliance') }}"
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
    </form>

    {{-- Results Table --}}
    @if($applications->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-gray-500 text-sm">No applications found for the selected filters.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide w-8">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Applicant</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Programme</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Campus</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Counsellor</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-green-600 uppercase tracking-wide">Verified</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-amber-600 uppercase tracking-wide">Pending</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-red-600 uppercase tracking-wide">Rejected</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Missing</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">Compliance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($applications as $i => $app)
                            @php
                                $total      = (int) $app->total_docs;
                                $verified   = (int) $app->verified_docs;
                                $pending    = (int) $app->pending_docs;
                                $rejected   = (int) $app->rejected_docs;
                                $missing    = (int) $app->missing_docs;
                                $pct        = $total > 0 ? round(($verified / $total) * 100) : null;

                                $complianceClass = match (true) {
                                    $total === 0              => 'bg-gray-100 text-gray-500',
                                    $rejected > 0             => 'bg-red-100 text-red-700',
                                    $pct === 100              => 'bg-green-100 text-green-700',
                                    $pct !== null && $pct >= 50 => 'bg-amber-100 text-amber-700',
                                    default                   => 'bg-red-50 text-red-600',
                                };
                                $complianceLabel = match (true) {
                                    $total === 0   => 'No Docs',
                                    $pct === 100   => 'Compliant',
                                    $rejected > 0  => 'Has Rejections',
                                    default        => ($pct ?? 0) . '%',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400 tabular-nums">
                                    {{ $applications->firstItem() + $i }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($app->lead)
                                        <span class="font-medium text-gray-900">
                                            {{ $app->lead->first_name }} {{ $app->lead->last_name }}
                                        </span><br>
                                        <span class="text-xs text-gray-500">{{ $app->lead->mobile }}</span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $app->programme?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $app->campus?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $app->assignedCounsellor?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-700 tabular-nums">
                                    {{ $total ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    @if($verified > 0)
                                        <span class="font-semibold text-green-600">{{ $verified }}</span>
                                    @else
                                        <span class="text-gray-300">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    @if($pending > 0)
                                        <span class="font-semibold text-amber-600">{{ $pending }}</span>
                                    @else
                                        <span class="text-gray-300">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    @if($rejected > 0)
                                        <span class="font-semibold text-red-600">{{ $rejected }}</span>
                                    @else
                                        <span class="text-gray-300">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    @if($missing > 0)
                                        <span class="font-semibold text-gray-500">{{ $missing }}</span>
                                    @else
                                        <span class="text-gray-300">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $complianceClass }}">
                                        {{ $complianceLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $app->submitted_at?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @can('crm.applications.view')
                                        <a href="{{ route('crm.applications.show', $app->uuid) }}"
                                           class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                            View →
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($applications->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $applications->links() }}
                </div>
            @endif
        </div>

        <p class="mt-2 text-xs text-gray-400 text-right">
            {{ number_format($applications->total()) }} applications &bull; Page {{ $applications->currentPage() }} of {{ $applications->lastPage() }}
        </p>
    @endif
</x-layouts.crm>
