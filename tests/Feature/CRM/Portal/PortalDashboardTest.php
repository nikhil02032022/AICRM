<?php

declare(strict_types=1);

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\CounsellingSessionStatus;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Documents\DocumentChecklistItem;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Services\CRM\Portal\PortalAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function dashboardSetup(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $lead = Lead::factory()->create(['institution_id' => $institution->id]);

    return [$institution, $lead];
}

function issueSessionCookie(Lead $lead, Institution $institution): string
{
    return app(PortalAuthService::class)->issueSession($lead, $institution);
}

function portalParam(Institution $institution): string
{
    return '?institution=' . $institution->uuid;
}

// ──────────────────────────────────────────────────────────────
// SP-003 — Authentication guard
// ──────────────────────────────────────────────────────────────

it('redirects unauthenticated visitors to login', function (): void {
    [$institution] = dashboardSetup();

    $this->get('/portal/dashboard' . portalParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

it('redirects unauthenticated visitors from portal root to login', function (): void {
    [$institution] = dashboardSetup();

    $this->get('/portal/' . portalParam($institution))
        ->assertRedirect();
});

// ──────────────────────────────────────────────────────────────
// SP-003 — Dashboard renders
// ──────────────────────────────────────────────────────────────

it('renders the dashboard for an authenticated applicant', function (): void {
    [$institution, $lead] = dashboardSetup();
    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertViewIs('portal.dashboard')
        ->assertSee($lead->first_name);
});

it('shows the empty state when the applicant has no applications', function (): void {
    [$institution, $lead] = dashboardSetup();
    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertSee('No applications yet');
});

// ──────────────────────────────────────────────────────────────
// SP-003 — Application status cards
// ──────────────────────────────────────────────────────────────

it('shows application status and programme on the dashboard', function (): void {
    [$institution, $lead] = dashboardSetup();

    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
        'status'         => ApplicationStatus::OFFER_ISSUED->value,
        'submitted_at'   => now(),
    ]);

    $token = issueSessionCookie($lead, $institution);

    $response = $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk();

    $response->assertSee($programme->name);
    $response->assertSee('Offer Issued');
});

it('shows multiple applications for a multi-programme applicant', function (): void {
    [$institution, $lead] = dashboardSetup();

    $p1 = CrmProgramme::factory()->create(['institution_id' => $institution->id]);
    $p2 = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $p1->id,
        'status'         => ApplicationStatus::UNDER_REVIEW->value,
    ]);
    Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $p2->id,
        'status'         => ApplicationStatus::SHORTLISTED->value,
    ]);

    $token = issueSessionCookie($lead, $institution);

    $response = $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk();

    $response->assertSee($p1->name);
    $response->assertSee($p2->name);
    $response->assertSee('Under Review');
    $response->assertSee('Shortlisted');
});

// ──────────────────────────────────────────────────────────────
// SP-003 — Document checklist
// ──────────────────────────────────────────────────────────────

it('shows pending document count when mandatory items are missing', function (): void {
    [$institution, $lead] = dashboardSetup();

    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    $app = Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
    ]);

    $checklist = DocumentChecklist::factory()->create([
        'institution_id' => $institution->id,
        'programme_id'   => $programme->id,
        'is_active'      => true,
    ]);

    DocumentChecklistItem::factory()->count(3)->create([
        'institution_id'       => $institution->id,
        'document_checklist_id' => $checklist->id,
        'is_mandatory'         => true,
    ]);

    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertSee('3 pending');
});

it('shows all submitted when all mandatory documents are uploaded', function (): void {
    [$institution, $lead] = dashboardSetup();

    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    $app = Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
    ]);

    $checklist = DocumentChecklist::factory()->create([
        'institution_id' => $institution->id,
        'programme_id'   => $programme->id,
        'is_active'      => true,
    ]);

    $item = DocumentChecklistItem::factory()->create([
        'institution_id'       => $institution->id,
        'document_checklist_id' => $checklist->id,
        'is_mandatory'         => true,
    ]);

    ApplicationDocument::factory()->create([
        'institution_id'              => $institution->id,
        'application_uuid'            => $app->uuid,
        'lead_uuid'                   => $lead->uuid,
        'document_checklist_item_id'  => $item->id,
    ]);

    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertSee('All submitted');
});

// ──────────────────────────────────────────────────────────────
// SP-003 — Payment history
// ──────────────────────────────────────────────────────────────

it('shows confirmed payment total on the dashboard', function (): void {
    [$institution, $lead] = dashboardSetup();

    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    $app = Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
    ]);

    PaymentTransaction::factory()->create([
        'institution_id'   => $institution->id,
        'application_uuid' => $app->uuid,
        'lead_uuid'        => $lead->uuid,
        'amount'           => 5000.00,
        'status'           => PaymentStatus::SUCCESS->value,
    ]);

    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertSee('5,000.00')
        ->assertSee('1 payment confirmed');
});

it('does not include failed payments in the total', function (): void {
    [$institution, $lead] = dashboardSetup();

    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    $app = Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
    ]);

    PaymentTransaction::factory()->create([
        'institution_id'   => $institution->id,
        'application_uuid' => $app->uuid,
        'lead_uuid'        => $lead->uuid,
        'amount'           => 9999.00,
        'status'           => PaymentStatus::FAILED->value,
    ]);

    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertSee('No payments yet')
        ->assertDontSee('9,999');
});

// ──────────────────────────────────────────────────────────────
// SP-003 — Upcoming appointments
// ──────────────────────────────────────────────────────────────

it('shows upcoming counselling sessions on the dashboard', function (): void {
    [$institution, $lead] = dashboardSetup();

    CounsellingSession::create([
        'institution_id'   => $institution->id,
        'lead_id'          => $lead->id,
        'session_type'     => 'online',
        'status'           => CounsellingSessionStatus::SCHEDULED->value,
        'mode'             => 'video',
        'scheduled_at'     => Carbon::tomorrow()->setTime(10, 0),
    ]);

    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertSee('Upcoming Appointments');
});

it('does not show past or cancelled appointments', function (): void {
    [$institution, $lead] = dashboardSetup();

    CounsellingSession::create([
        'institution_id' => $institution->id,
        'lead_id'        => $lead->id,
        'session_type'   => 'online',
        'status'         => CounsellingSessionStatus::CANCELLED->value,
        'mode'           => 'video',
        'scheduled_at'   => Carbon::yesterday(),
    ]);

    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertSee('No upcoming appointments scheduled');
});

// ──────────────────────────────────────────────────────────────
// SP-003 — Cross-applicant isolation
// ──────────────────────────────────────────────────────────────

it('does not expose another applicant\'s applications', function (): void {
    [$institution, $lead] = dashboardSetup();

    $otherLead = Lead::factory()->create(['institution_id' => $institution->id]);
    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $otherLead->uuid,
        'programme_id'   => $programme->id,
    ]);

    $token = issueSessionCookie($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/dashboard' . portalParam($institution))
        ->assertOk()
        ->assertSee('No applications yet');
});
