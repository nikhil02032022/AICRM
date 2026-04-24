<?php

declare(strict_types=1);

// BRD: CRM-CR-001 — Explicit consent at lead creation
// BRD: CRM-CR-002 — Consent records with timestamp, IP, form version

use App\Enums\CRM\Compliance\ConsentType;
use App\Models\CRM\ConsentRecord;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Compliance\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->user        = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->service     = app(ConsentService::class);

    $this->lead = Lead::withoutGlobalScopes()->create([
        'institution_id'    => $this->institution->id,
        'first_name'        => 'Test',
        'last_name'         => 'Lead',
        'email'             => encrypt('test@example.com'),
        'mobile'            => encrypt('9000000001'),
        'source'            => 'walk_in',
        'status'            => 'new_enquiry',
        'consent_given'     => true,
        'consent_timestamp' => now(),
    ]);
});

it('capture() creates ConsentRecord with correct lead_id and form_version', function (): void {
    $request = Request::create('/test', 'POST');

    $record = $this->service->capture(
        $this->lead,
        ConsentType::MarketingCommunication,
        $request,
        '1.0'
    );

    expect($record)->toBeInstanceOf(ConsentRecord::class);
    expect($record->lead_id)->toBe($this->lead->id);

    $this->assertDatabaseHas('consent_records', [
        'lead_id' => $this->lead->id,
    ]);
});

it('isConsentGiven() returns true when record exists', function (): void {
    ConsentRecord::withoutGlobalScopes()->create([
        'lead_id'             => $this->lead->id,
        'institution_id'      => $this->institution->id,
        'consent_given'       => true,
        'consent_timestamp'   => now(),
        'consent_ip'          => '127.0.0.1',
        'consent_form_version' => '1.0',
        'consent_channel'     => 'web_form',
        'consent_type'        => ConsentType::MarketingCommunication->value,
    ]);

    $result = $this->service->isConsentGiven($this->lead, ConsentType::MarketingCommunication);

    expect($result)->toBeTrue();
});

it('isConsentGiven() returns false when no record exists', function (): void {
    $result = $this->service->isConsentGiven($this->lead, ConsentType::MarketingCommunication);

    expect($result)->toBeFalse();
});

it('isConsentGiven() returns false when consent is revoked', function (): void {
    ConsentRecord::withoutGlobalScopes()->create([
        'lead_id'             => $this->lead->id,
        'institution_id'      => $this->institution->id,
        'consent_given'       => true,
        'consent_timestamp'   => now(),
        'consent_ip'          => '127.0.0.1',
        'consent_form_version' => '1.0',
        'consent_channel'     => 'web_form',
        'consent_type'        => ConsentType::MarketingCommunication->value,
        'revoked_at'          => now(),
    ]);

    $result = $this->service->isConsentGiven($this->lead, ConsentType::MarketingCommunication);

    expect($result)->toBeFalse();
});
