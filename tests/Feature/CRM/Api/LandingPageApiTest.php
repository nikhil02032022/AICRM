<?php

declare(strict_types=1);

// BRD: CRM-LC-005 — Landing page API CRUD and institution scoping tests
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LandingPageStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\LandingPage;
use App\Models\CRM\WebForm;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeMarketingAdmin(string $suffix = 'a'): array
{
    $institution = Institution::create([
        'name' => 'Marketing Test University '.$suffix,
        'code' => 'MTU'.strtoupper($suffix),
        'is_active' => true,
    ]);

    $admin = User::create([
        'name' => 'Marketing Admin '.$suffix,
        'email' => 'marketing-'.$suffix.'@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $admin->givePermissionTo(['crm.campaigns.manage']);

    return [$institution, $admin];
}

function makeLandingWebForm(int $institutionId, string $slug = 'marketing-form'): WebForm
{
    return WebForm::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institutionId,
        'name' => 'Marketing Capture Form',
        'slug' => $slug,
        'fields' => json_encode([
            ['id' => 'programme', 'type' => 'text', 'label' => 'Programme', 'required' => true, 'show_if' => null],
        ], JSON_THROW_ON_ERROR),
        'embed_token' => Str::random(64),
        'source' => LeadSource::WEBSITE_ORGANIC->value,
        'consent_form_version' => 'v1.0',
        'is_active' => true,
    ]);
}

function landingPagePayload(int $webFormId): array
{
    return [
        'name' => 'MBA Scholarship 2027',
        'slug' => 'mba-scholarship-2027',
        'status' => LandingPageStatus::PUBLISHED->value,
        'theme_variant' => 'sunrise',
        'headline' => 'Scholarships and fast-track counselling for MBA 2027',
        'subheadline' => 'Capture high-intent enquiries from paid campaigns with source-aware forms.',
        'cta_label' => 'Apply now',
        'cta_secondary_label' => 'View highlights',
        'content' => [
            ['eyebrow' => 'Placements', 'title' => 'Career outcomes', 'body' => 'Focused programme pathways with placement guidance.'],
        ],
        'attribution_params' => [
            'utm_source' => 'meta_ads',
            'utm_medium' => 'paid_social',
            'utm_campaign' => 'mba-2027',
        ],
        'seo_title' => 'MBA 2027 scholarships',
        'seo_description' => 'Scholarship-led MBA campaign page with CRM web form capture.',
        'web_form_id' => $webFormId,
    ];
}

it('can create a landing page via API', function (): void {
    [$institution, $admin] = makeMarketingAdmin();
    $webForm = makeLandingWebForm($institution->id);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/landing-pages', landingPagePayload($webForm->id));

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'MBA Scholarship 2027')
        ->assertJsonPath('data.web_form.name', 'Marketing Capture Form')
        ->assertJsonPath('data.attribution_params.utm_source', 'meta_ads');

    $this->assertDatabaseHas('landing_pages', [
        'institution_id' => $institution->id,
        'slug' => 'mba-scholarship-2027',
        'status' => LandingPageStatus::PUBLISHED->value,
    ]);
});

it('returns only landing pages from the authenticated institution', function (): void {
    [$institutionA, $adminA] = makeMarketingAdmin('a');
    [$institutionB, $adminB] = makeMarketingAdmin('b');

    $webFormA = makeLandingWebForm($institutionA->id, 'form-a');
    $webFormB = makeLandingWebForm($institutionB->id, 'form-b');

    $this->actingAs($adminA, 'sanctum')->postJson('/api/v1/crm/landing-pages', landingPagePayload($webFormA->id))->assertCreated();
    $this->actingAs($adminB, 'sanctum')->postJson('/api/v1/crm/landing-pages', array_merge(landingPagePayload($webFormB->id), [
        'slug' => 'institution-b-page',
        'name' => 'Institution B Page',
    ]))->assertCreated();

    $response = $this->actingAs($adminA, 'sanctum')->getJson('/api/v1/crm/landing-pages');

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.slug'))->toBe('mba-scholarship-2027');
});

it('cannot read another institutions landing page', function (): void {
    [$institutionA, $adminA] = makeMarketingAdmin('a');
    [, $adminB] = makeMarketingAdmin('b');
    $webForm = makeLandingWebForm($institutionA->id);

    $landingPage = LandingPage::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institutionA->id,
        'web_form_id' => $webForm->id,
        'created_by' => $adminA->id,
        'name' => 'Institution A only',
        'slug' => 'institution-a-only',
        'status' => LandingPageStatus::PUBLISHED,
        'theme_variant' => 'scholar',
        'headline' => 'Private to institution A',
        'cta_label' => 'Enquire now',
        'content' => [],
        'attribution_params' => ['utm_source' => 'google'],
        'published_at' => now(),
    ]);

    $this->actingAs($adminB, 'sanctum')
        ->getJson('/api/v1/crm/landing-pages/'.$landingPage->uuid)
        ->assertNotFound();
});

