<?php

declare(strict_types=1);

// BRD: EI-008 — Alumni Bridge: trigger ERP sync, dispatch job, mark success/failed, increment referrals

use App\Enums\CRM\AlumniBridgeStatus;
use App\Events\CRM\AlumniBridgeTriggeredEvent;
use App\Jobs\CRM\TriggerAlumniBridgeJob;
use App\Models\CRM\AlumniBridgeLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Integration\AlumniBridgeService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeAlumniBridgeContext(): array
{
    $institution = Institution::create([
        'name' => 'Alumni Uni', 'code' => 'ALU1', 'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Alumni Admin',
        'email' => 'alumni@admin.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $user->givePermissionTo(['crm.integrations.manage']);

    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'first_name' => 'Mohan',
        'last_name' => 'Lal',
        'mobile' => '9222222222',
        'email' => 'mohan@test.com',
        'source' => 'event',
        'lead_score' => 0,
        'temperature' => 'warm',
        'status' => 'converted',
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1.0',
    ]);

    return [$institution, $user, $lead];
}

// ─── Alumni Bridge: trigger creates log and dispatches job ────────────────

test('trigger creates AlumniBridgeLog and dispatches TriggerAlumniBridgeJob (EI-008)', function (): void {
    Queue::fake();
    Event::fake([AlumniBridgeTriggeredEvent::class]);

    [$institution, $user, $lead] = makeAlumniBridgeContext();

    $service = app(AlumniBridgeService::class);

    $log = $service->trigger($institution->id, [
        'lead_id' => $lead->id,
        'erp_student_id' => 'ERP-STU-001',
    ]);

    expect($log)->toBeInstanceOf(AlumniBridgeLog::class)
        ->and($log->status)->toBe(AlumniBridgeStatus::Pending)
        ->and($log->erp_student_id)->toBe('ERP-STU-001');

    Queue::assertPushed(TriggerAlumniBridgeJob::class);
    Event::assertDispatched(AlumniBridgeTriggeredEvent::class);
});

// ─── Alumni Bridge: markSuccess sets status and alumni ID ─────────────────

test('markSuccess updates AlumniBridgeLog to success with alumni ID (EI-008)', function (): void {
    [$institution, $user, $lead] = makeAlumniBridgeContext();

    $service = app(AlumniBridgeService::class);

    $log = AlumniBridgeLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'erp_student_id' => 'STU-001',
        'status' => AlumniBridgeStatus::Triggered,
    ]);

    $service->markSuccess($log, 'ALM-001');

    $log->refresh();

    expect($log->status)->toBe(AlumniBridgeStatus::Success)
        ->and($log->erp_alumni_id)->toBe('ALM-001');
});

// ─── Alumni Bridge: incrementReferrals adds to referrals_count ───────────

test('incrementReferrals adds 1 to referrals_count (EI-008)', function (): void {
    [$institution, $user, $lead] = makeAlumniBridgeContext();

    $service = app(AlumniBridgeService::class);

    $log = AlumniBridgeLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'erp_student_id' => 'STU-002',
        'status' => AlumniBridgeStatus::Success,
        'referrals_count' => 2,
    ]);

    $service->incrementReferrals($log);

    $log->refresh();

    expect($log->referrals_count)->toBe(3);
});

// ─── Alumni Bridge: markFailed sets status to failed ─────────────────────

test('markFailed sets AlumniBridgeLog status to failed (EI-008)', function (): void {
    [$institution, $user, $lead] = makeAlumniBridgeContext();

    $service = app(AlumniBridgeService::class);

    $log = AlumniBridgeLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'erp_student_id' => 'STU-003',
        'status' => AlumniBridgeStatus::Triggered,
    ]);

    $service->markFailed($log);

    $log->refresh();

    expect($log->status)->toBe(AlumniBridgeStatus::Failed);
});
