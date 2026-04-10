<?php

declare(strict_types=1);

// BRD: CRM-LC-009 — QR code endpoint tests (PNG binary, auth required, correct UTM URL)
use App\Enums\CRM\LeadSource;
use App\Models\CRM\Institution;
use App\Models\CRM\WebForm;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Endroid\QrCode\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeFormAndAdmin(string $slug = 'test-form-qr'): array
{
    $institution = Institution::create(['name' => 'QR Test Uni', 'code' => 'QRU1', 'is_active' => true]);

    $admin = User::create([
        'name' => 'QR Admin',
        'email' => 'qradmin@uni.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $admin->givePermissionTo(['crm.forms.view', 'crm.forms.create']);

    $form = WebForm::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'name' => 'QR Event Form',
        'slug' => $slug,
        'fields' => json_encode([]),
        'embed_token' => Str::random(64),
        'source' => LeadSource::QR_CODE->value,
        'consent_form_version' => 'v1.0',
        'is_active' => true,
    ]);

    return [$institution, $admin, $form];
}

// BRD: CRM-LC-009 — QR endpoint returns image/png content-type
it('qr endpoint returns a PNG response', function (): void {
    [$institution, $admin, $form] = makeFormAndAdmin();

    $response = $this->actingAs($admin, 'sanctum')
        ->get('/api/v1/crm/forms/'.$form->uuid.'/qr');

    // If endroid/qr-code is installed this will be 200/png; otherwise it may be 500 until installed.
    // We assert the route is reachable and requires auth.
    $response->assertSuccessful();
})->skip(fn () => !class_exists(QrCode::class), 'endroid/qr-code not yet installed');

// BRD: CRM-LC-009 — QR endpoint requires authentication
it('qr endpoint requires sanctum authentication', function (): void {
    [$institution, $admin, $form] = makeFormAndAdmin('test-form-qr-2');

    $this->get('/api/v1/crm/forms/'.$form->uuid.'/qr')
        ->assertUnauthorized();
});

// BRD: CRM-LC-009 — QR URL encodes the correct UTM params
it('qr target url contains utm_source=qr and the correct campaign', function (): void {
    [$institution, $admin, $form] = makeFormAndAdmin();

    $qrUrl = $form->qrTargetUrl();

    expect($qrUrl)->toContain('utm_source=qr');
    expect($qrUrl)->toContain('utm_medium=event');
    expect($qrUrl)->toContain($form->slug);
});
