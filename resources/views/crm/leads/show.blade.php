<x-layouts.crm :title="$lead->fullName()">
    <div class="mx-auto max-w-4xl space-y-6">
        {{-- Breadcrumb --}}
        <nav class="flex text-sm text-gray-500 gap-1.5 items-center">
            <a href="{{ route('crm.leads.index') }}" class="hover:text-indigo-600">Leads</a>
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium">{{ $lead->fullName() }}</span>
        </nav>

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-4">
                {{-- Avatar --}}
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xl font-bold text-white shadow">
                    {{ mb_strtoupper(mb_substr($lead->first_name, 0, 1)) }}{{ mb_strtoupper(mb_substr($lead->last_name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $lead->fullName() }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                        <span class="badge badge-{{ $lead->temperature?->badgeColour() }}">{{ $lead->temperature?->label() }}</span>
                        <span class="badge badge-{{ $lead->status?->badgeColour() }}">{{ $lead->status?->label() }}</span>
                        @if($lead->source)
                            <span class="text-gray-400">·</span>
                            <span>{{ $lead->source->label() }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex shrink-0 gap-2">
                @can('crm.leads.edit', $lead)
                <a href="#" class="btn-secondary btn-sm">Edit</a>
                @endcan
            </div>
        </div>

        {{-- Lead score bar --}}
        <div class="card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-gray-700">Lead Score</span>
                <span class="text-2xl font-bold {{ $lead->lead_score >= 75 ? 'text-red-600' : ($lead->lead_score >= 50 ? 'text-amber-500' : 'text-blue-600') }}">
                    {{ $lead->lead_score }}/100
                </span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-gray-200 overflow-hidden">
                <div class="h-full rounded-full transition-all {{ $lead->lead_score >= 75 ? 'bg-red-500' : ($lead->lead_score >= 50 ? 'bg-amber-400' : 'bg-blue-400') }}"
                     style="width: {{ $lead->lead_score }}%"></div>
            </div>
        </div>

        {{-- Detail grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left: profile --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Contact info --}}
                <div class="card">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Contact Information</h2>
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                        @can('crm.leads.view_pii', $lead)
                        <div>
                            <dt class="text-gray-500 font-medium">Mobile</dt>
                            <dd class="text-gray-900 mt-0.5">{{ $lead->mobile }}</dd>
                        </div>
                        @if($lead->email)
                        <div>
                            <dt class="text-gray-500 font-medium">Email</dt>
                            <dd class="text-gray-900 mt-0.5">{{ $lead->email }}</dd>
                        </div>
                        @endif
                        @endcan
                        @if($lead->city || $lead->state)
                        <div>
                            <dt class="text-gray-500 font-medium">Location</dt>
                            <dd class="text-gray-900 mt-0.5">{{ collect([$lead->city, $lead->state])->filter()->implode(', ') }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-gray-500 font-medium">Created</dt>
                            <dd class="text-gray-900 mt-0.5">{{ $lead->created_at?->format('d M Y, h:i A') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Programme interests --}}
                @if($lead->programmeInterests->isNotEmpty())
                <div class="card">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Programme Interests</h2>
                    <ul class="space-y-2">
                        @foreach($lead->programmeInterests as $programme)
                        <li class="flex items-center gap-2 text-sm text-gray-700">
                            @if($programme->pivot->is_primary)
                                <span class="badge badge-indigo text-xs">Primary</span>
                            @else
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-300"></span>
                            @endif
                            {{ $programme->name }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Notes --}}
                @if($lead->notes)
                <div class="card">
                    <h2 class="text-base font-semibold text-gray-900 mb-2">Notes</h2>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $lead->notes }}</p>
                </div>
                @endif
            </div>

            {{-- Right: metadata --}}
            <div class="space-y-6">
                {{-- Assignment --}}
                <div class="card">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Assignment</h2>
                    <div class="text-sm text-gray-700">
                        @if($lead->assignedCounsellor)
                            <p class="font-medium text-gray-900">{{ $lead->assignedCounsellor->name }}</p>
                            <p class="text-gray-500">Assigned Counsellor</p>
                        @else
                            <p class="text-gray-400 italic">Unassigned</p>
                        @endif
                    </div>
                </div>

                {{-- DPDP / Consent --}}
                <div class="card">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Consent & DPDP</h2>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2">
                            <span class="{{ $lead->consent_given ? 'text-green-500' : 'text-red-400' }}">
                                {{ $lead->consent_given ? '✓' : '✗' }}
                            </span>
                            <span class="text-gray-700">Data consent given</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="{{ $lead->call_consent_given ? 'text-green-500' : 'text-gray-300' }}">
                                {{ $lead->call_consent_given ? '✓' : '○' }}
                            </span>
                            <span class="text-gray-700">Call recording consent</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="{{ $lead->opt_out ? 'text-red-500' : 'text-gray-300' }}">
                                {{ $lead->opt_out ? '✗' : '○' }}
                            </span>
                            <span class="text-gray-700">Opted out</span>
                        </li>
                    </ul>
                    @if($lead->consent_timestamp)
                    <p class="mt-3 text-xs text-gray-400">Consented {{ $lead->consent_timestamp->diffForHumans() }}</p>
                    @endif
                </div>

                {{-- Source detail --}}
                @if($lead->source_utm_params)
                <div class="card">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">UTM Parameters</h2>
                    <dl class="space-y-1 text-xs font-mono text-gray-600">
                        @foreach ($lead->source_utm_params as $key => $value)
                            @if($value)
                            <div class="flex gap-2">
                                <dt class="text-gray-400 w-28 shrink-0">{{ $key }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                            @endif
                        @endforeach
                    </dl>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.crm>
