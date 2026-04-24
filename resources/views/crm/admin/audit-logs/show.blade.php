<x-layouts.crm title="Audit Log Entry">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Audit Log Entry</h1>
                <p class="mt-1 text-sm text-gray-500">Read-only record · Entry #{{ $log->id }}</p>
            </div>
            <a href="{{ route('crm.admin.audit-logs.index') }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Audit Trail
            </a>
        </div>

        {{-- Meta details --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3 mb-5">Entry Details</h2>
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Entry ID</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900">{{ $log->id }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Entity Type</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $log->subject_type ?? $log->auditable_type ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Entity ID</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900">{{ $log->subject_id ?? $log->auditable_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Action / Event</dt>
                    <dd class="mt-1">
                        @php $event = $log->event ?? 'unknown'; @endphp
                        @if(in_array($event, ['created','create']))
                            <span class="badge-green">{{ ucfirst($event) }}</span>
                        @elseif(in_array($event, ['updated','update']))
                            <span class="badge-yellow">{{ ucfirst($event) }}</span>
                        @elseif(in_array($event, ['deleted','delete']))
                            <span class="badge-red">{{ ucfirst($event) }}</span>
                        @else
                            <span class="badge-gray">{{ ucfirst($event) }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Performed By</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ optional($log->causer)->name ?? 'System / Unknown' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">IP Address</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900">{{ $log->ip_address ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Recorded At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $log->created_at->format('l, d F Y \a\t H:i:s T') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Before / After diff --}}
        <div class="grid gap-5 lg:grid-cols-2">
            {{-- Before --}}
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-800">Before</h3>
                    <span class="ml-auto text-xs text-gray-400">Old values</span>
                </div>
                @if($log->old_values)
                    <pre class="text-xs bg-gray-50 rounded p-3 overflow-x-auto text-gray-700 leading-relaxed">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <p class="text-xs text-gray-400 italic">No previous values (record was created).</p>
                @endif
            </div>

            {{-- After --}}
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-100 text-green-600">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-800">After</h3>
                    <span class="ml-auto text-xs text-gray-400">New values</span>
                </div>
                @if($log->new_values)
                    <pre class="text-xs bg-gray-50 rounded p-3 overflow-x-auto text-gray-700 leading-relaxed">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <p class="text-xs text-gray-400 italic">No new values (record was deleted).</p>
                @endif
            </div>
        </div>

    </div>
</x-layouts.crm>
