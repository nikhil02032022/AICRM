{{-- BRD: CRM-FM-001, CRM-FM-002, CRM-FM-005 — Counsellor fee panel for an application --}}
<x-layouts.crm title="Collect Fee">
    <div class="space-y-4">

        {{-- Breadcrumb / back --}}
        <nav class="flex items-center gap-1.5 text-sm text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('crm.applications.list') }}" class="hover:text-indigo-600 hover:underline">
                Applications
            </a>
            <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="font-medium text-gray-700">Collect Fee</span>
        </nav>

        {{-- Header --}}
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Collect Fee</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Application
                    <span class="font-mono text-xs text-gray-500">{{ $application->uuid }}</span>
                    @if ($application->programme)
                        — <span class="font-medium text-gray-700">{{ $application->programme->name }}</span>
                    @endif
                </p>
            </div>
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                {{ $application->status?->label() ?? $application->status }}
            </span>
        </div>

        {{-- Flash --}}
        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

            {{-- Initiate form (2/3) --}}
            <div class="lg:col-span-2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold text-gray-900">Generate Payment</h3>

                    <form method="POST"
                          action="{{ route('crm.payments.applications.initiate', $application->uuid) }}"
                          class="space-y-4">
                        @csrf

                        <div>
                            <label for="fee_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Fee Type <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <select id="fee_type" name="fee_type" required
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                @foreach (\App\Enums\CRM\Payments\FeeType::cases() as $ft)
                                    <option value="{{ $ft->value }}">{{ $ft->label() }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                Resolves the active fee structure for this programme.
                            </p>
                        </div>

                        <div>
                            <label for="gateway" class="block text-sm font-medium text-gray-700 mb-1">Gateway</label>
                            <select id="gateway" name="gateway"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">Default ({{ ucfirst((string) config('crm_payments.default_gateway')) }})</option>
                                @foreach (\App\Enums\CRM\Payments\GatewayProvider::cases() as $g)
                                    <option value="{{ $g->value }}">{{ $g->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <a href="{{ route('crm.applications.list') }}" class="btn-ghost-sm">Cancel</a>
                            <button type="submit" class="btn-primary-sm inline-flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Generate Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Side: applicant summary --}}
            <aside>
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">Applicant</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Name</dt>
                            <dd class="font-medium text-gray-900 text-right">
                                {{ $application->lead?->full_name ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Email</dt>
                            <dd class="text-gray-700 text-right truncate" title="{{ $application->lead?->email }}">
                                {{ $application->lead?->email ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Phone</dt>
                            <dd class="text-gray-700 text-right">{{ $application->lead?->phone ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>

        {{-- Recent transactions --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-3">
                <h3 class="text-sm font-semibold text-gray-700">Recent Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white">
                        <tr class="border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">UUID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Fee Type</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Gateway</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($transactions as $txn)
                            @php
                                $statusClass = match($txn->status?->value) {
                                    'success'        => 'bg-green-100 text-green-800',
                                    'failed'         => 'bg-red-100 text-red-800',
                                    'refunded',
                                    'refund_pending' => 'bg-amber-100 text-amber-800',
                                    'cancelled'      => 'bg-gray-100 text-gray-600',
                                    default          => 'bg-blue-100 text-blue-800',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="font-mono text-xs text-gray-700">{{ \Illuminate\Support\Str::limit($txn->uuid, 12, '…') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-700">{{ $txn->fee_type?->label() }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ number_format((float) $txn->amount, 2) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600">{{ $txn->gateway?->label() }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $txn->status?->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600">{{ $txn->created_at?->format('M d, Y H:i') }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-500">No transactions yet</p>
                                    <p class="mt-1 text-xs text-gray-400">Generate a payment to start.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.crm>
