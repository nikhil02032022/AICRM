<x-layouts.crm title="PII Erasure Requests">
    <div
        class="space-y-6"
        x-data="{ tab: '{{ request('status', 'all') }}' }"
    >

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">PII Erasure Requests</h1>
                <p class="mt-1 text-sm text-gray-500">DPDP Act 2023 — right to erasure within 30 days (CR-005)</p>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm mb-4">{{ session('success') }}</div>
        @endif

        {{-- Status Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                @foreach(['all' => 'All', 'pending' => 'Pending', 'scheduled' => 'Scheduled', 'erased' => 'Erased', 'failed' => 'Failed'] as $value => $label)
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
        @foreach(['all', 'pending', 'scheduled', 'erased', 'failed'] as $tabKey)
        <div x-show="tab === '{{ $tabKey }}'" x-transition>
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="table-th">Lead Name</th>
                                <th class="table-th">Requested At</th>
                                <th class="table-th">Scheduled Erasure At</th>
                                <th class="table-th">Erased At</th>
                                <th class="table-th text-center">Status</th>
                                <th class="table-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $filteredErasures = $tabKey === 'all'
                                    ? $erasureRequests
                                    : $erasureRequests->filter(fn($r) => strtolower($r->status?->value ?? '') === $tabKey);
                            @endphp
                            @forelse($filteredErasures as $er)
                                <tr class="hover:bg-gray-50/70 transition-colors duration-100">
                                    <td class="table-td">
                                        @if($er->lead)
                                            <a href="{{ route('crm.leads.show', $er->lead) }}"
                                                class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                                {{ $er->lead->full_name }}
                                            </a>
                                        @else
                                            <span class="text-gray-500 italic">Lead #{{ $er->lead_id }} (erased)</span>
                                        @endif
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $er->requested_at ? $er->requested_at->format('d M Y, H:i') : ($er->created_at?->format('d M Y, H:i') ?? '—') }}
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $er->scheduled_erasure_at ? $er->scheduled_erasure_at->format('d M Y, H:i') : '—' }}
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $er->erased_at ? $er->erased_at->format('d M Y, H:i') : '—' }}
                                    </td>
                                    <td class="table-td text-center">
                                        @php
                                            $statusBadge = match(strtolower($er->status?->value ?? '')) {
                                                'pending'   => 'badge-yellow',
                                                'scheduled' => 'badge-blue',
                                                'erased'    => 'badge-green',
                                                'failed'    => 'badge-red',
                                                default     => 'badge-gray',
                                            };
                                        @endphp
                                        <span class="{{ $statusBadge }}">{{ $er->status?->label() ?? ucfirst($er->status?->value ?? '—') }}</span>
                                    </td>
                                    <td class="table-td text-right">
                                        <a href="{{ route('crm.compliance.erasure.show', $er) }}"
                                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">No erasure requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tabKey === 'all' && method_exists($erasureRequests, 'hasPages') && $erasureRequests->hasPages())
                    <div class="border-t border-gray-100 px-5 py-3">{{ $erasureRequests->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
        @endforeach

    </div>
</x-layouts.crm>
