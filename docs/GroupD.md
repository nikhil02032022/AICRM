Ready for review
Select text to add comments on the plan
Group D — Lead Scoring Engine + Temperature + Override
Context
Groups A–C have built lead capture, web forms, and digital channel imports. Leads now exist in the DB but their lead_score (0–100) and temperature (Hot/Warm/Cold) are set by a stub inside RecalculateLeadScoreJob::calculateBasicScore() that only checks profile completeness and source. Group D replaces this stub with a full, configurable scoring engine (BRD §8.2, LQ-001–008), adds per-institution threshold configuration, event-driven recalculation, automated action triggers on score changes, and a manual override capability with a documented audit trail.

Scope (BRD Req IDs)
Req	Feature
LQ-001	Rule-based engine 0–100
LQ-002	5 scoring dimensions: demographics, course match, engagement, response time, geography
LQ-004	Score recalculated on every qualifying activity (event-driven)
LQ-005	Per-institution configurable Hot/Warm/Cold thresholds
LQ-006	Score threshold → automated actions (counsellor alert, nurture sequence)
LQ-007	Manual score override with documented reason + audit history
LQ-008	Lead quality grading by source (reportable via repository query)
Critical Files to Modify
File	Change
app/Jobs/CRM/RecalculateLeadScoreJob.php	Replace calculateBasicScore() stub with LeadScoringEngine call; fire LeadScoreChangedEvent on delta
app/Enums/CRM/LeadTemperature.php	Add fromScore(int $score, ?InstitutionScoringConfig $config) overload to use institution thresholds
app/Providers/AppServiceProvider.php	Register LeadScoreChangedEvent listeners (NotifyCounsellorOfHotLead, EnqueueNurtureSequence)
routes/web.php	Add score-override POST + scoring-config GET/PUT routes
routes/api.php	Add override + config API endpoints
resources/views/crm/leads/show.blade.php	Add override form + score history panel
Implementation Steps
Step 1 — Migrations (2 new tables)
database/migrations/2026_04_09_100001_create_institution_scoring_configs_table.php

institution_scoring_configs
  id, institution_id (FK institutions, unique)
  hot_threshold   unsigned tinyint default 75
  warm_threshold  unsigned tinyint default 50
  weights         JSON  -- {"demographics":20,"course_match":25,"engagement":25,"response_time":15,"geography":15}
  created_at, updated_at
database/migrations/2026_04_09_100002_create_lead_score_overrides_table.php

lead_score_overrides
  id, uuid (unique)
  lead_id (FK leads)
  previous_score  unsigned tinyint
  override_score  unsigned tinyint
  reason          text
  overridden_by   FK users
  expires_at      timestamp nullable  -- null = permanent
  created_at, updated_at
  indexes: lead_id, [lead_id, created_at desc]
Step 2 — Models
app/Models/CRM/InstitutionScoringConfig.php

$fillable: institution_id, hot_threshold, warm_threshold, weights
$casts: weights → array
Accessor: defaultWeights() static returning the JSON defaults
app/Models/CRM/LeadScoreOverride.php

$fillable: uuid, lead_id, previous_score, override_score, reason, overridden_by, expires_at
$casts: expires_at → datetime
Relations: lead(), overriddenBy() (User)
Scope: active() — where expires_at is null or > now()
Step 3 — Scorer Interface + 5 Dimension Scorers
app/Contracts/CRM/ScorerInterface.php

interface ScorerInterface {
    public function score(Lead $lead, InstitutionScoringConfig $config): int; // 0–100 raw
    public function dimension(): string; // e.g. 'demographics'
}
app/Services/CRM/Scoring/Scorers/DemographicsScorer.php

Points for: email present (+20), mobile verified (+20), city (+15), state (+15), nationality (+10), notes/academic (+20)
Max 100 raw → engine applies weight
app/Services/CRM/Scoring/Scorers/CourseMatchScorer.php

Points for: ≥1 programme interest (+50), ≥2 interests (+30), campus specified (+20)
Uses Lead::programmeInterests() relation count
app/Services/CRM/Scoring/Scorers/EngagementScorer.php

Currently: email_opened_count, whatsapp_read_count, form_revisit_count cols don't exist yet (those come from Group F)
Stub safely: uses lead->source as proxy — WhatsApp/IVR/Walk-in = higher engagement base
Add // TODO: Group F — wire real engagement events comment
Returns 0–100 based on source quality proxy (mirrors existing stub logic but as a class)
app/Services/CRM/Scoring/Scorers/ResponseTimeScorer.php

