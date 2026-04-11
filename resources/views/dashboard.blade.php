<x-layouts.crm title="Dashboard">

    <x-slot:header>Dashboard</x-slot:header>

    {{-- ── Welcome banner ── --}}
    <div class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 via-primary-700 to-violet-700 shadow-lg">
        <div class="relative px-6 py-6">
            {{-- Decorative background --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <svg class="absolute right-0 top-0 h-full w-72 opacity-10" viewBox="0 0 200 200" fill="none" aria-hidden="true">
                    <circle cx="160" cy="40" r="90" fill="white"/>
                    <circle cx="60" cy="160" r="70" fill="white"/>
                </svg>
            </div>

            <div class="relative flex flex-wrap items-center gap-4">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/20 text-lg font-bold text-white ring-1 ring-white/25">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-primary-200">Welcome back,</p>
                    <h2 class="text-xl font-bold text-white">{{ auth()->user()->name }}</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium text-white">
                            {{ auth()->user()->getRoleNames()->first() }}
                        </span>
                        @if(auth()->user()->institution)
                            <span class="text-xs text-primary-200">{{ auth()->user()->institution->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="hidden text-right sm:block">
                    <p class="text-xs text-primary-300">Today</p>
                    <p class="text-sm font-semibold text-white">{{ now()->format('l, d M Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Phase 0 foundation status ── --}}
    <div class="mb-6">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h3 class="text-base font-semibold text-gray-800">Phase 0 — Foundation Status</h3>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                All systems operational
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Laravel 13',       'Framework bootstrapped',       'bg-violet-100 text-violet-600',  'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 3.75h3m-3 3.75h3'],
                ['Multi-Tenancy',    'InstitutionScope active',      'bg-blue-100 text-blue-600',      'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z'],
                ['RBAC',             '11 BRD roles seeded',          'bg-indigo-100 text-indigo-600',  'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
                ['DPDP',             'PII scrubber + audit log',     'bg-green-100 text-green-600',    'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
                ['Horizon',          '4 queues configured',          'bg-amber-100 text-amber-600',    'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
                ['API Foundation',   'Standard envelope active',     'bg-cyan-100 text-cyan-600',      'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z'],
                ['Tailwind v4',      'UI framework ready',           'bg-sky-100 text-sky-600',        'M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42'],
                ['Livewire v3',      'Reactive components ready',    'bg-rose-100 text-rose-600',      'M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18'],
            ] as [$title, $desc, $iconClass, $iconPath])
                <div class="group flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-shadow duration-150 hover:shadow-md">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl {{ $iconClass }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-800">{{ $title }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $desc }}</p>
                    </div>
                    <div class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-green-500" title="Active"></div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Sprint 1 complete callout ── --}}
    <div class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-5 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-green-100">
                <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-0.5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-green-600">Completed</p>
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-600 px-2.5 py-0.5 text-xs font-semibold text-white">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-300"></span>
                        Sprint 1 — Complete
                    </span>
                </div>
                <h3 class="text-sm font-semibold text-green-900">Phase 1 — Lead Capture &amp; Management</h3>
                <p class="mt-1 text-xs text-green-700">All core lead management, deduplication, AI scoring, and ERP match features are now live.</p>
                <p class="mt-1 text-xs text-green-500">BRD: CRM-LC-001 → CRM-LC-020 &bull; Completed April 2026</p>
            </div>
            @can('crm.leads.view')
            <a href="{{ route('crm.leads.index') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-green-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Open Leads
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Lead Quality by Source widget (BRD: CRM-LQ-008) ── --}}
    @can('viewReport', \App\Models\CRM\InstitutionScoringConfig::class)
    @php
        $sourceQualityWidget = app(\App\Services\CRM\Scoring\LeadScoringService::class)
            ->getSourceQualityReport(auth()->user()->institution_id)
            ->take(5);
    @endphp
    @if($sourceQualityWidget->isNotEmpty())
    <div class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="text-base font-semibold text-gray-800">Lead Quality by Source</h3>
            <a href="{{ route('crm.scoring.source-quality') }}"
               class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                Full Report →
            </a>
        </div>
        <div class="card overflow-hidden p-0">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-100 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Source</th>
                        <th class="px-5 py-3 text-right">Avg Score</th>
                        <th class="px-5 py-3 text-right">Leads</th>
                        <th class="px-5 py-3 text-right">Conv. Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($sourceQualityWidget as $row)
                    @php
                        $srcLabel = '';
                        try { $srcLabel = \App\Enums\CRM\LeadSource::from($row->source)->label(); }
                        catch (\Throwable) { $srcLabel = ucwords(str_replace('_', ' ', $row->source)); }
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors duration-100">
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $srcLabel }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <span class="font-bold tabular-nums {{ $row->avg_score >= 70 ? 'text-green-600' : ($row->avg_score >= 50 ? 'text-blue-600' : 'text-gray-400') }}">
                                {{ $row->avg_score }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-700">{{ number_format($row->total) }}</td>
                        <td class="px-5 py-3 text-right font-semibold {{ $row->conversion_rate >= 30 ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $row->conversion_rate }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endcan

</x-layouts.crm>
