<?php

declare(strict_types=1);

// BRD: CRM-AP-016 — ERP Student Master conversion lifecycle tests

use App\Enums\CRM\ApplicationStatus;
use App\Events\CRM\ErpConversionSucceededEvent;
use App\Events\CRM\ErpConversionFailedEvent;
use App\Jobs\CRM\ConvertToErpStudentJob;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Lead;
use App\Models\CRM\OfferLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Event::fake();

    $this->user = User::factory()->create(['institution_id' => 1]);
    $this->lead = Lead::factory()->create(['institution_id' => 1]);
    $this->programme = CrmProgramme::factory()->create(['institution_id' => 1]);

    $this->application = Application::factory()
        ->for($this->lead, 'lead')
        ->for($this->programme, 'programme')
        ->create([
            'institution_id' => 1,
            'status' => ApplicationStatus::OFFER_ACCEPTED,
        ]);

    // Accepted offer letter required for conversion eligibility
    $this->offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id'       => 1,
            'status'               => 'accepted',
            'acceptance_recorded_at' => now(),
            'expires_at'           => now()->addDays(30),
        ]);
});

it('can trigger ERP conversion for OFFER_ACCEPTED application', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(
        route('api.v1.crm.applications.conversion.trigger', $this->application->uuid)
    );

    $response->assertStatus(202)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('application_conversion_logs', [
        'application_uuid' => $this->application->uuid,
        'lead_uuid'        => $this->lead->uuid,
        'status'           => 'pending',
        'institution_id'   => 1,
    ]);

    Queue::assertPushed(ConvertToErpStudentJob::class);
});

it('dispatches ConvertToErpStudentJob with correct institution on trigger', function () {
    $this->actingAs($this->user);

    $this->postJson(route('api.v1.crm.applications.conversion.trigger', $this->application->uuid));

    Queue::assertPushed(ConvertToErpStudentJob::class, fn ($job) => $job->institutionId === 1);
});

it('cannot trigger conversion when application is not OFFER_ACCEPTED', function () {
    $this->application->update(['status' => ApplicationStatus::SHORTLISTED]);
    $this->actingAs($this->user);

    $response = $this->postJson(
        route('api.v1.crm.applications.conversion.trigger', $this->application->uuid)
    );

    $response->assertStatus(422);
    Queue::assertNothingPushed();
});

it('cannot trigger conversion when successful conversion log already exists', function () {
    ApplicationConversionLog::create([
        'uuid'             => \Illuminate\Support\Str::uuid()->toString(),
        'institution_id'   => 1,
        'application_uuid' => $this->application->uuid,
        'lead_uuid'        => $this->lead->uuid,
        'status'           => 'success',
        'erp_student_id'   => 'ERP-001',
        'attempted_at'     => now(),
        'completed_at'     => now(),
        'retry_count'      => 0,
    ]);

    $this->actingAs($this->user);

    $response = $this->postJson(
        route('api.v1.crm.applications.conversion.trigger', $this->application->uuid)
    );

    $response->assertStatus(422);
    Queue::assertNothingPushed();
});

it('conversion job transitions application to ENROLLED on ERP success', function () {
    Queue::restore(); // allow actual job execution
    Event::restore(); // allow real events

    config(['services.a2a_erp.base_url' => 'https://erp.example.com']);

    Http::fake([
        'erp.example.com/api/v1/students' => Http::response([
            'data' => ['student_id' => 'ERP-STUDENT-001'],
        ], 200),
    ]);

    $log = ApplicationConversionLog::create([
        'uuid'               => \Illuminate\Support\Str::uuid()->toString(),
        'institution_id'     => 1,
        'application_uuid'   => $this->application->uuid,
        'lead_uuid'          => $this->lead->uuid,
        'converted_by_user_id' => $this->user->id,
        'status'             => 'pending',
        'attempted_at'       => now(),
        'retry_count'        => 0,
        'conversion_payload' => [
            'first_name'           => 'Test',
            'last_name'            => 'User',
            'email'                => 'test@example.com',
            'mobile'               => '9999999999',
            'programme_code'       => 'MBA',
            'campus_code'          => 'MUM',
            'admission_year'       => 2026,
            'crm_application_uuid' => $this->application->uuid,
        ],
    ]);

    (new ConvertToErpStudentJob($log->uuid, 1))->handle(
        app(\App\Services\CRM\Application\ApplicationPipelineService::class)
    );

    expect($this->application->refresh()->status)->toBe(ApplicationStatus::ENROLLED);
    expect($log->refresh()->status)->toBe('success');
    expect($log->refresh()->erp_student_id)->toBe('ERP-STUDENT-001');
});

