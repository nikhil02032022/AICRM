{{-- BRD: CRM-AI-001 — Claude API conversion probability badge with live polling and accept/reject feedback --}}
<div
    class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm"
    @if($this->shouldPoll()) wire:poll.60000ms @endif
>
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700">Conversion Probability</h3>
        <form method="POST" action="{{ route('crm.leads.ai-prediction.refresh', $leadUuid) }}" class="inline">
            @csrf
            <button type="submit"
                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
                title="Refresh prediction">
                ↻ Refresh
            </button>
        </form>
    </div>

    @php
        $score = $this->latestScore;
    @endphp

    {{-- No prediction yet --}}
    @if($score === null)
        <div class="text-center py-4">
            <p class="text-sm text-gray-400">Not scored yet</p>
            <p class="text-xs text-gray-300 mt-1">Click Refresh to generate a prediction</p>
        </div>

    {{-- Pending or Processing --}}
    @elseif(in_array($score->prediction_status?->value, ['pending', 'processing']))
        <div class="flex items-center gap-3 py-3">
            <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <div>
                <p class="text-sm text-gray-600 font-medium">Calculating…</p>
                <p class="text-xs text-gray-400">Auto-refreshes every 60 seconds</p>
            </div>
        </div>

    {{-- Failed --}}
    @elseif($score->prediction_status?->value === 'failed')
        <div class="text-center py-3">
            <p class="text-sm text-red-500 font-medium">Prediction unavailable</p>
            <p class="text-xs text-gray-400 mt-1">API error — click Refresh to retry</p>
        </div>

    {{-- Completed: insufficient data --}}
    @elseif($score->prediction_status?->value === 'completed' && (float)($score->confidence_score ?? 0) < 0.30)
        <div class="text-center py-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                Insufficient data
            </span>
            <p class="text-xs text-gray-400 mt-2">More interactions needed for a reliable prediction</p>
        </div>

    {{-- Completed with probability --}}
    @else
        @php
            $probability   = (float)($score->conversion_probability ?? 0);
            $pct           = number_format($probability * 100, 1);
            $confidence    = $score->conversionConfidenceLevel();
            $factors       = $score->prediction_factors ?? [];
            $refreshedAt   = $score->prediction_refreshed_at?->diffForHumans();
        @endphp

        {{-- Probability ring --}}
        <div class="flex items-center gap-4 mb-3">
            <div class="relative flex-shrink-0">
                <svg class="w-16 h-16 -rotate-90" viewBox="0 0 36 36">
                    <circle class="text-gray-100" stroke="currentColor" stroke-width="3.8"
                        fill="transparent" r="15.9" cx="18" cy="18"/>
                    <circle class="{{ $confidence?->ringClass() ?? 'text-indigo-500' }}"
                        stroke="currentColor" stroke-width="3.8" fill="transparent"
                        r="15.9" cx="18" cy="18"
                        stroke-dasharray="{{ number_format($probability * 100, 1) }} 100"
                        stroke-linecap="round"/>
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-800">
                    {{ $pct }}%
                </span>
            </div>
            <div>
                @if($confidence)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $confidence->badgeClass() }}">
                        {{ $confidence->label() }}
                    </span>
                @endif
                @if($refreshedAt)
                    <p class="text-xs text-gray-400 mt-1">Updated {{ $refreshedAt }}</p>
                @endif
            </div>
        </div>

        {{-- Top prediction factors --}}
        @if(count($factors) > 0)
            <ul class="space-y-1 mb-3">
                @foreach($factors as $factor)
                    <li class="flex items-start gap-2 text-xs text-gray-600">
                        @if(($factor['weight'] ?? '') === 'positive')
                            <span class="mt-0.5 text-green-500 font-bold">↑</span>
                        @elseif(($factor['weight'] ?? '') === 'negative')
                            <span class="mt-0.5 text-red-500 font-bold">↓</span>
                        @else
                            <span class="mt-0.5 text-gray-400">→</span>
                        @endif
                        <span>{{ $factor['factor'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Accept / Reject feedback --}}
        <div class="flex gap-2 pt-2 border-t border-gray-100">
            <form method="POST" action="{{ route('crm.leads.ai-prediction.feedback', $leadUuid) }}" class="flex-1">
                @csrf
                <input type="hidden" name="suggestion_uuid" value="{{ $score->uuid }}">
                <input type="hidden" name="decision" value="accepted">
                <button type="submit"
                    class="w-full text-xs py-1.5 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 font-medium transition-colors">
                    ✓ Accept
                </button>
            </form>
            <form method="POST" action="{{ route('crm.leads.ai-prediction.feedback', $leadUuid) }}" class="flex-1">
                @csrf
                <input type="hidden" name="suggestion_uuid" value="{{ $score->uuid }}">
                <input type="hidden" name="decision" value="rejected">
                <button type="submit"
                    class="w-full text-xs py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 font-medium transition-colors">
                    ✗ Reject
                </button>
            </form>
        </div>
    @endif
</div>
