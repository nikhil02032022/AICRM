<x-layouts.crm title="Security Incidents">
    <div
        class="space-y-6"
        x-data="{ tab: '{{ request('status', 'all') }}' }"
    >

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Security Incidents</h1>
                <p class="mt-1 text-sm text-gray-500">DPDP Act 2023 — breach notification within 72h (CR-010)</p>
            </div>
            <a href="{{ route('crm.compliance.security-incidents.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Report Incident
            </a>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm mb-4">{{ session('success') }}</div>
        @endif

        {{-- Status Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                @foreach(['all' => 'All', 'reported' => 'Reported', 'investigating' => 'Investigating', 'notified' => 'Notified', 'resolved' => 'Resolved'] as $value => $label)
                    <button
                        @click="tab = '{{ $value }}'"
                        :class="tab === '{{ $value }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="border-b-2 py-3 px-1 text-sm font-medium transition-colors duration-150">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tables per tab --}}
        @foreach(['all', 'reported', 'investigating', 'notified', 'resolved'] as $tabKey)
        <div x-show="tab === '{{ $tabKey }}'" x-transition>
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="table-th">Incident Type</th>
                                <th class="table-th">Detected At</th>
                                <th class="table-th">Reported By</th>
                                <th class="table-th text-center">Status</th>
                                <th class="table-th">Notified At</th>
                                <th class="table-th text-center">Days Open</th>
                                <th class="table-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $filteredIncidents = $tabKey === 'all'
                                    ? $incidents
                                    : $incidents->filter(fn($i) => strtolower($i->status?->value ?? '') === $tabKey);
                            @endphp
                            @forelse($filteredIncidents as $incident)
                                @php
                                    $statusVal = strtolower($incident->status?->value ?? '');
                                    $hoursOld  = $incident->detected_at ? $incident->detected_at->diffInHours(now()) : 0;
                                    $slaAlert  = $statusVal === 'reported' && $hoursOld >= 72;
                                    $statusBadge = match($statusVal) {
                                        'reported'      => 'badge-red',
                                        'investigating' => 'badge-yellow',
                                        'notified'      => 'badge-blue',
                                        'resolved'      => 'badge-green',
                                        default         => 'badge-gray',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors duration-100 {{ $slaAlert ? 'bg-red-50/40' : '' }}">
                                    <td class="table-td">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 text-sm">{{ $incident->incident_type }}</span>
                                            @if($slaAlert)
                                                <span class="badge-red text-xs">72h Breach!</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $incident->detected_at ? $incident->detected_at->format('d M Y, H:i') : '—' }}
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $incident->reportedBy?->name ?? '—' }}
                                    </td>
                                    <td class="table-td text-center">
                                        <span class="{{ $statusBadge }}">{{ $incident->status?->label() ?? ucfirst($statusVal) }}</span>
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $incident->notified_at ? $incident->notified_at->format('d M Y, H:i') : '—' }}
                                    </td>
                                    <td class="table-td text-center">
                                        <span class="text-sm font-medium {{ $incident->created_at->diffInDays(now()) > 7 ? 'text-red-600' : 'text-gray-700' }}">
                                            {{ $incident->created_at->diffInDays(now()) }}d
                                        </span>
                                    </td>
                                    <td class="table-td text-right">
                                        <a href="{{ route('crm.compliance.security-incidents.show', $incident) }}"
                                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-400">No security incidents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tabKey === 'all' && method_exists($incidents, 'hasPages') && $incidents->hasPages())
                    <div class="border-t border-gray-100 px-5 py-3">{{ $incidents->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
        @endforeach

    </div>
</x-layouts.crm>
