<x-layouts.crm title="Consent Record Detail">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Consent Record Detail</h1>
                <p class="mt-1 text-sm text-gray-500">Full consent capture information for this record</p>
            </div>
            <a href="{{ route('crm.compliance.consent.index') }}" class="btn-secondary">
                &larr; Back to Consent Records
            </a>
        </div>

        {{-- Detail Card --}}
        <div class="card p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Consent Information</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lead</dt>
                    <dd class="mt-1 text-sm font-medium">
                        <a href="{{ route('crm.leads.show', $record->lead) }}"
                            class="text-indigo-600 hover:text-indigo-800 hover:underline">
                            {{ $record->lead?->full_name ?? 'Lead #'.$record->lead_id }}
                        </a>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Consent Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $record->consent_type) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">IP Address</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900">{{ $record->ip_address ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Form Version</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $record->form_version ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Consented At</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $record->consented_at ? $record->consented_at->format('d M Y, H:i:s') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Opted Out At</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($record->opt_out_at)
                            <span class="badge-red">{{ $record->opt_out_at->format('d M Y, H:i:s') }}</span>
                        @else
                            <span class="badge-green">Active — not opted out</span>
                        @endif
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">User Agent</dt>
                    <dd class="mt-1 text-xs font-mono text-gray-700 break-all bg-gray-50 rounded p-2 border border-gray-200">
                        {{ $record->user_agent ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Opt-Out History --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">Opt-Out History</h2>
                <p class="text-xs text-gray-500 mt-0.5">All opt-out log entries for this lead</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="table-th">Channel</th>
                            <th class="table-th">Requested At</th>
                            <th class="table-th">Processed At</th>
                            <th class="table-th">Processed By</th>
                            <th class="table-th text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($optOutLogs as $log)
                            <tr class="hover:bg-gray-50/70 transition-colors duration-100">
                                <td class="table-td capitalize">{{ $log->channel ?? '—' }}</td>
                                <td class="table-td text-sm text-gray-600">
                                    {{ $log->requested_at ? $log->requested_at->format('d M Y, H:i') : '—' }}
                                </td>
                                <td class="table-td text-sm text-gray-600">
                                    {{ $log->processed_at ? $log->processed_at->format('d M Y, H:i') : '—' }}
                                </td>
                                <td class="table-td text-sm text-gray-600">{{ $log->processed_by_job ?? '—' }}</td>
                                <td class="table-td text-center">
                                    @if($log->processed_at)
                                        <span class="badge-green">Processed</span>
                                    @else
                                        <span class="badge-yellow">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">No opt-out history for this lead.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-layouts.crm>
