<?php

declare(strict_types=1);

// BRD: CRM-CR-005 — Right-to-erasure: PII anonymised within 30 days of verified request

use App\Enums\CRM\Compliance\PiiErasureStatus;
use App\Models\CRM\Compliance\PiiErasureRequest;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Compliance\PiiErasureService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->user        = User::factory()->create(['institution_id' => $this->institution->id]);
    $this->service     = app(PiiErasureService::class);

    $this->lead = Lead::withoutGlobalScopes()->create([
        'institution_id'   => $this->institution->id,
        'first_name'       => 'Priya',
        'last_name'        => 'Sharma',
        'email'            => encrypt('priya@example.com'),
        'mobile'           => encrypt('9876543210'),
        'source'           => 'web_form',
        'status'           => 'new_enquiry',
        'consent_given'    => true,
        'consent_timestamp' => now(),
    ]);
});

it('schedule() creates PiiErasureRequest with correct scheduled_erasure_at', function (): void {
    $erasureRequest = $this->service->schedule($this->lead, $this->institution->id);

    expect($erasureRequest)->toBeInstanceOf(PiiErasureRequest::class);
    expect($erasureRequest->lead_id)->toBe($this->lead->id);
    expect($erasureRequest->institution_id)->toBe($this->institution->id);

    $this->assertDatabaseHas('pii_erasure_requests', [
        'lead_id'        => $this->lead->id,
        'institution_id' => $this->institution->id,
    ]);

    // scheduled_erasure_at should be approximately 30 days from now
    $scheduled = $erasureRequest->fresh()->scheduled_erasure_at;
    expect($scheduled)->not->toBeNull();
    expect(now()->addDays(29)->lt($scheduled))->toBeTrue();
    expect($scheduled->lt(now()->addDays(31)))->toBeTrue();
});

it('erase() calls Lead::anonymisePII() and sets erased_at', function (): void {
    $erasureRequest = PiiErasureRequest::withoutGlobalScopes()->create([
        'lead_id'              => $this->lead->id,
        'institution_id'       => $this->institution->id,
        'requested_at'         => now()->subDays(31),
        'scheduled_erasure_at' => now()->subDay(),
        'status'               => PiiErasureStatus::Scheduled->value,
    ]);

    $this->service->erase($erasureRequest);

    $freshRequest = $erasureRequest->fresh();

    expect($freshRequest->erased_at)->not->toBeNull();
    expect($freshRequest->status->value)->toBe('erased');
});

it('erase() anonymises the lead PII fields', function (): void {
    $erasureRequest = PiiErasureRequest::withoutGlobalScopes()->create([
        'lead_id'              => $this->lead->id,
        'institution_id'       => $this->institution->id,
        'requested_at'         => now()->subDays(31),
        'scheduled_erasure_at' => now()->subDay(),
        'status'               => PiiErasureStatus::Scheduled->value,
    ]);

    $this->service->erase($erasureRequest);

    $freshLead = Lead::withoutGlobalScopes()->find($this->lead->id);

    expect($freshLead->pii_anonymised_at)->not->toBeNull();
});
