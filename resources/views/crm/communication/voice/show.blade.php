<x-layouts.crm title="Call Log Detail">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Call Log</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $callLog->direction?->value === 'inbound' ? 'Inbound' : 'Outbound' }} call
                    @if($callLog->called_at)
                        &middot; {{ $callLog->called_at->format('d M Y, H:i') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('crm.communication.voice.index') }}" class="btn-secondary-sm">Back to Call Log</a>
        </div>

        @if(session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if(session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif

        {{-- Call metadata card --}}
        <div class="card">
            <div class="card-body">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Call Details</h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm md:grid-cols-4">
                    <div>
                        <dt class="font-medium text-gray-500">Lead</dt>
                        <dd class="mt-0.5 text-gray-900">
                            @if($callLog->lead)
                                <a href="{{ route('crm.leads.show', $callLog->lead->uuid) }}" class="text-indigo-600 hover:underline">
                                    {{ $callLog->lead->full_name ?? '—' }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Counsellor</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $callLog->initiatedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Duration</dt>
                        <dd class="mt-0.5 text-gray-900">
                            @if($callLog->duration_seconds)
                                {{ gmdate('i:s', $callLog->duration_seconds) }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Disposition</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $callLog->disposition?->value ?? '—' }}</dd>
                    </div>
                    @if($callLog->disposition_notes)
                        <div class="col-span-2 md:col-span-4">
                            <dt class="font-medium text-gray-500">Notes</dt>
                            <dd class="mt-0.5 text-gray-900">{{ $callLog->disposition_notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- AI Transcription panel --}}
        <div class="card">
            <div class="card-body">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">AI Transcription</h2>
                @livewire('crm.communication.transcription-panel', ['callLogUuid' => $callLog->uuid])
            </div>
        </div>

        {{-- Recording (consent-gated) --}}
        @if($callLog->call_consent_given && $callLog->recording_url)
            <div class="card">
                <div class="card-body">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Recording</h2>
                    <a href="{{ route('crm.communication.voice.calls.recording', $callLog->uuid) }}" class="btn-secondary-sm">
                        Play Recording
                    </a>
                </div>
            </div>
        @endif

    </div>
</x-layouts.crm>
