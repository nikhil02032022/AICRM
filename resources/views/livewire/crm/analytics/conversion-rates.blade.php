<div class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Conversion Rates</h2>
            <p class="mt-1 text-sm text-gray-600">Applications vs enrolled by programme, batch, source, and counsellor</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="btn-secondary-sm inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </a>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}"
               class="btn-secondary-sm inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export XLSX
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap gap-3">
            {{-- From Date --}}
            <div>
                <label for="cr_from_date" class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                <input id="cr_from_date" type="date" wire:model.defer="filters.from_date"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"/>
            </div>

            {{-- To Date --}}
            <div>
                <label for="cr_to_date" class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                <input id="cr_to_date" type="date" wire:model.defer="filters.to_date"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"/>
            </div>

            {{-- Programme --}}
            <div>
                <label for="cr_programme_id" class="block text-xs font-medium text-gray-700 mb-1">Programme</label>
                <select id="cr_programme_id" wire:model.defer="filters.programme_id"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Programmes</option>
                    @foreach($programmes as $programme)
                        <option value="{{ $programme->id }}">{{ $programme->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Batch --}}
            <div>
                <label for="cr_batch" class="block text-xs font-medium text-gray-700 mb-1">Batch</label>
                <select id="cr_batch" wire:model.defer="filters.batch"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Batches</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch }}">{{ $batch }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Counsellor --}}
            <div>
                <label for="cr_counsellor_id" class="block text-xs font-medium text-gray-700 mb-1">Counsellor</label>
                <select id="cr_counsellor_id" wire:model.defer="filters.counsellor_id"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Counsellors</option>
                    @foreach($counsellors as $counsellor)
                        <option value="{{ $counsellor->id }}">{{ $counsellor->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Source --}}
            <div>
                <label for="cr_source" class="block text-xs font-medium text-gray-700 mb-1">Source</label>
                <input id="cr_source" type="text" wire:model.defer="filters.source" placeholder="e.g. organic, referral"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"/>
            </div>

            {{-- Actions --}}
            <div class="flex items-end gap-2">
                <button wire:click="applyFilters" class="btn-primary-sm inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    Apply Filters
                </button>
                <button wire:click="clearFilters" class="btn-ghost-sm inline-flex items-center gap-1.5">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Loading overlay --}}
    <div wire:loading.flex class="fixed inset-0 z-50 items-center justify-center bg-white/50">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent"></div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Programme</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Batch</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Source</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Counsellor</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Total Applications</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Enrolled</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Conversion Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($stats as $row)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $row['programme_name'] ?? '—' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            @if(!empty($row['batch']))
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                    {{ $row['batch'] }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if(!empty($row['source']))
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                    {{ $row['source'] }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600">{{ $row['counsellor_name'] ?? '—' }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-medium text-gray-900">{{ $row['total_applications'] }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">
                                {{ $row['enrolled_count'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @php
                                $rate = $row['conversion_rate'];
                                $color = $rate >= 50 ? 'text-green-700' : ($rate >= 20 ? 'text-yellow-700' : 'text-red-600');
                            @endphp
                            <span class="text-sm font-semibold {{ $color }}">{{ $rate }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center">
                            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-500">No data found</p>
                            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters or date range</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
