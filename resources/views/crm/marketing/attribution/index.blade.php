<x-layouts.crm>
    <x-slot:header>Multi-Touch Attribution</x-slot:header>

    @php
        $timelineRows = collect($timeline ?? []);
        $touchCount = $timelineRows->count();
        $firstTouch = $touchCount > 0 ? $timelineRows->first() : null;
        $lastTouch = $touchCount > 0 ? $timelineRows->last() : null;
    @endphp

    <div class="space-y-6">
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

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Find Lead</h3>
            <p class="mt-1 text-sm text-gray-600">Use filters and pick a lead. UUID is handled internally.</p>

            <form method="GET" action="{{ route('crm.marketing.attribution.index') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3 xl:items-end">
                <div>
                    <label for="source" class="block text-sm font-medium text-gray-700">Source</label>
                    <select id="source" name="source" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm text-gray-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                        <option value="">All Sources</option>
                        <option value="website_organic" @selected(($filters['source'] ?? '') === 'website_organic')>Website Organic</option>
                        <option value="google_ads" @selected(($filters['source'] ?? '') === 'google_ads')>Google Ads</option>
                        <option value="meta_ads" @selected(($filters['source'] ?? '') === 'meta_ads')>Meta Ads</option>
                        <option value="walk_in" @selected(($filters['source'] ?? '') === 'walk_in')>Walk In</option>
                        <option value="live_chat" @selected(($filters['source'] ?? '') === 'live_chat')>Live Chat</option>
                        <option value="referral" @selected(($filters['source'] ?? '') === 'referral')>Referral</option>
                    </select>
                </div>
                <div>
                    <label for="created_from" class="block text-sm font-medium text-gray-700">Created From</label>
                    <input
                        id="created_from"
                        name="created_from"
                        type="date"
                        value="{{ $filters['created_from'] ?? '' }}"
                        class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm text-gray-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label for="created_to" class="block text-sm font-medium text-gray-700">Created To</label>
                    <input
                        id="created_to"
                        name="created_to"
                        type="date"
                        value="{{ $filters['created_to'] ?? '' }}"
                        class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm text-gray-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <div class="xl:col-span-3">
                    <label for="lead_uuid" class="block text-sm font-medium text-gray-700">Select Lead</label>
                    <select id="lead_uuid" name="lead_uuid" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm text-gray-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                        <option value="">Choose lead from filtered results</option>
                        @foreach($leadOptions as $option)
                            @php
                                $leadName = trim((string) $option->first_name.' '.(string) $option->last_name);
                            @endphp
                            <option value="{{ $option->uuid }}" @selected($leadUuid === $option->uuid)>
                                {{ $leadName !== '' ? $leadName : 'Unnamed lead' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="xl:col-span-3 flex flex-wrap items-center justify-end gap-3 pt-1">
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:from-indigo-700 hover:to-violet-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Apply Filters
                    </button>
                    <a href="{{ route('crm.marketing.attribution.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-400 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                        Reset
                    </a>
                </div>
            </form>
        </section>

        @if($lead === null)
            <section class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h18M3 12h18m-18 4.5h10.5" />
                    </svg>
                </div>
                @if(!empty($leadUuid))
                    <p class="mt-3 text-sm font-medium text-gray-700">No lead found for UUID: <span class="font-semibold text-gray-900">{{ $leadUuid }}</span></p>
                    <p class="mt-1 text-sm text-gray-500">Verify the UUID and try again.</p>
                @else
                    <p class="mt-3 text-sm font-medium text-gray-700">Apply filters and select a lead to begin attribution analysis.</p>
                    <p class="mt-1 text-sm text-gray-500">Once loaded, you can add touchpoints and review attribution credits.</p>
                @endif
            </section>
        @endif

        @if($lead !== null)
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lead</p>
                    <p class="mt-2 text-base font-semibold text-gray-900">{{ $lead->fullName() }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $lead->uuid }}</p>
                </article>
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Touchpoints</p>
                    <p class="mt-2 text-2xl font-semibold text-indigo-900">{{ number_format($touchCount) }}</p>
                    <p class="mt-1 text-xs text-indigo-700">Recorded journey events</p>
                </article>
                <article class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">First Touch</p>
                    <p class="mt-2 text-base font-semibold text-sky-900">{{ $firstTouch?->source ?? '—' }}</p>
                    <p class="mt-1 text-xs text-sky-700">{{ $firstTouch?->touchpoint_at?->format('d M Y, h:i A') ?? 'Not available' }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Last Touch</p>
                    <p class="mt-2 text-base font-semibold text-emerald-900">{{ $lastTouch?->source ?? '—' }}</p>
                    <p class="mt-1 text-xs text-emerald-700">{{ $lastTouch?->touchpoint_at?->format('d M Y, h:i A') ?? 'Not available' }}</p>
                </article>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Add Touchpoint for {{ $lead->fullName() }}</h3>
                <p class="mt-1 text-sm text-gray-600">Capture campaign re-engagement, walk-in counselling, or assisted touch events.</p>

                <form method="POST" action="{{ route('crm.marketing.attribution.store', $lead->uuid) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <label for="source" class="block text-sm font-medium text-gray-700">Source <span class="text-red-500">*</span></label>
                        <input id="source" name="source" value="{{ old('source') }}" required class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="google_ads / walk_in / referral" />
                    </div>
                    <div>
                        <label for="touchpoint_at" class="block text-sm font-medium text-gray-700">Touchpoint At</label>
                        <input id="touchpoint_at" name="touchpoint_at" value="{{ old('touchpoint_at') }}" type="datetime-local" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="utm_source" class="block text-sm font-medium text-gray-700">UTM Source</label>
                        <input id="utm_source" name="utm_source" value="{{ old('utm_source') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="utm_medium" class="block text-sm font-medium text-gray-700">UTM Medium</label>
                        <input id="utm_medium" name="utm_medium" value="{{ old('utm_medium') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="utm_campaign" class="block text-sm font-medium text-gray-700">UTM Campaign</label>
                        <input id="utm_campaign" name="utm_campaign" value="{{ old('utm_campaign') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="utm_content" class="block text-sm font-medium text-gray-700">UTM Content</label>
                        <input id="utm_content" name="utm_content" value="{{ old('utm_content') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                            Add Touchpoint
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-1 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Attribution Timeline</h3>
                    <p class="text-xs text-gray-500">Chronological touchpoints with credit distribution</p>
                </div>

                @if($timelineRows->isEmpty())
                    <div class="px-6 py-12 text-center text-sm text-gray-600">No touchpoints recorded yet for this lead.</div>
                @else
                    <div class="space-y-3 px-4 py-4 sm:px-6 sm:py-6">
                        @foreach($timelineRows as $touch)
                            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $touch->source }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $touch->touchpoint_at?->format('d M Y, h:i A') }}</p>
                                    </div>
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-medium border',
                                        'bg-indigo-50 text-indigo-700 border-indigo-200' => $touch->touch_type === 'first_touch',
                                        'bg-emerald-50 text-emerald-700 border-emerald-200' => $touch->touch_type === 'last_touch',
                                        'bg-gray-100 text-gray-700 border-gray-200' => $touch->touch_type === 'middle_touch',
                                    ])>
                                        {{ str_replace('_', ' ', $touch->touch_type) }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1">Campaign: {{ $touch->utm_campaign ?: '—' }}</span>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">First: {{ $touch->first_touch_credit }}</span>
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">Last: {{ $touch->last_touch_credit }}</span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700">Linear: {{ $touch->linear_credit }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-layouts.crm>
