{{-- BRD: CRM-AI-010 — AI nurture journey suggestion dashboard --}}
<x-layouts.crm title="Nurture Journeys">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">AI Nurture Journeys</h1>
                <p class="mt-0.5 text-sm text-gray-500">Segment-wise suggestions for channel, timing, and follow-up orchestration.</p>
            </div>
            <form action="{{ route('crm.scoring.nba-journeys.generate') }}" method="POST" class="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:items-center">
                @csrf
                <input type="date" name="for_date" value="{{ $forDate }}" class="input-field text-sm" required aria-label="For date">
                <select name="segment" class="input-field text-sm" aria-label="Segment">
                    <option value="">All segments</option>
                    <option value="hot_leads">Hot Leads</option>
                    <option value="warm_leads">Warm Leads</option>
                    <option value="cold_or_inactive">Cold or Inactive</option>
                    <option value="application_started">Application Started</option>
                </select>
                <button type="submit" class="btn-primary-sm min-h-11">Generate Suggestions</button>
            </form>
        </div>

        <form method="GET" action="{{ route('crm.scoring.nba-journeys') }}" class="flex items-center gap-2">
            <input type="hidden" name="for_date" value="{{ $forDate }}">
            <label for="segment" class="text-xs font-semibold uppercase tracking-wide text-gray-500">Segment Filter</label>
            <select id="segment" name="segment" class="input-field w-52 text-sm" onchange="this.form.submit()">
                <option value="" @selected($segment === '')>All segments</option>
                <option value="hot_leads" @selected($segment === 'hot_leads')>Hot Leads</option>
                <option value="warm_leads" @selected($segment === 'warm_leads')>Warm Leads</option>
                <option value="cold_or_inactive" @selected($segment === 'cold_or_inactive')>Cold or Inactive</option>
                <option value="application_started" @selected($segment === 'application_started')>Application Started</option>
            </select>
        </form>

        <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
            AI journeys are recommendations only. Counsellor approval is required before execution.
        </div>

        @php
            $collection = collect($rows->resolve());
            $avgConfidence = $collection->isNotEmpty() ? round((float) $collection->avg('confidence_score'), 1) : 0.0;
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Journey Suggestions</p>
                <p class="mt-2 text-2xl font-bold text-indigo-900">{{ $collection->count() }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Average Confidence</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">{{ $avgConfidence }}%</p>
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Generated For</p>
                <p class="mt-2 text-2xl font-bold text-sky-900">{{ \Illuminate\Support\Carbon::parse($forDate)->format('d M Y') }}</p>
            </div>
        </div>

        @if($collection->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
                <p class="text-sm font-semibold text-gray-700">No nurture journey suggestions available for this date.</p>
                <p class="mt-1 text-sm text-gray-500">Run generation to create segment-wise journey recommendations.</p>
            </div>
        @else
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach($collection as $row)
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">{{ $row['segment_label'] }}</h2>
                                <p class="mt-1 text-xs text-gray-500">Segment: {{ str_replace('_', ' ', $row['segment_key']) }}</p>
                            </div>
                            <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                {{ $row['confidence_score'] }}% confidence
                            </span>
                        </div>

                        <p class="text-sm leading-relaxed text-gray-700">{{ $row['rationale'] }}</p>

                        <div class="mt-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Suggested Steps</p>
                            <ol class="mt-2 space-y-2">
                                @foreach(($row['steps'] ?? []) as $step)
                                    <li class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                        <span class="font-semibold text-gray-900">Day {{ $step['day_offset'] ?? 0 }}</span>
                                        <span class="text-gray-500">· {{ strtoupper((string) ($step['channel'] ?? 'email')) }}</span>
                                        <p class="mt-1 text-sm">{{ $step['action'] ?? '' }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <form action="{{ route('crm.scoring.ai-suggestions.decision') }}" method="POST">
                                @csrf
                                <input type="hidden" name="suggestion_type" value="nurture_journey">
                                <input type="hidden" name="suggestion_uuid" value="{{ $row['uuid'] }}">
                                <input type="hidden" name="decision" value="accepted">
                                <button type="submit" class="btn-primary-sm w-full justify-center">Accept Journey</button>
                            </form>
                            <form action="{{ route('crm.scoring.ai-suggestions.decision') }}" method="POST">
                                @csrf
                                <input type="hidden" name="suggestion_type" value="nurture_journey">
                                <input type="hidden" name="suggestion_uuid" value="{{ $row['uuid'] }}">
                                <input type="hidden" name="decision" value="dismissed">
                                <button type="submit" class="btn-secondary-sm w-full justify-center">Dismiss</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.crm>