it('can update a landing page via API', function (): void {
    [$institution, $admin] = makeMarketingAdmin();
    $webForm = makeLandingWebForm($institution->id);

    $createResponse = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/landing-pages', landingPagePayload($webForm->id))
        ->assertCreated();

    $uuid = $createResponse->json('data.uuid');

    $this->actingAs($admin, 'sanctum')
        ->putJson('/api/v1/crm/landing-pages/'.$uuid, [
            'headline' => 'Updated scholarship headline',
            'status' => LandingPageStatus::ARCHIVED->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.headline', 'Updated scholarship headline')
        ->assertJsonPath('data.status', LandingPageStatus::ARCHIVED->value);
});

it('can soft delete a landing page via API', function (): void {
    [$institution, $admin] = makeMarketingAdmin();
    $webForm = makeLandingWebForm($institution->id);

    $createResponse = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/landing-pages', landingPagePayload($webForm->id))
        ->assertCreated();

    $uuid = $createResponse->json('data.uuid');

    $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/v1/crm/landing-pages/'.$uuid)
        ->assertOk();

    $this->assertSoftDeleted('landing_pages', ['uuid' => $uuid]);
});

it('stores ordered content blocks for landing page builder payloads', function (): void {
    [$institution, $admin] = makeMarketingAdmin('c');
    $webForm = makeLandingWebForm($institution->id, 'form-c');

    $payload = landingPagePayload($webForm->id);
    $payload['content'] = [
        [
            'id' => 'block-b',
            'type' => 'value_card',
            'order' => 0,
            'eyebrow' => 'Scholarship',
            'title' => 'Fee support options',
            'body' => 'Compare merit and need-based support in one place.',
        ],
        [
            'id' => 'block-a',
            'type' => 'value_card',
            'order' => 1,
            'eyebrow' => 'Admissions',
            'title' => 'Guided counselling journey',
            'body' => 'Track enquiry to offer stage with counsellor support.',
        ],
    ];

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/landing-pages', $payload)
        ->assertCreated();

    expect($response->json('data.content.0.id'))->toBe('block-b');
    expect($response->json('data.content.0.order'))->toBe(0);
    expect($response->json('data.content.1.id'))->toBe('block-a');
    expect($response->json('data.content.1.order'))->toBe(1);
});

it('parses content_json payload into ordered content blocks', function (): void {
    [$institution, $admin] = makeMarketingAdmin('d');
    $webForm = makeLandingWebForm($institution->id, 'form-d');

    $payload = landingPagePayload($webForm->id);
    unset($payload['content']);
    $payload['content_json'] = json_encode([
        [
            'id' => 'block-json-2',
            'type' => 'value_card',
            'order' => 0,
            'eyebrow' => 'Placements',
            'title' => 'Recruiter connect week',
            'body' => 'Meet hiring partners and alumni mentors.',
        ],
        [
            'id' => 'block-json-1',
            'type' => 'value_card',
            'order' => 1,
            'eyebrow' => 'Admissions',
            'title' => 'Priority interview slots',
            'body' => 'Get a faster counselling turnaround after enquiry.',
        ],
    ], JSON_THROW_ON_ERROR);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/landing-pages', $payload)
        ->assertCreated();

    expect($response->json('data.content.0.id'))->toBe('block-json-2');
    expect($response->json('data.content.0.order'))->toBe(0);
    expect($response->json('data.content.1.id'))->toBe('block-json-1');
    expect($response->json('data.content.1.order'))->toBe(1);
});

it('stores stat and faq block composition fields', function (): void {
    [$institution, $admin] = makeMarketingAdmin('e');
    $webForm = makeLandingWebForm($institution->id, 'form-e');

    $payload = landingPagePayload($webForm->id);
    $payload['content'] = [
        [
            'id' => 'block-stat-1',
            'type' => 'stat',
            'order' => 0,
            'metric_label' => 'Placement Support',
            'metric_value' => '94%',
            'body' => 'Students placed in tracked hiring cycles.',
        ],
        [
            'id' => 'block-faq-1',
            'type' => 'faq',
            'order' => 1,
            'question' => 'Can I apply without an entrance score?',
            'answer' => 'You can submit enquiry now and share score during counselling.',
        ],
    ];

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/crm/landing-pages', $payload)
        ->assertCreated();

    expect($response->json('data.content.0.type'))->toBe('stat');
    expect($response->json('data.content.0.metric_label'))->toBe('Placement Support');
    expect($response->json('data.content.1.type'))->toBe('faq');
    expect($response->json('data.content.1.question'))->toBe('Can I apply without an entrance score?');
});