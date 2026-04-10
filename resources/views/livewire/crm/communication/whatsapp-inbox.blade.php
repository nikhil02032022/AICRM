<div class="space-y-4">

    {{-- Stats row (single row, 4 cols) --}}
    <div class="grid grid-cols-4 gap-3">
        @php
            $openCount  = \App\Models\CRM\WhatsAppConversation::where('status', \App\Enums\CRM\ConversationStatus::OPEN->value)->count();
            $pendCount  = \App\Models\CRM\WhatsAppConversation::where('status', \App\Enums\CRM\ConversationStatus::PENDING->value)->count();
            $resolCount = \App\Models\CRM\WhatsAppConversation::where('status', \App\Enums\CRM\ConversationStatus::RESOLVED->value)->count();
            $totalCount = \App\Models\CRM\WhatsAppConversation::count();
        @endphp
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide leading-none">Total</p>
                <p class="text-lg font-bold text-gray-900 leading-tight mt-0.5">{{ $totalCount }}</p>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 block"></span>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide leading-none">Open</p>
                <p class="text-lg font-bold text-green-700 leading-tight mt-0.5">{{ $openCount }}</p>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-yellow-50 flex items-center justify-center flex-shrink-0">
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 block"></span>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide leading-none">Pending</p>
                <p class="text-lg font-bold text-yellow-700 leading-tight mt-0.5">{{ $pendCount }}</p>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide leading-none">Resolved</p>
                <p class="text-lg font-bold text-gray-600 leading-tight mt-0.5">{{ $resolCount }}</p>
            </div>
        </div>
    </div>

    {{-- Search + Filter --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by lead name..."
                aria-label="Search conversations by lead name"
                class="input-field pl-9 py-2"
            />
        </div>

        <div class="relative">
            <select
                wire:model.live="filterStatus"
                aria-label="Filter by status"
                class="input-field py-2 pr-8 cursor-pointer appearance-none"
            >
                <option value="">All statuses</option>
                @foreach (\App\Enums\CRM\ConversationStatus::cases() as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Conversation List --}}
    <div class="card !p-0 overflow-hidden">

        {{-- Loading overlay --}}
        <div wire:loading.flex class="items-center justify-center gap-2 py-3 px-4 bg-indigo-50 border-b border-indigo-100 text-sm text-indigo-700">
            <svg class="animate-spin h-4 w-4 text-indigo-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Updating conversations...
        </div>

        <ul role="list" class="divide-y divide-gray-100">
            @forelse ($this->conversations as $conv)
                @php
                    $statusEnum = \App\Enums\CRM\ConversationStatus::tryFrom($conv->status);
                    $badgeClass = match($statusEnum) {
                        \App\Enums\CRM\ConversationStatus::OPEN     => 'bg-green-100 text-green-700',
                        \App\Enums\CRM\ConversationStatus::PENDING  => 'bg-yellow-100 text-yellow-700',
                        \App\Enums\CRM\ConversationStatus::RESOLVED => 'bg-gray-100 text-gray-500',
                        \App\Enums\CRM\ConversationStatus::EXPIRED  => 'bg-red-100 text-red-600',
                        default                                      => 'bg-gray-100 text-gray-500',
                    };
                    $initials = strtoupper(substr($conv->lead?->name ?? '?', 0, 2));
                    $avatarColors = ['bg-green-100 text-green-700', 'bg-indigo-100 text-indigo-700', 'bg-violet-100 text-violet-700', 'bg-blue-100 text-blue-700', 'bg-teal-100 text-teal-700'];
                    $avatarColor = $avatarColors[crc32($conv->uuid ?? '') % count($avatarColors)];
                @endphp
                <li>
                    <a
                        href="{{ route('crm.communication.whatsapp.conversation', $conv->uuid) }}"
                        class="flex items-center gap-4 px-4 py-3.5 hover:bg-gray-50 focus:outline-none focus:bg-indigo-50 transition-colors duration-150 cursor-pointer group"
                    >
                        {{-- Avatar --}}
                        <div class="flex-shrink-0 h-11 w-11 rounded-full {{ $avatarColor }} flex items-center justify-center font-semibold text-sm select-none" aria-hidden="true">
                            {{ $initials }}
                        </div>

                        {{-- Main content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-gray-900 text-sm truncate group-hover:text-indigo-700 transition-colors duration-150">
                                    {{ $conv->lead?->name ?? 'Unknown Lead' }}
                                </span>
                                <span class="text-xs text-gray-400 flex-shrink-0">
                                    {{ $conv->last_activity_at?->diffForHumans() ?? '' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2 mt-0.5">
                                <p class="text-xs text-gray-500 truncate">
                                    {{ $conv->latestMessage?->body ?? 'No messages yet' }}
                                </p>
                                @if ($statusEnum)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 {{ $badgeClass }}">
                                        {{ $statusEnum->label() }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Unread badge --}}
                        @if ($conv->unread_count > 0)
                            <div class="flex-shrink-0 ml-1 h-5 w-5 rounded-full bg-green-500 text-white text-xs flex items-center justify-center font-bold" aria-label="{{ $conv->unread_count }} unread messages">
                                {{ $conv->unread_count > 9 ? '9+' : $conv->unread_count }}
                            </div>
                        @endif

                        {{-- Chevron --}}
                        <svg class="w-4 h-4 text-gray-300 flex-shrink-0 group-hover:text-gray-400 transition-colors duration-150" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </li>
            @empty
                {{-- Empty State --}}
                <li class="flex flex-col items-center justify-center py-16 px-6 text-center">
                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700">No conversations found</p>
                    <p class="mt-1 text-xs text-gray-400">
                        @if ($this->search || $this->filterStatus)
                            Try adjusting your search or filter criteria.
                        @else
                            Inbound WhatsApp messages from leads will appear here.
                        @endif
                    </p>
                    @if ($this->search || $this->filterStatus)
                        <button
                            type="button"
                            wire:click="$set('search', ''); $set('filterStatus', '')"
                            class="btn-secondary-sm mt-4 cursor-pointer"
                        >
                            Clear filters
                        </button>
                    @endif
                </li>
            @endforelse
        </ul>
    </div>

    {{-- Pagination --}}
    @if ($this->conversations->hasPages())
        <div class="flex justify-end">
            {{ $this->conversations->links() }}
        </div>
    @endif

</div>

