            {{-- â”€â”€ LEFT: Profile sidebar â”€â”€ --}}
            <div class="w-72 min-w-72 shrink-0 space-y-4">

                {{-- Profile card --}}
                <div class="card p-5">

                    {{-- Avatar --}}
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500 to-violet-600 text-xl font-bold text-white"
                         aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($lead->first_name, 0, 1)) }}{{ mb_strtoupper(mb_substr($lead->last_name, 0, 1)) }}
                    </div>

                    {{-- Name + sub --}}
                    <div class="mb-4 text-center">
                        <p class="text-base font-bold text-gray-900">{{ $lead->fullName() }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ $primaryProg?->name ?? 'No programme selected' }}
                        </p>
                    </div>

                    {{-- Score ring (SVG) --}}
                    <div class="mb-4 flex justify-center">
                        <div class="relative mx-auto" style="width:80px;height:80px">
                            <svg width="80" height="80" viewBox="0 0 80 80"
                                 role="img" aria-label="AI Score {{ $lead->lead_score }} out of 100">
                                {{-- Track --}}
                                <circle cx="40" cy="40" r="32" fill="none"
                                        stroke="#E5E7EB" stroke-width="9"
                                        transform="rotate(-90 40 40)"/>
                                {{-- Fill --}}
                                @php
                                    $r2     = 32;
                                    $circ2  = round(2 * M_PI * $r2, 2);
                                    $fill2  = round(($lead->lead_score / 100) * $circ2, 2);
                                @endphp
                                <circle cx="40" cy="40" r="32" fill="none"
                                        stroke="{{ $scoreColour }}"
                                        stroke-width="9"
                                        stroke-linecap="round"
                                        stroke-dasharray="{{ $fill2 }} {{ $circ2 }}"
                                        stroke-dashoffset="0"
                                        transform="rotate(-90 40 40)"/>
                            </svg>
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center">
                                <span style="font-family:monospace;font-size:15px;font-weight:800;line-height:1;color:{{ $scoreColour }}">{{ $lead->lead_score }}</span>
                                <span style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#9CA3AF;margin-top:2px">AI Score</span>
                            </div>
                        </div>
                    </div>

                    {{-- Temperature + Status badges --}}
                    <div class="mb-4 flex flex-wrap justify-center gap-1.5">
                        @if($lead->temperature)
                            <span class="badge badge-{{ $lead->temperature->badgeColour() }}">{{ $lead->temperature->label() }}</span>
                        @endif
                        @if($lead->status)
                            <span class="badge badge-{{ $lead->status->badgeColour() }}">{{ $lead->status->label() }}</span>
                        @endif
                    </div>

                    {{-- Info rows --}}
                    <div class="divide-y divide-gray-50 border-t border-gray-100 pt-3">
                        @can('crm.leads.view_pii', $lead)
                        <div class="flex items-center justify-between py-2">
                            <span class="text-xs font-medium text-gray-500">Phone</span>
                            <span class="font-mono text-xs font-semibold text-gray-900">{{ $lead->mobile }}</span>
                        </div>
                        @if($lead->email)
                        <div class="flex items-start justify-between gap-2 py-2">
                            <span class="shrink-0 text-xs font-medium text-gray-500">Email</span>
                            <span class="max-w-[150px] truncate font-mono text-xs font-semibold text-gray-900"
                                  title="{{ $lead->email }}">{{ $lead->email }}</span>
                        </div>
                        @endif
                        @endcan
                        @if($primaryProg)
                        <div class="flex items-start justify-between gap-2 py-2">
                            <span class="shrink-0 text-xs font-medium text-gray-500">Programme</span>
                            <span class="text-right text-xs font-semibold text-gray-900">{{ $primaryProg->name }}</span>
                        </div>
                        @endif
                        @if($lead->city || $lead->state)
                        <div class="flex items-center justify-between py-2">
                            <span class="text-xs font-medium text-gray-500">City</span>
                            <span class="text-xs font-semibold text-gray-900">
                                {{ collect([$lead->city, $lead->state])->filter()->implode(', ') }}
                            </span>
                        </div>
                        @endif
                        @if($lead->source)
                        <div class="flex items-center justify-between py-2">
                            <span class="text-xs font-medium text-gray-500">Source</span>
                            <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600">
                                {{ $lead->source->label() }}
                            </span>
                        </div>
                        @endif
                        <div class="flex items-center justify-between py-2">
                            <span class="text-xs font-medium text-gray-500">Counsellor</span>
                            @if($lead->assignedCounsellor)
                                <span class="text-xs font-semibold text-gray-900">{{ $lead->assignedCounsellor->name }}</span>
                            @else
                                <span class="text-xs font-medium text-red-400">Unassigned</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-xs font-medium text-gray-500">Lead Since</span>
                            <span class="font-mono text-xs font-semibold text-gray-900">{{ $lead->created_at?->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- AI Next Best Action --}}
                <div class="card p-4">
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">AI Next Best Action</p>
                    <div class="rounded-lg border border-violet-100 bg-violet-50/60 p-3">
                        <p class="mb-1.5 text-[10px] font-bold text-violet-600">⚡ Recommended</p>
                        <p class="text-xs leading-relaxed text-gray-600">
                            AI scoring is active. Next best action will appear here once the engine analyses this lead's activity.
                        </p>
                        <button type="button" class="btn-primary-sm mt-2.5 w-full justify-center">
                            Request AI Analysis
                        </button>
                    </div>
                </div>

                {{-- BRD: CRM-EC-007 — Reassign counsellor action --}}
                @can('assign', $lead)
                <div class="card p-4"
                     x-data="{ open: false, counsellorId: '', submitting: false, error: '' }">
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Assign Counsellor</p>
                    <button type="button"
                            @click="open = !open"
                            class="btn-secondary-sm w-full justify-center">
                        {{ $lead->assignedCounsellor ? 'Reassign' : 'Assign' }} Counsellor
                    </button>
                    <div x-show="open" x-collapse class="mt-3 space-y-2">
                        <input type="number"
                               x-model="counsellorId"
                               placeholder="Counsellor User ID"
                               class="input-field w-full text-sm"
                               min="1">
                        <p x-text="error" x-show="error" class="text-xs text-red-600"></p>
                        <button type="button"
                                :disabled="submitting || !counsellorId"
                                @click="
                                    submitting = true; error = '';
                                    fetch('{{ route('crm.leads.assign', $lead) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('[name=csrf-token]').content,
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({ counsellor_id: counsellorId })
                                    })
                                    .then(r => r.json())
                                    .then(d => { if(d.success) { window.location.reload(); } else { error = d.message || 'Error occurred'; submitting = false; } })
                                    .catch(() => { error = 'Network error. Please try again.'; submitting = false; })
                                "
                                class="btn-primary-sm w-full justify-center">
                            <span x-show="!submitting">Confirm Assignment</span>
                            <span x-show="submitting">Assigning...</span>
                        </button>
                    </div>
                </div>
                @endcan

            </div>{{-- end LEFT --}}
