<?php

declare(strict_types=1);

// BRD: CRM-LC-001 — Public form rendering and submission tests
// BRD: CRM-LC-002 — Conditional field test (hidden required field not validated when condition false)
// BRD: CRM-LC-009 — QR source pre-set via URL param is captured
// BRD: CRM-LC-015 — UTM params from URL stored on lead
// BRD: CRM-CR-001 — Consent required
use App\Enums\CRM\LeadSource;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\WebForm;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

// ─── Factory helper ─────────────────────────────────────────────────────────

function makePublicForm(array $overrides = []): WebForm
{
    $institution = Institution::create(['name' => 'Public Test Uni', 'code' => 'PTU01', 'is_active' => true]);

    return WebForm::withoutGlobalScopes()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Open Day 2026',
        'slug' => 'open-day-2026',
        'fields' => json_encode([
            [
                'id' => 'programme_interest',
                'type' => 'select',
                'label' => 'Programme',
                'required' => true,
                'options' => ['MBA', 'MCA', 'BBA'],
                'show_if' => null,
            ],
            [
                'id' => 'specialisation',
                'type' => 'select',
                'label' => 'Specialisation',
                'required' => true,
                'options' => ['Finance', 'Marketing'],
                // Only shown if programme_interest = MBA (LC-002)
                'show_if' => ['field' => 'programme_interest', 'operator' => 'equals', 'value' => 'MBA'],
            ],
        ]),
        'embed_token' => Str::random(64),
        'source' => LeadSource::EVENT->value,
        'consent_form_version' => 'v1.0',
        'is_active' => true,
    ], $overrides));
}

function baseSubmission(): array
{
    return [
        'first_name' => 'Arjun',
        'last_name' => 'Sharma',
        'mobile' => '9876543210',
        'email' => 'arjun@example.com',
        'consent_given' => true,
        'consent_form_version' => 'v1.0',
        'programme_interest' => 'MBA',
    ];
}

// ─── Tests ──────────────────────────────────────────────────────────────────

// BRD: CRM-LC-001 — Public form renders without authentication
it('renders the public form without authentication', function (): void {
    makePublicForm();
    $this->get('/f/open-day-2026')->assertOk();
});

// BRD: CRM-LC-001 — Submitting valid data creates a lead with correct source
it('public form submission creates a lead with the form source', function (): void {
    makePublicForm();

    $this->postJson('/f/open-day-2026', baseSubmission())
        ->assertOk()
        ->assertJsonPath('success', true);

    $lead = Lead::withoutGlobalScopes()->where('mobile', '9876543210')->first();

    expect($lead)->not->toBeNull();
    expect($lead->source->value)->toBe(LeadSource::EVENT->value);
});

// BRD: CRM-CR-001 — Consent not given returns 422
it('rejects submission when consent is not given', function (): void {
    makePublicForm();

    $payload = baseSubmission();
    $payload['consent_given'] = false;

    $this->postJson('/f/open-day-2026', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['consent_given']);
});

// BRD: CRM-LC-015 — UTM params from URL are stored on the lead
it('captures UTM params from the submission and stores them on lead', function (): void {
    makePublicForm();

    $payload = array_merge(baseSubmission(), [
        'source_utm_params' => [
            'utm_source' => 'qr',
            'utm_medium' => 'event',
            'utm_campaign' => 'open-day-2026',
        ],
    ]);

    $this->postJson('/f/open-day-2026', $payload)->assertOk();

    $lead = Lead::withoutGlobalScopes()->where('mobile', '9876543210')->first();

    expect($lead->source_utm_params)->toMatchArray([
        'utm_source' => 'qr',
        'utm_medium' => 'event',
        'utm_campaign' => 'open-day-2026',
    ]);
});

// BRD: CRM-LC-009 — QR code campaign source captured correctly
it('stores qr_code source when form source is qr_code', function (): void {
    makePublicForm(['source' => LeadSource::QR_CODE->value, 'slug' => 'qr-form']);

    $this->postJson('/f/qr-form', array_merge(baseSubmission(), ['consent_form_version' => 'v1.0']))
        ->assertOk();

    $lead = Lead::withoutGlobalScopes()->where('mobile', '9876543210')->first();
    expect($lead->source->value)->toBe(LeadSource::QR_CODE->value);
});

// BRD: CRM-LC-002 — Conditional field: specialisation is NOT required when programme is MCA
it('does not require conditionally hidden field when condition is false', function (): void {
    makePublicForm();

    // Submit with MCA — specialisation's show_if requires MBA, so it's hidden
    $payload = array_merge(baseSubmission(), ['programme_interest' => 'MCA']);
    // Deliberately omit specialisation (it would be required if visible)

    // The server currently doesn't validate dynamic conditional fields server-side
    // (that is Alpine.js responsibility). The submission should succeed without specialisation.
    $this->postJson('/f/open-day-2026', $payload)->assertOk();
});

// Cross-institution slug isolation — slug from inst A not accidentally resolving for inst B
it('renders 404 for an inactive form slug', function (): void {
    makePublicForm(['is_active' => false, 'slug' => 'inactive-slug']);

    $this->get('/f/inactive-slug')->assertNotFound();
});

// BRD: CRM-LC-001 — Mobile number validation
it('rejects invalid mobile number format', function (): void {
    makePublicForm();

    $payload = array_merge(baseSubmission(), ['mobile' => '12345']);

    $this->postJson('/f/open-day-2026', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mobile']);
});

// BRD: CRM-LC-001 — iFrame embed endpoint renders without auth
it('renders the embed version without authentication', function (): void {
    makePublicForm();
    $this->get('/f/open-day-2026/embed')->assertOk();
});
