<?php

declare(strict_types=1);

// BRD: CRM-DM-010 — Completeness weighting.

use App\Enums\CRM\Documents\DocumentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Documents\DocumentChecklistItem;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Documents\DocumentCompletenessCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function completenessFixtures(): array
{
    $institution = Institution::factory()->create();
    $programme = CrmProgramme::factory()->for($institution)->create();
    $lead = Lead::factory()->for($institution)->create();
    $application = Application::factory()
        ->for($lead, 'lead')
        ->for($institution)
        ->create(['programme_id' => $programme->id]);

    $checklist = DocumentChecklist::factory()->create([
        'institution_id' => $institution->id,
        'programme_id'   => $programme->id,
        'is_active'      => true,
    ]);
    $mandatoryA = DocumentChecklistItem::factory()->create([
        'institution_id' => $institution->id,
        'document_checklist_id' => $checklist->id,
        'code' => 'A', 'is_mandatory' => true,
    ]);
    $mandatoryB = DocumentChecklistItem::factory()->create([
        'institution_id' => $institution->id,
        'document_checklist_id' => $checklist->id,
        'code' => 'B', 'is_mandatory' => true,
    ]);
    $optionalC = DocumentChecklistItem::factory()->create([
        'institution_id' => $institution->id,
        'document_checklist_id' => $checklist->id,
        'code' => 'C', 'is_mandatory' => false,
    ]);

    return compact('application', 'mandatoryA', 'mandatoryB', 'optionalC');
}

it('returns 0 when no docs verified', function () {
    ['application' => $app] = completenessFixtures();

    $score = app(DocumentCompletenessCalculator::class)->scoreFor($app);
    expect($score)->toBe(0.0);
});

it('weights mandatory items heavier than optional', function () {
    $f = completenessFixtures();
    /** @var Application $app */
    $app = $f['application'];

    // Only the optional verified.
    ApplicationDocument::factory()->create([
        'institution_id' => $app->institution_id,
        'application_uuid' => $app->uuid,
        'lead_uuid' => $app->lead_uuid,
        'document_checklist_item_id' => $f['optionalC']->id,
        'status' => DocumentStatus::VERIFIED->value,
    ]);

    app(DocumentCompletenessCalculator::class)->invalidate($app);
    $score = app(DocumentCompletenessCalculator::class)->scoreFor($app);

    // total weight = 1 + 1 + 0.25 = 2.25; earned = 0.25 → 11.11%
    expect($score)->toBeGreaterThan(10.0)->toBeLessThan(12.0);
});

it('reaches 100 when all mandatory + optional verified', function () {
    $f = completenessFixtures();
    $app = $f['application'];

    foreach ([$f['mandatoryA'], $f['mandatoryB'], $f['optionalC']] as $item) {
        ApplicationDocument::factory()->create([
            'institution_id' => $app->institution_id,
            'application_uuid' => $app->uuid,
            'lead_uuid' => $app->lead_uuid,
            'document_checklist_item_id' => $item->id,
            'status' => DocumentStatus::VERIFIED->value,
        ]);
    }

    app(DocumentCompletenessCalculator::class)->invalidate($app);
    $score = app(DocumentCompletenessCalculator::class)->scoreFor($app);

    expect($score)->toBe(100.0);
});
