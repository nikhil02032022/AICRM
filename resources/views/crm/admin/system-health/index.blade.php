<x-layouts.crm title="System Health">
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
    @endpush

    <div class="space-y-6"
         x-data="systemHealth()"
         x-init="init()"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">System Health</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Real-time infrastructure status · Auto-refreshes every 30 seconds ·
                    Last updated: <span class="font-medium" x-text="lastUpdated"></span>
                </p>
            </div>
            <button type="button" @click="refresh()" :disabled="loading" class="btn-secondary">
                <svg class="h-4 w-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>

        {{-- Overall status banner --}}
        <div
            :class="overallStatus === 'ok' ? 'bg-green-50 border-green-200 text-green-800' :
                    overallStatus === 'warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' :
                    overallStatus === 'critical' ? 'bg-red-50 border-red-200 text-red-800' :
                    'bg-gray-50 border-gray-200 text-gray-600'"
            class="rounded-xl border px-5 py-4 flex items-center gap-3"
            role="status"
            aria-live="polite"
        >
            <svg x-show="overallStatus === 'ok'" class="h-5 w-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <svg x-show="overallStatus === 'warning' || overallStatus === 'critical'" class="h-5 w-5 flex-shrink-0" :class="overallStatus === 'critical' ? 'text-red-500' : 'text-yellow-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <span class="text-sm font-semibold capitalize" x-text="
                overallStatus === 'ok' ? 'All systems operational' :
                overallStatus === 'warning' ? 'One or more systems require attention' :
                overallStatus === 'critical' ? 'Critical issue detected — immediate action required' :
                'Status unknown'
            "></span>
        </div>

        {{-- Component grid --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="comp in snapshot" :key="comp.component">
                <div
                    class="rounded-xl border bg-white shadow-sm p-5 cursor-pointer hover:shadow-md transition-shadow"
                    :class="comp.status === 'ok' ? 'border-green-100' : comp.status === 'warning' ? 'border-yellow-200' : comp.status === 'critical' ? 'border-red-200' : 'border-gray-200'"
                    @click="showHistory(comp.component)"
                    :aria-label="'View history for ' + comp.component"
                    tabindex="0"
                    @keydown.enter="showHistory(comp.component)"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-semibold text-gray-700 capitalize" x-text="comp.component.replace(/_/g,' ')"></span>
                        <span
                            :class="comp.status === 'ok' ? 'bg-green-100 text-green-700' :
                                    comp.status === 'warning' ? 'bg-yellow-100 text-yellow-700' :
                                    comp.status === 'critical' ? 'bg-red-100 text-red-700' :
                                    'bg-gray-100 text-gray-500'"
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium uppercase"
                            x-text="comp.status"
                        ></span>
                    </div>
                    <template x-if="comp.metric_name">
                        <p class="text-xs text-gray-500">
                            <span x-text="comp.metric_name"></span>:
                            <span class="font-semibold text-gray-700" x-text="comp.metric_value"></span>
                        </p>
                    </template>
                    <p class="text-xs text-gray-400 mt-1" x-text="comp.recorded_at ? new Date(comp.recorded_at).toLocaleTimeString() : ''"></p>
                </div>
            </template>
        </div>

        {{-- History panel --}}
        <div
            x-show="historyComponent !== null"
            x-cloak
            class="rounded-xl border border-gray-200 bg-white shadow-sm p-6"
        >
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-800 capitalize">
                    24-Hour Trend:
                    <span x-text="(historyComponent || '').replace(/_/g, ' ')"></span>
                </h2>
                <button type="button" @click="historyComponent = null; historyChart && historyChart.destroy()" aria-label="Close" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div x-show="historyLoading" class="flex items-center justify-center py-10 text-gray-400">
                <svg class="animate-spin h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Loading trend…
            </div>
            <canvas id="health-history-chart" x-show="!historyLoading" class="max-h-64" aria-label="Health trend chart" role="img"></canvas>
        </div>
    </div>

    <script>
    function systemHealth() {
        return {
            snapshot: @json($snapshot ?? []),
            loading: false,
            lastUpdated: 'Just now',
            overallStatus: 'ok',
            historyComponent: null,
            historyLoading: false,
            historyChart: null,
            pollInterval: null,

            init() {
                this.computeOverall();
                this.pollInterval = setInterval(() => this.refresh(), 30000);
            },

            computeOverall() {
                const statuses = this.snapshot.map(c => c.status);
                if (statuses.includes('critical')) { this.overallStatus = 'critical'; return; }
                if (statuses.includes('warning')) { this.overallStatus = 'warning'; return; }
                this.overallStatus = 'ok';
            },

            async refresh() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route('crm.admin.system-health.poll') }}', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.snapshot = json.data;
                        this.computeOverall();
                        this.lastUpdated = new Date().toLocaleTimeString();
                    }
                } finally {
                    this.loading = false;
                }
            },

            async showHistory(component) {
                this.historyComponent = component;
                this.historyLoading = true;
                if (this.historyChart) { this.historyChart.destroy(); this.historyChart = null; }

                try {
                    const res = await fetch(`{{ url('crm/admin/system-health/history') }}/${component}`, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    });
                    const json = await res.json();
                    if (json.success) {
                        this.$nextTick(() => {
                            const ctx = document.getElementById('health-history-chart');
                            if (!ctx) return;
                            this.historyChart = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: json.data.map(r => new Date(r.recorded_at).toLocaleTimeString()),
                                    datasets: [{
                                        label: component.replace(/_/g, ' '),
                                        data: json.data.map(r => r.metric_value),
                                        borderColor: '#6366f1',
                                        backgroundColor: 'rgba(99,102,241,0.08)',
                                        borderWidth: 2,
                                        pointRadius: 2,
                                        fill: true,
                                        tension: 0.4,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        x: { ticks: { maxTicksLimit: 8, font: { size: 11 } } },
                                        y: { beginAtZero: true, ticks: { font: { size: 11 } } },
                                    },
                                },
                            });
                        });
                    }
                } finally {
                    this.historyLoading = false;
                }
            },
        };
    }
    </script>
</x-layouts.crm>
