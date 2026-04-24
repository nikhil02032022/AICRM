<x-layouts.crm title="Alumni Pipeline">
    <div
        class="space-y-6"
        x-data="{ tab: '{{ request('status', 'all') }}' }"
    >

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Alumni Pipeline</h1>
                <p class="mt-1 text-sm text-gray-500">AL-001 — graduates seeded on application enrolment</p>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm mb-4">{{ session('success') }}</div>
        @endif

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @php
                $statusGroups = [
                    'pending'  => ['label' => 'Pending',  'color' => 'yellow', 'bg' => 'bg-yellow-50',  'border' => 'border-yellow-200', 'text' => 'text-yellow-800', 'count_text' => 'text-yellow-900'],
                    'eligible' => ['label' => 'Eligible', 'color' => 'blue',   'bg' => 'bg-blue-50',    'border' => 'border-blue-200',   'text' => 'text-blue-700',   'count_text' => 'text-blue-900'],
                    'synced'   => ['label' => 'Synced',   'color' => 'green',  'bg' => 'bg-green-50',   'border' => 'border-green-200',  'text' => 'text-green-700',  'count_text' => 'text-green-900'],
                    'failed'   => ['label' => 'Failed',   'color' => 'red',    'bg' => 'bg-red-50',     'border' => 'border-red-200',    'text' => 'text-red-700',    'count_text' => 'text-red-900'],
                ];
            @endphp
            @foreach($statusGroups as $key => $cfg)
                <div class="rounded-xl border {{ $cfg['border'] }} {{ $cfg['bg'] }} p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $cfg['text'] }}">{{ $cfg['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $cfg['count_text'] }}">
                        {{ $stats[$key] ?? $records->filter(fn($r) => strtolower($r->alumni_status?->value ?? '') === $key)->count() }}
                    </p>
                    <p class="mt-0.5 text-xs {{ $cfg['text'] }}">pipeline records</p>
                </div>
            @endforeach
        </div>

        {{-- Status Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                @foreach(['all' => 'All', 'pending' => 'Pending', 'eligible' => 'Eligible', 'synced' => 'Synced', 'failed' => 'Failed'] as $value => $label)
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
        @foreach(['all', 'pending', 'eligible', 'synced', 'failed'] as $tabKey)
        <div x-show="tab === '{{ $tabKey }}'" x-transition>
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="table-th">Lead Name</th>
                                <th class="table-th">Programme</th>
                                <th class="table-th">Application</th>
                                <th class="table-th">Enrolled / Graduated At</th>
                                <th class="table-th text-center">Status</th>
                                <th class="table-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $filteredRecords = $tabKey === 'all'
                                    ? $records
                                    : $records->filter(fn($r) => strtolower($r->alumni_status?->value ?? '') === $tabKey);
                            @endphp
                            @forelse($filteredRecords as $record)
                                <tr class="hover:bg-gray-50/70 transition-colors duration-100">
                                    <td class="table-td">
                                        <a href="{{ route('crm.leads.show', $record->lead) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                            {{ $record->lead?->full_name ?? 'Lead #'.$record->lead_id }}
                                        </a>
                                    </td>
                                    <td class="table-td text-sm text-gray-700">
                                        {{ $record->application?->programme?->name ?? '—' }}
                                    </td>
                                    <td class="table-td text-sm">
                                        @if($record->application)
                                            <a href="{{ route('crm.applications.pipeline.show', $record->application) }}"
                                                class="text-indigo-600 hover:text-indigo-800 hover:underline text-xs">
                                                #{{ $record->application->id }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="table-td text-sm text-gray-600">
                                        {{ $record->enrolled_at ? $record->enrolled_at->format('d M Y') : ($record->graduated_at ? $record->graduated_at->format('d M Y') : '—') }}
                                    </td>
                                    <td class="table-td text-center">
                                        <span class="{{ $record->alumni_status?->badgeClass() ?? 'badge-gray' }}">
                                            {{ $record->alumni_status?->label() ?? ucfirst($record->alumni_status?->value ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="table-td text-right">
                                        <a href="{{ route('crm.leads.show', $record->lead) }}"
                                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View Lead</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">No alumni pipeline records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tabKey === 'all' && method_exists($records, 'hasPages') && $records->hasPages())
                    <div class="border-t border-gray-100 px-5 py-3">{{ $records->withQueryString()->links() }}</div>
                @endif
            </div>
        </div>
        @endforeach

    </div>
</x-layouts.crm>
