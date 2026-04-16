{{-- BRD: CRM-AP-008, CRM-AP-009, CRM-AP-010 — Filterable application list table --}}
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Applications</h2>
            <p class="mt-1 text-sm text-gray-600">View and manage all applications</p>
        </div>
        <a href="{{ route('crm.applications.pipeline') }}" class="btn-secondary-sm">
            View as Kanban
        </a>
    </div>

    {{-- Filters --}}
    <div class="space-y-3 bg-white rounded-lg shadow-sm p-4">
        {{-- Search + Status --}}
        <div class="flex flex-wrap gap-3">
            <div class="relative flex-1 min-w-xs">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search by name, email..."
                    class="input-field pl-10"
                />
            </div>

            <select wire:model.live="filterStatus"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterCounsellor"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Counsellors</option>
                {{-- TODO: Populate from counsellors --}}
            </select>
        </div>

        {{-- Date range filters --}}
        <div class="flex flex-wrap gap-3">
            <div>
                <label for="from_date" class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                <input
                    id="from_date"
                    wire:model.live="filterDateFrom"
                    type="date"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                />
            </div>
            <div>
                <label for="to_date" class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                <input
                    id="to_date"
                    wire:model.live="filterDateTo"
                    type="date"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                />
            </div>
        </div>
    </div>

    {{-- Bulk actions bar (when selections exist) --}}
    @if (count($this->selectedApplications) > 0)
        <div class="flex items-center gap-3 bg-indigo-50 rounded-lg p-3 border border-indigo-200">
            <p class="text-sm font-medium text-indigo-900">
                {{ count($this->selectedApplications) }} application{{ count($this->selectedApplications) !== 1 ? 's' : '' }} selected
            </p>

            <select wire:model="bulkAction" class="text-sm border rounded-md px-2 py-1 border-indigo-300">
                <option value="">— Select Action —</option>
                <option value="bulk-status-update">Change Status</option>
                <option value="bulk-assign-counsellor">Assign Counsellor</option>
                <option value="bulk-export">Export</option>
            </select>

            <button wire:click="executeBulkAction" class="btn-primary-sm">
                Execute
            </button>

            <button wire:click="$set('selectedApplications', []); $set('selectAll', false)" class="btn-ghost-sm ml-auto">
                Clear Selection
            </button>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input
                            type="checkbox"
                            wire:model.live="selectAll"
                            class="rounded border-gray-300"
                        />
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">
                        <button wire:click="sortBy('submitted_at')" class="flex items-center gap-1 hover:text-gray-900">
                            Applicant
                            @if ($sortField === 'submitted_at')
                                <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Counsellor</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Submitted</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($applications as $application)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <input
                                type="checkbox"
                                wire:model.live="selectedApplications"
                                value="{{ $application->uuid }}"
                                class="rounded border-gray-300"
                            />
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $application->lead?->first_name }} {{ $application->lead?->last_name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $application->lead?->email }}
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-block rounded-full text-xs font-medium bg-{{ $application->status->badgeColour() }}-100 text-{{ $application->status->badgeColour() }}-800 px-2 py-1">
                                {{ $application->status->label() }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600">
                                {{ $application->assignedCounsellor?->name ?? '—' }}
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600">
                                {{ $application->submitted_at?->format('M d, Y') ?? '—' }}
                            </p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('crm.applications.show', $application->uuid) }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <p class="text-sm text-gray-500">No applications found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-between text-sm">
        <p class="text-gray-600">
            Showing {{ $applications->firstItem() ?? 0 }} to {{ $applications->lastItem() ?? 0 }} of {{ $applications->total() }}
        </p>
        {{ $applications->links() }}
    </div>
</div>
