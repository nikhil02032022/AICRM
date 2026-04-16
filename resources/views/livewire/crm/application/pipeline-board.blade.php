{{-- BRD: CRM-AP-008, CRM-AP-009 — Kanban board for application pipeline stages --}}
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Application Pipeline</h2>
            <p class="mt-1 text-sm text-gray-600">Manage applications across admission stages</p>
        </div>
        <a href="{{ route('crm.applications.list') }}" class="btn-secondary-sm">
            View as List
        </a>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-4 bg-white rounded-lg shadow-sm p-4">
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
            {{-- TODO: Iterate over counsellor list --}}
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
            <div class="space-y-3 bg-gray-50 rounded-lg p-4 min-h-96 max-h-96 overflow-y-auto">
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
                            class="bg-white rounded-md border border-gray-200 p-3 cursor-move hover:shadow-md transition-shadow"
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
                                <div class="mt-2 flex items-center gap-1 text-xs text-amber-700 bg-amber-50 rounded px-2 py-1">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0L10 9.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    📎 Offer {{ $application->currentOfferLetter->status }}
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
