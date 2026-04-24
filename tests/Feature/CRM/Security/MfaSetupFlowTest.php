<?php

declare(strict_types=1);

// NFR-SE-003 — MFA setup and verification flow for admin/manager roles

use App\Models\CRM\Institution;
use App\Models\User;
use App\Services\CRM\Security\MfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'institution-admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'counsellor', 'guard_name' => 'web']);

    $this->institution = Institution::factory()->create();
    $this->admin       = User::factory()->create([
        'institution_id' => $this->institution->id,
        'mfa_enabled'    => false,
    ]);
    $this->admin->assignRole('institution-admin');

    $this->counsellor = User::factory()->create([
        'institution_id' => $this->institution->id,
        'mfa_enabled'    => false,
    ]);
    $this->counsellor->assignRole('counsellor');

    $this->mfaService = app(MfaService::class);
    $this->google2fa  = app(Google2FA::class);
});

test('admin without MFA is redirected to setup on accessing protected route', function (): void {
    $this->actingAs($this->admin)
        ->get('/crm/leads')
        ->assertRedirect(route('crm.mfa.setup'));
});

test('counsellor without MFA is not redirected to MFA setup', function (): void {
    $this->actingAs($this->counsellor)
        ->get(route('crm.mfa.setup'))
        ->assertForbidden();
});

test('GET /crm/mfa/setup returns 200 for admin', function (): void {
    $this->actingAs($this->admin)
        ->get(route('crm.mfa.setup'))
        ->assertOk()
        ->assertViewIs('crm.auth.mfa.setup')
        ->assertViewHas('qr_url')
        ->assertViewHas('recovery_codes');
});

test('admin can enable MFA with valid TOTP code', function (): void {
    $mfaData = $this->mfaService->enableMfa($this->admin);
    $code    = $this->google2fa->getCurrentOtp($mfaData['secret']);

    $this->actingAs($this->admin)
        ->post(route('crm.mfa.enable'), ['code' => $code])
        ->assertRedirect(route('dashboard'));

    $this->admin->refresh();
    expect($this->admin->mfa_enabled)->toBeTrue();
});

test('MFA enable rejects invalid TOTP code', function (): void {
    $this->mfaService->enableMfa($this->admin);

    $this->actingAs($this->admin)
        ->post(route('crm.mfa.enable'), ['code' => '000000'])
        ->assertRedirect()
        ->assertSessionHasErrors('code');
});

test('admin with MFA enabled is redirected to verify when session flag is not set', function (): void {
    $mfaData = $this->mfaService->enableMfa($this->admin);
    $this->mfaService->activateMfa($this->admin);

    $this->actingAs($this->admin)
        ->get('/crm/leads')
        ->assertRedirect(route('crm.mfa.show-verify'));
});

test('POST /crm/mfa/verify sets session flag and redirects with valid code', function (): void {
    $mfaData = $this->mfaService->enableMfa($this->admin);
    $this->mfaService->activateMfa($this->admin);
    $code = $this->google2fa->getCurrentOtp($mfaData['secret']);

    $response = $this->actingAs($this->admin)
        ->post(route('crm.mfa.verify'), ['code' => $code]);

    $response->assertRedirect();
    expect(session('mfa_verified'))->toBeTrue();
});

test('MFA verify rejects invalid code', function (): void {
    $this->mfaService->enableMfa($this->admin);
    $this->mfaService->activateMfa($this->admin);

    $this->actingAs($this->admin)
        ->post(route('crm.mfa.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');
});
