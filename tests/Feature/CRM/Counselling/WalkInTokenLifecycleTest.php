<?php

declare(strict_types=1);

// BRD: CRM-EC-019 — Feature tests for walk-in token HTTP lifecycle
use App\Enums\CRM\Counselling\WalkInTokenStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Events\CRM\Counselling\WalkInTokenCalled;
use App\Events\CRM\Counselling\WalkInTokenStatusChanged;
use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\WalkInToken;
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

    $this->institution = Institution::create([
        'name' => 'Test Uni',
        'code' => 'TU01',
        'is_active' => true,
    ]);

    $this->campus = Campus::create([
        'institution_id' => $this->institution->id,
        'name' => 'Main Campus',
        'code' => 'MC01',
        'is_active' => true,
    ]);

    $this->counsellor = User::factory()->create([
        'institution_id' => $this->institution->id,
        'campus_id' => $this->campus->id,
    ]);
    $this->counsellor->givePermissionTo('walk_in_queue.manage');
});

it('kiosk POST issues a token and returns token_number in JSON', function (): void {
    Event::fake([WalkInTokenStatusChanged::class]);

    $response = $this->postJson(
        route('public.kiosk.walk-in-token', $this->institution->uuid),
        []
    );

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'token_number', 'message'])
        ->assertJsonPath('token_number', 1);

    expect(WalkInToken::withoutGlobalScopes()->count())->toBe(1);
});

it('kiosk creates a lead stub when visitor provides name and mobile', function (): void {
    Event::fake([WalkInTokenStatusChanged::class]);

    $this->postJson(route('public.kiosk.walk-in-token', $this->institution->uuid), [
        'visitor_name' => 'Rahul Kumar',
        'visitor_mobile' => '9876543210',
        'programme_interest' => 'MBA',
    ])->assertStatus(201);

    $token = WalkInToken::withoutGlobalScopes()->first();
    expect($token->lead_id)->not->toBeNull();

    $lead = Lead::withoutGlobalScopes()->find($token->lead_id);
    expect($lead->first_name)->toBe('Rahul Kumar');
    expect($lead->source)->toBe(LeadSource::WALK_IN->value);
});

it('counsellor calls next token and status becomes Called', function (): void {
    Event::fake([WalkInTokenStatusChanged::class, WalkInTokenCalled::class]);

    // Issue a token first
    $this->postJson(route('public.kiosk.walk-in-token', $this->institution->uuid), []);

    $response = $this->actingAs($this->counsellor)
        ->postJson(route('crm.walk-in-queue.call-next'));

    $response->assertOk()->assertJsonPath('status', WalkInTokenStatus::CALLED->value);

    $token = WalkInToken::withoutGlobalScopes()->first();
    expect($token->status)->toBe(WalkInTokenStatus::CALLED);
    expect($token->counsellor_id)->toBe($this->counsellor->id);
});

it('counsellor serves a token and status becomes Served', function (): void {
    Event::fake([WalkInTokenStatusChanged::class, WalkInTokenCalled::class]);

    $this->postJson(route('public.kiosk.walk-in-token', $this->institution->uuid), []);

    $this->actingAs($this->counsellor)->postJson(route('crm.walk-in-queue.call-next'));

    $token = WalkInToken::withoutGlobalScopes()->first();

    $this->actingAs($this->counsellor)
        ->postJson(route('crm.walk-in-queue.tokens.serve', $token->id))
        ->assertOk();

    $token->refresh();
    expect($token->status)->toBe(WalkInTokenStatus::SERVED);
});

it('counsellor cannot serve a token from another campus (403)', function (): void {
    Event::fake([WalkInTokenStatusChanged::class, WalkInTokenCalled::class]);

    // Create a different campus
    $otherCampus = Campus::create([
        'institution_id' => $this->institution->id,
        'name' => 'City Campus',
        'code' => 'CC01',
        'is_active' => true,
    ]);

    // Issue token at other campus
    WalkInToken::withoutGlobalScopes()->create([
        'institution_id' => $this->institution->id,
        'campus_id' => $otherCampus->id,
        'token_number' => 1,
        'token_date' => now()->toDateString(),
        'status' => WalkInTokenStatus::CALLED->value,
    ]);

    $token = WalkInToken::withoutGlobalScopes()->first();

    // Counsellor is assigned to $this->campus, not otherCampus
    $this->actingAs($this->counsellor)
        ->postJson(route('crm.walk-in-queue.tokens.serve', $token->id))
        ->assertForbidden();
});
