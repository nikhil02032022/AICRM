<x-layouts.crm title="Audit Trail">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Audit Trail</h1>
                <p class="mt-1 text-sm text-gray-500">Read-only — append-only audit trail of all system activity</p>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="card p-4">
            <form method="GET" action="{{ route('crm.admin.audit-logs.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="form-group mb-0">
                    <label for="model_type" class="form-label text-xs">Entity Type</label>
                    <input
                        id="model_type"
                        type="text"
                        name="model_type"
                        value="{{ request('model_type') }}"
                        placeholder="e.g. Lead"
                        class="form-input"
                    >
                </div>
                <div class="form-group mb-0">
                    <label for="user" class="form-label text-xs">User</label>
                    <input
                        id="user"
                        type="text"
                        name="user"
                        value="{{ request('user') }}"
                        placeholder="Name or email"
                        class="form-input"
                    >
                </div>
                <div class="form-group mb-0">
                    <label for="date_from" class="form-label text-xs">Date From</label>
                    <input
                        id="date_from"
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        class="form-input"
                    >
                </div>
                <div class="form-group mb-0">
                    <label for="date_to" class="form-label text-xs">Date To</label>
                    <input
                        id="date_to"
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        class="form-input"
                    >
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary flex-1">Filter</button>
                    @if(request()->hasAny(['model_type','user','date_from','date_to']))
                        <a href="{{ route('crm.admin.audit-logs.index') }}" class="btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Date / Time</th>
                            <th class="table-th">User</th>
                            <th class="table-th">Entity Type</th>
                            <th class="table-th">Entity ID</th>
                            <th class="table-th">Action</th>
                            <th class="table-th">IP Address</th>
                            <th class="table-th text-right">View</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="table-td text-sm text-gray-600 whitespace-nowrap">
                                    {{ $log->created_at->format('d M Y, H:i:s') }}
                                </td>
                                <td class="table-td text-gray-700">
                                    {{ optional($log->causer)->name ?? '—' }}
                                </td>
                                <td class="table-td">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                        {{ class_basename($log->subject_type ?? $log->auditable_type ?? '—') }}
                                    </span>
                                </td>
                                <td class="table-td text-gray-500 text-sm font-mono">
                                    {{ $log->subject_id ?? $log->auditable_id ?? '—' }}
                                </td>
                                <td class="table-td">
                                    @php
                                        $event = $log->event ?? $log->event ?? 'unknown';
                                    @endphp
                                    @if(in_array($event, ['created','create']))
                                        <span class="badge-green">{{ ucfirst($event) }}</span>
                                    @elseif(in_array($event, ['updated','update']))
                                        <span class="badge-yellow">{{ ucfirst($event) }}</span>
                                    @elseif(in_array($event, ['deleted','delete']))
                                        <span class="badge-red">{{ ucfirst($event) }}</span>
                                    @else
                                        <span class="badge-gray">{{ ucfirst($event) }}</span>
                                    @endif
                                </td>
                                <td class="table-td text-gray-500 text-sm font-mono">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                                <td class="table-td text-right">
                                    <a
                                        href="{{ route('crm.admin.audit-logs.show', $log) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-td text-center text-gray-400 py-10">
                                    No audit log entries found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.crm>
