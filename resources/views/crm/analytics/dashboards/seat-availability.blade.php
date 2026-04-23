{{-- BRD: CRM-AR-005 — Seat availability vs confirmed enrolments real-time view --}}
<x-layouts.crm title="Seat Availability">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Seat Availability</h1>
        <p class="mt-1 text-sm text-gray-500">Real-time seat capacity vs confirmed enrolments across all active programmes.</p>
    </x-slot:header>

    {{-- Summary KPI Tiles --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-8">
        <div class="card p-4 text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Seats</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 tabular-nums">{{ number_format($kpis['total_capacity']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Confirmed</p>
            <p class="mt-1 text-2xl font-bold text-green-700 tabular-nums">{{ number_format($kpis['total_enrolled']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Available</p>
            <p class="mt-1 text-2xl font-bold text-indigo-700 tabular-nums">{{ number_format($kpis['total_available']) }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Utilisation</p>
            <p class="mt-1 text-2xl font-bold tabular-nums
                {{ $kpis['overall_utilisation'] >= 100 ? 'text-red-600' : ($kpis['overall_utilisation'] >= 80 ? 'text-amber-600' : 'text-gray-900') }}">
                {{ $kpis['overall_utilisation'] }}%
            </p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Programmes Full</p>
            <p class="mt-1 text-2xl font-bold text-red-600 tabular-nums">{{ $kpis['programmes_full'] }}</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Near Capacity</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 tabular-nums">{{ $kpis['programmes_critical'] }}</p>
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex items-center gap-4 mb-4 text-xs text-gray-500">
        <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span> Full (≥100%)</span>
        <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-400"></span> Critical (80–99%)</span>
        <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span> Healthy (&lt;80%)</span>
        <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-full bg-gray-300"></span> Uncapped</span>
    </div>

    {{-- Programme Table --}}
    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="table-th">Programme</th>
                    <th class="table-th">Code</th>
                    <th class="table-th">Level</th>
                    <th class="table-th">Department</th>
                    <th class="table-th-center">Capacity</th>
                    <th class="table-th-center">Enrolled</th>
                    <th class="table-th-center">Available</th>
                    <th class="table-th">Utilisation</th>
                    <th class="table-th-center">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($programmes as $row)
                <tr class="hover:bg-gray-50">
                    <td class="table-td font-medium text-gray-900">{{ $row->name }}</td>
                    <td class="table-td text-gray-500 font-mono text-xs">{{ $row->code }}</td>
                    <td class="table-td text-gray-600">{{ ucfirst($row->level) }}</td>
                    <td class="table-td text-gray-600">{{ $row->department }}</td>
                    <td class="table-td-center tabular-nums">
                        @if($row->intake_capacity > 0)
                            {{ number_format($row->intake_capacity) }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="table-td-center tabular-nums font-semibold text-green-700">{{ number_format($row->confirmed_enrolments) }}</td>
                    <td class="table-td-center tabular-nums
                        {{ $row->status === 'full' ? 'text-red-600 font-semibold' : ($row->status === 'critical' ? 'text-amber-600 font-semibold' : 'text-gray-700') }}">
                        @if($row->intake_capacity > 0)
                            {{ number_format($row->available_seats) }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="table-td" style="min-width: 140px;">
                        @if($row->intake_capacity > 0)
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 rounded-full bg-gray-200 overflow-hidden">
                                @php
                                    $barPct = min(100, $row->utilisation_pct);
                                    $barColour = $row->status === 'full' ? 'bg-red-500'
                                        : ($row->status === 'critical' ? 'bg-amber-400' : 'bg-green-500');
                                @endphp
                                <div class="h-2 rounded-full {{ $barColour }} transition-all"
                                     style="width: {{ $barPct }}%"></div>
                            </div>
                            <span class="text-xs tabular-nums text-gray-600 w-10 text-right">{{ $row->utilisation_pct }}%</span>
                        </div>
                        @else
                            <span class="text-xs text-gray-400">Uncapped</span>
                        @endif
                    </td>
                    <td class="table-td-center">
                        @if($row->status === 'full')
                            <span class="badge-danger">Full</span>
                        @elseif($row->status === 'critical')
                            <span class="badge-warning">Critical</span>
                        @elseif($row->status === 'uncapped')
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Uncapped</span>
                        @else
                            <span class="badge-success">Healthy</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="table-td text-center text-gray-400 py-10">
                        No active programmes found for this institution.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.crm>
