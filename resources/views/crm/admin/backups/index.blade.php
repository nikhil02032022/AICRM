<x-layouts.crm title="Database Backups">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Database Backups</h1>
                <p class="mt-1 text-sm text-gray-500">Manage and monitor database backup snapshots</p>
            </div>
            <form
                method="POST"
                action="{{ route('crm.admin.backups.trigger') }}"
                onsubmit="return confirm('Start a new database backup now? This may take a few minutes.')"
            >
                @csrf
                <button type="submit" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Run Backup Now
                </button>
            </form>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        {{-- Stats row --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-white shadow-sm p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Backups</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $logs->total() }}</p>
            </div>
            <div class="rounded-xl border bg-white shadow-sm p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Backup</p>
                <p class="mt-2 text-sm font-semibold text-gray-900">
                    {{ $logs->first()?->created_at?->diffForHumans() ?? 'Never' }}
                </p>
            </div>
            <div class="rounded-xl border bg-white shadow-sm p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Latest Status</p>
                <p class="mt-2">
                    @if($logs->first())
                        @if($logs->first()->status?->value === 'completed')
                            <span class="badge-green">Completed</span>
                        @elseif($logs->first()->status?->value === 'failed')
                            <span class="badge-red">Failed</span>
                        @else
                            <span class="badge-yellow">{{ ucfirst($logs->first()->status?->value ?? 'Unknown') }}</span>
                        @endif
                    @else
                        <span class="badge-gray">No backups</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Table --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Filename</th>
                            <th class="table-th">Disk</th>
                            <th class="table-th">Size</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Started</th>
                            <th class="table-th">Completed</th>
                            <th class="table-th text-right">Download</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="table-td font-mono text-xs text-gray-700">{{ $log->filename }}</td>
                                <td class="table-td text-gray-600 text-sm">{{ $log->disk ?? 'local' }}</td>
                                <td class="table-td text-gray-600 text-sm whitespace-nowrap">
                                    {{ $log->formattedSize() }}
                                </td>
                                <td class="table-td">
                                    @if($log->status?->value === 'completed')
                                        <span class="badge-green">Completed</span>
                                    @elseif($log->status?->value === 'failed')
                                        <span class="badge-red">Failed</span>
                                    @elseif($log->status?->value === 'running')
                                        <span class="badge-yellow">Running</span>
                                    @else
                                        <span class="badge-gray">{{ ucfirst($log->status?->value ?? 'Pending') }}</span>
                                    @endif
                                </td>
                                <td class="table-td text-gray-500 text-sm whitespace-nowrap">
                                    {{ $log->started_at?->format('d M Y, H:i') ?? '—' }}
                                </td>
                                <td class="table-td text-gray-500 text-sm whitespace-nowrap">
                                    {{ $log->completed_at?->format('d M Y, H:i') ?? '—' }}
                                </td>
                                <td class="table-td text-right">
                                    @if($log->status?->value === 'completed')
                                        <a
                                            href="{{ route('crm.admin.backups.download', $log) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 shadow-sm hover:bg-indigo-100 transition-colors"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Download
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-td text-center text-gray-400 py-10">
                                    No backups found. Run your first backup using the button above.
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
