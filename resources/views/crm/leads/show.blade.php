{{-- BRD: CRM-EC-004 — Lead 360° detail view with complete activity timeline (annotation was incorrectly CRM-LC-011) --}}
<x-layouts.crm :title="$lead->fullName()">
    @php
        $primaryProg = $lead->programmeInterests->firstWhere('pivot.is_primary', true)
                    ?? $lead->programmeInterests->first();
        $circ        = round(2 * M_PI * 30, 2);
        $filled      = round(($lead->lead_score / 100) * $circ, 2);
        $scoreColour = match(true) {
            $lead->lead_score >= 75 => '#10B981',
            $lead->lead_score >= 50 => '#F59E0B',
            default                 => '#6366F1',
        };
        $daysActive  = max(1, (int) ($lead->created_at?->diffInDays(now()) ?? 1));
        $touchpoints = $auditLogs->count();
    @endphp

    <div class="space-y-5" x-data="leadDetailPage()">

        {{-- ── Page header ── --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('crm.leads.index') }}"
                   class="btn-secondary-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $lead->fullName() }}</h1>
                    <p class="mt-0.5 text-xs text-gray-500">
                       
                        &middot; Lead since {{ $lead->created_at?->format('d M Y') }}
                        @if($lead->institution)&middot; {{ $lead->institution->name }}@endif
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @can('crm.leads.edit', $lead)
                <button type="button" @click="openEdit()" class="btn-secondary-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </button>
                @endcan
                @can('crm.leads.delete', $lead)
                <button type="button" @click="openDelete()" class="btn-secondary-sm border-red-200 text-red-600 hover:bg-red-50 hover:border-red-400">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
                @endcan
                <button type="button" class="btn-secondary-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Application
                </button>
                @if($lead->canConvertToStudent())
                <button type="button" class="btn-primary-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Convert to Student
                </button>
                @endif
            </div>
        </div>

        {{-- BRD: CRM-LC-018 — Duplicate suspected banner ── --}}
        @if($lead->is_duplicate_suspected)
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5"
             role="alert" aria-live="polite">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-amber-900">Possible duplicate lead detected</p>
                <p class="mt-0.5 text-xs text-amber-700">
                    This lead matches an existing record on mobile, email, or name + programme.
                    @if($lead->duplicate_of_uuid)
                        <a href="{{ route('crm.leads.show', $lead->duplicate_of_uuid) }}"
                           class="font-medium underline underline-offset-2 hover:text-amber-900 transition-colors">
                            View suspected original lead →
                        </a>
                    @endif
                </p>
            </div>
        </div>
        @endif

        {{-- ── 2-column grid ── --}}
        <div class="flex items-start gap-5">

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
                        <p class="mb-1.5 text-[10px] font-bold text-violet-600">âš¡ Recommended</p>
                        <p class="text-xs leading-relaxed text-gray-600">
                            AI scoring is active. Next best action will appear here once the engine analyses this lead's activity.
                        </p>
                        <button type="button" class="btn-primary-sm mt-2.5 w-full justify-center">
                            Request AI Analysis
                        </button>
                    </div>
                </div>

            </div>{{-- end LEFT --}}

            {{-- â”€â”€ RIGHT: Stats + Tabbed content â”€â”€ --}}
            <div class="min-w-0 flex-1 space-y-5">

                {{-- Mini stats row â€” 4 tiles matching mockup --}}
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">

                    {{-- Touchpoints --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-[3px] bg-primary-500"></div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Touchpoints</p>
                            <p class="mt-2 text-2xl font-bold leading-none text-gray-900">{{ $touchpoints }}</p>
                        </div>
                    </div>

                    {{-- Days Active --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-[3px] bg-green-500"></div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Days Active</p>
                            <p class="mt-2 text-2xl font-bold leading-none text-gray-900">{{ $daysActive }}</p>
                        </div>
                    </div>

                    {{-- AI Score --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-[3px]" style="background-color:{{ $scoreColour }}"></div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">AI Score</p>
                            <p class="mt-2 text-2xl font-bold leading-none" style="color:{{ $scoreColour }}">{{ $lead->lead_score }}</p>
                        </div>
                    </div>

                    {{-- Consent --}}
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="h-[3px] bg-violet-500"></div>
                        <div class="px-5 py-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Consent</p>
                            <p class="mt-2 text-2xl font-bold leading-none {{ $lead->consent_given ? 'text-green-600' : 'text-red-500' }}">{{ $lead->consent_given ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>

                </div>

                {{-- â”€â”€ Tabbed card â”€â”€ --}}
                <div class="card overflow-hidden p-0"
                     x-data="{ tab: 'timeline' }">

                    {{-- Tab strip --}}
                    <div class="flex overflow-x-auto border-b border-gray-100" role="tablist">
                        @foreach([
                            'timeline' => 'Timeline',
                            'info'     => 'Contact Info',
                            'dpdp'     => 'DPDP',
                            'utm'      => 'UTM',
                        ] as $key => $label)
                        <button type="button"
                                role="tab"
                                :aria-selected="tab === '{{ $key }}'"
                                @click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}'
                                    ? 'border-primary-600 text-primary-700'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap border-b-2 px-5 py-3 text-xs font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>

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

                </div>{{-- end tabbed card --}}

            </div>{{-- end RIGHT --}}

        </div>{{-- end 2-col grid --}}

        {{-- ===== Edit Lead Modal ===== --}}
        @can('crm.leads.edit', $lead)

    {{-- Backdrop --}}
    <div x-show="editOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm"
         @click="closeEdit()"
         aria-hidden="true"
         style="display:none"
    ></div>

    {{-- Dialog --}}
    <div id="edit-lead-modal"
         x-show="editOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6"
         role="dialog" aria-modal="true" aria-labelledby="edit-modal-title"
         @keydown.escape.window="closeEdit()"
         style="display:none"
    >
        <div class="relative my-auto w-full max-w-2xl rounded-xl bg-white shadow-2xl" @click.stop>

            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 id="edit-modal-title" class="text-lg font-semibold text-gray-900">Edit Lead</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Update lead details — changes are saved immediately.</p>
                </div>
                <button type="button" @click="closeEdit()" aria-label="Close"
                        class="ml-4 flex-shrink-0 rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form id="edit-lead-form" @submit.prevent="submitEdit()" class="space-y-5 px-6 py-5" novalidate>

                {{-- Name --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="el_first_name">First Name <span class="text-red-500">*</span></label>
                        <input id="el_first_name" type="text" x-model="editForm.first_name"
                               :class="{'border-red-500': editErrors.first_name}"
                               class="input-field" autocomplete="given-name">
                        <p x-show="editErrors.first_name" x-text="editErrors.first_name" role="alert" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="label" for="el_last_name">Last Name <span class="text-red-500">*</span></label>
                        <input id="el_last_name" type="text" x-model="editForm.last_name"
                               :class="{'border-red-500': editErrors.last_name}"
                               class="input-field" autocomplete="family-name">
                        <p x-show="editErrors.last_name" x-text="editErrors.last_name" role="alert" class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>

                {{-- Email --}}
                <div>
                    <label class="label" for="el_email">Email</label>
                    <input id="el_email" type="email" x-model="editForm.email"
                           :class="{'border-red-500': editErrors.email}"
                           class="input-field" autocomplete="email">
                    <p x-show="editErrors.email" x-text="editErrors.email" role="alert" class="mt-1 text-xs text-red-600"></p>
                </div>

                {{-- Source + Status --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="el_source">Lead Source</label>
                        <select id="el_source" x-model="editForm.source" class="input-field">
                            <option value="">— Select Source —</option>
                            @foreach($sourceOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="el_status">Status</label>
                        <select id="el_status" x-model="editForm.status" class="input-field">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- City + State --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="el_city">City</label>
                        <input id="el_city" type="text" x-model="editForm.city" class="input-field">
                    </div>
                    <div>
                        <label class="label" for="el_state">State</label>
                        <input id="el_state" type="text" x-model="editForm.state" class="input-field">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="label" for="el_notes">Notes</label>
                    <textarea id="el_notes" x-model="editForm.notes" rows="3"
                              class="input-field resize-none" maxlength="1000"></textarea>
                </div>

                {{-- Global error --}}
                <div x-show="editGlobalError" x-text="editGlobalError" role="alert"
                     class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            </form>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button type="button" @click="closeEdit()" :disabled="editSubmitting" class="btn-secondary">Cancel</button>
                <button type="submit" form="edit-lead-form" :disabled="editSubmitting"
                        class="btn-primary disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="!editSubmitting" class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </span>
                    <span x-show="editSubmitting" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 0v12z"></path>
                        </svg>
                        Saving…
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endcan

    {{-- ===== Delete Confirmation Modal ===== --}}
    @can('crm.leads.delete', $lead)

    <div x-show="deleteOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm"
         @click="closeDelete()"
         aria-hidden="true"
         style="display:none"
    ></div>

    <div x-show="deleteOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="delete-modal-title"
         @keydown.escape.window="closeDelete()"
         style="display:none"
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" @click.stop>
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="delete-modal-title" class="text-base font-semibold text-gray-900">Archive Lead</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Are you sure you want to archive <strong>{{ $lead->fullName() }}</strong>?
                        The lead will be soft-deleted and can be restored by an admin.
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="closeDelete()" :disabled="deleteSubmitting" class="btn-secondary">Cancel</button>
                <button type="button" @click="submitDelete()" :disabled="deleteSubmitting"
                        class="btn-primary !bg-red-600 !border-red-600 hover:!bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="!deleteSubmitting">Archive Lead</span>
                    <span x-show="deleteSubmitting" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 12 0 12 0v12z"></path>
                        </svg>
                        Archiving…
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endcan

    </div>{{-- end x-data="leadDetailPage()" --}}

    @push('scripts')
    <script>
    function leadDetailPage() {
        return {
            // ── Edit modal ──
            editOpen: false,
            editSubmitting: false,
            editErrors: {},
            editGlobalError: '',
            editForm: {
                first_name: @json($lead->first_name),
                last_name:  @json($lead->last_name),
                email:      @json($lead->email ?? ''),
                source:     @json($lead->source?->value ?? ''),
                status:     @json($lead->status?->value ?? ''),
                city:       @json($lead->city ?? ''),
                state:      @json($lead->state ?? ''),
                notes:      @json($lead->notes ?? ''),
            },

            openEdit()  { this.editOpen = true; this.editErrors = {}; this.editGlobalError = ''; },
            closeEdit() { this.editOpen = false; this.editSubmitting = false; this.editErrors = {}; this.editGlobalError = ''; },

            async submitEdit() {
                this.editSubmitting  = true;
                this.editErrors      = {};
                this.editGlobalError = '';

                try {
                    const res = await fetch('{{ route('crm.leads.update', $lead->uuid) }}', {
                        method: 'PUT',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept':       'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(this.editForm),
                    });

                    const json = await res.json();

                    if (json.success) {
                        this.closeEdit();
                        window.location.reload();
                        return;
                    }

                    if (res.status === 422 && json.errors) {
                        this.editErrors = Object.fromEntries(
                            Object.entries(json.errors).map(([k, v]) => [k, v[0]])
                        );
                    } else {
                        this.editGlobalError = json.error?.message ?? 'An unexpected error occurred.';
                    }
                } catch {
                    this.editGlobalError = 'Network error. Please try again.';
                } finally {
                    this.editSubmitting = false;
                }
            },

            // ── Delete modal ──
            deleteOpen: false,
            deleteSubmitting: false,

            openDelete()  { this.deleteOpen = true; },
            closeDelete() { this.deleteOpen = false; this.deleteSubmitting = false; },

            async submitDelete() {
                this.deleteSubmitting = true;
                try {
                    const res = await fetch('{{ route('crm.leads.destroy', $lead->uuid) }}', {
                        method: 'DELETE',
                        credentials: 'include',
                        headers: {
                            'Accept':       'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    const json = await res.json();
                    if (json.success) {
                        window.location.href = '{{ route('crm.leads.index') }}';
                        return;
                    }
                    this.closeDelete();
                    this.editGlobalError = json.error?.message ?? 'Could not archive lead.';
                } catch {
                    this.closeDelete();
                } finally {
                    this.deleteSubmitting = false;
                }
            },
        };
    }
    </script>
    @endpush

</x-layouts.crm>