it('conversion job records failed log on ERP API error', function () {
    Queue::restore();

    config(['services.a2a_erp.base_url' => 'https://erp.example.com']);

    Http::fake([
        'erp.example.com/api/v1/students' => Http::response([], 500),
    ]);

    $log = ApplicationConversionLog::create([
        'uuid'               => \Illuminate\Support\Str::uuid()->toString(),
        'institution_id'     => 1,
        'application_uuid'   => $this->application->uuid,
        'lead_uuid'          => $this->lead->uuid,
        'converted_by_user_id' => $this->user->id,
        'status'             => 'pending',
        'attempted_at'       => now(),
        'retry_count'        => 0,
        'conversion_payload' => ['crm_application_uuid' => $this->application->uuid],
    ]);

    (new ConvertToErpStudentJob($log->uuid, 1))->handle(
        app(\App\Services\CRM\Application\ApplicationPipelineService::class)
    );

    expect($log->refresh()->status)->toBe('failed');
    expect($log->refresh()->retry_count)->toBe(1);
    expect($log->refresh()->error_message)->not->toBeNull();
    // Application should remain OFFER_ACCEPTED (not enrolled)
    expect($this->application->refresh()->status)->toBe(ApplicationStatus::OFFER_ACCEPTED);
});

it('can manually retry eligible failed conversion via API', function () {
    $log = ApplicationConversionLog::create([
        'uuid'             => \Illuminate\Support\Str::uuid()->toString(),
        'institution_id'   => 1,
        'application_uuid' => $this->application->uuid,
        'lead_uuid'        => $this->lead->uuid,
        'status'           => 'failed',
        'attempted_at'     => now()->subHour(),
        'retry_count'      => 1,
        'next_retry_at'    => now()->subMinutes(5),
        'conversion_payload' => [],
        'error_message'    => 'ERP API timeout',
    ]);

    $this->actingAs($this->user);

    $response = $this->postJson(
        route('api.v1.crm.conversions.retry', $log->uuid)
    );

    $response->assertStatus(202)->assertJson(['success' => true]);
    Queue::assertPushed(ConvertToErpStudentJob::class);
});

it('cannot retry a successful conversion', function () {
    $log = ApplicationConversionLog::create([
        'uuid'             => \Illuminate\Support\Str::uuid()->toString(),
        'institution_id'   => 1,
        'application_uuid' => $this->application->uuid,
        'lead_uuid'        => $this->lead->uuid,
        'status'           => 'success',
        'erp_student_id'   => 'ERP-001',
        'attempted_at'     => now()->subHour(),
        'completed_at'     => now()->subHour(),
        'retry_count'      => 0,
        'conversion_payload' => [],
    ]);

    $this->actingAs($this->user);

    $response = $this->postJson(
        route('api.v1.crm.conversions.retry', $log->uuid)
    );

    $response->assertStatus(422);
    Queue::assertNothingPushed();
});

it('conversion job dispatches ErpConversionSucceededEvent on success', function () {
    Queue::restore();
    Event::fake([ErpConversionSucceededEvent::class, ErpConversionFailedEvent::class]);

    config(['services.a2a_erp.base_url' => 'https://erp.example.com']);

    Http::fake([
        'erp.example.com/api/v1/students' => Http::response([
            'data' => ['student_id' => 'ERP-EVT-001'],
        ], 200),
    ]);

    $log = ApplicationConversionLog::create([
        'uuid'               => \Illuminate\Support\Str::uuid()->toString(),
        'institution_id'     => 1,
        'application_uuid'   => $this->application->uuid,
        'lead_uuid'          => $this->lead->uuid,
        'converted_by_user_id' => $this->user->id,
        'status'             => 'pending',
        'attempted_at'       => now(),
        'retry_count'        => 0,
        'conversion_payload' => ['crm_application_uuid' => $this->application->uuid],
    ]);

    (new ConvertToErpStudentJob($log->uuid, 1))->handle(
        app(\App\Services\CRM\Application\ApplicationPipelineService::class)
    );

    Event::assertDispatched(ErpConversionSucceededEvent::class, fn ($e) =>
        $e->erpStudentId === 'ERP-EVT-001'
    );
});

it('API lists conversion logs with status filter', function () {
    // Create logs with different statuses
    foreach (['success', 'failed', 'pending'] as $status) {
        ApplicationConversionLog::create([
            'uuid'             => \Illuminate\Support\Str::uuid()->toString(),
            'institution_id'   => 1,
            'application_uuid' => $this->application->uuid,
            'lead_uuid'        => $this->lead->uuid,
            'status'           => $status,
            'attempted_at'     => now(),
            'retry_count'      => 0,
            'conversion_payload' => [],
        ]);
    }

    $this->actingAs($this->user);

    $response = $this->getJson(
        route('api.v1.crm.conversions.index', ['status' => 'success'])
    );

    $response->assertOk()->assertJson(['success' => true]);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('success');
});
