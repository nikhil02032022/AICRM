<?php

declare(strict_types=1);

// BRD: DM-006 — DigiLocker integration: initiate request, dispatch verification job, handle failure

use App\Enums\CRM\DigiLockerStatus;
use App\Jobs\CRM\VerifyDigiLockerDocumentJob;
use App\Models\CRM\DigiLockerDocument;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Integration\DigiLockerService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeDigiLockerContext(): array
{
    $institution = Institution::create([
        'name' => 'DigiLocker Uni', 'code' => 'DLU1', 'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'DL Admin',
        'email' => 'dl@admin.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $user->givePermissionTo(['crm.integrations.manage']);

    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'first_name' => 'Rahul',
        'last_name' => 'Sharma',
        'mobile' => '9898989898',
        'email' => 'rahul@test.com',
        'source' => 'walk_in',
        'lead_score' => 0,
        'temperature' => 'warm',
        'status' => 'new_enquiry',
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1.0',
    ]);

    return [$institution, $user, $lead];
}

// ─── DigiLocker: initiateRequest creates record and dispatches job ─────────

test('initiateRequest creates DigiLockerDocument and dispatches VerifyDigiLockerDocumentJob (DM-006)', function (): void {
    Queue::fake();

    [$institution, $user, $lead] = makeDigiLockerContext();

    $service = app(DigiLockerService::class);

    $document = $service->initiateRequest($institution->id, [
        'lead_id' => $lead->id,
        'document_type' => 'marksheet_10',
        'consent_record_id' => 'CONSENT-ABC-001',
    ]);

    expect($document)->toBeInstanceOf(DigiLockerDocument::class)
        ->and($document->status)->toBe(DigiLockerStatus::Pending)
        ->and($document->document_type)->toBe('marksheet_10')
        ->and($document->consent_record_id)->toBe('CONSENT-ABC-001');

    Queue::assertPushed(VerifyDigiLockerDocumentJob::class);
});

// ─── DigiLocker: markVerified sets status to verified ─────────────────────

test('markVerified updates DigiLockerDocument status to verified (DM-006)', function (): void {
    [$institution, $user, $lead] = makeDigiLockerContext();

    $service = app(DigiLockerService::class);

    $document = DigiLockerDocument::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'document_type' => 'aadhaar',
        'status' => DigiLockerStatus::Requested,
        'consent_record_id' => 'CONSENT-002',
    ]);

    $service->markVerified($document, 'https://digilocker.gov.in/doc/123', '/storage/docs/rahul_aadhaar.pdf');

    $document->refresh();

    expect($document->status)->toBe(DigiLockerStatus::Verified)
        ->and($document->is_verified)->toBeTrue()
        ->and($document->verified_at)->not->toBeNull();
});

// ─── DigiLocker: markFailed sets status to failed ─────────────────────────

test('markFailed sets DigiLockerDocument status to failed (DM-006)', function (): void {
    [$institution, $user, $lead] = makeDigiLockerContext();

    $service = app(DigiLockerService::class);

    $document = DigiLockerDocument::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'document_type' => 'pan',
        'status' => DigiLockerStatus::Requested,
        'consent_record_id' => 'CONSENT-003',
    ]);

    $service->markFailed($document);

    $document->refresh();

    expect($document->status)->toBe(DigiLockerStatus::Failed);
});

// ─── DigiLocker: institution scope enforced ────────────────────────────────

test('DigiLockerDocument list is scoped to institution (DM-006)', function (): void {
    [$institution, $user, $lead] = makeDigiLockerContext();

    $otherInstitution = Institution::create([
        'name' => 'Other Uni', 'code' => 'OTH1', 'is_active' => true,
    ]);

    DigiLockerDocument::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'document_type' => 'degree_certificate',
        'status' => DigiLockerStatus::Pending,
        'consent_record_id' => 'C-001',
    ]);

    DigiLockerDocument::withoutGlobalScopes()->create([
        'institution_id' => $otherInstitution->id,
        'lead_id' => $lead->id,
        'document_type' => 'pan',
        'status' => DigiLockerStatus::Pending,
        'consent_record_id' => 'C-002',
    ]);

    $service = app(DigiLockerService::class);
    $results = $service->list($institution->id);

    expect($results->total())->toBe(1);
});
