                    {{-- ── Contact Info tab ── --}}
                    <div x-show="tab === 'info'"
                         x-transition:enter="transition-opacity duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="space-y-6 p-6">

                        {{-- Contact details --}}
                        <div>
                            <h3 class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Contact Details</h3>
                            <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                                @can('crm.leads.view_pii', $lead)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Mobile</dt>
                                    <dd class="mt-0.5 font-mono text-sm text-gray-900">{{ $lead->mobile }}</dd>
                                </div>
                                @if($lead->email)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Email</dt>
                                    <dd class="mt-0.5 break-all font-mono text-sm text-gray-900">{{ $lead->email }}</dd>
                                </div>
                                @endif
                                @endcan
                                @if($lead->city || $lead->state)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Location</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">
                                        {{ collect([$lead->city, $lead->state])->filter()->implode(', ') }}
                                    </dd>
                                </div>
                                @endif
                                @if($lead->nationality)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Nationality</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $lead->nationality }}</dd>
                                </div>
                                @endif
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Lead Created</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $lead->created_at?->format('d M Y, h:i A') }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Programme interests --}}
                        @if($lead->programmeInterests->isNotEmpty())
                        <div>
                            <h3 class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Programme Interests</h3>
                            <ul class="space-y-2">
                                @foreach($lead->programmeInterests as $prog)
                                <li class="flex items-center gap-2 text-sm">
                                    @if($prog->pivot->is_primary)
                                        <span class="badge badge-indigo">Primary</span>
                                    @else
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-gray-300" aria-hidden="true"></span>
                                    @endif
                                    <span class="text-gray-800">{{ $prog->name }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Notes --}}
                        @if($lead->notes)
                        <div>
                            <h3 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Notes</h3>
                            <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $lead->notes }}</p>
                        </div>
                        @endif

                    </div>

