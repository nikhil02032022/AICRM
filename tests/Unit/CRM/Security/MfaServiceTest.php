<?php

declare(strict_types=1);

// NFR-SE-003 — MfaService unit tests: secret generation, TOTP verification, enable/disable lifecycle

use App\Models\User;
use App\Services\CRM\Security\MfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->google2fa = app(Google2FA::class);
    $this->service   = app(MfaService::class);
    $this->user      = User::factory()->create(['mfa_enabled' => false]);
});

test('enableMfa returns qr_url, secret, and 8 recovery codes', function (): void {
    $result = $this->service->enableMfa($this->user);

    expect($result)->toHaveKeys(['qr_url', 'secret', 'recovery_codes'])
        ->and($result['recovery_codes'])->toHaveCount(8)
        ->and($result['secret'])->not->toBeEmpty()
        ->and($result['qr_url'])->toContain(rawurlencode($this->user->email));
});

test('enableMfa stores encrypted secret on user model', function (): void {
    $this->service->enableMfa($this->user);

    $this->user->refresh();
    expect($this->user->google2fa_secret)->not->toBeNull();
});

test('verifyTotp returns true for valid TOTP code', function (): void {
    $result = $this->service->enableMfa($this->user);

    $code = $this->google2fa->getCurrentOtp($result['secret']);

    expect($this->service->verifyTotp($this->user, $code))->toBeTrue();
});

test('verifyTotp returns false for invalid TOTP code', function (): void {
    $this->service->enableMfa($this->user);

    expect($this->service->verifyTotp($this->user, '000000'))->toBeFalse();
});

test('activateMfa sets mfa_enabled true and mfa_enabled_at timestamp', function (): void {
    $this->service->enableMfa($this->user);
    $this->service->activateMfa($this->user);

    $this->user->refresh();
    expect($this->user->mfa_enabled)->toBeTrue()
        ->and($this->user->mfa_enabled_at)->not->toBeNull();
});

test('disableMfa clears all MFA fields and sets mfa_enabled false', function (): void {
    $this->service->enableMfa($this->user);
    $this->service->activateMfa($this->user);
    $this->service->disableMfa($this->user);

    $this->user->refresh();
    expect($this->user->mfa_enabled)->toBeFalse()
        ->and($this->user->google2fa_secret)->toBeNull()
        ->and($this->user->mfa_recovery_codes)->toBeNull()
        ->and($this->user->mfa_enabled_at)->toBeNull();
});

test('verifyRecoveryCode accepts a valid recovery code and removes it', function (): void {
    $result = $this->service->enableMfa($this->user);
    $plainCode = $result['recovery_codes'][0];

    $verified = $this->service->verifyRecoveryCode($this->user, $plainCode);

    expect($verified)->toBeTrue();
    $this->user->refresh();
    // Used code must be consumed — only 7 remaining
    expect($this->user->mfa_recovery_codes)->toHaveCount(7);
});

test('verifyRecoveryCode rejects invalid code', function (): void {
    $this->service->enableMfa($this->user);

    expect($this->service->verifyRecoveryCode($this->user, 'XXXX-YYYY'))->toBeFalse();
});
