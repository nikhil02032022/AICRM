{{-- BRD: CRM-FM-011 — Refund requests with approval actions --}}
<x-layouts.crm title="Refund Requests">
    <div class="space-y-4">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Refund Requests</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Track refund requests through counsellor → manager → finance approval.
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{-- Table --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Refund ID</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Requested</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($refunds as $refund)
                            @php
                                $statusClass = match($refund->status?->value) {
                                    'pending'           => 'bg-blue-100 text-blue-800',
                                    'manager_approved' => 'bg-indigo-100 text-indigo-800',
                                    'approved'          => 'bg-emerald-100 text-emerald-800',
                                    'processed'         => 'bg-green-100 text-green-800',
                                    'rejected', 'failed' => 'bg-red-100 text-red-800',
                                    default             => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="font-mono text-xs text-gray-700">
                                        {{ \Illuminate\Support\Str::limit($refund->uuid, 12, '…') }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ number_format((float) $refund->amount, 2) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $refund->status?->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="max-w-xs truncate text-sm text-gray-600" title="{{ $refund->reason }}">
                                        {{ \Illuminate\Support\Str::limit($refund->reason, 60) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600">{{ $refund->created_at?->format('M d, Y') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @can ('payments.refund.approve')
                                            @if ($refund->status === \App\Enums\CRM\Payments\RefundStatus::PENDING)
                                                <form method="POST" action="{{ route('crm.payments.refunds.manager-approve', $refund) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-secondary-sm inline-flex items-center gap-1.5">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        Manager Approve
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($refund->status === \App\Enums\CRM\Payments\RefundStatus::MANAGER_APPROVED)
                                                <form method="POST" action="{{ route('crm.payments.refunds.finance-approve', $refund) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-primary-sm inline-flex items-center gap-1.5">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        Finance Approve
                                                    </button>
                                                </form>
                                            @endif
                                            @if (in_array($refund->status, [\App\Enums\CRM\Payments\RefundStatus::PENDING, \App\Enums\CRM\Payments\RefundStatus::MANAGER_APPROVED], true))
                                                <form method="POST" action="{{ route('crm.payments.refunds.reject', $refund) }}"
                                                      onsubmit="return confirm('Reject this refund request?');">
                                                    @csrf
                                                    <input type="hidden" name="reason" value="Rejected from list view" />
                                                    <button type="submit" class="btn-ghost-sm inline-flex items-center gap-1.5 text-red-600 hover:bg-red-50">
                                                        Reject
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-16 text-center">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-500">No refund requests</p>
                                    <p class="mt-1 text-xs text-gray-400">Refund requests will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($refunds->hasPages())
                <div class="border-t border-gray-200 px-6 py-3">{{ $refunds->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.crm>
