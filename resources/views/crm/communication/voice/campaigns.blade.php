<x-layouts.crm title="Telecalling Campaigns">
    <div class="space-y-6" x-data="{ openCreate: false }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Telecalling Campaigns</h1>
                <p class="mt-1 text-sm text-gray-600">Define campaign lists, assign agents, set time windows, and launch dialler runs with trackable progress.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-secondary-sm" @click="openCreate = !openCreate">Create Campaign</button>
                <a href="{{ route('crm.communication.voice.index') }}" class="btn-primary-sm">Back to Call Log</a>
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-alert type="error" :message="$errors->first()" />
        @endif

        <div class="card" x-show="openCreate" x-transition>
            <div class="card-body">
                <h2 class="text-lg font-semibold text-gray-900">New Telecalling Campaign</h2>
                <form method="POST" action="{{ route('crm.communication.voice.campaigns.store') }}" class="mt-4 grid gap-4 lg:grid-cols-2">
                    @csrf

                    <div>
                        <label for="name" class="label">Campaign Name <span class="text-red-600">*</span></label>
                        <input id="name" name="name" type="text" class="input-field" required value="{{ old('name') }}">
                    </div>

                    <div>
                        <label for="status" class="label">Status</label>
                        <select id="status" name="status" class="input-field">
                            @foreach (\App\Enums\CRM\TelecallingCampaignStatus::cases() as $status)
                                <option value="{{ $status->value }}" {{ old('status', \App\Enums\CRM\TelecallingCampaignStatus::DRAFT->value) === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="description" class="label">Description</label>
                        <textarea id="description" name="description" rows="2" class="input-field">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label for="start_time_window" class="label">Start Time Window</label>
                        <input id="start_time_window" name="start_time_window" type="datetime-local" class="input-field" value="{{ old('start_time_window') }}">
                    </div>

                    <div>
                        <label for="end_time_window" class="label">End Time Window</label>
                        <input id="end_time_window" name="end_time_window" type="datetime-local" class="input-field" value="{{ old('end_time_window') }}">
                    </div>

                    <div>
                        <p class="label">Assign Agents <span class="text-red-600">*</span></p>
                        <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
                            @forelse ($agents as $agent)
                                <label class="flex min-h-11 items-center gap-2 rounded-md border border-gray-100 p-2 hover:bg-gray-50">
                                    <input type="checkbox" name="agent_ids[]" value="{{ $agent->id }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ in_array((string) $agent->id, array_map('strval', old('agent_ids', [])), true) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700">{{ $agent->name }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500">No agents available.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <p class="label">Select Leads <span class="text-red-600">*</span></p>
                        <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
                            @forelse ($candidateLeads as $lead)
                                <label class="flex min-h-11 items-start gap-2 rounded-md border border-gray-100 p-2 hover:bg-gray-50">
                                    <input type="checkbox" name="lead_uuids[]" value="{{ $lead->uuid }}" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ in_array((string) $lead->uuid, array_map('strval', old('lead_uuids', [])), true) ? 'checked' : '' }}>
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

                    <div class="lg:col-span-2">
                        <button type="submit" class="btn-primary-sm">Create Campaign</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="table-th">Campaign</th>
                            <th class="table-th">Window</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Assignments</th>
                            <th class="table-th">Progress</th>
                            <th class="table-th">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($campaigns as $campaign)
                            @php($progress = $progressByUuid[$campaign->uuid] ?? ['placed_calls' => 0, 'total_leads' => 0, 'queued_calls' => 0, 'failed_calls' => 0])
                            <tr>
                                <td class="table-td">
                                    <p class="font-semibold text-gray-900">{{ $campaign->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $campaign->description ?: 'No description' }}</p>
                                </td>
                                <td class="table-td text-gray-600">
                                    <p>{{ $campaign->start_time_window?->format('d M Y, h:i A') ?? 'Immediate' }}</p>
                                    <p class="text-xs text-gray-500">to {{ $campaign->end_time_window?->format('d M Y, h:i A') ?? 'No end' }}</p>
                                </td>
                                <td class="table-td">
                                    <span @class([
                                        'badge',
                                        'badge-gray' => $campaign->status?->value === 'DRAFT',
                                        'badge-blue' => $campaign->status?->value === 'SCHEDULED',
                                        'badge-amber' => $campaign->status?->value === 'ACTIVE',
                                        'badge-red' => $campaign->status?->value === 'PAUSED',
                                        'badge-green' => $campaign->status?->value === 'COMPLETED',
                                    ])>
                                        {{ $campaign->status?->label() ?? 'Draft' }}
                                    </span>
                                </td>
                                <td class="table-td text-gray-600">
                                    <p>{{ $campaign->agents_count }} agent(s)</p>
                                    <p class="text-xs text-gray-500">{{ $campaign->leads_count }} lead(s)</p>
                                </td>
                                <td class="table-td text-gray-600">
                                    <p>Calls: {{ $progress['placed_calls'] }}/{{ $progress['total_leads'] }}</p>
                                    <p class="text-xs text-gray-500">Queued {{ $progress['queued_calls'] }} • Failed {{ $progress['failed_calls'] }}</p>
                                </td>
                                <td class="table-td">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('crm.communication.voice.campaigns.edit', $campaign->uuid) }}" class="btn-secondary-sm">Edit</a>
                                        <form method="POST" action="{{ route('crm.communication.voice.campaigns.launch', $campaign->uuid) }}">
                                            @csrf
                                            <button type="submit" class="btn-secondary-sm" {{ $campaign->status?->value === 'COMPLETED' ? 'disabled' : '' }}>Launch</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-td py-8 text-center text-gray-500">No telecalling campaigns created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">{{ $campaigns->links() }}</div>
            </div>
        </div>
    </div>
</x-layouts.crm>