assigned_counsellor_id set (counsellor responded) → check time delta since created_at
< 1hr: 100, 1–4hr: 80, 4–24hr: 60, >24hr: 30, unassigned: 0
app/Services/CRM/Scoring/Scorers/GeographyScorer.php

Config-based: institution's campus_city list (pulled from institution model or JSON config)
Lead city matches campus city: 100, same state: 60, no city: 20
Step 4 — LeadScoringEngine Service
app/Services/CRM/Scoring/LeadScoringEngine.php

class LeadScoringEngine {
    public function __construct(private array $scorers) {} // injected via service provider

    public function calculate(Lead $lead): int {
        // 1. Load or default institution scoring config
        $config = InstitutionScoringConfig::firstOrCreate(
            ['institution_id' => $lead->institution_id],
            ['hot_threshold' => 75, 'warm_threshold' => 50, 'weights' => InstitutionScoringConfig::defaultWeights()]
        );
        // 2. Check active manual override
        $override = LeadScoreOverride::where('lead_id', $lead->id)->active()->latest()->first();
        if ($override) return $override->override_score;
        // 3. Run each scorer, apply weight
        $weights = $config->weights;
        $total = 0;
        foreach ($this->scorers as $scorer) {
            $dim = $scorer->dimension();
            $raw = $scorer->score($lead, $config); // 0-100
            $total += ($raw / 100) * ($weights[$dim] ?? 0);
        }
        return (int) min(100, round($total));
    }

    public function temperatureFor(int $score, InstitutionScoringConfig $config): LeadTemperature {
        return match(true) {
            $score >= $config->hot_threshold  => LeadTemperature::HOT,
            $score >= $config->warm_threshold => LeadTemperature::WARM,
            default                            => LeadTemperature::COLD,
        };
    }
}
Register in a new app/Providers/CRM/CrmScoringServiceProvider.php:

Bind LeadScoringEngine as singleton with all 5 scorers wired
Register in bootstrap/providers.php
Step 5 — Replace RecalculateLeadScoreJob Stub
app/Jobs/CRM/RecalculateLeadScoreJob.php — replace calculateBasicScore():

public function handle(LeadScoringEngine $engine): void {
    $lead = Lead::withoutGlobalScopes()->findOrFail($this->leadUuid); // use uuid
    $previousScore = $lead->lead_score;
    $newScore = $engine->calculate($lead);
    $config = InstitutionScoringConfig::firstOrCreate([...]);
    $newTemp = $engine->temperatureFor($newScore, $config);

    $lead->updateQuietly(['lead_score' => $newScore, 'temperature' => $newTemp]);

    if ($previousScore !== $newScore) {
        LeadScoreChangedEvent::dispatch($lead, $previousScore, $newScore, $newTemp);
    }
    Log::info('lead.score_recalculated', [...non-PII...]);
}
Remove calculateBasicScore() private method entirely.

Step 6 — LeadScoreChangedEvent + Action Listeners (LQ-006)
app/Events/CRM/LeadScoreChangedEvent.php

public function __construct(
    public readonly Lead $lead,
    public readonly int $previousScore,
    public readonly int $newScore,
    public readonly LeadTemperature $newTemperature,
) {}
app/Listeners/CRM/NotifyCounsellorOfHotLead.php (LQ-006 — Hot → alert)

Fires only when $event->newTemperature === LeadTemperature::HOT AND previous was not HOT
If $lead->assigned_counsellor_id is set: send DB notification + optionally email
Use Laravel's Notification facade (stub mail for now — Group F owns WhatsApp/SMS)
app/Listeners/CRM/EnqueueNurtureSequence.php (LQ-006 — Cold → nurture)

Fires only when $event->newTemperature === LeadTemperature::COLD AND previous was not COLD
Logs intent (Log::info('lead.cold_nurture_enqueued', [...])); actual drip is Group F
Add a nurture_queued_at flag update on the lead (add nullable nurture_queued_at datetime col via migration)
Register both in AppServiceProvider::boot().

Step 7 — Score Override (LQ-007)
app/Http/Requests/Web/CRM/StoreScoreOverrideRequest.php

override_score: required|integer|min:0|max:100
reason: required|string|min:10|max:1000
expires_at: nullable|date|after:now
app/Http/Controllers/Web/CRM/LeadScoreOverrideWebController.php

