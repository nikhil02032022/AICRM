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

                        {{-- Academic Background — BRD: CRM-EC-001 --}}
                        @if($lead->qualification || $lead->marks_10th || $lead->marks_12th || $lead->graduation_percentage || $lead->preferred_intake)
                        <div>
                            <h3 class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Academic Background</h3>
                            <dl class="grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                                @if($lead->qualification)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-gray-500">Highest Qualification</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $lead->qualification }}</dd>
                                </div>
                                @endif
                                @if($lead->marks_10th)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">10th Marks</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">
                                        {{ $lead->marks_10th }}%
                                        @if($lead->board_10th) <span class="text-gray-400">({{ $lead->board_10th }})</span>@endif
                                    </dd>
                                </div>
                                @endif
                                @if($lead->marks_12th)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">12th Marks</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">
                                        {{ $lead->marks_12th }}%
                                        @if($lead->board_12th) <span class="text-gray-400">({{ $lead->board_12th }})</span>@endif
                                    </dd>
                                </div>
                                @endif
                                @if($lead->graduation_percentage)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Graduation %</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">
                                        {{ $lead->graduation_percentage }}%
                                        @if($lead->graduation_university) <span class="text-gray-400">({{ $lead->graduation_university }})</span>@endif
                                    </dd>
                                </div>
                                @endif
                                @if($lead->preferred_intake)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Preferred Intake</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $lead->preferred_intake }}</dd>
                                </div>
                                @endif
                                @if($lead->date_of_birth)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500">Date of Birth</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900">{{ $lead->date_of_birth->format('d M Y') }}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                        @endif

                    </div>

