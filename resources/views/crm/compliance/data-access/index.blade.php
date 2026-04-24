<x-layouts.crm title="Data Access Requests">
    <div
        class="space-y-6"
        x-data="{ tab: '{{ request('status', 'all') }}' }"
    >

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Access Requests</h1>
                <p class="mt-1 text-sm text-gray-500">DPDP Act 2023 — right to access personal data (CR-004)</p>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm mb-4">{{ session('success') }}</div>
        @endif

        {{-- Status Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                @foreach(['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'delivered' => 'Delivered', 'failed' => 'Failed'] as $value => $label)
                    <button
                        @click="tab = '{{ $value }}'"
                        :class="tab === '{{ $value }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="border-b-2 py-3 px-1 text-sm font-medium transition-colors duration-150">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tables per tab --}}
        @foreach(['all', 'pending', 'processing', 'delivered', 'failed'] as $tabKey)
        <div x-show="tab === '{{ $tabKey }}'" x-transition>
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="table-th">Lead Name</th>
                                <th class="table-th">Requested At</th>
                                <th class="table-th">Delivery Method</th>
                                <th class="table-th text-center">Status</th>
                                <th class="table-th">Processed At</th>
                                <th class="table-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $filteredRequests = $tabKey === 'all'
                                    ? $requests
                                    : $requests->filter(fn($r) => strtolower($r->status?->value ?? '') === $tabKey);
                            @endphp
                            @forelse($filteredRequests as $dar)
                                <tr class="hover:bg-gray-50/70 transition-colors duration-100">
                                    <td class="table-td">
                                        <a href="{{ route('crm.leads.show', $dar->lead) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                            {{ $dar->lead?->full_name ?? 'Lead #'.$dar->lead_id }}
                                        </a>
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $dar->requested_at ? $dar->requested_at->format('d M Y, H:i') : ($dar->created_at?->format('d M Y, H:i') ?? '—') }}
                                    </td>
                                    <td class="table-td text-sm capitalize text-gray-700">{{ $dar->delivery_method ?? '—' }}</td>
                                    <td class="table-td text-center">
                                        @php
                                            $statusBadge = match(strtolower($dar->status?->value ?? '')) {
                                                'pending'    => 'badge-yellow',
                                                'processing' => 'badge-blue',
                                                'delivered'  => 'badge-green',
                                                'failed'     => 'badge-red',
                                                default      => 'badge-gray',
                                            };
                                        @endphp
                                        <span class="{{ $statusBadge }}">{{ $dar->status?->label() ?? ucfirst($dar->status?->value ?? '—') }}</span>
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $dar->processed_at ? $dar->processed_at->format('d M Y, H:i') : '—' }}
                                    </td>
                                    <td class="table-td text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('crm.compliance.data-access.show', $dar) }}"
                                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View</a>
                                            @if(strtolower($dar->status?->value ?? '') === 'pending')
                                                <a href="{{ route('crm.compliance.data-access.show', $dar) }}"
                                                    class="text-xs font-medium text-emerald-600 hover:text-emerald-800">Process</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">No data access requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tabKey === 'all' && method_exists($requests, 'hasPages') && $requests->hasPages())
                    <div class="border-t border-gray-100 px-5 py-3">{{ $requests->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
        @endforeach

    </div>
</x-layouts.crm>