store(Lead $lead, StoreScoreOverrideRequest $request): creates LeadScoreOverride, dispatches RecalculateLeadScoreJob
destroy(Lead $lead, LeadScoreOverride $override): soft-deletes or expires override immediately
app/Http/Controllers/Api/CRM/LeadScoreOverrideController.php (API mirror)

store() / index() — returns LeadScoreOverrideResource
app/Http/Resources/CRM/LeadScoreOverrideResource.php

app/Policies/CRM/LeadScoreOverridePolicy.php

create: crm.leads.score.override permission
delete: own override OR admin
Add permissions crm.leads.score.override to PermissionSeeder + assign to Institution Admin, Admissions Manager, Senior Counsellor.

Step 8 — Scoring Config Management (LQ-005)
app/Http/Controllers/Web/CRM/ScoringConfigWebController.php

edit(): show config form for auth user's institution
update(UpdateScoringConfigRequest $request): save thresholds + weights, dispatch recalc for ALL institution leads
app/Http/Requests/Web/CRM/UpdateScoringConfigRequest.php

hot_threshold: required|int|min:1|max:100|gt:warm_threshold
warm_threshold: required|int|min:1|max:99
weights.* sum must = 100
app/Http/Controllers/Api/CRM/ScoringConfigController.php + ScoringConfigResource

Step 9 — Routes
routes/web.php — inside crm. group:

// Score overrides (LQ-007)
Route::post('/leads/{lead:uuid}/score-overrides', [LeadScoreOverrideWebController::class, 'store'])
    ->name('leads.score-overrides.store')
    ->middleware('can:crm.leads.score.override');
Route::delete('/leads/{lead:uuid}/score-overrides/{override:uuid}', [LeadScoreOverrideWebController::class, 'destroy'])
    ->name('leads.score-overrides.destroy')
    ->middleware('can:crm.leads.score.override');

// Scoring config (LQ-005)
Route::get('/settings/scoring', [ScoringConfigWebController::class, 'edit'])
    ->name('settings.scoring.edit')
    ->middleware('can:crm.settings.scoring');
Route::put('/settings/scoring', [ScoringConfigWebController::class, 'update'])
    ->name('settings.scoring.update')
    ->middleware('can:crm.settings.scoring');
routes/api.php — inside v1/crm group:

Route::get('leads/{lead:uuid}/score-overrides', [LeadScoreOverrideController::class, 'index']);
Route::post('leads/{lead:uuid}/score-overrides', [LeadScoreOverrideController::class, 'store']);
Route::get('scoring-config', [ScoringConfigController::class, 'show']);
Route::put('scoring-config', [ScoringConfigController::class, 'update']);
Step 10 — Blade Views
resources/views/crm/leads/partials/score-override.blade.php

Override form: numeric input (0–100), textarea for reason, optional expiry date
Score history table: previous score → override score, reason, who, when, active/expired badge
Alpine.js: toggle form visibility
Modify resources/views/crm/leads/show.blade.php

Add "Override Score" button (visible to permitted roles) that toggles the score-override partial
Show "Manually Overridden" banner if active override exists (similar to duplicate detection banner)
resources/views/crm/settings/scoring.blade.php

Threshold sliders/inputs: Hot threshold, Warm threshold
Dimension weights (5 number inputs that sum to 100, with live JS validation)
"Save & Recalculate All Leads" button with warning
Step 11 — LQ-008 (Source Quality Reporting)
app/Repositories/CRM/Lead/EloquentLeadRepository.php — add method:

public function scoreBySource(int $institutionId): Collection
// Returns: LeadSource => [avg_score, total, hot_count, warm_count, cold_count]
app/Http/Controllers/Api/CRM/LeadQualityReportController.php

GET /api/v1/crm/reports/lead-quality-by-source
Returns the above query result as JSON
Middleware: can:crm.reports.view
Step 12 — Additional Migration
2026_04_09_100003_add_nurture_queued_at_to_leads_table.php

