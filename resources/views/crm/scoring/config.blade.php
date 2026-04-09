{{-- BRD: CRM-LQ-001, CRM-LQ-005 — Scoring weights and temperature threshold configuration --}}
<x-layouts.crm title="Scoring Configuration">
    <div class="space-y-6"
         x-data="scoringConfig({
             profile_completeness: {{ $config->weights['profile_completeness'] ?? 25 }},
             programme_interest:   {{ $config->weights['programme_interest']   ?? 20 }},
             source_quality:       {{ $config->weights['source_quality']       ?? 20 }},
             engagement:           {{ $config->weights['engagement']           ?? 20 }},
             consent:              {{ $config->weights['consent']              ?? 5  }},
             geographic:           {{ $config->weights['geographic']           ?? 5  }},
             response_time:        {{ $config->weights['response_time']        ?? 5  }},
             hot_threshold:        {{ $config->hot_threshold ?? 75 }},
             warm_threshold:       {{ $config->warm_threshold ?? 50 }},
         })">

        {{-- Page header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Lead Scoring Configuration</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Configure how each signal contributes to the lead score (0–100) for your institution.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('crm.scoring.source-quality') }}" class="btn-secondary-sm">
                    Source Quality Report →
                </a>
                <a href="{{ route('crm.leads.index') }}" class="btn-secondary-sm">
                    Back to Leads
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3" role="alert">
            <svg class="h-5 w-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
        @endif

        <form action="{{ route('crm.scoring.config.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- LEFT: Signal weight sliders --}}
                <div class="lg:col-span-2 space-y-4">
                    <div class="card p-6">
                        <h2 class="mb-1 text-sm font-bold text-gray-900">Signal Weights</h2>
                        <p class="mb-5 text-xs text-gray-500">Each weight controls how much a signal category contributes to the final score. Adjust to match your institution's admission priorities.</p>

                        @php
                            $signals = [
                                ['key' => 'profile_completeness', 'label' => 'Profile Completeness', 'desc' => 'Email, city, state, name, nationality', 'max' => 30, 'color' => 'indigo'],
                                ['key' => 'programme_interest',   'label' => 'Programme Interest',   'desc' => 'At least one programme of interest linked', 'max' => 30, 'color' => 'violet'],
                                ['key' => 'source_quality',       'label' => 'Source Quality',       'desc' => 'Lead channel quality (Referral > Google > Web)', 'max' => 30, 'color' => 'blue'],
                                ['key' => 'engagement',           'label' => 'Engagement Signals',   'desc' => 'Status advancement, counsellor assigned, activities', 'max' => 30, 'color' => 'emerald'],
                                ['key' => 'consent',              'label' => 'Consent (DPDP)',        'desc' => 'Data processing consent given', 'max' => 10, 'color' => 'green'],
                                ['key' => 'geographic',           'label' => 'Geographic Data',      'desc' => 'City/state data completeness', 'max' => 10, 'color' => 'teal'],
                                ['key' => 'response_time',        'label' => 'Response Time',        'desc' => 'Counsellor response speed (activates in Group E)', 'max' => 10, 'color' => 'orange'],
                            ];
                        @endphp

                        <div class="space-y-5">
                            @foreach($signals as $signal)
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <div>
                                        <label for="w_{{ $signal['key'] }}" class="text-sm font-semibold text-gray-800">{{ $signal['label'] }}</label>
                                        <p class="text-xs text-gray-400">{{ $signal['desc'] }}</p>
                                    </div>
                                    <span class="text-sm font-bold tabular-nums text-{{ $signal['color'] }}-600"
                                          x-text="weights.{{ $signal['key'] }} + ' pts'"></span>
                                </div>
                                <input id="w_{{ $signal['key'] }}"
                                       name="{{ $signal['key'] }}"
                                       type="range"
                                       min="0" max="{{ $signal['max'] }}" step="1"
                                       x-model.number="weights.{{ $signal['key'] }}"
                                       class="w-full h-2 cursor-pointer appearance-none rounded-full bg-gray-200 accent-{{ $signal['color'] }}-500"
                                       aria-label="{{ $signal['label'] }} weight">
                                @error($signal['key'])
                                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Temperature thresholds --}}
                    <div class="card p-6">
                        <h2 class="mb-1 text-sm font-bold text-gray-900">Temperature Thresholds</h2>
                        <p class="mb-5 text-xs text-gray-500">Set the score boundaries for HOT, WARM, and COLD classification. HOT must be strictly above WARM.</p>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="hot_threshold" class="label flex items-center gap-2">
                                    <span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>
                                    HOT Threshold <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input id="hot_threshold" name="hot_threshold" type="number"
                                           min="2" max="100"
                                           x-model.number="hotThreshold"
                                           :class="{'border-red-500': hotThreshold <= warmThreshold}"
                                           class="input-field pr-12">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm font-bold text-red-500">/ 100</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Score ≥ this value → HOT 🔥</p>
                                @error('hot_threshold')
                                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="warm_threshold" class="label flex items-center gap-2">
                                    <span class="inline-block h-2 w-2 rounded-full bg-orange-500"></span>
                                    WARM Threshold <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input id="warm_threshold" name="warm_threshold" type="number"
                                           min="1" max="99"
                                           x-model.number="warmThreshold"
                                           class="input-field pr-12">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm font-bold text-orange-500">/ 100</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Score ≥ this value → WARM ☀️ (below → COLD 🧊)</p>
                                @error('warm_threshold')
                                    <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p x-show="hotThreshold <= warmThreshold"
                           x-cloak
                           class="mt-3 text-xs font-medium text-red-600" role="alert">
                            HOT threshold must be strictly greater than WARM threshold.
                        </p>
                    </div>

                    {{-- Save button --}}
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-gray-400">
                            Last saved: {{ $config->updated_at?->diffForHumans() ?? 'Never — using defaults' }}
                        </p>
                        <button type="submit"
                                :disabled="hotThreshold <= warmThreshold"
                                class="btn-primary-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            Save Configuration
                        </button>
                    </div>
                </div>

                {{-- RIGHT: Live score preview --}}
                <div class="space-y-4">
                    <div class="card p-5 sticky top-4">
                        <h3 class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Live Score Preview</h3>
                        <p class="mb-4 text-xs text-gray-500">See how a fully complete lead would score with the current weights.</p>

                        {{-- Score gauge --}}
                        <div class="mb-4 flex justify-center">
                            <div class="relative" style="width:88px;height:88px">
                                <svg width="88" height="88" viewBox="0 0 88 88" aria-hidden="true">
                                    <circle cx="44" cy="44" r="36" fill="none" stroke="#E5E7EB" stroke-width="9" transform="rotate(-90 44 44)"/>
                                    <circle cx="44" cy="44" r="36" fill="none"
                                            :stroke="previewColor"
                                            stroke-width="9"
                                            stroke-linecap="round"
                                            :stroke-dasharray="previewArc + ' ' + previewCirc"
                                            transform="rotate(-90 44 44)"/>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="font-mono text-xl font-black leading-none" :style="'color:' + previewColor" x-text="previewScore"></span>
                                    <span class="mt-1 text-[8px] font-bold uppercase tracking-widest text-gray-400">Score</span>
                                </div>
                            </div>
                        </div>

                        {{-- Temperature badge --}}
                        <div class="mb-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                                  :class="{
                                      'bg-red-100 text-red-700': previewTemp === 'HOT',
                                      'bg-orange-100 text-orange-700': previewTemp === 'WARM',
                                      'bg-blue-100 text-blue-700': previewTemp === 'COLD',
                                  }">
                                <span x-text="previewTemp === 'HOT' ? '🔥' : previewTemp === 'WARM' ? '☀️' : '🧊'"></span>
                                <span x-text="previewTemp"></span>
                            </span>
                        </div>

                        {{-- Signal breakdown --}}
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between text-gray-500 font-semibold border-b pb-1 mb-1">
                                <span>Signal</span><span>Pts</span>
                            </div>
                            <div class="flex justify-between"><span class="text-gray-600">Profile (full)</span><span class="font-bold tabular-nums" x-text="weights.profile_completeness"></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Programme</span><span class="font-bold tabular-nums" x-text="weights.programme_interest"></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Source (Referral)</span><span class="font-bold tabular-nums" x-text="weights.source_quality"></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Engagement</span><span class="font-bold tabular-nums" x-text="Math.round(weights.engagement * 0.75)"></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Consent</span><span class="font-bold tabular-nums" x-text="weights.consent"></span></div>
                            <div class="flex justify-between"><span class="text-gray-600">Geographic</span><span class="font-bold tabular-nums" x-text="weights.geographic"></span></div>
                            <div class="flex justify-between text-gray-400"><span>Response time (stub)</span><span>0</span></div>
                            <div class="flex justify-between border-t pt-1 mt-1 font-bold">
                                <span class="text-gray-900">Total (capped at 100)</span>
                                <span class="tabular-nums" :style="'color:' + previewColor" x-text="previewScore"></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    @push('scripts')
    <script>
    function scoringConfig(initial) {
        return {
            weights: {
                profile_completeness: initial.profile_completeness,
                programme_interest:   initial.programme_interest,
                source_quality:       initial.source_quality,
                engagement:           initial.engagement,
                consent:              initial.consent,
                geographic:           initial.geographic,
                response_time:        initial.response_time,
            },
            hotThreshold:  initial.hot_threshold,
            warmThreshold: initial.warm_threshold,

            get previewScore() {
                const w = this.weights;
                // Simulate fully complete lead (all signals at 100%)
                // Engagement: status advanced (50%) + counsellor assigned (25%) = 75%
                const raw = w.profile_completeness
                    + w.programme_interest
                    + w.source_quality                       // Referral = 100%
                    + Math.round(w.engagement * 0.75)        // 75% engagement
                    + w.consent
                    + w.geographic
                    + 0;                                      // response_time stub
                return Math.min(100, raw);
            },
            get previewTemp() {
                const s = this.previewScore;
                if (s >= this.hotThreshold)  return 'HOT';
                if (s >= this.warmThreshold) return 'WARM';
                return 'COLD';
            },
            get previewColor() {
                return this.previewTemp === 'HOT'  ? '#EF4444'
                     : this.previewTemp === 'WARM' ? '#F59E0B'
                     : '#6366F1';
            },
            get previewCirc() { return +(2 * Math.PI * 36).toFixed(2); },
            get previewArc()  { return +((this.previewScore / 100) * this.previewCirc).toFixed(2); },
        };
    }
    </script>
    @endpush
</x-layouts.crm>
