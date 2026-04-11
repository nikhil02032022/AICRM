<x-layouts.crm>
    <x-slot:header>Cost Per Lead Tracking</x-slot:header>

    @php
        $rows = $report->getCollection();
        $totalSpend = (float) $rows->sum(fn ($row) => (float) $row->amount);
        $totalAttributedLeads = (int) $rows->sum(fn ($row) => (int) ($row->getAttribute('attributed_leads_count') ?? 0));
        $overallCpl = $totalAttributedLeads > 0 ? round($totalSpend / $totalAttributedLeads, 2) : null;
        $topSource = $rows
            ->groupBy('source')
            ->map(fn ($group) => (int) $group->sum(fn ($row) => (int) ($row->getAttribute('attributed_leads_count') ?? 0)))
            ->sortDesc()
            ->keys()
            ->first();
    @endphp

    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 p-6 shadow-sm sm:p-8">
            <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-emerald-200/40 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-14 -left-10 h-32 w-32 rounded-full bg-cyan-200/40 blur-2xl"></div>

            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">LC-017</p>
                <h2 class="mt-2 text-2xl font-semibold text-gray-900 sm:text-3xl">Campaign spend and cost-per-lead intelligence</h2>
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600 sm:text-base">
                    Track spend against attributed leads using first-touch, last-touch, or linear attribution. Use this screen to identify expensive channels, optimize budget allocation, and improve campaign ROI.
                </p>
            </div>
        </section>

        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status" aria-live="polite">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4" role="alert">
                <p class="text-sm font-semibold text-red-800">Please review the highlighted fields.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Spend Rows</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($rows->count()) }}</p>
                <p class="mt-1 text-xs text-gray-500">Entries in current filtered view</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Total Spend</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-900">INR {{ number_format($totalSpend, 2) }}</p>
                <p class="mt-1 text-xs text-emerald-700">Sum of visible campaign spends</p>
            </article>
            <article class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Attributed Leads</p>
                <p class="mt-2 text-2xl font-semibold text-cyan-900">{{ number_format($totalAttributedLeads) }}</p>
                <p class="mt-1 text-xs text-cyan-700">Leads counted by selected attribution models</p>
            </article>
            <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Overall CPL</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-900">
                    @if($overallCpl !== null)
                        INR {{ number_format($overallCpl, 2) }}
                    @else
                        —
                    @endif
                </p>
                <p class="mt-1 text-xs text-indigo-700">
                    @if($topSource)
                        Top source by attributed leads: {{ $topSource }}
                    @else
                        No source dominance yet
                    @endif
                </p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Add Campaign Spend</h3>
                <p class="mt-1 text-sm text-gray-600">Log a spend row with attribution model and campaign period.</p>

                <form method="POST" action="{{ route('crm.marketing.cost-tracking.store') }}" class="mt-5 grid gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <label for="source" class="block text-sm font-medium text-gray-700">Source <span class="text-red-500">*</span></label>
                        <input id="source" name="source" required value="{{ old('source') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="google_ads" />
                    </div>
                    <div>
                        <label for="campaign_name" class="block text-sm font-medium text-gray-700">Campaign Name</label>
                        <input id="campaign_name" name="campaign_name" value="{{ old('campaign_name') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="mba-2027" />
                    </div>
                    <div>
                        <label for="attribution_model" class="block text-sm font-medium text-gray-700">Attribution Model <span class="text-red-500">*</span></label>
                        <select id="attribution_model" name="attribution_model" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                            <option value="last_touch" @selected(old('attribution_model') === 'last_touch')>Last Touch</option>
                            <option value="first_touch" @selected(old('attribution_model') === 'first_touch')>First Touch</option>
                            <option value="linear" @selected(old('attribution_model') === 'linear')>Linear</option>
                        </select>
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                        <input id="amount" name="amount" value="{{ old('amount') }}" type="number" step="0.01" min="0" required class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="50000" />
                    </div>
                    <div>
                        <label for="period_start" class="block text-sm font-medium text-gray-700">Period Start <span class="text-red-500">*</span></label>
                        <input id="period_start" name="period_start" value="{{ old('period_start') }}" type="date" required class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label for="period_end" class="block text-sm font-medium text-gray-700">Period End <span class="text-red-500">*</span></label>
                        <input id="period_end" name="period_end" value="{{ old('period_end') }}" type="date" required class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            Save Spend Entry
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">CPL Filters</h3>
                <p class="mt-1 text-sm text-gray-600">Filter by source, campaign, and date range to inspect channel efficiency.</p>

                <form method="GET" action="{{ route('crm.marketing.cost-tracking.index') }}" class="mt-5 grid gap-4 md:grid-cols-2 md:items-end">
                    <div>
                        <label for="filter_source" class="block text-sm font-medium text-gray-700">Source</label>
                        <input id="filter_source" name="source" value="{{ $filters['source'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="google_ads" />
                    </div>
                    <div>
                        <label for="filter_campaign_name" class="block text-sm font-medium text-gray-700">Campaign</label>
                        <input id="filter_campaign_name" name="campaign_name" value="{{ $filters['campaign_name'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="mba-2027" />
                    </div>
                    <div>
                        <label for="filter_period_start" class="block text-sm font-medium text-gray-700">From</label>
                        <input id="filter_period_start" name="period_start" type="date" value="{{ $filters['period_start'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div>
                        <label for="filter_period_end" class="block text-sm font-medium text-gray-700">To</label>
                        <input id="filter_period_end" name="period_end" type="date" value="{{ $filters['period_end'] ?? '' }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-500 px-5 py-2.5 text-sm font-semibold text-emerald-700 transition-colors hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            Apply Filters
                        </button>
                        @if(!empty($filters['source']) || !empty($filters['campaign_name']) || !empty($filters['period_start']) || !empty($filters['period_end']))
                            <a href="{{ route('crm.marketing.cost-tracking.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                                Clear Filters
                            </a>
                        @endif
                    </div>
                </form>
            </section>
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Spend vs Attributed Leads</h3>
                <p class="text-xs text-gray-500">Rows sorted by latest campaign period.</p>
            </div>

            @if($report->isEmpty())
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l3-3 3 3 4-6" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-medium text-gray-700">No spend records found for selected filters.</p>
                    <p class="mt-1 text-sm text-gray-500">Add a spend row or remove filters to view CPL insights.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Model</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Spend</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Attributed Leads</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">CPL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($report as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row->source }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $row->campaign_name ?: '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $row->period_start?->format('d M Y') }} - {{ $row->period_end?->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <span class="inline-flex rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">
                                            {{ $row->attribution_model?->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $row->currency }} {{ number_format((float) $row->amount, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $row->getAttribute('attributed_leads_count') }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-emerald-700">
                                        @if($row->getAttribute('cost_per_lead') !== null)
                                            {{ $row->currency }} {{ number_format((float) $row->getAttribute('cost_per_lead'), 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 bg-gray-50 px-6 py-3">
                    {{ $report->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts.crm>
