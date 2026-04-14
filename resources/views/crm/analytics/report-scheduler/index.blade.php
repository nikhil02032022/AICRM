<x-layouts.crm title="Scheduled Reports">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Scheduled Reports</h1>
                <p class="mt-1 text-sm text-gray-500">Automatically deliver reports to recipient inboxes on a schedule</p>
            </div>
            @can('crm.reports.manage')
            <a href="{{ route('crm.reports.scheduler.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Schedule
            </a>
            @endcan
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Report</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Frequency</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Format</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Recipients</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Next Run</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($schedules as $schedule)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $schedule->customReport->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ $schedule->frequency->value }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 uppercase">{{ $schedule->format->value }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ count($schedule->recipient_emails) }} recipients</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $schedule->next_run_at ? $schedule->next_run_at->format('d M Y, H:i') : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($schedule->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">Active</span>
                            @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Paused</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3"
                                 x-data="{ dispatching: false }"
                            >
                                @can('crm.reports.manage')
                                <button
                                    type="button"
                                    :disabled="dispatching"
                                    @click="dispatching = true; fetch('{{ route('crm.reports.scheduler.dispatch', $schedule->uuid) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } }).then(() => dispatching = false)"
                                    class="text-sm font-medium text-green-600 hover:text-green-800 disabled:opacity-50"
                                    aria-label="Dispatch now"
                                >
                                    <span x-show="!dispatching">Send Now</span>
                                    <span x-show="dispatching">Sending…</span>
                                </button>
                                <a href="{{ route('crm.reports.scheduler.edit', $schedule->uuid) }}"
                                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                <form method="POST" action="{{ route('crm.reports.scheduler.destroy', $schedule->uuid) }}" onsubmit="return confirm('Remove this schedule?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-700">Delete</button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">
                            No schedules configured. <a href="{{ route('crm.reports.scheduler.create') }}" class="text-indigo-600 hover:underline">Create the first schedule</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schedules->hasPages())
        <div class="flex justify-end">{{ $schedules->links() }}</div>
        @endif
    </div>
</x-layouts.crm>
