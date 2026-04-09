                    {{-- â”€â”€ UTM tab â”€â”€ --}}
                    <div x-show="tab === 'utm'"
                         x-transition:enter="transition-opacity duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="p-5">
                        @if($lead->source_utm_params && collect($lead->source_utm_params)->filter()->isNotEmpty())
                        <h3 class="mb-4 text-[10px] font-bold uppercase tracking-wider text-gray-400">UTM Attribution</h3>
                        <dl class="space-y-2">
                            @foreach($lead->source_utm_params as $key => $value)
                            @if($value)
                            <div class="flex items-center gap-4">
                                <dt class="w-36 shrink-0 font-mono text-xs text-gray-400">{{ $key }}</dt>
                                <dd class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs font-medium text-gray-800">{{ $value }}</dd>
                            </div>
                            @endif
                            @endforeach
                        </dl>
                        @else
                        <div class="py-12 text-center">
                            <p class="text-sm text-gray-400">No UTM parameters recorded for this lead.</p>
                        </div>
                        @endif
                    </div>

