<?php

declare(strict_types=1);

// BRD: CRM-EC-015 — Counselling session booking, outcome recording, and cancellation
use App\DTOs\CRM\BookSessionDTO;
use App\DTOs\CRM\UpdateSessionDTO;
use App\Enums\CRM\CounsellingSessionStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\SessionType;
use App\Events\CRM\CounsellingSessionBookedEvent;
use App\Events\CRM\CounsellingSessionCancelledEvent;
use App\Events\CRM\CounsellingSessionCompletedEvent;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Counselling\CounsellingService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    $this->institution = Institution::create(['name' => 'Test Uni', 'code' => 'TU01', 'is_active' => true]);

    $this->counsellor = User::factory()->create([
        'institution_id' => $this->institution->id,
        'is_active' => true,
    ]);
    $this->counsellor->assignRole('senior-counsellor');

    $this->admin = User::factory()->create([
        'institution_id' => $this->institution->id,
        'is_active' => true,
    ]);
    $this->admin->assignRole('institution-admin');

    $this->lead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Priya',
        'last_name' => 'Sharma',
        'mobile' => '9876543201',
        'source' => LeadSource::REFERRAL->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 10,
    ]);

    $this->svc = app(CounsellingService::class);
});

it('books a new session and fires CounsellingSessionBookedEvent', function (): void {
    Event::fake([CounsellingSessionBookedEvent::class]);

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::tomorrow()->setHour(10),
        mode: 'online',
    ));

    expect($session)->toBeInstanceOf(CounsellingSession::class)
        ->and($session->status)->toBe(CounsellingSessionStatus::SCHEDULED);
    Event::assertDispatched(CounsellingSessionBookedEvent::class);
});

it('records session as completed and fires CounsellingSessionCompletedEvent', function (): void {
    Event::fake([CounsellingSessionBookedEvent::class, CounsellingSessionCompletedEvent::class]);

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::yesterday(),
        mode: 'phone',
    ));

    $updated = $this->svc->updateOutcome($session, UpdateSessionDTO::fromValidated([
        'status' => CounsellingSessionStatus::COMPLETED->value,
        'post_session_notes' => 'Session went well.',
    ]));

    expect($updated->status)->toBe(CounsellingSessionStatus::COMPLETED);
    Event::assertDispatched(CounsellingSessionCompletedEvent::class);
});

it('cancels a session and fires CounsellingSessionCancelledEvent', function (): void {
    Event::fake([CounsellingSessionBookedEvent::class, CounsellingSessionCancelledEvent::class]);

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::tomorrow()->setHour(14),
        mode: 'offline',
    ));

    $updated = $this->svc->updateOutcome($session, UpdateSessionDTO::fromValidated([
        'status' => CounsellingSessionStatus::CANCELLED->value,
    ]));

    expect($updated->status)->toBe(CounsellingSessionStatus::CANCELLED);
    Event::assertDispatched(CounsellingSessionCancelledEvent::class);
});

it('throws DomainException when updating a terminal session', function (): void {
    Event::fake();

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::yesterday(),
        mode: 'online',
    ));

    $this->svc->updateOutcome($session, UpdateSessionDTO::fromValidated([
        'status' => CounsellingSessionStatus::CANCELLED->value,
    ]));

    expect(fn () => $this->svc->updateOutcome($session->fresh(), UpdateSessionDTO::fromValidated([
        'status' => CounsellingSessionStatus::COMPLETED->value,
    ])))->toThrow(DomainException::class);
});

it('generates a booking token', function (): void {
    Event::fake();

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::tomorrow(),
        mode: 'online',
    ));

    $token = $this->svc->generateBookingToken($session);

    expect($token)->toBeString()->toHaveLength(48);
    expect($session->fresh()->booking_token)->toBe($token);
});

it('senior counsellor can create session via web route', function (): void {
    $this->actingAs($this->counsellor)
        ->post(route('crm.leads.sessions.store', $this->lead), [
            'counsellor_id' => $this->counsellor->id,
            'session_type' => SessionType::INITIAL->value,
            'scheduled_at' => Carbon::tomorrow()->setHour(11)->toDateTimeString(),
            'mode' => 'online',
        ])
        ->assertRedirect();

    expect(CounsellingSession::withoutGlobalScopes()->where('lead_id', $this->lead->getKey())->count())->toBe(1);
});

it('junior counsellor cannot cancel session', function (): void {
    Event::fake();

    $junior = User::factory()->create(['institution_id' => $this->institution->id, 'is_active' => true]);
    $junior->assignRole('junior-counsellor');

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::tomorrow(),
        mode: 'online',
    ));

    $this->actingAs($junior)
        ->delete(route('crm.sessions.destroy', $session))
        ->assertForbidden();
});

it('sessions are scoped to institution', function (): void {
    Event::fake();

    $otherInst = Institution::create(['name' => 'Other Uni', 'code' => 'OU02', 'is_active' => true]);
    $otherUser = User::factory()->create(['institution_id' => $otherInst->id, 'is_active' => true]);
    $otherUser->assignRole('senior-counsellor');
    $otherLead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $otherInst->id,
        'first_name' => 'Other',
        'last_name' => 'Lead',
        'mobile' => '9000000099',
        'source' => LeadSource::WEBSITE_ORGANIC->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 5,
    ]);

    $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::tomorrow(),
        mode: 'online',
    ));
    $this->svc->book(new BookSessionDTO(
        leadId: $otherLead->getKey(),
        counsellorId: $otherUser->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::tomorrow(),
        mode: 'online',
    ));

    $sessions = $this->lead->sessions()->count();
    expect($sessions)->toBe(1);
});

it('admin can view sessions list for a lead', function (): void {
    $this->actingAs($this->admin)
        ->get(route('crm.leads.sessions.index', $this->lead))
        ->assertOk();
});

it('CounsellingSessionStatus isTerminal returns true for cancelled', function (): void {
    expect(CounsellingSessionStatus::CANCELLED->isTerminal())->toBeTrue()
        ->and(CounsellingSessionStatus::SCHEDULED->isTerminal())->toBeFalse();
});
