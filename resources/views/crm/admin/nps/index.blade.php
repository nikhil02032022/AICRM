{{-- BRD: CRM-AL-004 — NPS snapshot history and latest score for admin --}}
<x-layouts.crm title="Alumni NPS Scores">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Alumni NPS Scores</h1>
        <p class="mt-1 text-sm text-gray-500">Track Net Promoter Score trends across academic years and programmes.</p>
    </x-slot:header>

    <x-slot:headerActions>
        @can('manage', \App\Models\CRM\Alumni\AlumniNpsSnapshot::class)
        <a href="{{ route('crm.admin.nps.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
            </svg>
            Add NPS Entry
        </a>
        @endcan
    </x-slot:headerActions>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3.5 text-sm text-green-800 shadow-sm">
        <svg class="h-5 w-5 flex-shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
        </svg>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Latest Score + Trend --}}
    @if($latest)
    @php
        $npsBg = $latest->nps_score > 50
            ? 'bg-green-50 border-green-200'
            : ($latest->nps_score >= 0 ? 'bg-amber-50 border-amber-200' : 'bg-red-50 border-red-200');
    @endphp
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- Score tile --}}
        <div class="rounded-xl border {{ $npsBg }} p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Latest NPS Score</p>
            <p class="mt-2 text-4xl font-bold tabular-nums {{ $latest->scoreColourClass() }}">{{ $latest->scoreLabel() }}</p>
            <p class="mt-1 text-xs text-gray-500">
                Promoters {{ $latest->promoters_pct }}% &middot;
                Neutrals {{ $latest->neutrals_pct }}% &middot;
                Detractors {{ $latest->detractors_pct }}%
            </p>
            <p class="mt-1 text-xs text-gray-400">
                Survey date: {{ $latest->survey_date->format('d M Y') }}
                &nbsp;&bull;&nbsp;
                {{ $latest->source->label() }}
            </p>
        </div>

        {{-- Trend sparkline --}}
        <div class="col-span-1 sm:col-span-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">NPS Trend (12 months)</p>
            @if($trend->count() > 1)
            <div class="relative h-24">
                <canvas id="npsSparkline"></canvas>
            </div>
            @else
            <p class="text-sm text-gray-400 py-6">Not enough data points for a trend chart yet. Add more NPS entries.</p>
            @endif
        </div>
    </div>
    @else
    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 px-6 py-8 text-center">
        <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
        </svg>
        <p class="text-sm font-semibold text-gray-600">No NPS data recorded yet</p>
        <p class="mt-1 text-xs text-gray-400">Add your first NPS entry to start tracking alumni satisfaction.</p>
    </div>
    @endif

    {{-- Snapshots table --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-700">NPS Snapshot History</h2>
        </div>

        @if($snapshots->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <p class="text-sm text-gray-500">No NPS snapshots recorded yet.</p>
            @can('manage', \App\Models\CRM\Alumni\AlumniNpsSnapshot::class)
            <a href="{{ route('crm.admin.nps.create') }}" class="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-700">Add the first entry →</a>
            @endcan
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Survey Date</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Academic Year</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Programme</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">NPS Score</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Promoters</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Neutrals</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Detractors</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Source</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach($snapshots as $snapshot)
                <tr class="transition-colors hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $snapshot->survey_date->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $snapshot->academicYear?->label ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $snapshot->programme?->name ?? 'All Programmes' }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-bold tabular-nums {{ $snapshot->scoreColourClass() }}">{{ $snapshot->scoreLabel() }}</span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm tabular-nums text-green-700">{{ $snapshot->promoters_pct }}%</td>
                    <td class="px-6 py-4 text-center text-sm tabular-nums text-gray-500">{{ $snapshot->neutrals_pct }}%</td>
                    <td class="px-6 py-4 text-center text-sm tabular-nums text-red-500">{{ $snapshot->detractors_pct }}%</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium
                            {{ $snapshot->source === \App\Enums\CRM\Alumni\NpsSnapshotSource::Manual ? 'bg-gray-100 text-gray-600' : 'bg-indigo-50 text-indigo-700' }}">
                            {{ $snapshot->source->label() }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($snapshots->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">
            {{ $snapshots->links() }}
        </div>
        @endif
        @endif
    </div>

    @if($trend->count() > 1)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const trend = @json($trend->map(fn($s) => ['date' => $s->survey_date->format('M y'), 'score' => $s->nps_score]));
        new Chart(document.getElementById('npsSparkline'), {
            type: 'line',
            data: {
                labels: trend.map(t => t.date),
                datasets: [{
                    data: trend.map(t => t.score),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' NPS: ' + ctx.parsed.y } } },
                scales: {
                    y: { ticks: { font: { size: 10 }, precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { ticks: { font: { size: 10 } } },
                },
            },
        });
    })();
    </script>
    @endpush
    @endif
</x-layouts.crm>
