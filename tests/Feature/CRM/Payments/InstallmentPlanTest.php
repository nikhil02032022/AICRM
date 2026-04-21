<?php

declare(strict_types=1);

// BRD: CRM-FM-009 — Installment plan validation & application mapping.

use App\Enums\CRM\Payments\FeeType;
use App\Enums\CRM\Payments\InstallmentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\FeeInstallmentPlan;
use App\Services\CRM\Payments\ApplicationInstallmentService;
use App\Services\CRM\Payments\FeeInstallmentPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects plans whose schedule does not sum to 100', function () {
    $inst = Institution::factory()->create();
    $this->expectException(InvalidArgumentException::class);

    app(FeeInstallmentPlanService::class)->create([
        'institution_id' => $inst->id,
        'name' => 'Bad plan',
        'fee_type' => FeeType::TUITION_ADVANCE->value,
        'total_amount' => 1000,
        'schedule' => [
            ['sequence' => 1, 'percent' => 40, 'due_offset_days' => 0],
            ['sequence' => 2, 'percent' => 50, 'due_offset_days' => 30],
        ],
    ]);
});

it('applies a plan to an application and produces schedule rows', function () {
    $institution = Institution::factory()->create();
    $programme = CrmProgramme::factory()->for($institution)->create();
    $lead = Lead::factory()->for($institution)->create();
    $application = Application::factory()
        ->for($lead, 'lead')->for($institution)
        ->create(['programme_id' => $programme->id]);

    $plan = FeeInstallmentPlan::factory()->create([
        'institution_id' => $institution->id,
        'total_amount'   => 100000,
    ]);

    $rows = app(ApplicationInstallmentService::class)->applyPlan($application, $plan);

    expect($rows)->toHaveCount(2);
    expect((float) $rows[0]->amount)->toBe(50000.00);
    expect($rows[0]->status)->toBe(InstallmentStatus::PENDING);
});
