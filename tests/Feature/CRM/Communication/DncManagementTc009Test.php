<?php

declare(strict_types=1);

use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Communication\DncService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// -----------------------------------------------------------------------
// Shared test context
// -----------------------------------------------------------------------
function makeDncContext(bool $withDncPermission = true): array
{
    $institution = Institution::create([
        'name'      => 'DNC Test Institute',
        'code'      => 'DTI',
        'is_active' => true,
    ]);

    $user = User::create([
        'name'           => 'DNC Manager',
        'email'          => 'dnc-manager@test.local',
        'password'       => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    if ($withDncPermission) {
        $user->givePermissionTo('crm.dnc.manage');
    }

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid'                 => (string) Str::uuid(),
        'institution_id'       => $institution->id,
        'first_name'           => 'Priya',
        'last_name'            => 'Nair',
        'mobile'               => '9988776655',
        'source'               => 'walk_in',
        'status'               => 'new_enquiry',
        'temperature'          => 'warm',
        'lead_score'           => 60,
        'consent_given'        => true,
        'consent_timestamp'    => now(),
        'consent_form_version' => 'v1',
        'call_consent_given'   => true,
        'opt_out'              => false,
    ]);

    return [$institution, $user, $lead];
}

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

// -----------------------------------------------------------------------
// Service unit-level tests
// -----------------------------------------------------------------------

// BRD: CRM-TC-009 — addToDnc sets dnc_at and dnc_reason; also sets opt_out
it('adds a lead to DNC and sets opt_out', function (): void {
    [, , $lead] = makeDncContext();

    $service = app(DncService::class);
    $service->addToDnc($lead, 'Do not call — requested removal');

    $fresh = $lead->fresh();

    expect($fresh->dnc_at)->not->toBeNull()
        ->and($fresh->dnc_reason)->toBe('Do not call — requested removal')
        ->and($fresh->opt_out)->toBeTrue();
});

// BRD: CRM-TC-009 — addToDnc is idempotent: second call must not overwrite dnc_at
it('addToDnc is idempotent – second call preserves original dnc_at', function (): void {
    [, , $lead] = makeDncContext();

    $service = app(DncService::class);
    $service->addToDnc($lead, 'First reason');
    $firstDncAt = $lead->fresh()->dnc_at;

    // Advance time and call again
    $this->travel(5)->minutes();
    $service->addToDnc($lead, 'Second reason');

    $fresh = $lead->fresh();

    expect($fresh->dnc_at->toIso8601String())->toBe($firstDncAt->toIso8601String())
        ->and($fresh->dnc_reason)->toBe('First reason'); // not overwritten
});

// BRD: CRM-TC-009 — removeFromDnc clears dnc_at and dnc_reason but preserves opt_out
it('removes a lead from DNC but preserves opt_out flag', function (): void {
    [, , $lead] = makeDncContext();

    $service = app(DncService::class);
    $service->addToDnc($lead, 'Remove test');
    $service->removeFromDnc($lead);

    $fresh = $lead->fresh();

    expect($fresh->dnc_at)->toBeNull()
        ->and($fresh->dnc_reason)->toBeNull()
        ->and($fresh->opt_out)->toBeTrue(); // DPDP: opt_out must not be cleared here
});

// BRD: CRM-TC-009 — paginateDncLeads returns only DNC leads for the institution
it('paginates only DNC leads for the institution', function (): void {
    [$institution, , $dncLead] = makeDncContext();

    // A second lead that is NOT on DNC
    Lead::withoutGlobalScopes()->create([
        'uuid'           => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name'     => 'Arjun',
        'last_name'      => 'Mehta',
        'mobile'         => '9876543100',
        'source'         => 'website_organic',
        'status'         => 'new_enquiry',
        'temperature'    => 'cold',
        'lead_score'     => 30,
        'consent_given'  => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
        'opt_out'        => false,
    ]);

    $service = app(DncService::class);
    $service->addToDnc($dncLead, 'Listed for test');

    $results = $service->paginateDncLeads($institution->id);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->uuid)->toBe($dncLead->uuid);
});

// -----------------------------------------------------------------------
// Web controller / route tests
// -----------------------------------------------------------------------

// BRD: CRM-TC-009 — DNC index page requires crm.dnc.manage permission
it('shows DNC list page to authorised user', function (): void {
    [$institution, $user] = makeDncContext();

    $response = $this->actingAs($user)
        ->get(route('crm.communication.voice.dnc.index'));

    $response->assertOk()
        ->assertSeeText('Do-Not-Call (DNC) List');
});

// BRD: CRM-TC-009 — DNC index page blocked without permission
it('blocks DNC list page for user without crm.dnc.manage', function (): void {
    [$institution, $user] = makeDncContext(false);

    $response = $this->actingAs($user)
        ->get(route('crm.communication.voice.dnc.index'));

    $response->assertForbidden();
});

// BRD: CRM-TC-009 — POST to DNC store adds lead with reason
it('adds a lead to DNC via web route', function (): void {
    [, $user, $lead] = makeDncContext();

    $response = $this->actingAs($user)
        ->post(route('crm.communication.voice.dnc.store', $lead->uuid), [
            'reason' => 'Lead explicitly requested no contact',
        ]);

    $response->assertRedirect();

    $fresh = $lead->fresh();

    expect($fresh->dnc_at)->not->toBeNull()
        ->and($fresh->dnc_reason)->toBe('Lead explicitly requested no contact');
});

// BRD: CRM-TC-009 — Reason is required; empty reason should fail validation
it('rejects DNC store request without a reason', function (): void {
    [, $user, $lead] = makeDncContext();

    $response = $this->actingAs($user)
        ->post(route('crm.communication.voice.dnc.store', $lead->uuid), [
            'reason' => '',
        ]);

    $response->assertSessionHasErrors('reason');

    expect($lead->fresh()->dnc_at)->toBeNull();
});

// BRD: CRM-TC-009 — DELETE to DNC destroy removes lead from DNC
it('removes a lead from DNC via web route', function (): void {
    [, $user, $lead] = makeDncContext();

    // First add to DNC
    $lead->update(['dnc_at' => now(), 'dnc_reason' => 'Test', 'opt_out' => true, 'opt_out_at' => now()]);

    $response = $this->actingAs($user)
        ->delete(route('crm.communication.voice.dnc.destroy', $lead->uuid));

    $response->assertRedirect();

    $fresh = $lead->fresh();

    expect($fresh->dnc_at)->toBeNull()
        ->and($fresh->dnc_reason)->toBeNull()
        ->and($fresh->opt_out)->toBeTrue(); // preserved by DPDP design
});

// BRD: CRM-TC-009 — Web route enforces permission on store
it('blocks unauthorised user from adding lead to DNC', function (): void {
    [, $user, $lead] = makeDncContext(false);

    $response = $this->actingAs($user)
        ->post(route('crm.communication.voice.dnc.store', $lead->uuid), [
            'reason' => 'Attempted DNC add without permission',
        ]);

    $response->assertForbidden();

    expect($lead->fresh()->dnc_at)->toBeNull();
});
