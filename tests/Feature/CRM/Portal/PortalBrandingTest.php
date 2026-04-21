<?php

declare(strict_types=1);

use App\Models\CRM\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Register a minimal portal test route with the branding middleware
    // so we can assert branding is injected without needing a real offer letter token.
    Route::get('/_portal_branding_probe', function () {
        $branding = view()->getShared()['branding'] ?? [];

        return response()->json([
            'name'          => $branding['name'] ?? null,
            'logo_path'     => $branding['logo_path'] ?? null,
            'primary_color' => $branding['primary_color'] ?? null,
        ]);
    })->middleware(['portal.branding']);
});

// ────────────────────────────────────────────────────────────
// SP-001 — BrandingMiddleware: domain resolution
// ────────────────────────────────────────────────────────────

it('injects institution branding when domain matches', function (): void {
    $institution = Institution::factory()->create([
        'domain'    => 'my-university.com',
        'logo_path' => 'logos/my-uni.png',
        'settings'  => ['primary_color' => '#e11d48'],
        'is_active' => true,
    ]);

    $this->getJson('http://my-university.com/_portal_branding_probe')
        ->assertOk()
        ->assertJsonPath('name', $institution->name)
        ->assertJsonPath('logo_path', 'logos/my-uni.png')
        ->assertJsonPath('primary_color', '#e11d48');
});

it('falls back to default branding when no institution matches the domain', function (): void {
    $this->getJson('http://unknown-domain.com/_portal_branding_probe')
        ->assertOk()
        ->assertJsonPath('name', config('crm_portal.branding.default_institution_name'))
        ->assertJsonPath('logo_path', config('crm_portal.branding.default_logo'))
        ->assertJsonPath('primary_color', config('crm_portal.branding.default_primary_color'));
});

it('resolves branding via institution uuid query param bypass', function (): void {
    $institution = Institution::factory()->create([
        'domain'    => 'real-domain.com',
        'logo_path' => 'logos/test.png',
        'settings'  => ['primary_color' => '#0ea5e9'],
        'is_active' => true,
    ]);

    // Accessing from a different host but passing institution UUID
    $this->getJson('http://localhost/_portal_branding_probe?institution=' . $institution->uuid)
        ->assertOk()
        ->assertJsonPath('name', $institution->name)
        ->assertJsonPath('primary_color', '#0ea5e9');
});

it('returns 403 when institution is inactive', function (): void {
    Institution::factory()->create([
        'domain'    => 'inactive.com',
        'is_active' => false,
    ]);

    $this->get('http://inactive.com/_portal_branding_probe')
        ->assertForbidden();
});

it('falls back to default logo when institution logo_path is null', function (): void {
    Institution::factory()->create([
        'domain'    => 'no-logo.com',
        'logo_path' => null,
        'is_active' => true,
    ]);

    $this->getJson('http://no-logo.com/_portal_branding_probe')
        ->assertOk()
        ->assertJsonPath('logo_path', config('crm_portal.branding.default_logo'));
});

it('falls back to default primary color when settings do not include primary_color', function (): void {
    Institution::factory()->create([
        'domain'    => 'no-color.com',
        'settings'  => [],
        'is_active' => true,
    ]);

    $this->getJson('http://no-color.com/_portal_branding_probe')
        ->assertOk()
        ->assertJsonPath('primary_color', config('crm_portal.branding.default_primary_color'));
});

// ────────────────────────────────────────────────────────────
// SP-001 — Portal route group: offer letter still accessible
// ────────────────────────────────────────────────────────────

it('portal.offers routes are named correctly and accessible', function (): void {
    expect(Route::has('portal.offers.show'))->toBeTrue()
        ->and(Route::has('portal.offers.accept'))->toBeTrue()
        ->and(Route::has('portal.offers.decline'))->toBeTrue();
});
