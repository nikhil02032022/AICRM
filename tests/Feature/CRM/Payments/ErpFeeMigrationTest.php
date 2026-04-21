<?php

declare(strict_types=1);

// BRD: CRM-FM-013 — ERP fee migration on conversion

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Events\CRM\ErpConversionSucceededEvent;
use App\Jobs\CRM\Payments\MigrateConvertedApplicationFeesJob;
use App\Listeners\CRM\Payments\MigrateFeesOnApplicationConverted;
use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Models\User;
use App\Services\CRM\Payments\ErpFeeMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('listener dispatches MigrateConvertedApplicationFeesJob on ErpConversionSucceededEvent', function () {
    Bus::fake();

    $institution = Institution::factory()->create();
    $user        = User::factory()->create(['institution_id' => $institution->id]);
    $lead        = Lead::factory()->for($institution)->create();
    $application = Application::factory()->for($lead, 'lead')->for($institution)
        ->create(['status' => ApplicationStatus::ENROLLED]);

    ApplicationConversionLog::withoutGlobalScopes()->insert([
        'uuid'                 => Str::uuid()->toString(),
        'institution_id'       => $institution->id,
        'application_uuid'     => $application->uuid,
        'lead_uuid'            => $lead->uuid,
        'converted_by_user_id' => $user->id,
        'status'               => 'success',
        'erp_student_id'       => 'ERP-FEE-001',
        'attempted_at'         => now(),
        'completed_at'         => now(),
        'retry_count'          => 0,
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    (new MigrateFeesOnApplicationConverted)
        ->handle(new ErpConversionSucceededEvent($application, 'ERP-FEE-001'));

    Bus::assertDispatched(MigrateConvertedApplicationFeesJob::class);
});

it('builds fee payload from successful and refunded transactions', function () {
    $institution = Institution::factory()->create();
    $lead        = Lead::factory()->for($institution)->create();
    $application = Application::factory()->for($lead, 'lead')->for($institution)->create();

    PaymentTransaction::factory()->create([
        'institution_id' => $institution->id,
        'application_uuid' => $application->uuid,
        'amount' => 1500,
        'status' => PaymentStatus::SUCCESS->value,
        'confirmed_at' => now(),
    ]);
    PaymentTransaction::factory()->create([
        'institution_id' => $institution->id,
        'application_uuid' => $application->uuid,
        'amount' => 500,
        'status' => PaymentStatus::REFUNDED->value,
    ]);

    $payload = app(ErpFeeMigrationService::class)->buildPayload($application);

    expect($payload['crm_application_uuid'])->toBe($application->uuid)
        ->and($payload['transactions'])->toHaveCount(2)
        ->and($payload['total_collected'])->toBe(1500.0)
        ->and($payload['total_refunded'])->toBe(500.0);
});
