{{-- BRD: CRM-AP-008, CRM-AP-009 — Kanban board for application pipeline stages --}}
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Application Pipeline</h2>
            <p class="mt-1 text-sm text-gray-600">Manage applications across admission stages and monitor AP-011 seat pressure in the same workspace.</p>
        </div>
        <a href="{{ route('crm.applications.list') }}" class="btn-secondary-sm">
            View as List
        </a>
    </div>

    {{-- AP-011 seat totals --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Configured Programmes</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $seatCapacityTotals['programme_count'] }}</p>
            <p class="mt-2 text-sm text-slate-600">Active programmes with seat metrics available.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total Seats</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $seatCapacityTotals['total_seats'] }}</p>
            <p class="mt-2 text-sm text-slate-600">Configured intake capacity across the visible catalogue.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Applications</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $seatCapacityTotals['application_count'] }}</p>
            <p class="mt-2 text-sm text-slate-600">Live primary-programme application count in the pipeline.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Critical Programmes</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $seatCapacityTotals['critical_programmes'] }}</p>
            <p class="mt-2 text-sm text-slate-600">Programmes at 80%+ utilisation or already full.</p>
        </div>
    </div>

    {{-- AP-011 programme cards --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">CRM-AP-011</p>
                <h3 class="mt-1 text-xl font-semibold text-slate-900">Programme Seat Availability</h3>
                <p class="mt-1 text-sm text-slate-600">Seat capacity is compared against live application volume using each applicant's primary programme interest.</p>
            </div>
            <p class="text-sm text-slate-500">Available seats across programmes: {{ $seatCapacityTotals['available_seats'] }}</p>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
            @forelse ($seatAvailabilityOverview as $programme)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-semibold text-slate-900">{{ $programme['programme_name'] }}</h4>
                            <p class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-500">{{ $programme['programme_code'] ?: 'Code Pending' }}</p>
                        </div>
                        <span
                            @class([
                                'inline-flex min-h-9 items-center rounded-full px-3 py-1 text-xs font-semibold',
                                'bg-emerald-100 text-emerald-700' => $programme['capacity_status'] === 'healthy',
                                'bg-amber-100 text-amber-700' => $programme['capacity_status'] === 'warning',
                                'bg-rose-100 text-rose-700' => in_array($programme['capacity_status'], ['critical', 'full'], true),
                                'bg-slate-200 text-slate-700' => $programme['capacity_status'] === 'not_configured',
                            ])
                        >
                            {{ $programme['capacity_status_label'] }}
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-white p-3 text-center shadow-sm ring-1 ring-slate-200/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Seats</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $programme['total_seats'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 text-center shadow-sm ring-1 ring-slate-200/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Apps</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $programme['application_count'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 text-center shadow-sm ring-1 ring-slate-200/70">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Open</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $programme['available_seats'] }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Utilisation</p>
                            <p class="mt-1 text-sm text-slate-600">{{ number_format((float) $programme['utilisation_percentage'], 2) }}% of configured intake in pipeline</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Seat UUID</p>
                            <p class="mt-1 max-w-28 truncate text-xs text-slate-600">{{ $programme['programme_uuid'] ?: 'Not synced' }}</p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <h4 class="text-base font-semibold text-slate-900">No programme capacity configured yet</h4>
                    <p class="mt-2 text-sm text-slate-600">Add intake capacity on active programmes to enable AP-011 seat visibility on the admissions pipeline.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
        {{-- Search --}}
        <div class="relative flex-1 min-w-xs">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Search applicants..."
                class="input-field pl-10"
            />
        </div>

        {{-- Counsellor filter --}}
        <select wire:model.live="filterCounsellor"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Counsellors</option>
            @foreach ($counsellors as $counsellor)
                <option value="{{ $counsellor->id }}">{{ $counsellor->name }}</option>
            @endforeach
        </select>

        {{-- Admission cycle filter --}}
        <select wire:model.live="filterAdmissionCycle"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Cycles</option>
            {{-- TODO: Iterate over admission cycles --}}
        </select>
    </div>

    {{-- Kanban columns --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($columnsByStatus as $column)
            <div class="min-h-96 max-h-96 space-y-3 overflow-y-auto rounded-2xl bg-gray-50 p-4 ring-1 ring-slate-200/70">
                {{-- Column header --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">
                        {{ $column['label'] }}
                    </h3>
                    <span class="inline-flex items-center justify-center rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-600">
                        {{ $column['count'] }}
                    </span>
                </div>

                {{-- Applications in column (draggable) --}}
                <div
                    class="space-y-2"
                    data-column-status="{{ $column['status']->value }}"
                    ondrop="handleDrop(event, '{{ $column['status']->value }}')"
                    ondragover="allowDrop(event)"
                >
                    @forelse ($column['applications'] as $application)
                        <div
                            class="cursor-move rounded-xl border border-gray-200 bg-white p-3 transition-shadow hover:shadow-md"
                            draggable="true"
                            ondragstart="handleDragStart(event, '{{ $application->uuid }}')"
                        >
                            {{-- Applicant name + email --}}
                            <p class="text-sm font-medium text-gray-900">
                                {{ $application->lead?->first_name }} {{ $application->lead?->last_name }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500 truncate">
                                {{ $application->lead?->email }}
                            </p>

                            {{-- Meta row: status, counsellor --}}
                            <div class="mt-2 flex items-center justify-between">
                                <span class="inline-block rounded-full text-xs font-medium bg-{{ $column['badgeColour'] }}-100 text-{{ $column['badgeColour'] }}-800 px-2 py-1">
                                    {{ $column['status']->label() }}
                                </span>
                                @if ($application->assignedCounsellor)
                                    <span class="text-xs text-gray-600">
                                        {{ $application->assignedCounsellor->name }}
                                    </span>
                                @endif
                            </div>

                            {{-- Offer status (if applicable) --}}
                            @if ($application->currentOfferLetter)
                                <div class="mt-2 flex items-center gap-1 rounded px-2 py-1 text-xs text-amber-700 bg-amber-50">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0L10 9.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    Offer {{ $application->currentOfferLetter->status }}
                                </div>
                            @endif

                            {{-- Quick actions --}}
                            <div class="mt-3 flex gap-2">
                                <a href="{{ route('crm.applications.show', $application->uuid) }}" class="btn-ghost-sm flex-1 text-center">
                                    View
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <p class="text-sm">No applications</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Drag-drop JavaScript handler --}}
    <script>
        let draggedApplicationUuid = null;

        function handleDragStart(event, uuid) {
            draggedApplicationUuid = uuid;
            event.dataTransfer.effectAllowed = 'move';
            event.target.classList.add('opacity-50');
        }

        function allowDrop(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        }

        function handleDrop(event, toStatus) {
            event.preventDefault();
            if (draggedApplicationUuid) {
                @this.transitionApplication(draggedApplicationUuid, toStatus);
                draggedApplicationUuid = null;
            }
        }

        // Reset drag styling on drag end
        document.addEventListener('dragend', (event) => {
            event.target?.classList.remove('opacity-50');
        });
    </script>
</div>
