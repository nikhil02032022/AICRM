<div>
    {{-- Call Next button --}}
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-gray-500">
            {{ $this->tokens->where('status', 'waiting')->count() }} waiting
        </p>
        <button
            wire:click="callNext"
            x-on:callNextRequested.window="
                fetch('{{ route('crm.walk-in-queue.call-next') }}', {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'},
                })
                .then(r => r.json())
                .then(data => { if (data.token_number) { $wire.$refresh(); } })
                .catch(() => {})
            "
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none">
            Call Next Token
        </button>
    </div>

    {{-- Token list --}}
    @if ($this->tokens->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center">
            <p class="text-sm text-gray-400">No active tokens in queue today.</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($this->tokens as $token)
                @php
                    $colours = [
                        'blue'   => 'bg-blue-100 text-blue-800',
                        'yellow' => 'bg-yellow-100 text-yellow-800',
                        'indigo' => 'bg-indigo-100 text-indigo-800',
                        'green'  => 'bg-green-100 text-green-800',
                        'slate'  => 'bg-slate-100 text-slate-700',
                    ];
                    $badge = $colours[$token->status->badgeColour()] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm">
                    <div class="flex items-center gap-4">
                        <span class="text-2xl font-bold text-gray-900 tabular-nums w-12 text-center">
                            {{ str_pad($token->token_number, 3, '0', STR_PAD_LEFT) }}
                        </span>
                        <div>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge }}">
                                {{ $token->status->label() }}
                            </span>
                            @if ($token->visitor_name)
                                <p class="mt-0.5 text-xs text-gray-500">{{ $token->visitor_name }}</p>
                            @endif
                        </div>
                    </div>

                    @unless ($token->status->isTerminal())
                        <div class="flex gap-2">
                            <button
                                wire:click
                                x-on:click="
                                    fetch('{{ route('crm.walk-in-queue.tokens.serve', $token->id) }}', {
                                        method: 'POST',
                                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'},
                                    }).then(() => $wire.$refresh());
                                "
                                class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-500">
                                Serve
                            </button>
                            <button
                                x-on:click="
                                    fetch('{{ route('crm.walk-in-queue.tokens.skip', $token->id) }}', {
                                        method: 'POST',
                                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'},
                                    }).then(() => $wire.$refresh());
                                "
                                class="rounded-lg bg-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-300">
                                Skip
                            </button>
                        </div>
                    @endunless
                </div>
            @endforeach
        </div>
    @endif
</div>
