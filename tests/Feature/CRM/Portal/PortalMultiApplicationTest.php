<?php

declare(strict_types=1);

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Documents\DocumentChecklistItem;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Services\CRM\Portal\PortalAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

function multiAppSetup(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $lead        = Lead::factory()->create(['institution_id' => $institution->id]);

    return [$institution, $lead];
}

function multiAppSession(Lead $lead, Institution $institution): string
{
    return app(PortalAuthService::class)->issueSession($lead, $institution);
}

function multiAppParam(Institution $institution): string
{
    return '?institution=' . $institution->uuid;
}

// ──────────────────────────────────────────────────────────────
// SP-006 — Authentication guard: index
// ──────────────────────────────────────────────────────────────

it('redirects unauthenticated visitors from applications index to login', function (): void {
    [$institution] = multiAppSetup();

    $this->get('/portal/applications' . multiAppParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

// ──────────────────────────────────────────────────────────────
// SP-006 — Authentication guard: show
// ──────────────────────────────────────────────────────────────

it('redirects unauthenticated visitors from application detail to login', function (): void {
    [$institution] = multiAppSetup();

    $this->get('/portal/applications/fake-uuid' . multiAppParam($institution))
        ->assertRedirect(route('portal.auth.login'));
});

// ──────────────────────────────────────────────────────────────
// SP-006 — Applications index
// ──────────────────────────────────────────────────────────────

it('renders the applications index for an authenticated applicant', function (): void {
    [$institution, $lead] = multiAppSetup();
    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications' . multiAppParam($institution))
        ->assertOk()
        ->assertViewIs('portal.applications.index');
});

it('shows empty state when the applicant has no applications', function (): void {
    [$institution, $lead] = multiAppSetup();
    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications' . multiAppParam($institution))
        ->assertOk()
        ->assertSee('No applications yet');
});

it('shows a single application in the list', function (): void {
    [$institution, $lead] = multiAppSetup();

    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
        'status'         => ApplicationStatus::UNDER_REVIEW->value,
        'submitted_at'   => now(),
    ]);

    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications' . multiAppParam($institution))
        ->assertOk()
        ->assertSee($programme->name)
        ->assertSee('Under Review');
});

it('shows all applications for a multi-programme applicant', function (): void {
    [$institution, $lead] = multiAppSetup();

    $p1 = CrmProgramme::factory()->create(['institution_id' => $institution->id]);
    $p2 = CrmProgramme::factory()->create(['institution_id' => $institution->id]);
    $p3 = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    foreach ([[$p1, ApplicationStatus::UNDER_REVIEW], [$p2, ApplicationStatus::SHORTLISTED], [$p3, ApplicationStatus::OFFER_ISSUED]] as [$prog, $status]) {
        Application::factory()->create([
            'institution_id' => $institution->id,
            'lead_uuid'      => $lead->uuid,
            'programme_id'   => $prog->id,
            'status'         => $status->value,
            'submitted_at'   => now(),
        ]);
    }

    $token = multiAppSession($lead, $institution);

    $response = $this->withCookie('portal_session', $token)
        ->get('/portal/applications' . multiAppParam($institution))
        ->assertOk();

    $response->assertSee($p1->name);
    $response->assertSee($p2->name);
    $response->assertSee($p3->name);
    $response->assertSee('Under Review');
    $response->assertSee('Shortlisted');
    $response->assertSee('Offer Issued');
});

it('does not show another applicant\'s applications in the list', function (): void {
    [$institution, $lead] = multiAppSetup();

    $otherLead = Lead::factory()->create(['institution_id' => $institution->id]);
    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $otherLead->uuid,
        'programme_id'   => $programme->id,
    ]);

    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications' . multiAppParam($institution))
        ->assertOk()
        ->assertSee('No applications yet');
});

// ──────────────────────────────────────────────────────────────
// SP-006 — Application detail
// ──────────────────────────────────────────────────────────────

it('renders the application detail for an authenticated applicant', function (): void {
    [$institution, $lead] = multiAppSetup();

    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    $app = Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $lead->uuid,
        'programme_id'   => $programme->id,
        'status'         => ApplicationStatus::SHORTLISTED->value,
        'submitted_at'   => now(),
    ]);

    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $app->uuid . multiAppParam($institution))
        ->assertOk()
        ->assertViewIs('portal.applications.show')
        ->assertSee($programme->name)
        ->assertSee('Shortlisted');
});

it('shows the document checklist on the application detail', function (): void {
    [$institution, $lead] = multiAppSetup();

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
        'institution_id'        => $institution->id,
        'document_checklist_id' => $checklist->id,
        'is_mandatory'          => true,
        'name'                  => 'Marksheet',
    ]);

    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $app->uuid . multiAppParam($institution))
        ->assertOk()
        ->assertSee('Marksheet')
        ->assertSee('Pending');
});

it('shows payment history on the application detail', function (): void {
    [$institution, $lead] = multiAppSetup();

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
        'amount'           => 12500.00,
        'status'           => PaymentStatus::SUCCESS->value,
    ]);

    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $app->uuid . multiAppParam($institution))
        ->assertOk()
        ->assertSee('12,500.00');
});

it('redirects to the applications list when accessing another applicant\'s detail', function (): void {
    [$institution, $lead] = multiAppSetup();

    $otherLead = Lead::factory()->create(['institution_id' => $institution->id]);
    $programme = CrmProgramme::factory()->create(['institution_id' => $institution->id]);

    $otherApp = Application::factory()->create([
        'institution_id' => $institution->id,
        'lead_uuid'      => $otherLead->uuid,
        'programme_id'   => $programme->id,
    ]);

    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $otherApp->uuid . multiAppParam($institution))
        ->assertRedirect(route('portal.applications.index'));
});

it('cannot access an application from a different institution', function (): void {
    [$institution, $lead] = multiAppSetup();

    $otherInstitution = Institution::factory()->create(['is_active' => true]);
    $otherLead        = Lead::factory()->create(['institution_id' => $otherInstitution->id]);
    $programme        = CrmProgramme::factory()->create(['institution_id' => $otherInstitution->id]);

    $otherApp = Application::factory()->create([
        'institution_id' => $otherInstitution->id,
        'lead_uuid'      => $otherLead->uuid,
        'programme_id'   => $programme->id,
    ]);

    $token = multiAppSession($lead, $institution);

    $this->withCookie('portal_session', $token)
        ->get('/portal/applications/' . $otherApp->uuid . multiAppParam($institution))
        ->assertRedirect(route('portal.applications.index'));
});
