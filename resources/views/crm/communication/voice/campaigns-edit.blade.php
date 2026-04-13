<x-layouts.crm title="Edit Telecalling Campaign">
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Telecalling Campaign</h1>
                <p class="mt-1 text-sm text-gray-600">Update assignment, status, and time window for {{ $campaign->name }}.</p>
            </div>
            <a href="{{ route('crm.communication.voice.campaigns.index') }}" class="btn-secondary-sm">Back to Campaigns</a>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-alert type="error" :message="$errors->first()" />
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('crm.communication.voice.campaigns.update', $campaign->uuid) }}" class="grid gap-4 lg:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="label">Campaign Name <span class="text-red-600">*</span></label>
                        <input id="name" name="name" type="text" class="input-field" required value="{{ old('name', $campaign->name) }}">
                    </div>

                    <div>
                        <label for="status" class="label">Status</label>
                        <select id="status" name="status" class="input-field">
                            @foreach (\App\Enums\CRM\TelecallingCampaignStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('status', $campaign->status?->value) === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="description" class="label">Description</label>
                        <textarea id="description" name="description" rows="2" class="input-field">{{ old('description', $campaign->description) }}</textarea>
                    </div>

                    <div>
                        <label for="start_time_window" class="label">Start Time Window</label>
                        <input id="start_time_window" name="start_time_window" type="datetime-local" class="input-field" value="{{ old('start_time_window', $campaign->start_time_window?->format('Y-m-d\\TH:i')) }}">
                    </div>

                    <div>
                        <label for="end_time_window" class="label">End Time Window</label>
                        <input id="end_time_window" name="end_time_window" type="datetime-local" class="input-field" value="{{ old('end_time_window', $campaign->end_time_window?->format('Y-m-d\\TH:i')) }}">
                    </div>

                    <div>
                        <p class="label">Assign Agents <span class="text-red-600">*</span></p>
                        <div class="max-h-56 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
                            @forelse ($agents as $agent)
                                <label class="flex min-h-11 items-center gap-2 rounded-md border border-gray-100 p-2 hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        name="agent_ids[]"
                                        value="{{ $agent->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        {{ in_array((int) $agent->id, array_map('intval', old('agent_ids', $selectedAgentIds)), true) ? 'checked' : '' }}
                                    >
                                    <span class="text-sm text-gray-700">{{ $agent->name }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500">No agents available.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <p class="label">Select Leads <span class="text-red-600">*</span></p>
                        <div class="max-h-56 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
                            @forelse ($candidateLeads as $lead)
                                <label class="flex min-h-11 items-start gap-2 rounded-md border border-gray-100 p-2 hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        name="lead_uuids[]"
                                        value="{{ $lead->uuid }}"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        {{ in_array((string) $lead->uuid, array_map('strval', old('lead_uuids', $selectedLeadUuids)), true) ? 'checked' : '' }}
                                    >
                                    <span class="text-sm text-gray-700">
                                        <span class="font-medium">{{ trim($lead->first_name.' '.$lead->last_name) }}</span>
                                        <span class="block text-xs text-gray-500">Score {{ $lead->lead_score }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500">No callable leads available.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="lg:col-span-2 flex items-center gap-2">
                        <button type="submit" class="btn-primary-sm">Update Campaign</button>
                        <a href="{{ route('crm.communication.voice.campaigns.index') }}" class="btn-secondary-sm">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.crm>
