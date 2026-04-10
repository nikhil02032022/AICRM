<x-layouts.crm title="Broadcast: {{ $broadcast->name }}">
    <div class="max-w-3xl space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('crm.communication.whatsapp.broadcasts.index') }}"
               class="flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500"
               aria-label="Back to broadcasts">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight truncate">{{ $broadcast->name }}</h1>
                    @php
                        $colour = $broadcast->status->colour();
                        $badgeClass = match($colour) {
                            'green'  => 'bg-green-100 text-green-800',
                            'yellow' => 'bg-yellow-100 text-yellow-800',
                            'blue'   => 'bg-blue-100 text-blue-800',
                            'red'    => 'bg-red-100 text-red-800',
                            default  => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                        {{ $broadcast->status->label() }}
                    </span>
                </div>
                <p class="mt-0.5 text-sm text-gray-500">WhatsApp Broadcast · BRD CC-015</p>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800" role="alert">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800" role="alert">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Launch Card (DRAFT only) --}}
        @if ($broadcast->status->isEditable())
            <div class="flex items-center justify-between gap-4 px-5 py-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-emerald-900">Ready to launch?</p>
                        <p class="mt-0.5 text-xs text-emerald-700">Launching will dispatch WhatsApp messages to all matching leads immediately. DNC &amp; opted-out leads are automatically excluded.</p>
                    </div>
                </div>
                @can('crm.campaigns.send')
                    {{-- Hidden launch form --}}
                    <form id="form-launch-wa-{{ $broadcast->uuid }}"
                          method="POST"
                          action="{{ route('crm.communication.whatsapp.broadcasts.launch', $broadcast->uuid) }}"
                          class="hidden">
                        @csrf
                    </form>
                    <button type="button"
                            @click="$dispatch('confirm-launch', { formId: 'form-launch-wa-{{ $broadcast->uuid }}', itemName: '{{ addslashes($broadcast->name) }}' })"
                            class="btn-primary gap-2 whitespace-nowrap flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Launch Now
                    </button>
                @endcan
            </div>
        @endif

        {{-- Details Card --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">
            <div class="px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Broadcast Details</h2>
            </div>
            <div class="px-6 py-5">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Template</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $broadcast->template?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                {{ $broadcast->status->label() }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Recipients</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $broadcast->lead_count > 0 ? number_format($broadcast->lead_count) : 'Computed on launch' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Dispatched</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $broadcast->dispatched_count > 0 ? number_format($broadcast->dispatched_count) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Launched At</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $broadcast->launched_at?->format('d M Y, H:i') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created By</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $broadcast->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $broadcast->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Recipient Segment Summary --}}
        @if (! empty($broadcast->recipient_filter))
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Recipient Segment</h2>
                </div>
                <div class="px-6 py-5 space-y-4 text-sm text-gray-700">
                    @if (! empty($broadcast->recipient_filter['statuses']))
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Lead Status</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($broadcast->recipient_filter['statuses'] as $s)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-xs text-gray-700 font-medium">{{ $s }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if (! empty($broadcast->recipient_filter['temperatures']))
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Temperature</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($broadcast->recipient_filter['temperatures'] as $t)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-xs text-gray-700 font-medium">{{ $t }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if (! empty($broadcast->recipient_filter['sources']))
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Source</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($broadcast->recipient_filter['sources'] as $src)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-xs text-gray-700 font-medium">{{ $src }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if (! empty($broadcast->recipient_filter['date_from']) || ! empty($broadcast->recipient_filter['date_to']))
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Enquiry Date</p>
                            <p class="text-sm text-gray-700">
                                {{ $broadcast->recipient_filter['date_from'] ?? '—' }}
                                <span class="text-gray-400 mx-1">to</span>
                                {{ $broadcast->recipient_filter['date_to'] ?? '—' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="px-5 py-4 bg-blue-50 border border-blue-100 rounded-xl">
                <p class="text-sm text-blue-700">
                    <svg class="w-4 h-4 inline-block mr-1 -mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    No segment filters set — this broadcast targets <strong>all leads</strong> with a valid mobile number (DNC &amp; opted-out excluded).
                </p>
            </div>
        @endif

    </div>

    {{-- Confirm launch modal --}}
    <x-crm.confirm-modal
        variant="launch"
        title="Launch WhatsApp broadcast?"
        subtext="Messages will be dispatched immediately to all matching leads. This action cannot be undone."
        confirm-label="Yes, launch now"
    />
</x-layouts.crm>
