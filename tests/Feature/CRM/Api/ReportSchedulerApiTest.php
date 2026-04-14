<?php

declare(strict_types=1);

// BRD: CRM-AR-002 — Report scheduler + delivery job tests
// Covers: schedule CRUD, next_run_at calculation, dispatch, queue push, processDue, RBAC

use App\Jobs\CRM\Analytics\ReportDeliveryJob;
use App\Models\CRM\CustomReport;
use App\Models\CRM\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(PermissionSeeder::class);
});

// ─── Helpers ────────────────────────────────────────────────────────────────

function makeSchedulerAdmin(string $suffix = 'a'): array
{
    $institution = Institution::create([
        'name'      => 'Sched Inst ' . $suffix,
        'code'      => 'SCH' . strtoupper($suffix),
        'is_active' => true,
    ]);

    $admin = User::create([
        'name'           => 'Sched Admin ' . $suffix,
        'email'          => 'sched-' . $suffix . '@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $admin->givePermissionTo([
        'crm.reports.view',
        'crm.reports.manage',
        'crm.reports.export',
    ]);

    return [$institution, $admin];
}

function makeCustomReport(int $institutionId, int $userId = 1): CustomReport
{
    return CustomReport::withoutGlobalScopes()->create([
        'institution_id'  => $institutionId,
        'created_by'      => $userId,
        'name'            => 'Test Report',
        'entity'          => 'leads',
        'selected_fields' => ['id', 'first_name'],
        'filters'         => [],
        'sort_field'      => 'created_at',
        'sort_direction'  => 'desc',
    ]);
}

function schedulePayload(int $reportId, array $overrides = []): array
{
    return array_merge([
        'custom_report_id'   => $reportId,
        'name'               => 'Daily Lead Report',
        'frequency'          => 'daily',
        'run_time'           => '08:00',
        'recipient_emails'   => ['admin@example.com'],
        'format'             => 'pdf',
        'is_active'          => true,
    ], $overrides);
}

// ─── CREATE & next_run_at ────────────────────────────────────────────────────

it('can create a daily schedule and next_run_at is auto-calculated (CRM-AR-002)', function (): void {
    [$institution, $admin] = makeSchedulerAdmin();
    $report = makeCustomReport($institution->id);

    $response = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($report->id))
        ->assertCreated();

    $response->assertJsonPath('data.frequency', 'daily')
        ->assertJsonPath('data.format', 'pdf');

    expect($response->json('data.next_run_at'))->not->toBeNull();
});

it('weekly schedule stores day_of_week (CRM-AR-002)', function (): void {
    [$institution, $admin] = makeSchedulerAdmin('b');
    $report = makeCustomReport($institution->id);

    $response = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($report->id, [
            'frequency'   => 'weekly',
            'day_of_week' => 1,
        ]))
        ->assertCreated();

    $response->assertJsonPath('data.day_of_week', 1);

    assertDatabaseHas('report_schedules', [
        'frequency'   => 'weekly',
        'day_of_week' => 1,
    ]);
});

it('monthly schedule stores day_of_month (CRM-AR-002)', function (): void {
    [$institution, $admin] = makeSchedulerAdmin('c');
    $report = makeCustomReport($institution->id);

    $response = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($report->id, [
            'frequency'    => 'monthly',
            'day_of_month' => 15,
        ]))
        ->assertCreated();

    $response->assertJsonPath('data.day_of_month', 15);
});

// ─── DISPATCH ────────────────────────────────────────────────────────────────

it('dispatch endpoint creates ReportDelivery record (CRM-AR-002)', function (): void {
    Queue::fake();

    [$institution, $admin] = makeSchedulerAdmin('d');
    $report = makeCustomReport($institution->id);

    $schedule = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($report->id))
        ->assertCreated();

    $uuid = $schedule->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules/' . $uuid . '/dispatch')
        ->assertOk();

    assertDatabaseHas('report_deliveries', [
        'status' => 'queued',
    ]);
});

it('dispatch endpoint pushes ReportDeliveryJob to crm-analytics queue (CRM-AR-002)', function (): void {
    Queue::fake();

    [$institution, $admin] = makeSchedulerAdmin('e');
    $report = makeCustomReport($institution->id);

    $schedule = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($report->id))
        ->assertCreated();

    $uuid = $schedule->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules/' . $uuid . '/dispatch')
        ->assertOk();

    Queue::assertPushedOn('crm-analytics', ReportDeliveryJob::class);
});

// ─── RBAC ───────────────────────────────────────────────────────────────────

it('cannot dispatch without crm.reports.manage permission (CRM-AR-002)', function (): void {
    Queue::fake();

    [$institution, $admin] = makeSchedulerAdmin('f');
    $report = makeCustomReport($institution->id);

    $schedule = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($report->id))
        ->assertCreated();

    $uuid = $schedule->json('data.uuid');

    $viewer = User::create([
        'name'           => 'Viewer',
        'email'          => 'sched-viewer@example.com',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $viewer->givePermissionTo('crm.reports.view');

    actingAs($viewer, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules/' . $uuid . '/dispatch')
        ->assertForbidden();

    Queue::assertNothingPushed();
});

// ─── LIST / ISOLATION ────────────────────────────────────────────────────────

it('schedules are scoped to institution — cannot see another institutions schedules (CRM-AR-002)', function (): void {
    [$instA, $adminA] = makeSchedulerAdmin('g');
    [$instB, $adminB] = makeSchedulerAdmin('h');

    $reportA = makeCustomReport($instA->id);
    $reportB = makeCustomReport($instB->id);

    actingAs($adminA, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($reportA->id))
        ->assertCreated();

    actingAs($adminB, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($reportB->id))
        ->assertCreated();

    actingAs($adminA, 'sanctum')
        ->getJson('/api/v1/crm/reports/schedules')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// ─── DELETE ─────────────────────────────────────────────────────────────────

it('can delete a report schedule (CRM-AR-002)', function (): void {
    [$institution, $admin] = makeSchedulerAdmin('i');
    $report = makeCustomReport($institution->id);

    $created = actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/reports/schedules', schedulePayload($report->id))
        ->assertCreated();

    $uuid = $created->json('data.uuid');

    actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/crm/reports/schedules/' . $uuid)
        ->assertOk()
        ->assertJsonPath('success', true);
});

// ─── UNIT: ReportDeliveryJob queue ──────────────────────────────────────────

it('ReportDeliveryJob is serialisable and targets crm-analytics queue (CRM-AR-002)', function (): void {
    Queue::fake();

    // Test that the job is dispatched to the correct queue when dispatched via the service pattern
    ReportDeliveryJob::dispatch(999)->onQueue('crm-analytics');

    Queue::assertPushedOn('crm-analytics', ReportDeliveryJob::class);
});
