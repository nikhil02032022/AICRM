<x-layouts.crm title="Call Log">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Call Log</h1>
                <p class="mt-1 text-sm text-gray-500">Click-to-call history and IVR inbound calls</p>
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        <div class="card">
            <div class="card-body">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="table-th">Lead</th>
                            <th class="table-th">Direction</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Duration</th>
                            <th class="table-th">Disposition</th>
                            <th class="table-th">Initiated By</th>
                            <th class="table-th">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($callLogs as $log)
                        <tr>
                            <td class="table-td font-medium">
                                @if ($log->lead)
                                    <a href="{{ route('crm.leads.show', $log->lead->uuid) }}" class="text-blue-600 hover:underline">{{ $log->lead->name }}</a>
                                @else
                                    <span class="text-gray-400">Unknown</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <span @class(['badge', 'badge-blue' => $log->direction->value === 'OUTBOUND', 'badge-green' => $log->direction->value === 'INBOUND'])>
                                    {{ $log->direction->value }}
                                </span>
                            </td>
                            <td class="table-td">{{ $log->status->value }}</td>
                            <td class="table-td text-gray-500">{{ $log->duration_seconds ? gmdate('i:s', $log->duration_seconds) : '—' }}</td>
                            <td class="table-td text-gray-600">{{ $log->disposition?->value ?? '—' }}</td>
                            <td class="table-td text-gray-500">{{ $log->initiatedBy?->name ?? 'IVR' }}</td>
                            <td class="table-td text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="table-td text-center text-gray-400 py-8">No calls logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $callLogs->links() }}</div>
            </div>
        </div>

    </div>
</x-layouts.crm>
