<x-layouts.crm title="Call Monitoring">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Supervisor Call Monitoring</h1>
                <p class="mt-1 text-sm text-gray-600">Join active calls in Listen, Whisper, or Barge-In mode with consent-aware audit logging.</p>
            </div>
            <a href="{{ route('crm.communication.voice.index') }}" class="btn-secondary-sm">Back to Call Log</a>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-alert type="error" :message="$errors->first()" />
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            <section class="card lg:col-span-3">
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Active Calls</h2>
                        <span class="text-xs text-gray-500">{{ $activeCalls->total() }} in queue</span>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="table-th">Lead</th>
                                    <th class="table-th">Status</th>
                                    <th class="table-th">Initiated</th>
                                    <th class="table-th">Mode</th>
                                    <th class="table-th">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($activeCalls as $call)
                                    <tr>
                                        <td class="table-td font-medium text-slate-900">{{ $call->lead?->name ?? 'Unknown lead' }}</td>
                                        <td class="table-td">{{ $call->status->label() }}</td>
                                        <td class="table-td text-slate-500">{{ $call->called_at?->diffForHumans() ?? 'Just now' }}</td>
                                        <td class="table-td">
                                            <form method="POST" action="{{ route('crm.communication.voice.monitor.store') }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="call_log_uuid" value="{{ $call->uuid }}">
                                                <select name="mode" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                                    <option value="LISTEN">Listen</option>
                                                    <option value="WHISPER">Whisper</option>
                                                    <option value="BARGE_IN">Barge-In</option>
                                                </select>
                                        </td>
                                        <td class="table-td">
                                                <button type="submit" class="btn-primary-sm">Start</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="table-td py-8 text-center text-slate-500">No active calls available for monitoring.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>{{ $activeCalls->links() }}</div>
                </div>
            </section>

            <aside class="card lg:col-span-2">
                <div class="card-body space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Monitoring Sessions</h2>
                    <div class="space-y-3">
                        @forelse ($monitorSessions as $session)
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $session->callLog?->lead?->name ?? 'Unknown lead' }}</p>
                                        <p class="text-xs text-slate-500">{{ $session->mode->label() }} · {{ $session->status->value }}</p>
                                        <p class="text-xs text-slate-500">Started {{ $session->started_at?->diffForHumans() }}</p>
                                    </div>
                                </div>
                                @if ($session->status->value === 'ACTIVE')
                                    <form method="POST" action="{{ route('crm.communication.voice.monitor.stop', $session->uuid) }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="btn-secondary-sm">End Session</button>
                                    </form>
                                @else
                                    <p class="mt-2 text-xs text-slate-500">Duration: {{ $session->duration_seconds }}s</p>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-sm text-slate-500">No monitoring sessions yet.</p>
                        @endforelse
                    </div>
                    <div>{{ $monitorSessions->links() }}</div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.crm>
