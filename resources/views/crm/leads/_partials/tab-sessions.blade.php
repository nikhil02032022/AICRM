{{-- BRD: CRM-EC-015 — Sessions tab panel: book + list existing sessions --}}
<div x-show="tab === 'sessions'"
     x-transition:enter="transition-opacity duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     class="p-0">

    {{-- Book a new session --}}
    @can('crm.sessions.create')
    <div class="border-b border-gray-100 bg-gray-50/60 p-4">
        <p class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">Book Session</p>
        <livewire:crm.lead.session-booking-form
            :lead-uuid="$lead->uuid"
            :lead-id="$lead->getKey()"
            :key="'session-form-'.$lead->uuid" />
    </div>
    @endcan

    {{-- Sessions list --}}
    <div class="p-4">
        <p class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400">All Sessions</p>
        @forelse($lead->sessions()->with('counsellor:id,name')->orderByDesc('scheduled_at')->limit(20)->get() as $session)
            <div class="mb-2 flex items-center justify-between rounded-lg border border-gray-100 bg-white p-3 shadow-sm"
                 wire:key="session-{{ $session->getKey() }}">
                <div>
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $session->session_type->label() }}
                        <span class="ml-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase badge-{{ $session->status->badgeColour() }}">
                            {{ $session->status->label() }}
                        </span>
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ $session->scheduled_at?->format('d M Y, g:i A') }}
                        &middot; {{ ucfirst($session->mode) }}
                        @if($session->counsellor)
                            &middot; {{ $session->counsellor->name }}
                        @endif
                    </p>
                </div>
                @if(!$session->status->isTerminal())
                    @can('crm.sessions.cancel')
                    <button type="button"
                            onclick="if(confirm('Cancel this session?')) {
                                fetch('{{ route('crm.sessions.destroy', $session) }}', {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('[name=csrf-token]').content,
                                        'Accept': 'application/json'
                                    }
                                }).then(() => window.location.reload());
                            }"
                            class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-100">
                        Cancel
                    </button>
                    @endcan
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
                <p class="text-sm text-gray-500">No sessions scheduled yet.</p>
            </div>
        @endforelse
    </div>

</div>
