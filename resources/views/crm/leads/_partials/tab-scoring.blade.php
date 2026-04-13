                    {{-- ── Scoring tab ── --}}
                    <div x-show="tab === 'scoring'"
                         x-transition:enter="transition-opacity duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="p-6 space-y-6">

                        {{-- Score breakdown --}}
                        <div>
                            <h3 class="mb-4 text-[10px] font-bold uppercase tracking-wider text-gray-400">Score Breakdown</h3>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @php
                                    $scoreBreakdown = [
                                        ['label' => 'Profile', 'desc' => 'Name, email, city, state, nationality', 'color' => 'indigo'],
                                        ['label' => 'Programme', 'desc' => 'Programme of interest linked', 'color' => 'violet'],
                                        ['label' => 'Source', 'desc' => $lead->source?->label() ?? '—', 'color' => 'blue'],
                                        ['label' => 'Engagement', 'desc' => 'Status + counsellor assigned', 'color' => 'emerald'],
                                    ];
                                @endphp
                                @foreach($scoreBreakdown as $item)
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-{{ $item['color'] }}-500">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $item['desc'] }}</p>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="flex-1 rounded-full bg-gray-100" style="height:8px">
                                    @php
                                        $barColor = $lead->lead_score >= 75 ? '#EF4444' : ($lead->lead_score >= 50 ? '#F59E0B' : '#6366F1');
                                    @endphp
                                    <div class="rounded-full" style="height:8px;width:{{ $lead->lead_score }}%;background:{{ $barColor }};transition:width 0.5s ease"></div>
                                </div>
                                <span class="font-mono text-sm font-bold tabular-nums" style="color:{{ $barColor }}">{{ $lead->lead_score }}/100</span>
                                @if($lead->score_manually_overridden)
                                <span class="badge badge-orange text-[10px]">Manually Set</span>
                                @endif
                            </div>
                        </div>

                        {{-- BRD: CRM-LQ-003 — Latest AI score rationale and async refresh trigger --}}
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600">AI Scoring Rationale</h3>
                                    @if($latestAiScore)
                                        <p class="mt-2 text-xs text-gray-700">{{ $latestAiScore->explanation }}</p>
                                        <p class="mt-2 text-[11px] text-gray-500">
                                            Model: <span class="font-mono">{{ $latestAiScore->model_version }}</span>
                                            · Calculated: {{ $latestAiScore->calculated_at?->format('d M Y, h:i A') }}
                                        </p>
                                    @else
                                        <p class="mt-2 text-xs text-gray-600">AI rationale not available yet for this lead. Trigger analysis to generate the first snapshot.</p>
                                    @endif
                                </div>
                                <form action="{{ route('crm.leads.ai-score.recalculate', $lead->uuid) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-primary-sm whitespace-nowrap">Run AI Analysis</button>
                                </form>
                            </div>
                        </div>

                        {{-- BRD: CRM-LQ-010 — Churn risk visibility with recommended next actions --}}
                        <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="w-full">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-rose-600">Churn Risk Signal</h3>
                                    @if($latestChurnFlag)
                                        @php
                                            $riskValue = $latestChurnFlag->risk_level?->value;
                                            $riskClass = $riskValue === 'high'
                                                ? 'badge-red'
                                                : ($riskValue === 'medium' ? 'badge-orange' : 'badge-green');

                                            $recommendedActions = match($riskValue) {
                                                'high' => [
                                                    'Schedule counsellor call within 24 hours.',
                                                    'Send personalised fee and scholarship guidance.',
                                                    'Create follow-up task with escalation owner.',
                                                ],
                                                'medium' => [
                                                    'Send programme-fit nudges and reminders.',
                                                    'Review pending questionnaire fields.',
                                                    'Plan next follow-up within 48 hours.',
                                                ],
                                                default => [
                                                    'Continue scheduled nurture cadence.',
                                                    'Monitor engagement for inactivity drift.',
                                                ],
                                            };
                                        @endphp
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="badge {{ $riskClass }} text-[10px] uppercase">
                                                {{ $latestChurnFlag->risk_level?->label() ?? 'Unknown Risk' }}
                                            </span>
                                            <span class="text-xs font-mono text-rose-700">Score: {{ $latestChurnFlag->risk_score }}/100</span>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-700">{{ $latestChurnFlag->rationale }}</p>

                                        @if(is_array($latestChurnFlag->indicators) && !empty($latestChurnFlag->indicators))
                                            <div class="mt-3">
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Detected Indicators</p>
                                                <ul class="mt-2 space-y-1 text-xs text-gray-700">
                                                    @foreach($latestChurnFlag->indicators as $label => $value)
                                                        <li>
                                                            <span class="font-semibold text-gray-800">{{ ucfirst(str_replace('_', ' ', (string) $label)) }}:</span>
                                                            {{ $value }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div class="mt-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Recommended Next Actions</p>
                                            <ul class="mt-2 space-y-1 text-xs text-gray-700">
                                                @foreach($recommendedActions as $action)
                                                    <li>{{ $action }}</li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        <p class="mt-2 text-[11px] text-gray-500">Flagged: {{ $latestChurnFlag->flagged_at?->format('d M Y, h:i A') }}</p>
                                    @else
                                        <p class="mt-2 text-xs text-gray-600">Churn risk snapshot not available yet. Trigger analysis to generate a risk profile.</p>
                                    @endif
                                </div>
                                <form action="{{ route('crm.leads.churn-risk.recalculate', $lead->uuid) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-secondary-sm whitespace-nowrap" aria-label="Recalculate churn risk">Run Churn Analysis</button>
                                </form>
                            </div>
                        </div>

                        {{-- BRD: CRM-AI-004 — Inbound sentiment signal for communication urgency triage --}}
                        <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="w-full">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-700">Inbound Sentiment Signal</h3>
                                    @if($latestSentimentFlag)
                                        @php
                                            $sentimentValue = $latestSentimentFlag->sentiment_label?->value;
                                            $sentimentClass = $sentimentValue === 'negative'
                                                ? 'badge-red'
                                                : ($sentimentValue === 'positive' ? 'badge-green' : 'badge-blue');
                                        @endphp
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="badge {{ $sentimentClass }} text-[10px] uppercase">
                                                {{ $latestSentimentFlag->sentiment_label?->label() ?? 'Unknown' }}
                                            </span>
                                            <span class="text-xs font-mono text-amber-700">Score: {{ $latestSentimentFlag->sentiment_score }}</span>
                                            @if($latestSentimentFlag->is_urgent)
                                                <span class="badge badge-red text-[10px] uppercase">Urgent</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-xs text-gray-700">{{ $latestSentimentFlag->rationale }}</p>
                                        @if($latestSentimentFlag->source_excerpt)
                                            <div class="mt-2 rounded-md border border-amber-200 bg-white p-2.5">
                                                <p class="text-xs italic leading-relaxed text-gray-600">"{{ $latestSentimentFlag->source_excerpt }}"</p>
                                            </div>
                                        @endif
                                        <p class="mt-2 text-[11px] text-gray-500">Flagged: {{ $latestSentimentFlag->flagged_at?->format('d M Y, h:i A') }}</p>
                                    @else
                                        <p class="mt-2 text-xs text-gray-600">No sentiment snapshot available yet. Trigger analysis to classify the latest inbound communication.</p>
                                    @endif
                                </div>
                                <form action="{{ route('crm.leads.sentiment.recalculate', $lead->uuid) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-secondary-sm whitespace-nowrap" aria-label="Recalculate inbound sentiment">Run Sentiment Scan</button>
                                </form>
                            </div>
                        </div>

                        {{-- Manual override form --}}
                        @can('override', $lead)
                        <div x-data="{ overrideOpen: false }">
                            <button type="button" @click="overrideOpen = !overrideOpen"
                                    class="btn-secondary-sm">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Override Score
                            </button>

                            <div x-show="overrideOpen"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-5"
                                 style="display:none">
                                <h4 class="mb-3 text-sm font-semibold text-amber-900">Manual Score Override</h4>
                                <p class="mb-4 text-xs text-amber-700">
                                    BRD: CRM-LQ-007 — Override the system-calculated score. A reason is required and will be recorded in the audit log.
                                </p>
                                <form action="{{ route('crm.leads.score-override', $lead->uuid) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="override_score" class="label">
                                                Override Score (0–100) <span class="text-red-500">*</span>
                                            </label>
                                            <input id="override_score" name="override_score" type="number"
                                                   min="0" max="100"
                                                   value="{{ old('override_score', $lead->lead_score) }}"
                                                   class="input-field {{ $errors->has('override_score') ? 'border-red-500' : '' }}"
                                                   required>
                                            @error('override_score')
                                                <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label for="reason" class="label">
                                            Reason for Override <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="reason" name="reason" rows="3"
                                                  placeholder="Explain why this score is being manually overridden…"
                                                  class="input-field resize-none {{ $errors->has('reason') ? 'border-red-500' : '' }}"
                                                  required maxlength="500">{{ old('reason') }}</textarea>
                                        @error('reason')
                                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button type="submit" class="btn-primary-sm">Apply Override</button>
                                        <button type="button" @click="overrideOpen = false" class="btn-secondary-sm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        {{-- Override history --}}
                        @if($scoreOverrides->isNotEmpty())
                        <div>
                            <h3 class="mb-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Score Override History</h3>
                            <div class="overflow-hidden rounded-xl border border-gray-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="border-b border-gray-100 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                                        <tr>
                                            <th class="px-4 py-2.5">Date</th>
                                            <th class="px-4 py-2.5 text-right">Before</th>
                                            <th class="px-4 py-2.5 text-right">After</th>
                                            <th class="px-4 py-2.5">Override By</th>
                                            <th class="px-4 py-2.5">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($scoreOverrides as $override)
                                        <tr>
                                            <td class="px-4 py-2.5 font-mono text-gray-500 whitespace-nowrap">{{ $override->created_at?->format('d M Y, h:i A') }}</td>
                                            <td class="px-4 py-2.5 text-right font-bold text-gray-400">{{ $override->previous_score }}</td>
                                            <td class="px-4 py-2.5 text-right font-bold text-indigo-600">{{ $override->overridden_score }}</td>
                                            <td class="px-4 py-2.5 font-semibold text-gray-700 whitespace-nowrap">{{ $override->overriddenBy?->name ?? '—' }}</td>
                                            <td class="px-4 py-2.5 text-gray-500 max-w-xs truncate">{{ $override->reason }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @else
                        <p class="text-xs text-gray-400">No score overrides recorded for this lead.</p>
                        @endif

                        {{-- BRD: CRM-LQ-009 — Counsellor questionnaire response capture for this lead --}}
                        @can('crm.questionnaires.respond')
                        @if($activeQuestionnaires->isNotEmpty())
                            <div class="space-y-4">
                                <h3 class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Qualification Questionnaires</h3>
                                @foreach($activeQuestionnaires as $questionnaire)
                                    @php
                                        $savedResponse = $responseByQuestionnaireId->get($questionnaire->id);
                                        $savedValues = is_array($savedResponse?->responses) ? $savedResponse->responses : [];
                                    @endphp
                                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                                        <div class="mb-3 flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-gray-900">{{ $questionnaire->name }}</p>
                                            @if($savedResponse)
                                                <span class="badge badge-green text-[10px]">Response Saved</span>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ route('crm.scoring.questionnaires.responses.store', ['questionnaire' => $questionnaire->uuid, 'lead' => $lead->uuid]) }}" class="space-y-3">
                                            @csrf
                                            @foreach(($questionnaire->questions ?? []) as $question)
                                                @php
                                                    $questionKey = $question['key'] ?? null;
                                                    $questionLabel = $question['label'] ?? $questionKey;
                                                    $questionType = $question['type'] ?? 'text';
                                                    $required = (bool)($question['required'] ?? false);
                                                @endphp
                                                @if($questionKey)
                                                    <div>
                                                        <label class="label" for="q_{{ $questionnaire->id }}_{{ $questionKey }}">{{ $questionLabel }}@if($required) <span class="text-red-500">*</span>@endif</label>
                                                        @if($questionType === 'select' && is_array($question['options'] ?? null))
                                                            <select id="q_{{ $questionnaire->id }}_{{ $questionKey }}" name="responses[{{ $questionKey }}]" class="input-field" @if($required) required @endif>
                                                                <option value="">Select</option>
                                                                @foreach($question['options'] as $option)
                                                                    <option value="{{ $option }}" @selected((string)($savedValues[$questionKey] ?? '') === (string)$option)>{{ $option }}</option>
                                                                @endforeach
                                                            </select>
                                                        @elseif($questionType === 'boolean')
                                                            <select id="q_{{ $questionnaire->id }}_{{ $questionKey }}" name="responses[{{ $questionKey }}]" class="input-field" @if($required) required @endif>
                                                                <option value="">Select</option>
                                                                <option value="yes" @selected((string)($savedValues[$questionKey] ?? '') === 'yes')>Yes</option>
                                                                <option value="no" @selected((string)($savedValues[$questionKey] ?? '') === 'no')>No</option>
                                                            </select>
                                                        @else
                                                            <input id="q_{{ $questionnaire->id }}_{{ $questionKey }}" type="text" name="responses[{{ $questionKey }}]" class="input-field" value="{{ $savedValues[$questionKey] ?? '' }}" @if($required) required @endif>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                            <div class="flex justify-end">
                                                <button type="submit" class="btn-secondary-sm">Save Responses</button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @endcan

                    </div>{{-- end scoring tab panel --}}
