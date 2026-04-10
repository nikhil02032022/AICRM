<?php

declare(strict_types=1);

// BRD: CRM-EC-016 — Public-facing appointment booking (unauthenticated)
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\SessionType;
use App\Events\CRM\CounsellingSessionBookedEvent;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
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

    $this->lead = Lead::withoutGlobalScopes()->create([
        'uuid' => Str::uuid(),
        'institution_id' => $this->institution->id,
        'first_name' => 'Arjun',
        'last_name' => 'Kapoor',
        'mobile' => '9876522222',
        'source' => LeadSource::WEBSITE_ORGANIC->value,
        'status' => LeadStatus::NEW_ENQUIRY->value,
        'consent_given' => true,
        'lead_score' => 30,
    ]);
});

it('public booking show page renders for a valid lead slug', function (): void {
    $this->get(route('public.booking.show', $this->lead->uuid))
        ->assertOk();
});

it('public booking show page returns 404 for unknown slug', function (): void {
    $this->get(route('public.booking.show', 'invalid-uuid-here'))
        ->assertNotFound();
});

it('public booking show page returns 404 when consent not given', function (): void {
    $this->lead->update(['consent_given' => false]);

    $this->get(route('public.booking.show', $this->lead->uuid))
        ->assertNotFound();
});

it('submitting the public booking form creates a session', function (): void {
    Event::fake([CounsellingSessionBookedEvent::class]);

    $this->post(route('public.booking.submit', $this->lead->uuid), [
        'counsellor_id' => $this->counsellor->id,
        'session_type' => SessionType::INITIAL->value,
        'scheduled_at' => now()->addDay()->setHour(10)->toDateTimeString(),
        'mode' => 'online',
    ])->assertRedirect(route('public.booking.confirmation', $this->lead->uuid));

    expect(CounsellingSession::withoutGlobalScopes()->where('lead_id', $this->lead->getKey())->count())->toBe(1);
    Event::assertDispatched(CounsellingSessionBookedEvent::class);
});

it('public booking submit returns 404 for lead without consent', function (): void {
    $this->lead->update(['consent_given' => false]);

    $this->post(route('public.booking.submit', $this->lead->uuid), [
        'counsellor_id' => $this->counsellor->id,
        'session_type' => SessionType::INITIAL->value,
        'scheduled_at' => now()->addDay()->toDateTimeString(),
        'mode' => 'online',
    ])->assertNotFound();
});

it('public booking submit validates required fields', function (): void {
    $this->post(route('public.booking.submit', $this->lead->uuid), [])
        ->assertSessionHasErrors(['counsellor_id', 'session_type', 'scheduled_at', 'mode']);
});

it('confirmation page renders after booking', function (): void {
    $this->withSession(['session_uuid' => 'fake-uuid'])
        ->get(route('public.booking.confirmation', $this->lead->uuid))
        ->assertOk();
});

it('public booking does not require authentication', function (): void {
    // No actingAs — unauthenticated
    $this->get(route('public.booking.show', $this->lead->uuid))
        ->assertOk();
});

it('public booking with invalid session type returns validation error', function (): void {
    $this->post(route('public.booking.submit', $this->lead->uuid), [
        'counsellor_id' => $this->counsellor->id,
        'session_type' => 'invalid_type',
        'scheduled_at' => now()->addDay()->toDateTimeString(),
        'mode' => 'online',
    ])->assertSessionHasErrors(['session_type']);
});
