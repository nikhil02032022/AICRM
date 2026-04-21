<?php

declare(strict_types=1);

// BRD: CRM-AP-018 — ERP onboarding workflow trigger tests

use App\Enums\CRM\ApplicationStatus;
use App\Events\CRM\ErpConversionSucceededEvent;
use App\Jobs\CRM\TriggerErpOnboardingWorkflowsJob;
use App\Listeners\CRM\HandleErpConversionSucceeded;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Erp\ErpOnboardingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeOnboardingFixtures(): array
{
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['institution_id' => $institution->id]);
    $lead = Lead::factory()->for($institution)->create();
    $programme = CrmProgramme::factory()->for($institution)->create();

    $application = Application::factory()
        ->for($lead, 'lead')
        ->for($programme, 'programme')
        ->for($institution)
        ->create(['status' => ApplicationStatus::ENROLLED]);

    $logUuid = Str::uuid()->toString();
    ApplicationConversionLog::withoutGlobalScopes()->insert([
        'uuid'                 => $logUuid,
        'institution_id'       => $institution->id,
        'application_uuid'     => $application->uuid,
        'lead_uuid'            => $lead->uuid,
        'converted_by_user_id' => $user->id,
        'status'               => 'success',
        'erp_student_id'       => 'ERP-ONBOARD-001',
        'attempted_at'         => now(),
        'completed_at'         => now(),
        'retry_count'          => 0,
        'conversion_payload'   => json_encode(['crm_application_uuid' => $application->uuid]),
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    $log = ApplicationConversionLog::withoutGlobalScopes()->where('uuid', $logUuid)->first();

    return compact('institution', 'user', 'lead', 'programme', 'application', 'log', 'logUuid');
}

it('ErpConversionSucceededEvent listener dispatches TriggerErpOnboardingWorkflowsJob', function () {
    Queue::fake();
    ['institution' => $institution, 'application' => $application] = makeOnboardingFixtures();

    $event = new ErpConversionSucceededEvent($application, 'ERP-ONBOARD-001');
    app(HandleErpConversionSucceeded::class)->handle($event);

    Queue::assertPushed(TriggerErpOnboardingWorkflowsJob::class, fn ($job) =>
        $job->erpStudentId === 'ERP-ONBOARD-001'
        && $job->institutionId === $institution->id
    );
});

it('listener does not dispatch job when no success conversion log exists', function () {
    Queue::fake();
    ['application' => $application, 'logUuid' => $logUuid] = makeOnboardingFixtures();

    // Delete the log so no success log exists for this application
    ApplicationConversionLog::withoutGlobalScopes()->where('uuid', $logUuid)->delete();

    $event = new ErpConversionSucceededEvent($application, 'ERP-ONBOARD-001');
    app(HandleErpConversionSucceeded::class)->handle($event);

    Queue::assertNothingPushed();
});

it('onboarding job calls all three ERP workflow endpoints', function () {
    ['institution' => $institution, 'log' => $log] = makeOnboardingFixtures();

    config(['services.a2a_erp.base_url' => 'https://erp.example.com']);

    Http::fake([
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/id-card'       => Http::response([], 200),
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/lms-enrol'     => Http::response([], 200),
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/hostel-prompt' => Http::response([], 200),
    ]);

    (new TriggerErpOnboardingWorkflowsJob($log->uuid, $institution->id, 'ERP-ONBOARD-001'))
        ->handle(app(ErpOnboardingWorkflowService::class));

    Http::assertSentCount(3);
});

it('onboarding job updates onboarding_triggered_at and onboarding_status on conversion log', function () {
    ['institution' => $institution, 'log' => $log] = makeOnboardingFixtures();

    config(['services.a2a_erp.base_url' => 'https://erp.example.com']);

    Http::fake([
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/id-card'       => Http::response([], 200),
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/lms-enrol'     => Http::response([], 200),
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/hostel-prompt' => Http::response([], 200),
    ]);

    (new TriggerErpOnboardingWorkflowsJob($log->uuid, $institution->id, 'ERP-ONBOARD-001'))
        ->handle(app(ErpOnboardingWorkflowService::class));

    $updated = ApplicationConversionLog::withoutGlobalScopes()->where('uuid', $log->uuid)->first();

    expect($updated->onboarding_triggered_at)->not->toBeNull();
    // MySQL normalizes JSON key order alphabetically
    expect($updated->onboarding_status['id_card'])->toBeTrue();
    expect($updated->onboarding_status['lms_enrolment'])->toBeTrue();
    expect($updated->onboarding_status['hostel_prompt'])->toBeTrue();
});

it('onboarding job stores partial results when some ERP endpoints fail', function () {
    ['institution' => $institution, 'log' => $log] = makeOnboardingFixtures();

    config(['services.a2a_erp.base_url' => 'https://erp.example.com']);

    Http::fake([
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/id-card'       => Http::response([], 200),
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/lms-enrol'     => Http::response([], 500),
        'erp.example.com/api/v1/students/ERP-ONBOARD-001/hostel-prompt' => Http::response([], 200),
    ]);

    (new TriggerErpOnboardingWorkflowsJob($log->uuid, $institution->id, 'ERP-ONBOARD-001'))
        ->handle(app(ErpOnboardingWorkflowService::class));

    $updated = ApplicationConversionLog::withoutGlobalScopes()->where('uuid', $log->uuid)->first();

    expect($updated->onboarding_status['id_card'])->toBeTrue();
    expect($updated->onboarding_status['lms_enrolment'])->toBeFalse();
    expect($updated->onboarding_status['hostel_prompt'])->toBeTrue();
    expect($updated->onboarding_triggered_at)->not->toBeNull();
});
