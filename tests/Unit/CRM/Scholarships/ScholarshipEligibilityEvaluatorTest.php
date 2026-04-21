<?php

declare(strict_types=1);

// BRD: CRM-FM-007 — Scholarship eligibility evaluator unit tests.

use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Scholarships\ScholarshipCategory;
use App\Models\CRM\Scholarships\ScholarshipEligibilityRule;
use App\Services\CRM\Scholarships\ScholarshipEligibilityEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function scholarshipApp(int $leadScore = 90): Application
{
    $institution = Institution::factory()->create();
    $programme = CrmProgramme::factory()->for($institution)->create();
    $lead = Lead::factory()->for($institution)->create(['lead_score' => $leadScore]);

    return Application::factory()
        ->for($lead, 'lead')
        ->for($institution)
        ->create(['programme_id' => $programme->id]);
}

it('returns categories with no rules', function () {
    $app = scholarshipApp();
    ScholarshipCategory::factory()->create([
        'institution_id' => $app->institution_id,
        'programme_id'   => $app->programme_id,
        'is_active'      => true,
    ]);

    $result = app(ScholarshipEligibilityEvaluator::class)->evaluate($app);

    expect($result)->toHaveCount(1);
});

it('respects programme scoping', function () {
    $app = scholarshipApp();
    ScholarshipCategory::factory()->create([
        'institution_id' => $app->institution_id,
        'programme_id'   => 99999,
        'is_active'      => true,
    ]);

    $result = app(ScholarshipEligibilityEvaluator::class)->evaluate($app);

    expect($result)->toHaveCount(0);
});

it('ignores inactive categories', function () {
    $app = scholarshipApp();
    ScholarshipCategory::factory()->create([
        'institution_id' => $app->institution_id,
        'programme_id'   => $app->programme_id,
        'is_active'      => false,
    ]);

    $result = app(ScholarshipEligibilityEvaluator::class)->evaluate($app);

    expect($result)->toHaveCount(0);
});

it('only matches when rule operator passes', function () {
    $app = scholarshipApp(90);
    $cat = ScholarshipCategory::factory()->create([
        'institution_id' => $app->institution_id,
        'programme_id'   => $app->programme_id,
    ]);
    ScholarshipEligibilityRule::factory()->create([
        'institution_id'          => $app->institution_id,
        'scholarship_category_id' => $cat->id,
        'attribute'               => 'lead.lead_score',
        'operator'                => 'gte',
        'value'                   => [85],
    ]);

    $match = app(ScholarshipEligibilityEvaluator::class)->evaluate($app);
    expect($match)->toHaveCount(1);

    ScholarshipEligibilityRule::query()->update(['value' => json_encode([95])]);
    $noMatch = app(ScholarshipEligibilityEvaluator::class)->evaluate($app->fresh());
    expect($noMatch)->toHaveCount(0);
});

it('excludes a category whose only rule references a non-whitelisted attribute', function () {
    // Security-conservative default: unknown attributes are skipped; a category
    // with NO evaluable rules fails closed rather than passing silently.
    $app = scholarshipApp();
    $cat = ScholarshipCategory::factory()->create([
        'institution_id' => $app->institution_id,
        'programme_id'   => $app->programme_id,
    ]);
    ScholarshipEligibilityRule::factory()->create([
        'institution_id'          => $app->institution_id,
        'scholarship_category_id' => $cat->id,
        'attribute'               => 'application.not_whitelisted',
        'operator'                => 'gte',
        'value'                   => [1],
    ]);

    $result = app(ScholarshipEligibilityEvaluator::class)->evaluate($app);
    expect($result)->toHaveCount(0);
});
