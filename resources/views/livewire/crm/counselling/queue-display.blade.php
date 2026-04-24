<div>
    {{-- Currently called token --}}
    @if ($this->currentToken)
        <div class="mb-8">
            <p class="text-gray-400 uppercase tracking-widest text-sm mb-3">Now Serving</p>
            <div class="text-9xl font-black tabular-nums text-white leading-none">
                {{ str_pad($this->currentToken->token_number, 3, '0', STR_PAD_LEFT) }}
            </div>
        </div>
    @else
        <div class="mb-8">
            <p class="text-gray-500 text-xl">Queue is currently empty</p>
        </div>
    @endif

    {{-- Recent tokens --}}
    @if ($this->recentTokens->isNotEmpty())
        <div class="mt-10 border-t border-gray-700 pt-8">
            <p class="text-gray-500 uppercase tracking-widest text-xs mb-4">Recent Tokens</p>
            <div class="flex flex-wrap justify-center gap-4">
                @foreach ($this->recentTokens as $token)
                    <div class="rounded-xl bg-gray-800 px-5 py-3 text-center">
                        <p class="text-2xl font-bold tabular-nums text-gray-200">
                            {{ str_pad($token->token_number, 3, '0', STR_PAD_LEFT) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">{{ $token->status->label() }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