$table->timestamp('nurture_queued_at')->nullable()->after('temperature')
New Files Summary
Path	Purpose
database/migrations/2026_04_09_100001_create_institution_scoring_configs_table.php	Threshold + weights config
database/migrations/2026_04_09_100002_create_lead_score_overrides_table.php	Override audit log
database/migrations/2026_04_09_100003_add_nurture_queued_at_to_leads_table.php	Cold lead nurture flag
app/Models/CRM/InstitutionScoringConfig.php	Config model
app/Models/CRM/LeadScoreOverride.php	Override model
app/Contracts/CRM/ScorerInterface.php	Scorer contract
app/Services/CRM/Scoring/LeadScoringEngine.php	Core engine
app/Services/CRM/Scoring/Scorers/DemographicsScorer.php	
app/Services/CRM/Scoring/Scorers/CourseMatchScorer.php	
app/Services/CRM/Scoring/Scorers/EngagementScorer.php	Stub for now
app/Services/CRM/Scoring/Scorers/ResponseTimeScorer.php	
app/Services/CRM/Scoring/Scorers/GeographyScorer.php	
app/Events/CRM/LeadScoreChangedEvent.php	
app/Listeners/CRM/NotifyCounsellorOfHotLead.php	LQ-006 hot action
app/Listeners/CRM/EnqueueNurtureSequence.php	LQ-006 cold action
app/Providers/CRM/CrmScoringServiceProvider.php	Engine singleton
app/Http/Controllers/Web/CRM/LeadScoreOverrideWebController.php	
app/Http/Controllers/Web/CRM/ScoringConfigWebController.php	
app/Http/Controllers/Api/CRM/LeadScoreOverrideController.php	
app/Http/Controllers/Api/CRM/ScoringConfigController.php	
app/Http/Controllers/Api/CRM/LeadQualityReportController.php	LQ-008
app/Http/Requests/Web/CRM/StoreScoreOverrideRequest.php	
app/Http/Requests/Web/CRM/UpdateScoringConfigRequest.php	
app/Http/Resources/CRM/LeadScoreOverrideResource.php	
app/Http/Resources/CRM/ScoringConfigResource.php	
app/Policies/CRM/LeadScoreOverridePolicy.php	
resources/views/crm/leads/partials/score-override.blade.php	
resources/views/crm/settings/scoring.blade.php	
docs/usermanual/lead-scoring.md	User manual
docs/Sprint_Group_D_Lead_Scoring.md	Sprint detail doc
Modified Files
Path	Change
app/Jobs/CRM/RecalculateLeadScoreJob.php	Replace stub with engine call + fire event
app/Enums/CRM/LeadTemperature.php	fromScore() accepts optional config
app/Providers/AppServiceProvider.php	Register 2 new event listeners
bootstrap/providers.php	Add CrmScoringServiceProvider
routes/web.php	4 new routes
routes/api.php	4 new routes
resources/views/crm/leads/show.blade.php	Override form + banner
database/seeders/PermissionSeeder.php	crm.leads.score.override, crm.settings.scoring, crm.reports.view
database/seeders/RoleSeeder.php	Assign new permissions to roles
docs/Phase1_Sprint_Master_Plan.md	Mark Group D complete, add sprint doc link
docs/usermanual/README.md	Add lead-scoring.md entry
User Manual Plan: docs/usermanual/lead-scoring.md
Sections:

Overview — what the scoring engine does, why it matters
Who Can Use Each Feature — role matrix (view score / override / configure thresholds)
Understanding Lead Score — 0–100 scale, what feeds into it (5 dimensions explained in plain language)
Lead Temperature — Hot/Warm/Cold, how thresholds work, badge colours
How Scores Update — when recalculation happens (on create, status change, override)
Manually Overriding a Score — step-by-step with screenshot placeholders
Configuring Thresholds & Weights (Admins only) — sliders, sum-to-100 rule
Automated Alerts — what happens when a lead goes Hot or Cold
Lead Quality by Source Report — how to read it, what to act on
Troubleshooting — score not updating, override not showing, etc.
Verification
Unit tests — tests/Unit/CRM/Scoring/ — one test file per scorer + engine
Assert scorer returns 0–100
Assert engine applies weights correctly
Assert engine respects active override
Assert institution thresholds used over defaults
Feature tests — tests/Feature/CRM/Scoring/
POST /api/v1/crm/leads/{uuid}/score-overrides → 201, lead score updated
DELETE override → score reverts on next recalc
PUT /api/v1/crm/scoring-config → thresholds persisted
LeadScoreChangedEvent dispatched when score changes
NotifyCounsellorOfHotLead fired when temp transitions to HOT
Manual smoke test
Create lead with all fields → score > 50 (Warm)
Create lead with only mobile → score < 30 (Cold)
Override to 80 → temp shows Hot, banner visible
Delete override → next recalc reverts
Change hot_threshold to 90 → same lead now Warm