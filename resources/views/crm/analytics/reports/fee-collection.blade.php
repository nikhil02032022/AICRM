{{-- BRD: CRM-AR-014 — Fee Collection Report: payment transactions by student, programme, fee type, amount, and status --}}
<x-layouts.crm title="Fee Collection Report">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <span>Reports</span>
            <span>/</span>
            <span class="text-gray-900 font-medium">Fee Collection</span>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Fee Collection Report</h1>
        <p class="mt-1 text-sm text-gray-500">Payment transactions attempted in the selected period — by student, programme, fee type, and gateway.</p>
    </x-slot:header>

    {{-- Summary Tiles --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Collected</p>
            <p class="mt-1 text-2xl font-bold text-green-600">₹{{ number_format((float) $summary->collected, 0) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">{{ (int) $summary->successful_count }} successful</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">₹{{ number_format((float) $summary->pending_amount, 0) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">initiated / pending</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Refunded</p>
            <p class="mt-1 text-2xl font-bold text-purple-600">₹{{ number_format((float) $summary->refunded, 0) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">refund pending + refunded</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Transactions</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format((int) $summary->total_transactions) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">all statuses in period</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('crm.analytics.reports.fee-collection') }}"
          class="mb-6 card p-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">

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

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Fee Type</label>
                <select name="fee_type"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Types</option>
                    @foreach($feeTypes as $feeType)
                        <option value="{{ $feeType->value }}" @selected($filters['fee_type'] === $feeType->value)>
                            {{ $feeType->label() }}
                        </option>
                    @endforeach
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
            <a href="{{ route('crm.analytics.reports.fee-collection') }}"
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
    @if($transactions->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-gray-500 text-sm">No transactions found for the selected filters.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide w-8">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Programme</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Fee Type</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Gateway</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Attempted</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Confirmed</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Counsellor</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($transactions as $i => $tx)
                            @php
                                $statusClass = match($tx->status) {
                                    \App\Enums\CRM\Payments\PaymentStatus::SUCCESS         => 'bg-green-100 text-green-800',
                                    \App\Enums\CRM\Payments\PaymentStatus::PENDING,
                                    \App\Enums\CRM\Payments\PaymentStatus::INITIATED       => 'bg-amber-100 text-amber-800',
                                    \App\Enums\CRM\Payments\PaymentStatus::REFUND_PENDING,
                                    \App\Enums\CRM\Payments\PaymentStatus::REFUNDED        => 'bg-purple-100 text-purple-800',
                                    default                                                 => 'bg-red-100 text-red-800',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400 tabular-nums">
                                    {{ $transactions->firstItem() + $i }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($tx->lead)
                                        <span class="font-medium text-gray-900">
                                            {{ $tx->lead->first_name }} {{ $tx->lead->last_name }}
                                        </span>
                                        <br>
                                        <span class="text-xs text-gray-500">{{ $tx->lead->mobile }}</span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $tx->application?->programme?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $tx->fee_type->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 tabular-nums">
                                    ₹{{ number_format((float) $tx->amount, 0) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 capitalize">
                                    {{ str_replace('_', ' ', $tx->gateway->value) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusClass }}">
                                        {{ $tx->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $tx->attempted_at?->format('d M Y') }}<br>
                                    <span class="text-xs text-gray-400">{{ $tx->attempted_at?->format('H:i') }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    @if($tx->confirmed_at)
                                        {{ $tx->confirmed_at->format('d M Y') }}<br>
                                        <span class="text-xs text-gray-400">{{ $tx->confirmed_at->format('H:i') }}</span>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $tx->lead?->assignedCounsellor?->name ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        <p class="mt-2 text-xs text-gray-400 text-right">
            {{ number_format($transactions->total()) }} transactions &bull; Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}
        </p>
    @endif
</x-layouts.crm>
