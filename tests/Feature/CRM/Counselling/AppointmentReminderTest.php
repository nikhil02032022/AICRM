<?php

declare(strict_types=1);

// BRD: CRM-EC-017 — Appointment reminders: 24h and 1h windows
use App\DTOs\CRM\BookSessionDTO;
use App\Enums\CRM\CounsellingSessionStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\SessionType;
use App\Jobs\CRM\SendAppointmentReminderJob;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Notifications\CRM\AppointmentReminderNotification;
use App\Repositories\CRM\Counselling\CounsellingSessionRepositoryInterface;
use App\Services\CRM\Counselling\CounsellingService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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

    $this->lead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Meera',
        'last_name' => 'Patel',
        'mobile' => '9876543888',
        'source' => LeadSource::REFERRAL->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 15,
    ]);

    $this->svc = app(CounsellingService::class);
});

it('SendAppointmentReminderJob is pushed to crm-notifications queue', function (): void {
    Queue::fake();

    SendAppointmentReminderJob::dispatch();

    Queue::assertPushedOn('crm-notifications', SendAppointmentReminderJob::class);
});

it('sends 24h reminder for a session scheduled tomorrow', function (): void {
    Event::fake();
    Notification::fake();

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::now()->addHours(23)->addMinutes(30),
        mode: 'online',
    ));

    // Mark 24h reminder as not sent
    $session->update(['reminder_24h_sent' => false]);

    // Run the job directly
    app(SendAppointmentReminderJob::class)->handle(
        app(CounsellingSessionRepositoryInterface::class)
    );

    expect($session->fresh()->reminder_24h_sent)->toBeTrue();
});

it('sends 1h reminder for a session scheduled within the hour', function (): void {
    Event::fake();
    Notification::fake();

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::now()->addMinutes(55),
        mode: 'phone',
    ));

    $session->update(['reminder_24h_sent' => true, 'reminder_1h_sent' => false]);

    app(SendAppointmentReminderJob::class)->handle(
        app(CounsellingSessionRepositoryInterface::class)
    );

    expect($session->fresh()->reminder_1h_sent)->toBeTrue();
});

it('does not send reminder for cancelled sessions', function (): void {
    Event::fake();
    Notification::fake();

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::now()->addHours(23),
        mode: 'online',
    ));

    CounsellingSession::withoutGlobalScopes()->where('id', $session->id)
        ->update(['status' => CounsellingSessionStatus::CANCELLED->value, 'reminder_24h_sent' => false]);

    app(SendAppointmentReminderJob::class)->handle(
        app(CounsellingSessionRepositoryInterface::class)
    );

    // Cancelled session should not have reminder sent
    expect($session->fresh()->reminder_24h_sent)->toBeFalse();
});

it('AppointmentReminderNotification can be instantiated', function (): void {
    Event::fake();

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::tomorrow(),
        mode: 'online',
    ));

    $notification = new AppointmentReminderNotification($session, '24h');
    expect($notification)->toBeInstanceOf(AppointmentReminderNotification::class);
});

it('reminder is not sent twice for same window', function (): void {
    Event::fake();
    Notification::fake();

    $session = $this->svc->book(new BookSessionDTO(
        leadId: $this->lead->getKey(),
        counsellorId: $this->counsellor->id,
        sessionType: SessionType::INITIAL,
        scheduledAt: Carbon::now()->addHours(23),
        mode: 'online',
    ));

    // Already sent
    $session->update(['reminder_24h_sent' => true]);

    app(SendAppointmentReminderJob::class)->handle(
        app(CounsellingSessionRepositoryInterface::class)
    );

    // Should not be re-sent (repository only returns sessions where reminder_24h_sent = false)
    Notification::assertNothingSent();
});
