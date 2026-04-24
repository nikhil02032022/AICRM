{{-- BRD: CRM-LC-011 — Live-searchable, filterable lead table --}}
<div>
    {{-- Search bar --}}
    <div class="relative mb-4">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input
            wire:model.live.debounce.400ms="search"
            type="search"
            placeholder="Search by name, email, phone, programme..."
            class="input-field pl-10 pr-4"
            aria-label="Search leads"
        >
    </div>

    {{-- Filter chips + dropdowns + sort --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        {{-- Temperature quick-filter chips --}}
        <button wire:click="$set('filterTemperature', '')"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors
                {{ $filterTemperature === '' && $filterStatus === '' ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            All Leads
        </button>
        <button wire:click="$set('filterTemperature', 'hot')"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors
                {{ $filterTemperature === 'hot' ? 'border-red-300 bg-red-50 text-red-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            🔴 Hot
        </button>
        <button wire:click="$set('filterTemperature', 'warm')"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors
                {{ $filterTemperature === 'warm' ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            🟡 Warm
        </button>
        <button wire:click="$set('filterTemperature', 'cold')"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors
                {{ $filterTemperature === 'cold' ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            🔵 Cold
        </button>

        {{-- Divider --}}
        <div class="mx-1 h-5 w-px bg-gray-200"></div>

        {{-- Status select --}}
        <select wire:model.live="filterStatus"
            class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 focus:border-primary-400 focus:outline-none focus:ring-1 focus:ring-primary-400 cursor-pointer"
            aria-label="Filter by status">
            @foreach ($this->statusOptions() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        {{-- Source select --}}
        <select wire:model.live="filterSource"
            class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 focus:border-primary-400 focus:outline-none focus:ring-1 focus:ring-primary-400 cursor-pointer"
            aria-label="Filter by source">
            @foreach ($this->sourceOptions() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        {{-- Sort controls --}}
        <div class="ml-auto flex gap-2">
            <button wire:click="sortBy('lead_score')" class="btn-secondary-sm">
                Score
                @if($sortField === 'lead_score')
                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                @endif
            </button>
            <button wire:click="sortBy('created_at')" class="btn-secondary-sm">
                Date
                @if($sortField === 'created_at')
                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                @endif
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="w-48 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Lead</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Programme Interest</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Source</th>
                        <th scope="col" class="w-20 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <button wire:click="sortBy('lead_score')" class="flex items-center gap-1 hover:text-primary-600 focus:outline-none">
                                Score
                                @if($sortField === 'lead_score')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="w-28 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                            <button wire:click="sortBy('status')" class="flex items-center gap-1 hover:text-primary-600 focus:outline-none">
                                Status
                                @if($sortField === 'status')
                                    <span aria-hidden="true">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="w-24 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Conv. %</th>
                        <th scope="col" class="w-32 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Counsellor</th>
                        <th scope="col" class="w-28 px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Last Touch</th>
                        <th scope="col" class="w-24 px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($this->leads as $lead)
                    <tr class="hover:bg-gray-50 transition-colors duration-100">

                        {{-- Lead: name + email --}}
                        <td class="px-4 py-3">
                            <a href="{{ route('crm.leads.show', $lead->uuid) }}" class="group block">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                                        {{ $lead->first_name }} {{ $lead->last_name }}
                                    </span>
                                    {{-- BRD: CRM-LC-018 — Duplicate suspected indicator --}}
                                    @if($lead->is_duplicate_suspected)
                                    <span class="inline-flex items-center gap-0.5 rounded-full border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700"
                                          title="Possible duplicate lead">
                                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                        </svg>
                                        Dup
                                    </span>
                                    @endif
                                </div>
                                @if($lead->email)
                                    <div class="mt-0.5 font-mono text-xs text-gray-400">{{ $lead->email }}</div>
                                @endif
                            </a>
                        </td>

                        {{-- Programme Interest --}}
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @php
                                $primaryProgramme = $lead->programmeInterests->firstWhere('pivot.is_primary', true)
                                    ?? $lead->programmeInterests->first();
                            @endphp
                            @if($primaryProgramme)
                                {{ $primaryProgramme->name }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Source tag pill --}}
                        <td class="px-4 py-3">
                            @if($lead->source)
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                    {{ $lead->source->label() }}
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Score pill --}}
                        <td class="px-4 py-3">
                            @php
                                $scoreColour = match(true) {
                                    $lead->lead_score >= 75 => 'bg-green-100 text-green-700',
                                    $lead->lead_score >= 50 => 'bg-amber-100 text-amber-700',
                                    default                 => 'bg-red-100 text-red-700',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 font-mono text-xs font-bold {{ $scoreColour }}">
                                {{ $lead->lead_score }}
                            </span>
                        </td>

                        {{-- Status + Temperature badges --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                @if($lead->status)
                                    <span class="badge badge-{{ $lead->status->badgeColour() }}">{{ $lead->status->label() }}</span>
                                @endif
                                @if($lead->temperature)
                                    <span class="badge badge-{{ $lead->temperature->badgeColour() }}">{{ $lead->temperature->label() }}</span>
                                @endif
                            </div>
                        </td>

                        {{-- CRM-AI-001: Conversion Probability --}}
                        <td class="px-4 py-3">
                            @php $pred = $lead->latestPrediction; @endphp
                            @if($pred && $pred->prediction_status?->value === 'completed' && $pred->conversion_probability !== null && (float)$pred->confidence_score >= 0.30)
                                @php
                                    $pctVal  = (float)$pred->conversion_probability;
                                    $pctDisp = number_format($pctVal * 100, 1).'%';
                                    $pctCls  = match(true) {
                                        $pctVal >= 0.70 => 'bg-green-100 text-green-700',
                                        $pctVal >= 0.40 => 'bg-amber-100 text-amber-700',
                                        default         => 'bg-red-100 text-red-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 font-mono text-xs font-bold {{ $pctCls }}">
                                    {{ $pctDisp }}
                                </span>
                            @elseif($pred && in_array($pred->prediction_status?->value, ['pending','processing']))
                                <span class="text-xs text-gray-400">…</span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Counsellor --}}
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @if($lead->assignedCounsellor)
                                {{ $lead->assignedCounsellor->name }}
                            @else
                                <span class="text-xs font-medium text-red-400">Unassigned</span>
                            @endif
                        </td>

                        {{-- Last Touch --}}
                        <td class="px-4 py-3 font-mono text-xs text-gray-400">
                            {{ $lead->updated_at?->diffForHumans() ?? '—' }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('crm.leads.show', $lead->uuid) }}"
                                   class="btn-ghost-sm"
                                   aria-label="View {{ $lead->first_name }} {{ $lead->last_name }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <button type="button"
                                    class="btn-ghost-sm"
                                    aria-label="Call {{ $lead->first_name }} {{ $lead->last_name }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </button>
                                <button type="button"
                                    class="btn-ghost-sm"
                                    aria-label="Message {{ $lead->first_name }} {{ $lead->last_name }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-500">No leads found</p>
                            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters or create a new lead.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        @if ($this->leads->hasPages())
        <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3">
            <span class="text-xs text-gray-500">
                Showing {{ $this->leads->firstItem() }}–{{ $this->leads->lastItem() }}
                of {{ number_format($this->leads->total()) }} leads
            </span>
            <div class="flex gap-2">
                @if ($this->leads->onFirstPage())
                    <span class="btn-secondary-sm cursor-not-allowed opacity-50">← Prev</span>
                @else
                    <button wire:click="previousPage" class="btn-secondary-sm">← Prev</button>
                @endif
                @if ($this->leads->hasMorePages())
                    <button wire:click="nextPage" class="btn-primary-sm">Next →</button>
                @else
                    <span class="btn-primary-sm cursor-not-allowed opacity-50">Next →</span>
                @endif
            </div>
        </div>
        @else
        <div class="border-t border-gray-100 px-4 py-3">
            <span class="text-xs text-gray-500">{{ $this->leads->total() }} lead{{ $this->leads->total() !== 1 ? 's' : '' }}</span>
        </div>
        @endif
    </div>

    {{-- Loading overlay --}}
    <div wire:loading.flex class="fixed inset-0 z-50 items-center justify-center bg-white/50">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-600 border-t-transparent"></div>
    </div>
</div>
