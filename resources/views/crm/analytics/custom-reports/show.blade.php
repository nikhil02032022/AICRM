<x-layouts.crm title="{{ $report->name }}">
    <div class="space-y-6"
         x-data="{
            running: {{ $autoRun ? 'true' : 'false' }},
            results: @json($results ?? null),
            columns: @json($columns ?? []),
            async runReport() {
                this.running = true;
                try {
                    const res = await fetch('{{ route('crm.reports.custom.run', $report->uuid) }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.results = json.data.rows;
                        this.columns = json.data.columns;
                    }
                } finally { this.running = false; }
            }
         }"
         x-init="if (running) { runReport(); }"
    >
        {{-- Header --}}
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('crm.reports.custom.index') }}" class="text-gray-400 hover:text-gray-600" aria-label="Back">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $report->name }}</h1>
                    <p class="mt-0.5 text-sm text-gray-500 capitalize">{{ $report->entity->value }} · {{ count($report->selected_fields) }} columns</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @can('crm.reports.manage')
                <a href="{{ route('crm.reports.custom.edit', $report->uuid) }}" class="btn-secondary text-sm">Edit Report</a>
                @endcan
                <button type="button" @click="runReport()" :disabled="running" class="btn-primary text-sm disabled:opacity-60">
                    <svg class="h-4 w-4" :class="running ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-show="!running">Run Report</span>
                    <span x-show="running">Running…</span>
                </button>
                <a href="{{ route('crm.reports.custom.show', $report->uuid) }}?export=csv"
                   class="btn-secondary text-sm"
                   x-show="results && results.length > 0">
                    Export CSV
                </a>
            </div>
        </div>

        {{-- Loading state --}}
        <div x-show="running" class="flex items-center justify-center py-20 text-gray-400">
            <svg class="animate-spin h-8 w-8 mr-3" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span>Running report…</span>
        </div>

        {{-- Results table --}}
        <div x-show="!running && results !== null" class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <template x-for="col in columns" :key="col">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap" x-text="col"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-if="results && results.length > 0">
                        <template x-for="(row, ri) in results" :key="ri">
                            <tr class="hover:bg-gray-50">
                                <template x-for="col in columns" :key="col">
                                    <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap" x-text="row[col] ?? '—'"></td>
                                </template>
                            </tr>
                        </template>
                    </template>
                    <template x-if="results && results.length === 0">
                        <tr>
                            <td :colspan="columns.length" class="px-6 py-12 text-center text-sm text-gray-400">
                                No records match the current filters.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Empty / pre-run state --}}
        <div x-show="!running && results === null" class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <p class="mt-3 text-sm font-medium text-gray-600">Click "Run Report" to see results</p>
            <p class="mt-1 text-xs text-gray-400">Last run: {{ $report->last_run_at ? $report->last_run_at->diffForHumans() : 'Never' }}</p>
        </div>
    </div>
</x-layouts.crm>
