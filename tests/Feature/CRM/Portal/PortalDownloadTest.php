<?php

declare(strict_types=1);

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\OfferLetter;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Services\CRM\Portal\PortalAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function downloadSetup(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $lead        = Lead::factory()->create(['institution_id' => $institution->id]);

    return [$institution, $lead];
}

function downloadSession(Lead $lead, Institution $institution): string
{
    return app(PortalAuthService::class)->issueSession($lead, $institution);
}

function downloadParam(Institution $institution): string
{
    return '?institution=' . $institution->uuid;
}

function makeApplication(Institution $institution, Lead $lead, ?CrmProgramme $programme = null): Application
{
    $programme ??= CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    return Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
        'status'         => ApplicationStatus::OFFER_ISSUED->value,
        'submitted_at'   => now(),
    ]);
}

function makeOfferLetter(Application $app, Institution $institution, Lead $lead, array $overrides = []): OfferLetter
{
    return OfferLetter::create(array_merge([
        'institution_id'  => $institution->id,
        'application_uuid' => $app->uuid,
        'lead_uuid'       => $lead->uuid,
        'programme_uuid'  => $app->programme_id,
        'status'          => 'generated',
        'generated_at'    => now(),
    ], $overrides));
}

// ──────────────────────────────────────────────────────────────
// SP-005 — Authentication guard
// ──────────────────────────────────────────────────────────────

it('redirects unauthenticated user away from offer-letter download', function (): void {
    [$institution] = downloadSetup();

    $this->get('/portal/downloads/fake-uuid/offer-letter' . downloadParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

it('redirects unauthenticated user away from admission-letter download', function (): void {
    [$institution] = downloadSetup();

    $this->get('/portal/downloads/fake-uuid/admission-letter' . downloadParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

it('redirects unauthenticated user away from receipt download', function (): void {
    [$institution] = downloadSetup();

    $this->get('/portal/downloads/receipts/fake-uuid' . downloadParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

// ──────────────────────────────────────────────────────────────
// SP-005 — Offer letter download
// ──────────────────────────────────────────────────────────────

it('redirects to a signed S3 URL when offer letter pdf_path is set', function (): void {
    [$institution, $lead] = downloadSetup();
    $app = makeApplication($institution, $lead);
    makeOfferLetter($app, $institution, $lead, [
        'pdf_path' => 'offer-letters/test.pdf',
        'status'   => 'generated',
    ]);

    Storage::fake('s3');
    Storage::disk('s3')->put('offer-letters/test.pdf', '%PDF-dummy');

    $token = downloadSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/downloads/' . $app->uuid . '/offer-letter' . downloadParam($institution))
        ->assertRedirect();
});

it('redirects back with error when offer letter pdf is not yet generated', function (): void {
    [$institution, $lead] = downloadSetup();
    $app = makeApplication($institution, $lead);
    makeOfferLetter($app, $institution, $lead, ['pdf_path' => null, 'status' => 'pending']);

    $token = downloadSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->from('/portal/dashboard' . downloadParam($institution))
        ->get('/portal/downloads/' . $app->uuid . '/offer-letter' . downloadParam($institution))
        ->assertRedirect('/portal/dashboard' . downloadParam($institution))
        ->assertSessionHas('error');
});

it('redirects back with error when application has no offer letter', function (): void {
    [$institution, $lead] = downloadSetup();
    $app = makeApplication($institution, $lead);

    $token = downloadSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->from('/portal/dashboard' . downloadParam($institution))
        ->get('/portal/downloads/' . $app->uuid . '/offer-letter' . downloadParam($institution))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('cannot download another applicant\'s offer letter', function (): void {
    [$institution, $lead]     = downloadSetup();
    $otherLead = Lead::factory()->create(['institution_id' => $institution->id]);
    $otherApp  = makeApplication($institution, $otherLead);
    makeOfferLetter($otherApp, $institution, $otherLead, [
        'pdf_path' => 'offer-letters/other.pdf',
        'status'   => 'generated',
    ]);

    $token = downloadSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->from('/portal/dashboard' . downloadParam($institution))
        ->get('/portal/downloads/' . $otherApp->uuid . '/offer-letter' . downloadParam($institution))
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ──────────────────────────────────────────────────────────────
// SP-005 — Admission letter download
// ──────────────────────────────────────────────────────────────

it('returns an admission confirmation PDF when offer is accepted', function (): void {
    [$institution, $lead] = downloadSetup();
    $app = makeApplication($institution, $lead);
    makeOfferLetter($app, $institution, $lead, [
        'status'                  => 'accepted',
        'acceptance_recorded_at'  => now(),
    ]);

    $token = downloadSession($lead, $institution);

    $response = $this->withCookie('portal_session', $token)
        ->get('/portal/downloads/' . $app->uuid . '/admission-letter' . downloadParam($institution));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'attachment; filename="admission-confirmation.pdf"');
});

it('redirects back with error when offer not yet accepted', function (): void {
    [$institution, $lead] = downloadSetup();
    $app = makeApplication($institution, $lead);
    makeOfferLetter($app, $institution, $lead, ['status' => 'generated']);

    $token = downloadSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->from('/portal/dashboard' . downloadParam($institution))
        ->get('/portal/downloads/' . $app->uuid . '/admission-letter' . downloadParam($institution))
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ──────────────────────────────────────────────────────────────
// SP-005 — Payment receipt download
// ──────────────────────────────────────────────────────────────

it('returns a payment receipt PDF for a confirmed transaction', function (): void {
    [$institution, $lead] = downloadSetup();
    $app = makeApplication($institution, $lead);

    $txn = PaymentTransaction::factory()->create([
        'institution_id'    => $institution->id,
        'application_uuid'  => $app->uuid,
        'lead_uuid'         => $lead->uuid,
        'amount'            => 12500.00,
        'status'            => PaymentStatus::SUCCESS->value,
        'confirmed_at'      => now(),
        'gateway_payment_id' => 'pay_TEST123',
    ]);

    $token = downloadSession($lead, $institution);

    $response = $this->withCookie('portal_session', $token)
        ->get('/portal/downloads/receipts/' . $txn->uuid . downloadParam($institution));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'attachment; filename="payment-receipt.pdf"');
});

it('redirects back with error for a pending (non-confirmed) transaction', function (): void {
    [$institution, $lead] = downloadSetup();
    $app = makeApplication($institution, $lead);

    $txn = PaymentTransaction::factory()->create([
        'institution_id'   => $institution->id,
        'application_uuid' => $app->uuid,
        'lead_uuid'        => $lead->uuid,
        'status'           => PaymentStatus::PENDING->value,
    ]);

    $token = downloadSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->from('/portal/dashboard' . downloadParam($institution))
        ->get('/portal/downloads/receipts/' . $txn->uuid . downloadParam($institution))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('cannot download another applicant\'s payment receipt', function (): void {
    [$institution, $lead] = downloadSetup();
    $otherLead = Lead::factory()->create(['institution_id' => $institution->id]);
    $otherApp  = makeApplication($institution, $otherLead);

    $txn = PaymentTransaction::factory()->create([
        'institution_id'   => $institution->id,
        'application_uuid' => $otherApp->uuid,
        'lead_uuid'        => $otherLead->uuid,
        'status'           => PaymentStatus::SUCCESS->value,
        'confirmed_at'     => now(),
    ]);

    $token = downloadSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->from('/portal/dashboard' . downloadParam($institution))
        ->get('/portal/downloads/receipts/' . $txn->uuid . downloadParam($institution))
        ->assertRedirect()
        ->assertSessionHas('error');
});
