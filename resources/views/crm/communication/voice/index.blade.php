<x-layouts.crm title="Call Log">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Call Log</h1>
                <p class="mt-1 text-sm text-gray-500">Click-to-call history and IVR inbound calls</p>
            </div>
            <div class="flex items-center gap-2">
                @can('crm.settings.manage')
                    <a href="{{ route('crm.communication.voice.dispositions.index') }}" class="btn-secondary-sm">Disposition Settings</a>
                @endcan
                @can('crm.dnc.manage')
                    <a href="{{ route('crm.communication.voice.dnc.index') }}" class="btn-secondary-sm">DNC List</a>
                @endcan
                <a href="{{ route('crm.communication.voice.campaigns.index') }}" class="btn-secondary-sm">Telecalling Campaigns</a>
                <a href="{{ route('crm.communication.voice.monitor.index') }}" class="btn-secondary-sm">Supervisor Monitor</a>
                <a href="{{ route('crm.communication.voice.scripts.index') }}" class="btn-secondary-sm">Open Call Scripts</a>
                <a href="{{ route('crm.communication.voice.dialler.index') }}" class="btn-primary-sm">Open Power Dialler</a>
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('crm.communication.voice.index') }}" class="grid gap-3 md:grid-cols-5">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search lead/agent/provider call ID"
                        class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 md:col-span-2"
                    >
                    <input
                        type="date"
                        name="from_date"
                        value="{{ $fromDate ?? '' }}"
                        class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    >
                    <input
                        type="date"
                        name="to_date"
                        value="{{ $toDate ?? '' }}"
                        class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    >
                    <div class="flex items-center gap-2">
                        <select
                            name="has_recording"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                        >
                            <option value="all" @selected(($hasRecording ?? 'all') === 'all')>All calls</option>
                            <option value="yes" @selected(($hasRecording ?? 'all') === 'yes')>With recording</option>
                            <option value="no" @selected(($hasRecording ?? 'all') === 'no')>Without recording</option>
                        </select>
                        <button type="submit" class="btn-secondary-sm">Filter</button>
                    </div>
                </form>
            </div>
        </div>

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
                            <th class="table-th">Recording</th>
                            <th class="table-th">Update</th>
                            <th class="table-th">Initiated By</th>
                            <th class="table-th">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($callLogs as $log)
                        <tr>
                            <td class="table-td font-medium">
                                @if ($log->lead)
                                    <a href="{{ route('crm.leads.show', $log->lead->uuid) }}" class="text-blue-600 hover:underline">{{ $log->lead->fullName() }}</a>
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
                            <td class="table-td text-gray-600">{{ $log->disposition?->value ? ($dispositionLabelMap[$log->disposition->value] ?? $log->disposition->value) : '—' }}</td>
                            <td class="table-td">
                                @if ($log->call_consent_given && filled($log->recording_url))
                                    <a href="{{ route('crm.communication.voice.calls.recording', $log->uuid) }}" class="btn-secondary-sm">Play</a>
                                @elseif (! $log->call_consent_given)
                                    <span class="text-xs text-gray-500">No consent</span>
                                @else
                                    <span class="text-xs text-gray-400">Pending</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <form method="POST" action="{{ route('crm.communication.voice.calls.disposition', $log->uuid) }}" class="grid gap-2 sm:grid-cols-3">
                                    @csrf
                                    <select name="disposition" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                                        <option value="">Select</option>
                                        @foreach (($dispositionOptions ?? []) as $code => $label)
                                            <option value="{{ $code }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input
                                        type="number"
                                        name="duration_seconds"
                                        min="0"
                                        placeholder="Duration (s)"
                                        class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                    >
                                    <button type="submit" class="btn-secondary-sm">Save</button>
                                </form>
                            </td>
                            <td class="table-td text-gray-500">{{ $log->initiatedBy?->name ?? 'IVR' }}</td>
                            <td class="table-td text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="table-td text-center text-gray-400 py-8">No calls logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $callLogs->links() }}</div>
            </div>
        </div>

    </div>
</x-layouts.crm>
