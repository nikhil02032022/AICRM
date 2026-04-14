<?php

declare(strict_types=1);

// BRD: EI-010 — LMS Auto-Enrolment: trigger job, mark enrolled/failed, increment attempts

use App\Enums\CRM\LmsEnrolmentStatus;
use App\Jobs\CRM\TriggerLmsEnrolmentJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\LmsEnrolmentLog;
use App\Models\User;
use App\Services\CRM\Integration\LmsEnrolmentService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeLmsContext(): array
{
    $institution = Institution::create([
        'name' => 'LMS University', 'code' => 'LMS1', 'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'LMS Admin',
        'email' => 'lms@admin.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $user->givePermissionTo(['crm.integrations.manage']);

    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'first_name' => 'Anita',
        'last_name' => 'Rao',
        'mobile' => '9333333333',
        'email' => 'anita@test.com',
        'source' => 'website',
        'lead_score' => 80,
        'temperature' => 'hot',
        'status' => 'converted',
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1.0',
    ]);

    return [$institution, $user, $lead];
}

// ─── LMS: trigger creates log and dispatches job ─────────────────────────

test('trigger creates LmsEnrolmentLog and dispatches TriggerLmsEnrolmentJob (EI-010)', function (): void {
    Queue::fake();

    [$institution, $user, $lead] = makeLmsContext();

    $service = app(LmsEnrolmentService::class);

    $log = $service->trigger($institution->id, [
        'lead_id' => $lead->id,
        'erp_student_id' => 'STU-LMS-001',
        'lms_provider' => 'camplus',
        'lms_course_id' => 'COURSE-BCA-101',
    ]);

    expect($log)->toBeInstanceOf(LmsEnrolmentLog::class)
        ->and($log->status)->toBe(LmsEnrolmentStatus::Pending)
        ->and($log->lms_provider)->toBe('camplus')
        ->and($log->lms_course_id)->toBe('COURSE-BCA-101');

    Queue::assertPushed(TriggerLmsEnrolmentJob::class);
});

// ─── LMS: markEnrolled updates status ────────────────────────────────────

test('markEnrolled updates LmsEnrolmentLog to enrolled status (EI-010)', function (): void {
    [$institution, $user, $lead] = makeLmsContext();

    $service = app(LmsEnrolmentService::class);

    $log = LmsEnrolmentLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'erp_student_id' => 'STU-LMS-002',
        'lms_provider' => 'moodle',
        'lms_course_id' => 'MOODLE-CSE-201',
        'status' => LmsEnrolmentStatus::Queued,
        'attempt_count' => 1,
    ]);

    $service->markEnrolled($log, 'MOODLE-USER-555');

    $log->refresh();

    expect($log->status)->toBe(LmsEnrolmentStatus::Enrolled)
        ->and($log->lms_user_id)->toBe('MOODLE-USER-555');
});

// ─── LMS: markFailed sets status to failed ───────────────────────────────

test('markFailed sets LmsEnrolmentLog status to failed (EI-010)', function (): void {
    [$institution, $user, $lead] = makeLmsContext();

    $service = app(LmsEnrolmentService::class);

    $log = LmsEnrolmentLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'erp_student_id' => 'STU-LMS-003',
        'lms_provider' => 'camplus',
        'lms_course_id' => 'CAMP-MBA-301',
        'status' => LmsEnrolmentStatus::Queued,
        'attempt_count' => 3,
    ]);

    $service->markFailed($log);

    $log->refresh();

    expect($log->status)->toBe(LmsEnrolmentStatus::Failed);
});

// ─── LMS: incrementAttempts increments count ─────────────────────────────

test('incrementAttempts increases attempt_count by 1 (EI-010)', function (): void {
    [$institution, $user, $lead] = makeLmsContext();

    $service = app(LmsEnrolmentService::class);

    $log = LmsEnrolmentLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'erp_student_id' => 'STU-LMS-004',
        'lms_provider' => 'moodle',
        'lms_course_id' => 'MDL-IT-401',
        'status' => LmsEnrolmentStatus::Retrying,
        'attempt_count' => 1,
    ]);

    $service->incrementAttempts($log);

    $log->refresh();

    expect($log->attempt_count)->toBe(2);
});
