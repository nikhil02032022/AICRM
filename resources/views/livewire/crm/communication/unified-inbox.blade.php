<div x-data="{ activeTab: $wire.entangle('activeTab') }" wire:poll.10s="refresh">

    {{-- Card shell (matches system .card but p-0 for full-width sections) --}}
    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">

        {{-- ── Tab bar (pill buttons) ── --}}
        <div class="border-b border-gray-100 px-6 py-3.5">
            <nav class="flex items-center gap-2" aria-label="Inbox channel tabs" role="tablist">
                @foreach ([
                    ['key' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z'],
                    ['key' => 'email',    'label' => 'Email',    'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    ['key' => 'sms',      'label' => 'SMS',      'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                ] as $tab)
                <button
                    type="button"
                    role="tab"
                    wire:click="setTab('{{ $tab['key'] }}')"
                    aria-selected="{{ $activeTab === $tab['key'] ? 'true' : 'false' }}"
                    aria-controls="inbox-panel"
                    @class([
                        'inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-medium transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1',
                        'bg-primary-600 text-white shadow-sm' => $activeTab === $tab['key'],
                        'text-gray-500 hover:text-gray-800 hover:bg-gray-100' => $activeTab !== $tab['key'],
                    ])
                >
                    <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/>
                    </svg>
                    {{ $tab['label'] }}
                    @php $count = $this->unreadCounts[$tab['key']] ?? 0 @endphp
                    @if ($count > 0)
                        <span @class([
                            'inline-flex items-center justify-center min-w-[1.125rem] h-[1.125rem] px-1 text-[10px] font-bold rounded-full leading-none',
                            'bg-white text-primary-600'    => $activeTab === $tab['key'],
                            'bg-red-500 text-white'        => $activeTab !== $tab['key'],
                        ]) aria-label="{{ $count }} unread">
                            {{ $count > 99 ? '99+' : $count }}
                        </span>
                    @endif
                </button>
                @endforeach
            </nav>
        </div>

        {{-- ── Search bar ── --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-white">
            <label for="inbox-search" class="sr-only">Search inbox messages</label>
            <div class="relative w-full max-w-sm">
                {{-- Search icon --}}
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3" aria-hidden="true">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    id="inbox-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search messages…"
                    class="block w-full rounded-lg border border-gray-300 bg-white pl-10 pr-4 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition duration-150"
                >
            </div>
        </div>

        {{-- ── Message list ── --}}
        <div id="inbox-panel" role="tabpanel" class="divide-y divide-gray-100" wire:loading.class="opacity-60 pointer-events-none">
            @forelse ($this->inbox as $item)
            <div class="flex items-center gap-3 px-6 py-3.5 hover:bg-gray-50/70 transition-colors cursor-default">

                {{-- Avatar --}}
                <div class="flex-shrink-0 h-9 w-9 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-sm font-semibold select-none" aria-hidden="true">
                    {{ strtoupper(substr($item['name'] ?? '?', 0, 1)) }}
                </div>

                {{-- Message body --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span @class([
                            'text-sm truncate',
                            'font-semibold text-gray-900' => !empty($item['unread']),
                            'font-medium text-gray-800'   => empty($item['unread']),
                        ])>{{ $item['name'] ?? 'Unknown' }}</span>
                        <span class="flex-shrink-0 text-xs text-gray-400 tabular-nums">{{ $item['time'] ?? '' }}</span>
                    </div>
                    <p @class([
                        'text-xs truncate mt-0.5',
                        'text-gray-700 font-medium' => !empty($item['unread']),
                        'text-gray-500'             => empty($item['unread']),
                    ])>{{ $item['preview'] ?? '' }}</p>
                </div>

                {{-- Unread indicator --}}
                @if (!empty($item['unread']))
                    <span class="flex-shrink-0 h-2 w-2 rounded-full bg-primary-500" aria-label="Unread message"></span>
                @endif

            </div>
            @empty
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                </svg>
                <p class="text-sm font-medium text-gray-500">No messages in <span class="capitalize">{{ $activeTab }}</span> inbox</p>
                <p class="text-xs text-gray-400 mt-1">New inbound messages will appear here automatically.</p>
            </div>
            @endforelse
        </div>

        {{-- ── Pagination ── --}}
        @if ($this->inbox->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/60">
            {{ $this->inbox->links() }}
        </div>
        @endif

    </div>

</div>
