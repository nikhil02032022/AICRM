                    {{-- â”€â”€ Timeline tab â”€â”€ --}}
                    <div x-show="tab === 'timeline'"
                         x-transition:enter="transition-opacity duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="p-6">
                        @if($auditLogs->isNotEmpty())
                        <ul style="list-style:none;padding:0;margin:0">
                            @foreach($auditLogs as $log)
                            @php
                                $dotColour = match($log->action) {
                                    'created'  => '#10B981',
                                    'updated'  => '#6366F1',
                                    'deleted'  => '#EF4444',
                                    'restored' => '#F59E0B',
                                    default    => '#9CA3AF',
                                };
                                $actionTitle = match($log->action) {
                                    'created'  => 'Lead created',
                                    'updated'  => 'Lead updated',
                                    'deleted'  => 'Lead archived',
                                    'restored' => 'Lead restored',
                                    default    => ucfirst($log->action),
                                };
                                $changed = ($log->action === 'updated' && $log->new_values)
                                    ? collect($log->new_values)->keys()
                                          ->map(fn($k) => str_replace('_', ' ', $k))
                                          ->implode(', ')
                                    : null;
                            @endphp
                            <li style="display:flex;gap:16px;padding-bottom:{{ $loop->last ? '0' : '28px' }}">
                                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0">
                                    <div style="width:10px;height:10px;border-radius:50%;background:{{ $dotColour }};flex-shrink:0;margin-top:3px;box-shadow:0 0 0 3px {{ $dotColour }}22"></div>
                                    @if(!$loop->last)
                                    <div style="width:1px;flex:1;background:#E5E7EB;margin-top:5px"></div>
                                    @endif
                                </div>
                                <div>
                                    <p style="font-size:13px;font-weight:600;color:#111827;line-height:1.4">
                                        {{ $actionTitle }}@if($changed) <span style="font-weight:400;color:#6B7280">&mdash; {{ $changed }}</span>@endif
                                    </p>
                                    <p style="font-size:11px;color:#9CA3AF;margin-top:5px">
                                        {{ $log->created_at?->format('d M Y, h:i A') }}@if($log->actor) &middot; {{ $log->actor->name }}@endif
                                    </p>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        @else
                        <div class="py-12 text-center">
                            <svg class="mx-auto mb-2 h-8 w-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-gray-400">No activity recorded yet.</p>
                        </div>
                        @endif

                    </div>{{-- end timeline tab panel --}}
