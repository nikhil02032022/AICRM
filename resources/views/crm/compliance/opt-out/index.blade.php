<x-layouts.crm title="Opt-Out Management">
    <div
        class="space-y-6"
        x-data="{ tab: '{{ request('tab', 'pending') }}' }"
    >

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Opt-Out Management</h1>
                <p class="mt-1 text-sm text-gray-500">CR-003 — opt-outs must be honoured within 24h</p>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm mb-4">{{ session('success') }}</div>
        @endif

        {{-- Alpine Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                <button
                    @click="tab = 'pending'"
                    :class="tab === 'pending' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="border-b-2 py-3 px-1 text-sm font-medium transition-colors duration-150">
                    Pending
                    @if(isset($pendingCount) && $pendingCount > 0)
                        <span class="ml-1.5 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">{{ $pendingCount }}</span>
                    @endif
                </button>
                <button
                    @click="tab = 'processed'"
                    :class="tab === 'processed' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="border-b-2 py-3 px-1 text-sm font-medium transition-colors duration-150">
                    Processed
                </button>
            </nav>
        </div>

        {{-- Pending Tab --}}
        <div x-show="tab === 'pending'" x-transition>
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="table-th">Lead Name</th>
                                <th class="table-th">Channel</th>
                                <th class="table-th">Requested At</th>
                                <th class="table-th">Processed At</th>
                                <th class="table-th">Processed By Job</th>
                                <th class="table-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($pendingLogs as $log)
                                @php
                                    $hoursOld = $log->requested_at ? $log->requested_at->diffInHours(now()) : 0;
                                    $slaBreach = $hoursOld >= 20;
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors duration-100">
                                    <td class="table-td">
                                        <a href="{{ route('crm.leads.show', $log->lead) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                            {{ $log->lead?->full_name ?? 'Lead #'.$log->lead_id }}
                                        </a>
                                    </td>
                                    <td class="table-td capitalize text-sm">{{ $log->channel ?? '—' }}</td>
                                    <td class="table-td text-sm text-gray-600">
                                        <div class="flex items-center gap-2">
                                            {{ $log->requested_at ? $log->requested_at->format('d M Y, H:i') : '—' }}
                                            @if($slaBreach)
                                                <span class="badge-yellow">SLA Risk</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="table-td text-sm text-gray-400">—</td>
                                    <td class="table-td text-sm text-gray-400">—</td>
                                    <td class="table-td text-right">
                                        <form method="POST"
                                            action="{{ route('crm.compliance.opt-out.process', $log) }}"
                                            onsubmit="return confirm('Process this opt-out request? This action cannot be undone.')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                Process
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">No pending opt-out requests.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(isset($pendingLogs) && method_exists($pendingLogs, 'hasPages') && $pendingLogs->hasPages())
                    <div class="border-t border-gray-100 px-5 py-3">{{ $pendingLogs->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>

        {{-- Processed Tab --}}
        <div x-show="tab === 'processed'" x-transition>
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="table-th">Lead Name</th>
                                <th class="table-th">Channel</th>
                                <th class="table-th">Requested At</th>
                                <th class="table-th">Processed At</th>
                                <th class="table-th">Processed By Job</th>
                                <th class="table-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($processedLogs as $log)
                                <tr class="hover:bg-gray-50/70 transition-colors duration-100">
                                    <td class="table-td">
                                        <a href="{{ route('crm.leads.show', $log->lead) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                            {{ $log->lead?->full_name ?? 'Lead #'.$log->lead_id }}
                                        </a>
                                    </td>
                                    <td class="table-td capitalize text-sm">{{ $log->channel ?? '—' }}</td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $log->requested_at ? $log->requested_at->format('d M Y, H:i') : '—' }}
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $log->processed_at ? $log->processed_at->format('d M Y, H:i') : '—' }}
                                    </td>
                                    <td class="table-td text-sm text-gray-600">{{ $log->processed_by_job ?? '—' }}</td>
                                    <td class="table-td text-right">
                                        <span class="badge-green">Done</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">No processed opt-out requests.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(isset($processedLogs) && method_exists($processedLogs, 'hasPages') && $processedLogs->hasPages())
                    <div class="border-t border-gray-100 px-5 py-3">{{ $processedLogs->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>

    </div>
</x-layouts.crm>
