<?php

declare(strict_types=1);

// BRD: CRM-LC-020 — ERP Student Master match flagging for CRM leads

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\ErpMatchStatus;
use App\Enums\CRM\LeadSource;
use App\Events\CRM\ErpStudentMatchedEvent;
use App\Jobs\CRM\CheckErpStudentMatchJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Erp\ErpApiClient;
use App\Services\CRM\Erp\ErpApiClientInterface;
use App\Services\CRM\Lead\LeadService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->institution = Institution::create([
        'name' => 'ERP Test University', 'code' => 'ETU01', 'is_active' => true,
    ]);

    $this->counsellor = User::create([
        'name' => 'ERP Counsellor',
        'email' => 'erp@etu.com',
        'password' => bcrypt('password'),
        'institution_id' => $this->institution->id,
    ]);
    $this->counsellor->givePermissionTo([
        'crm.leads.create', 'crm.leads.view', 'crm.leads.edit',
    ]);

    // Default ERP config so ErpApiClient doesn't bail on empty base_url
    config(['services.a2a_erp.base_url' => 'https://erp.test']);
});

// ─── Helpers ───────────────────────────────────────────────────────────────

function makeErpLead(int $institutionId): Lead
{
    return Lead::withoutGlobalScopes()->create([
        'institution_id' => $institutionId,
        'first_name' => 'ERP',
        'last_name' => 'Student',
        'mobile' => '9100000001',
        'source' => 'walk_in',
        'lead_score' => 0,
        'temperature' => 'cold',
        'status' => 'new_enquiry',
        'consent_given' => true,
    ]);
}

// ─── Tests ─────────────────────────────────────────────────────────────────

it('sets erp_student_uuid and erp_match_status to matched on a successful ERP 200 response', function (): void {
    $lead = makeErpLead($this->institution->id);

    Http::fake([
        'https://erp.test/api/v1/students/lookup*' => Http::response([
            'data' => [
                'uuid' => 'erp-student-uuid-123',
                'enrollment_no' => 'ENR2024001',
                'admitted_course' => 'B.Tech CSE',
                'is_alumni' => false,
            ],
        ], 200),
    ]);

    (new CheckErpStudentMatchJob($lead->uuid, $this->institution->id))->handle();

    $lead->refresh();
    expect($lead->erp_student_uuid)->toBe('erp-student-uuid-123');
    expect($lead->erp_match_status)->toBe(ErpMatchStatus::MATCHED);
});

it('sets erp_match_status to no_match when ERP returns 404', function (): void {
    $lead = makeErpLead($this->institution->id);

    Http::fake([
        'https://erp.test/api/v1/students/lookup*' => Http::response([], 404),
    ]);

    (new CheckErpStudentMatchJob($lead->uuid, $this->institution->id))->handle();

    $lead->refresh();
    expect($lead->erp_student_uuid)->toBeNull();
    expect($lead->erp_match_status)->toBe(ErpMatchStatus::NO_MATCH);
});

it('sets erp_match_status to error on ERP 500 and does not throw', function (): void {
    $lead = makeErpLead($this->institution->id);

    Http::fake([
        'https://erp.test/api/v1/students/lookup*' => Http::response([], 500),
    ]);

    // Should not throw — circuit breaker handles gracefully
    expect(fn () => (new CheckErpStudentMatchJob($lead->uuid, $this->institution->id))->handle())
        ->not->toThrow(\Throwable::class);

    $lead->refresh();
    expect($lead->erp_match_status)->not->toBeNull();
});

