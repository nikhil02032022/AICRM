{{-- BRD: CRM-LC-011 — Live-searchable, filterable lead table --}}
<div>
    {{-- Toolbar --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        {{-- Search --}}
        <div class="relative max-w-xs w-full">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>
            <input
                wire:model.live.debounce.400ms="search"
                type="search"
                placeholder="Search leads..."
                class="input-field pl-9 text-sm"
            >
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap gap-2">
            <select wire:model.live="filterStatus" class="input-field text-sm py-1.5 pl-2 pr-7 min-w-[130px]">
                @foreach ($this->statusOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterTemperature" class="input-field text-sm py-1.5 pl-2 pr-7 min-w-[140px]">
                @foreach ($this->temperatureOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterSource" class="input-field text-sm py-1.5 pl-2 pr-7 min-w-[130px]">
                @foreach ($this->sourceOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            @can('crm.leads.create')
            <a href="{{ route('crm.leads.create') }}" class="btn-primary btn-sm whitespace-nowrap">
                + New Lead
            </a>
            @endcan
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">
                            <button wire:click="sortBy('created_at')" class="flex items-center gap-1 hover:text-indigo-600">
                                Name
                                @if($sortField === 'created_at')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">Source</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">
                            <button wire:click="sortBy('lead_score')" class="flex items-center gap-1 hover:text-indigo-600">
                                Score
                                @if($sortField === 'lead_score')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">Temp</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">
                            <button wire:click="sortBy('status')" class="flex items-center gap-1 hover:text-indigo-600">
                                Status
                                @if($sortField === 'status')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">Counsellor</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600">Created</th>
                        <th scope="col" class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($this->leads as $lead)
                    <tr class="hover:bg-indigo-50/40 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <a href="{{ route('crm.leads.show', $lead->uuid) }}" class="hover:text-indigo-600">
                                {{ $lead->first_name }} {{ $lead->last_name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $lead->source?->label() ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-16 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full rounded-full {{ $lead->lead_score >= 75 ? 'bg-red-500' : ($lead->lead_score >= 50 ? 'bg-amber-400' : 'bg-blue-400') }}"
                                         style="width: {{ $lead->lead_score }}%"></div>
                                </div>
                                <span class="text-xs font-mono text-gray-600">{{ $lead->lead_score }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php $temp = $lead->temperature; @endphp
                            @if($temp)
                                <span class="badge badge-{{ $temp->badgeColour() }}">
                                    {{ $temp->label() }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php $status = $lead->status; @endphp
                            @if($status)
                                <span class="badge badge-{{ $status->badgeColour() }}">
                                    {{ $status->label() }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $lead->assignedCounsellor?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $lead->created_at?->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('crm.leads.show', $lead->uuid) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                View →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="font-medium">No leads found</p>
                            <p class="text-sm mt-1">Try adjusting your filters or create a new lead.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->leads->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">
            {{ $this->leads->links() }}
        </div>
        @endif
    </div>

    {{-- Loading overlay --}}
    <div wire:loading.flex class="fixed inset-0 z-50 items-center justify-center bg-white/50">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent"></div>
    </div>
</div>
