<x-layouts.crm title="Power Dialler">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Power Dialler</h1>
                <p class="mt-1 text-sm text-gray-500">Queue outbound calls from your assigned leads with consent and DNC safeguards.</p>
            </div>
            <a href="{{ route('crm.communication.voice.index') }}" class="btn-secondary-sm">Back to Call Log</a>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card lg:col-span-1">
                <div class="card-body">
                    <h2 class="text-sm font-semibold text-gray-900">Start New Dialler Session</h2>
                    <p class="mt-1 text-xs text-gray-500">BRD: CRM-TC-001 — auto-places one call at a time through queue workers.</p>

                    <form method="POST" action="{{ route('crm.communication.voice.dialler.start') }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="campaign_name" class="label">Campaign Name</label>
                            <input id="campaign_name" name="campaign_name" type="text" class="input-field" placeholder="May Intake Round 1" value="{{ old('campaign_name') }}">
                            @error('campaign_name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="lead_limit" class="label">Lead Limit</label>
                            <input id="lead_limit" name="lead_limit" type="number" min="1" max="200" class="input-field" value="{{ old('lead_limit', 25) }}">
                            @error('lead_limit')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <p class="label">Candidate Leads (optional explicit queue)</p>
                            <div class="max-h-60 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
                                @forelse($candidateLeads as $lead)
                                    <label class="flex items-start gap-3 rounded-md border border-gray-100 p-2 hover:bg-gray-50">
                                        <input type="checkbox" name="lead_uuids[]" value="{{ $lead->uuid }}" class="mt-0.5">
                                        <span class="text-sm text-gray-700">
                                            <span class="font-medium">{{ $lead->fullName() }}</span>
                                            <span class="block text-xs text-gray-500">Score {{ $lead->lead_score }} • {{ $lead->status->label() }} • {{ strtoupper($lead->temperature->value) }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-500">No callable assigned leads found.</p>
                                @endforelse
                            </div>
                            @error('lead_uuids')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('lead_uuids.*')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn-primary-sm w-full">Start Dialler</button>
                    </form>
                </div>
            </div>

            <div class="card lg:col-span-2">
                <div class="card-body">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Sessions</h2>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr>
                                    <th class="table-th">Session</th>
                                    <th class="table-th">Status</th>
                                    <th class="table-th">Progress</th>
                                    <th class="table-th">Started By</th>
                                    <th class="table-th">Updated</th>
                                    <th class="table-th">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($sessions as $session)
                                    <tr>
                                        <td class="table-td">
                                            <span class="font-medium text-gray-800">{{ $session->campaign_name ?? 'General Queue' }}</span>
                                            <span class="block text-xs text-gray-500">{{ $session->uuid }}</span>
                                        </td>
                                        <td class="table-td">
                                            <span @class([
                                                'badge',
                                                'badge-blue' => $session->status->value === 'QUEUED',
                                                'badge-amber' => $session->status->value === 'ACTIVE',
                                                'badge-green' => $session->status->value === 'COMPLETED',
                                                'badge-gray' => $session->status->value === 'STOPPED',
                                                'badge-red' => $session->status->value === 'FAILED',
                                            ])>
                                                {{ $session->status->label() }}
                                            </span>
                                        </td>
                                        <td class="table-td text-gray-600">
                                            <span class="font-medium">{{ $session->placed_calls }}/{{ $session->total_leads }}</span>
                                            <span class="block text-xs text-gray-500">Queued {{ $session->queued_calls }}, Skipped {{ $session->skipped_calls }}, Failed {{ $session->failed_calls }}</span>
                                        </td>
                                        <td class="table-td text-gray-600">{{ $session->starter?->name ?? 'System' }}</td>
                                        <td class="table-td text-gray-500">{{ $session->updated_at->diffForHumans() }}</td>
                                        <td class="table-td">
                                            <div class="flex items-center gap-2">
                                                @if (in_array($session->status->value, ['QUEUED', 'ACTIVE'], true))
                                                    <form method="POST" action="{{ route('crm.communication.voice.dialler.next', $session->uuid) }}">
                                                        @csrf
                                                        <button type="submit" class="btn-secondary-sm">Queue Next</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('crm.communication.voice.dialler.stop', $session->uuid) }}">
                                                        @csrf
                                                        <button type="submit" class="btn-danger-sm">Stop</button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-gray-500">No action</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="table-td py-8 text-center text-gray-500">No dialler sessions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $sessions->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.crm>