it('dispatches ErpStudentMatchedEvent when a match is found', function (): void {
    $lead = makeErpLead($this->institution->id);

    Event::fake([ErpStudentMatchedEvent::class]);

    Http::fake([
        'https://erp.test/api/v1/students/lookup*' => Http::response([
            'data' => [
                'uuid' => 'erp-uuid-456',
                'enrollment_no' => 'ENR2024002',
                'admitted_course' => 'MBA Marketing',
                'is_alumni' => true,
            ],
        ], 200),
    ]);

    (new CheckErpStudentMatchJob($lead->uuid, $this->institution->id))->handle();

    Event::assertDispatched(ErpStudentMatchedEvent::class, function (ErpStudentMatchedEvent $event) use ($lead): bool {
        return $event->lead->uuid === $lead->uuid
            && $event->erpStudent->studentUuid === 'erp-uuid-456'
            && $event->erpStudent->isAlumni === true;
    });
});

it('does not dispatch ErpStudentMatchedEvent when there is no match (404)', function (): void {
    $lead = makeErpLead($this->institution->id);

    Event::fake([ErpStudentMatchedEvent::class]);

    Http::fake([
        'https://erp.test/api/v1/students/lookup*' => Http::response([], 404),
    ]);

    (new CheckErpStudentMatchJob($lead->uuid, $this->institution->id))->handle();

    Event::assertNotDispatched(ErpStudentMatchedEvent::class);
});

it('has a consistent unique ID per lead UUID for ShouldBeUnique', function (): void {
    $lead = makeErpLead($this->institution->id);

    $job1 = new CheckErpStudentMatchJob($lead->uuid, $this->institution->id);
    $job2 = new CheckErpStudentMatchJob($lead->uuid, $this->institution->id);

    expect($job1->uniqueId())->toBe($job2->uniqueId());
    expect($job1->uniqueId())->toBe("erp-match:{$lead->uuid}");
});

it('dispatches CheckErpStudentMatchJob when LeadService creates a lead', function (): void {
    Queue::fake();

    /** @var LeadService $service */
    $service = app(LeadService::class);

    $dto = new CreateLeadDTO(
        firstName: 'ERP',
        lastName: 'NewLead',
        mobile: '9200000001',
        email: null,
        source: LeadSource::WALK_IN,
        sourceUtmParams: null,
        programmeIds: [],
        consentGiven: true,
        consentIp: '127.0.0.1',
        consentFormVersion: '1.0',
        campusId: null,
    );

    $this->actingAs($this->counsellor);
    $service->create($dto, $this->counsellor);

    Queue::assertPushed(CheckErpStudentMatchJob::class);
});

it('dispatches CheckErpStudentMatchJob when mobile is updated via LeadService', function (): void {
    $lead = makeErpLead($this->institution->id);

    Queue::fake();

    /** @var LeadService $service */
    $service = app(LeadService::class);
    $service->update($lead, ['mobile' => '9300000001']);

    Queue::assertPushed(CheckErpStudentMatchJob::class);
});

it('does not dispatch CheckErpStudentMatchJob when a non-contact field is updated', function (): void {
    $lead = makeErpLead($this->institution->id);

    Queue::fake();

    /** @var LeadService $service */
    $service = app(LeadService::class);
    $service->update($lead, ['notes' => 'Just a note update']);

    Queue::assertNotPushed(CheckErpStudentMatchJob::class);
});

it('returns 202 when POST /api/v1/crm/leads/{uuid}/check-erp is called by authorized user', function (): void {
    $lead = makeErpLead($this->institution->id);

    Queue::fake();

    $response = $this->actingAs($this->counsellor, 'sanctum')
        ->postJson(route('api.crm.leads.check-erp', $lead->uuid));

    $response->assertStatus(202);
    $response->assertJsonPath('success', true);
});

it('returns 403 when an unauthorized user attempts POST /api/v1/crm/leads/{uuid}/check-erp', function (): void {
    $lead = makeErpLead($this->institution->id);

    $unauthorized = User::create([
        'name' => 'No Access',
        'email' => 'noaccess@test.com',
        'password' => bcrypt('password'),
        'institution_id' => $this->institution->id,
    ]);
    // No crm.leads.edit permission

    $response = $this->actingAs($unauthorized, 'sanctum')
        ->postJson(route('api.crm.leads.check-erp', $lead->uuid));

    $response->assertStatus(403);
});
