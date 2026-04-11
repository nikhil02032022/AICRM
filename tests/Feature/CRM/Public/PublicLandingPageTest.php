<?php

declare(strict_types=1);

// BRD: CRM-LC-005 — Public landing page visibility tests
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LandingPageStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\LandingPage;
use App\Models\CRM\WebForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makePublishedLandingPage(array $overrides = []): LandingPage
{
    $institution = Institution::create([
        'name' => 'Public Landing University',
        'code' => 'PLU01',
        'is_active' => true,
    ]);

    $creator = User::create([
        'name' => 'Public Marketing User',
        'email' => 'public-marketing@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $webForm = WebForm::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'Scholarship Form',
        'slug' => 'scholarship-form',
        'fields' => json_encode([
            ['id' => 'programme', 'type' => 'text', 'label' => 'Programme', 'required' => true, 'show_if' => null],
        ], JSON_THROW_ON_ERROR),
        'embed_token' => Str::random(64),
        'source' => LeadSource::WEBSITE_ORGANIC->value,
        'consent_form_version' => 'v1.0',
        'is_active' => true,
    ]);

    return LandingPage::withoutGlobalScopes()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'web_form_id' => $webForm->id,
        'created_by' => $creator->id,
        'name' => 'MBA 2027 Scholarship',
        'slug' => 'mba-2027-scholarship',
        'status' => LandingPageStatus::PUBLISHED,
        'theme_variant' => 'scholar',
        'headline' => 'Scholarships for MBA 2027 aspirants',
        'subheadline' => 'Capture attributed campaign leads inside the CRM form flow.',
        'cta_label' => 'Submit enquiry',
        'cta_secondary_label' => 'Explore benefits',
        'content' => [
            ['eyebrow' => 'Placements', 'title' => 'Career acceleration', 'body' => 'Placement-focused positioning for paid campaigns.'],
        ],
        'attribution_params' => ['utm_source' => 'google', 'utm_campaign' => 'mba-2027'],
        'published_at' => now(),
    ], $overrides));
}

it('renders a published landing page publicly', function (): void {
    $landingPage = makePublishedLandingPage();

    $this->get('/lp/'.$landingPage->slug)
        ->assertOk()
        ->assertSeeText('Scholarships for MBA 2027 aspirants')
        ->assertSeeText('Submit enquiry')
        ->assertSee('title="Scholarship Form"', false);

    $this->assertDatabaseHas('landing_page_views', [
        'institution_id' => $landingPage->institution_id,
        'landing_page_id' => $landingPage->id,
    ]);
});

it('returns 404 for a draft landing page', function (): void {
    $landingPage = makePublishedLandingPage([
        'slug' => 'draft-page',
        'status' => LandingPageStatus::DRAFT,
        'published_at' => null,
    ]);

    $this->get('/lp/'.$landingPage->slug)->assertNotFound();

    $this->assertDatabaseCount('landing_page_views', 0);
});

it('stores UTM metadata when rendering a published landing page', function (): void {
    $landingPage = makePublishedLandingPage([
        'slug' => 'mba-2027-utm',
    ]);

    $this->get('/lp/'.$landingPage->slug.'?utm_source=meta&utm_medium=paid_social&utm_campaign=mba_launch')
        ->assertOk();

    $this->assertDatabaseHas('landing_page_views', [
        'institution_id' => $landingPage->institution_id,
        'landing_page_id' => $landingPage->id,
        'utm_source' => 'meta',
        'utm_medium' => 'paid_social',
        'utm_campaign' => 'mba_launch',
    ]);
});