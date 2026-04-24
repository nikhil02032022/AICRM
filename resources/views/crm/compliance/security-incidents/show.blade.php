<x-layouts.crm title="{{ $incident->incident_type }} — #{{ $incident->id }}">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $incident->incident_type }} — #{{ $incident->id }}</h1>
                <p class="mt-1 text-sm text-gray-500">Security incident detail — DPDP Act 2023 CR-010</p>
            </div>
            <a href="{{ route('crm.compliance.security-incidents.index') }}" class="btn-secondary">
                &larr; Back to Incidents
            </a>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Detail Card --}}
        <div class="card p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Incident Information</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Incident Type</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $incident->incident_type }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</dt>
                    <dd class="mt-1">
                        @php
                            $statusBadge = match(strtolower($incident->status?->value ?? '')) {
                                'reported'      => 'badge-red',
                                'investigating' => 'badge-yellow',
                                'notified'      => 'badge-blue',
                                'resolved'      => 'badge-green',
                                default         => 'badge-gray',
                            };
                        @endphp
                        <span class="{{ $statusBadge }}">{{ $incident->status?->label() ?? ucfirst($incident->status?->value ?? '—') }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Detected At</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $incident->detected_at ? $incident->detected_at->format('d M Y, H:i:s') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Reported By</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $incident->reportedBy?->name ?? '—' }}</dd>
                </div>
                @if($incident->notified_at)
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Notified At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $incident->notified_at->format('d M Y, H:i:s') }}</dd>
                </div>
                @endif
                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Description</dt>
                    <dd class="mt-1 text-sm text-gray-700 whitespace-pre-line leading-relaxed bg-gray-50 rounded-lg p-3 border border-gray-200">{{ $incident->description }}</dd>
                </div>
            </dl>
        </div>

        {{-- Actions Row --}}
        <div class="flex flex-wrap gap-3">

            {{-- Send Breach Notification --}}
            @if(!$incident->notified_at)
                <div class="card p-5 flex-1 min-w-64">
                    <h2 class="text-sm font-semibold text-gray-800 mb-2">Send Breach Notification</h2>
                    <p class="text-xs text-gray-500 mb-3">Required by DPDP Act 2023 within 72 hours of detection.</p>
                    <form method="POST" action="{{ route('crm.compliance.security-incidents.update', $incident) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="send_notification" value="1">
                        <button type="submit"
                            onclick="return confirm('Send breach notification now? This will mark the incident as notified.')"
                            class="btn-primary text-sm">
                            Send Breach Notification
                        </button>
                    </form>
                </div>
            @endif

            {{-- Update Status --}}
            <div class="card p-5 flex-1 min-w-64">
                <h2 class="text-sm font-semibold text-gray-800 mb-2">Update Status</h2>
                <form method="POST" action="{{ route('crm.compliance.security-incidents.update', $incident) }}" class="flex items-end gap-3">
                    @csrf
                    @method('PUT')
                    <div class="flex-1">
                        <label for="status" class="block text-xs font-medium text-gray-700 mb-1">New Status</label>
                        <select name="status" id="status"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Enums\CRM\SecurityIncidentStatus::cases() as $statusCase)
                                <option value="{{ $statusCase->value }}"
                                    @selected($incident->status?->value === $statusCase->value)>
                                    {{ $statusCase->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary text-sm">Update</button>
                </form>
            </div>
        </div>

        {{-- Documentation JSON --}}
        @if($incident->documentation_json)
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Documentation</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Structured documentation and audit trail for this incident</p>
                </div>
                <div class="p-5">
                    <pre class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs font-mono text-gray-800 overflow-x-auto max-h-80 overflow-y-auto leading-relaxed">{{ json_encode($incident->documentation_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif

    </div>
</x-layouts.crm>
