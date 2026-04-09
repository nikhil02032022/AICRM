                    {{-- â”€â”€ DPDP tab â”€â”€ --}}
                    <div x-show="tab === 'dpdp'"
                         x-transition:enter="transition-opacity duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="p-5">
                        <h3 class="mb-4 text-[10px] font-bold uppercase tracking-wider text-gray-400">DPDP Act 2023 â€” Consent Record</h3>
                        <ul class="space-y-3" role="list">

                            {{-- Data consent --}}
                            <li class="flex items-center gap-3">
                                @if($lead->consent_given)
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Data Processing Consent</p>
                                    @if($lead->consent_given && $lead->consent_timestamp)
                                    <p class="text-xs text-gray-400">
                                        {{ $lead->consent_timestamp->format('d M Y, h:i A') }}
                                        @if($lead->consent_form_version) Â· v{{ $lead->consent_form_version }} @endif
                                    </p>
                                    @endif
                                </div>
                            </li>

                            {{-- Call recording consent --}}
                            <li class="flex items-center gap-3">
                                @if($lead->call_consent_given)
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100" aria-hidden="true">
                                        <span class="h-2 w-2 rounded-full bg-gray-300"></span>
                                    </span>
                                @endif
                                <p class="text-sm font-medium text-gray-900">Call Recording Consent</p>
                            </li>

                            {{-- Opt-out --}}
                            <li class="flex items-center gap-3">
                                @if($lead->opt_out)
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100" aria-hidden="true">
                                        <svg class="h-3.5 w-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100" aria-hidden="true">
                                        <span class="h-2 w-2 rounded-full bg-gray-300"></span>
                                    </span>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        Opt-out {{ $lead->opt_out ? '(DNC active)' : 'â€” not opted out' }}
                                    </p>
                                    @if($lead->opt_out && $lead->opt_out_at)
                                    <p class="text-xs text-gray-400">Since {{ $lead->opt_out_at->format('d M Y') }}</p>
                                    @endif
                                </div>
                            </li>

                        </ul>

                        @if($lead->consent_ip)
                        <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                            <span class="text-xs text-gray-500">Consent IP: </span>
                            <span class="font-mono text-xs font-medium text-gray-700">{{ $lead->consent_ip }}</span>
                        </div>
                        @endif
                    </div>

