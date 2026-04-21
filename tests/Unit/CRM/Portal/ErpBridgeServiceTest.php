<?php

declare(strict_types=1);

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Portal\ErpBridgeToken;
use App\Services\CRM\Portal\ErpBridgeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function erpSetup(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $lead        = Lead::factory()->create(['institution_id' => $institution->id]);
    $programme   = CrmProgramme::factory()->create(['institution_id' => $institution->id]);
    $application = Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
        'status'         => ApplicationStatus::ENROLLED->value,
        'submitted_at'   => now(),
    ]);

    return [$institution, $lead, $application];
}

function erpService(): ErpBridgeService
{
    return app(ErpBridgeService::class);
}

// ──────────────────────────────────────────────────────────────
// isEnabled
// ──────────────────────────────────────────────────────────────

it('reports disabled when erp_bridge_base_url is empty', function (): void {
    config(['crm_portal.erp_bridge_base_url' => '']);

    expect(erpService()->isEnabled())->toBeFalse();
});

it('reports enabled when erp_bridge_base_url is set', function (): void {
    config(['crm_portal.erp_bridge_base_url' => 'https://erp.example.com']);

    expect(erpService()->isEnabled())->toBeTrue();
});

// ──────────────────────────────────────────────────────────────
// issue
// ──────────────────────────────────────────────────────────────

it('creates an erp_bridge_tokens row and returns a 80-char hex plain token', function (): void {
    [$institution, $lead, $application] = erpSetup();

    $plainToken = erpService()->issue($lead, $application, $institution);

    expect($plainToken)->toHaveLength(80)
        ->and(ctype_xdigit($plainToken))->toBeTrue()
        ->and(ErpBridgeToken::count())->toBe(1);

    $record = ErpBridgeToken::first();
    expect($record->token_hash)->toBe(hash('sha256', $plainToken))
        ->and($record->used_at)->toBeNull()
        ->and($record->lead_uuid)->toBe($lead->uuid)
        ->and($record->institution_id)->toBe($institution->id)
        ->and($record->application_uuid)->toBe($application->uuid);
});

it('sets expires_at to configured minutes from now', function (): void {
    config(['crm_portal.erp_bridge_token_expiry_minutes' => 5]);
    [$institution, $lead, $application] = erpSetup();

    Carbon::setTestNow(Carbon::parse('2026-04-21 10:00:00'));
    erpService()->issue($lead, $application, $institution);
    Carbon::setTestNow(null);

    $record = ErpBridgeToken::first();
    expect($record->expires_at->toDateTimeString())->toBe('2026-04-21 10:05:00');
});

it('throws AuthorizationException when application belongs to a different lead', function (): void {
    [$institution, $lead, $application] = erpSetup();
    $otherLead = Lead::factory()->create(['institution_id' => $institution->id]);

    erpService()->issue($otherLead, $application, $institution);
})->throws(AuthorizationException::class);

it('throws AuthorizationException when application belongs to a different institution', function (): void {
    [$institution, $lead, $application] = erpSetup();
    $otherInstitution = Institution::factory()->create(['is_active' => true]);

    erpService()->issue($lead, $application, $otherInstitution);
})->throws(AuthorizationException::class);

it('throws RuntimeException for non-enrolled application', function (): void {
    [$institution, $lead, $application] = erpSetup();
    $application->update(['status' => ApplicationStatus::OFFER_ACCEPTED->value]);

    erpService()->issue($lead, $application, $institution);
})->throws(\RuntimeException::class, 'enrolled');

// ──────────────────────────────────────────────────────────────
// buildRedirectUrl
// ──────────────────────────────────────────────────────────────

it('builds a correctly structured ERP redirect URL', function (): void {
    config(['crm_portal.erp_bridge_base_url' => 'https://erp.example.com']);
    [$institution, $lead] = erpSetup();

    $url = erpService()->buildRedirectUrl('abc123', $lead, $institution);

    expect($url)->toStartWith('https://erp.example.com/sso?')
        ->and($url)->toContain('token=abc123')
        ->and($url)->toContain('institution=' . $institution->uuid)
        ->and($url)->toContain('applicant=' . $lead->uuid);
});

it('strips trailing slash from erp_bridge_base_url', function (): void {
    config(['crm_portal.erp_bridge_base_url' => 'https://erp.example.com/']);
    [$institution, $lead] = erpSetup();

    $url = erpService()->buildRedirectUrl('tok', $lead, $institution);

    expect($url)->toStartWith('https://erp.example.com/sso?');
});

// ──────────────────────────────────────────────────────────────
// consume
// ──────────────────────────────────────────────────────────────

it('marks a valid token as used and returns the record', function (): void {
    [$institution, $lead, $application] = erpSetup();
    $plainToken = erpService()->issue($lead, $application, $institution);

    $record = erpService()->consume($plainToken, $institution);

    expect($record->isUsed())->toBeTrue()
        ->and($record->lead_uuid)->toBe($lead->uuid);
});

it('throws RuntimeException when consuming an unknown token', function (): void {
    [$institution] = erpSetup();

    erpService()->consume('deadbeef', $institution);
})->throws(\RuntimeException::class, 'Invalid');

it('throws RuntimeException when consuming an already-used token', function (): void {
    [$institution, $lead, $application] = erpSetup();
    $plainToken = erpService()->issue($lead, $application, $institution);

    erpService()->consume($plainToken, $institution);
    erpService()->consume($plainToken, $institution);
})->throws(\RuntimeException::class, 'already been used');

it('throws RuntimeException when consuming an expired token', function (): void {
    config(['crm_portal.erp_bridge_token_expiry_minutes' => 5]);
    [$institution, $lead, $application] = erpSetup();

    Carbon::setTestNow(Carbon::parse('2026-04-21 10:00:00'));
    $plainToken = erpService()->issue($lead, $application, $institution);
    Carbon::setTestNow(Carbon::parse('2026-04-21 10:06:00'));

    erpService()->consume($plainToken, $institution);

    Carbon::setTestNow(null);
})->throws(\RuntimeException::class, 'expired');
