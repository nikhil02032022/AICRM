<?php

declare(strict_types=1);

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Portal\ErpBridgeToken;
use App\Services\CRM\Portal\PortalAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function erpFeatureSetup(string $status = 'enrolled'): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $lead        = Lead::factory()->create(['institution_id' => $institution->id]);
    $programme   = CrmProgramme::factory()->create(['institution_id' => $institution->id]);
    $application = Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
        'status'         => $status,
        'submitted_at'   => now(),
    ]);

    return [$institution, $lead, $application];
}

function erpFeatureSession(Lead $lead, Institution $institution): string
{
    return app(PortalAuthService::class)->issueSession($lead, $institution);
}

function erpParam(Institution $institution): string
{
    return '?institution=' . $institution->uuid;
}

// ──────────────────────────────────────────────────────────────
// Auth guard
// ──────────────────────────────────────────────────────────────

it('redirects unauthenticated request to login', function (): void {
    [$institution, , $application] = erpFeatureSetup();

    $this->get('/portal/applications/' . $application->uuid . '/erp-transition' . erpParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

// ──────────────────────────────────────────────────────────────
// ERP bridge disabled (stub mode)
// ──────────────────────────────────────────────────────────────

it('redirects back with info flash when bridge is disabled', function (): void {
    config(['crm_portal.erp_bridge_base_url' => '']);
    [$institution, $lead, $application] = erpFeatureSetup();
    $token = erpFeatureSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $application->uuid . '/erp-transition' . erpParam($institution))
        ->assertRedirect(route('portal.applications.show', $application->uuid))
        ->assertSessionHas('info');

    expect(ErpBridgeToken::count())->toBe(0);
});

it('does not create a bridge token row when bridge is disabled', function (): void {
    config(['crm_portal.erp_bridge_base_url' => '']);
    [$institution, $lead, $application] = erpFeatureSetup();
    $token = erpFeatureSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $application->uuid . '/erp-transition' . erpParam($institution));

    expect(ErpBridgeToken::count())->toBe(0);
});

// ──────────────────────────────────────────────────────────────
// ERP bridge enabled — enrolled application
// ──────────────────────────────────────────────────────────────

it('issues a token and redirects to ERP URL when bridge is enabled and application is enrolled', function (): void {
    config(['crm_portal.erp_bridge_base_url' => 'https://erp.example.com']);
    [$institution, $lead, $application] = erpFeatureSetup('enrolled');
    $token = erpFeatureSession($lead, $institution);

    $response = $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $application->uuid . '/erp-transition' . erpParam($institution));

    $response->assertRedirect();
    $location = $response->headers->get('Location');
    expect($location)->toStartWith('https://erp.example.com/sso?')
        ->and($location)->toContain('institution=' . $institution->uuid)
        ->and($location)->toContain('applicant=' . $lead->uuid);

    expect(ErpBridgeToken::count())->toBe(1);
    expect(ErpBridgeToken::first()->used_at)->toBeNull();
});

it('creates a single-use token that is not yet consumed', function (): void {
    config(['crm_portal.erp_bridge_base_url' => 'https://erp.example.com']);
    [$institution, $lead, $application] = erpFeatureSetup('enrolled');
    $token = erpFeatureSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $application->uuid . '/erp-transition' . erpParam($institution));

    $bridgeRecord = ErpBridgeToken::first();
    expect($bridgeRecord->isUsed())->toBeFalse()
        ->and($bridgeRecord->isExpired())->toBeFalse()
        ->and($bridgeRecord->lead_uuid)->toBe($lead->uuid)
        ->and($bridgeRecord->application_uuid)->toBe($application->uuid);
});

// ──────────────────────────────────────────────────────────────
// Non-enrolled application
// ──────────────────────────────────────────────────────────────

it('redirects back with error when application is not enrolled', function (): void {
    config(['crm_portal.erp_bridge_base_url' => 'https://erp.example.com']);
    [$institution, $lead, $application] = erpFeatureSetup(ApplicationStatus::OFFER_ACCEPTED->value);
    $token = erpFeatureSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $application->uuid . '/erp-transition' . erpParam($institution))
        ->assertRedirect(route('portal.applications.show', $application->uuid))
        ->assertSessionHas('error');

    expect(ErpBridgeToken::count())->toBe(0);
});

// ──────────────────────────────────────────────────────────────
// Cross-applicant access
// ──────────────────────────────────────────────────────────────

it('redirects to applications index when applicant tries another lead\'s application', function (): void {
    config(['crm_portal.erp_bridge_base_url' => 'https://erp.example.com']);
    [$institution, , $application] = erpFeatureSetup('enrolled');
    $otherLead  = Lead::factory()->create(['institution_id' => $institution->id]);
    $otherToken = erpFeatureSession($otherLead, $institution);

    $this->withCookie('portal_session', $otherToken)
        ->get('/portal/applications/' . $application->uuid . '/erp-transition' . erpParam($institution))
        ->assertRedirect(route('portal.applications.index'));

    expect(ErpBridgeToken::count())->toBe(0);
});

it('redirects to applications index for an unknown application uuid', function (): void {
    config(['crm_portal.erp_bridge_base_url' => 'https://erp.example.com']);
    [$institution, $lead] = erpFeatureSetup('enrolled');
    $token = erpFeatureSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/00000000-0000-0000-0000-000000000000/erp-transition' . erpParam($institution))
        ->assertRedirect(route('portal.applications.index'));
});
