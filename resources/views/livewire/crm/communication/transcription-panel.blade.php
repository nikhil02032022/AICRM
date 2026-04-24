<div class="space-y-4" @if($this->callLog->transcription_status && !$this->callLog->transcription_status->isTerminal()) wire:poll.10000ms @endif>

    {{-- Status badge --}}
    @if($this->callLog->transcription_status)
        @php $status = $this->callLog->transcription_status; @endphp
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700">AI Transcription</span>
                @php
                    $badgeClass = match($status->colour()) {
                        'yellow' => 'bg-yellow-100 text-yellow-800',
                        'blue'   => 'bg-blue-100 text-blue-800',
                        'green'  => 'bg-green-100 text-green-800',
                        'red'    => 'bg-red-100 text-red-800',
                        default  => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                    {{ $status->label() }}
                    @if($status === \App\Enums\CRM\AI\TranscriptionStatus::Processing)
                        <svg class="ml-1 h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    @endif
                </span>
            </div>

            {{-- Retry button for failed transcriptions --}}
            @if($status === \App\Enums\CRM\AI\TranscriptionStatus::Failed)
                <form method="POST" action="{{ route('crm.communication.voice.calls.transcription.retry', $this->callLog->uuid) }}">
                    @csrf
                    <button type="submit" class="btn-secondary-sm">Retry Transcription</button>
                </form>
            @endif
        </div>

        {{-- AI Summary card — shown when completed --}}
        @if($status === \App\Enums\CRM\AI\TranscriptionStatus::Completed && $this->callLog->transcription_summary)
            @php $summary = $this->callLog->transcription_summary; @endphp
            <div class="card">
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">AI Summary</h3>
                        @php
                            $tempColour = match($summary['lead_temperature'] ?? '') {
                                'Hot'  => 'bg-red-100 text-red-800',
                                'Warm' => 'bg-orange-100 text-orange-800',
                                'Cold' => 'bg-blue-100 text-blue-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tempColour }}">
                            {{ $summary['lead_temperature'] ?? '' }} Lead
                        </span>
                    </div>

                    @if(!empty($summary['summary_sentence']))
                        <p class="text-sm italic text-gray-600">{{ $summary['summary_sentence'] }}</p>
                    @endif

                    @if(!empty($summary['interests']))
                        <div>
                            <p class="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Interests</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($summary['interests'] as $item)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ $item }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($summary['objections']))
                        <div>
                            <p class="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Objections</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($summary['objections'] as $item)
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">{{ $item }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($summary['next_steps']))
                        <div>
                            <p class="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500">Next Steps</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($summary['next_steps'] as $item)
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">{{ $item }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($this->callLog->transcription_model)
                        <p class="text-xs text-gray-400">
                            Model: {{ $this->callLog->transcription_model }}
                            @if($this->callLog->transcription_token_count)
                                &middot; {{ number_format($this->callLog->transcription_token_count) }} tokens
                            @endif
                            @if($this->callLog->transcribed_at)
                                &middot; {{ $this->callLog->transcribed_at->diffForHumans() }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            {{-- Raw transcript (collapsible) --}}
            @if($this->callLog->transcript_text)
                <details class="rounded-lg border border-gray-200">
                    <summary class="cursor-pointer rounded-lg px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Raw Transcript
                    </summary>
                    <div class="border-t border-gray-200 px-4 py-3">
                        <pre class="whitespace-pre-wrap text-xs text-gray-600">{{ $this->callLog->transcript_text }}</pre>
                    </div>
                </details>
            @endif
        @endif

        @if($status === \App\Enums\CRM\AI\TranscriptionStatus::Pending || $status === \App\Enums\CRM\AI\TranscriptionStatus::Processing)
            <p class="text-sm text-gray-500">The AI summary is being generated. This page will update automatically.</p>
        @endif

        @if($status === \App\Enums\CRM\AI\TranscriptionStatus::Failed)
            <p class="text-sm text-red-600">Transcription failed. You can retry using the button above.</p>
        @endif

    @else
        <p class="text-sm text-gray-400">No transcript submitted for this call.</p>
    @endif

</div>
