<?php

declare(strict_types=1);

// BRD: CRM-CR-005 — PII anonymised within 30 days of verified erasure request

use App\Enums\CRM\Compliance\PiiErasureStatus;
use App\Jobs\CRM\Compliance\ErasePersonalDataJob;
use App\Models\CRM\Compliance\PiiErasureRequest;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\CRM\Admin\SystemAdminRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Compliance\ComplianceRolePermissionSeeder::class);
    $this->seed(\Database\Seeders\CRM\Alumni\AlumniRolePermissionSeeder::class);

    $this->institution = Institution::factory()->create();
    $this->user        = User::factory()->create(['institution_id' => $this->institution->id]);

    $this->lead = Lead::withoutGlobalScopes()->create([
        'institution_id'   => $this->institution->id,
        'first_name'       => 'Sunil',
        'last_name'        => 'Verma',
        'email'            => encrypt('sunil.verma@example.com'),
        'mobile'           => encrypt('9999988888'),
        'source'           => 'walk_in',
        'status'           => 'new_enquiry',
        'consent_given'    => true,
        'consent_timestamp' => now(),
    ]);

    $this->erasureRequest = PiiErasureRequest::withoutGlobalScopes()->create([
        'lead_id'              => $this->lead->id,
        'institution_id'       => $this->institution->id,
        'requested_at'         => now()->subDays(31),
        'scheduled_erasure_at' => now()->subDay(), // past due — eligible for erasure
        'status'               => PiiErasureStatus::Scheduled->value,
    ]);
});

it('ErasePersonalDataJob anonymises PII fields', function (): void {
    ErasePersonalDataJob::dispatchSync();

    $freshLead = Lead::withoutGlobalScopes()->find($this->lead->id);

    // Lead should have pii_anonymised_at set (the anonymisePII method sets this)
    expect($freshLead->pii_anonymised_at)->not->toBeNull();
});

it('erased request has erased_at set', function (): void {
    ErasePersonalDataJob::dispatchSync();

    $freshRequest = PiiErasureRequest::withoutGlobalScopes()->find($this->erasureRequest->id);

    expect($freshRequest->erased_at)->not->toBeNull();
});

it('erased request status is updated to erased', function (): void {
    ErasePersonalDataJob::dispatchSync();

    $freshRequest = PiiErasureRequest::withoutGlobalScopes()->find($this->erasureRequest->id);

    expect($freshRequest->status->value)->toBe(PiiErasureStatus::Erased->value);
});

it('job skips requests not yet due', function (): void {
    // Update the request to be scheduled in the future
    $this->erasureRequest->update([
        'scheduled_erasure_at' => now()->addDays(10),
    ]);

    ErasePersonalDataJob::dispatchSync();

    $freshRequest = PiiErasureRequest::withoutGlobalScopes()->find($this->erasureRequest->id);

    // Should still be in scheduled state since it is not due yet
    expect($freshRequest->status->value)->toBe(PiiErasureStatus::Scheduled->value);
    expect($freshRequest->erased_at)->toBeNull();
});
