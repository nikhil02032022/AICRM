<?php

declare(strict_types=1);

// BRD: DM-006 — Group Q integration hardening: idempotency, event dispatch, cross-institution guard, failure hook

use App\Enums\CRM\DigiLockerStatus;
use App\Events\CRM\DigiLockerVerifiedEvent;
use App\Jobs\CRM\VerifyDigiLockerDocumentJob;
use App\Models\CRM\DigiLockerDocument;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Integration\DigiLockerService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeDLIntegrationContext(): array
{
    $institution = Institution::create(['name' => 'DL Intg Uni', 'code' => 'DLIU', 'is_active' => true]);

    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id'       => $institution->id,
        'first_name'           => 'Test',
        'last_name'            => 'User',
        'mobile'               => '9000000001',
        'email'                => 'dlintg@test.com',
        'source'               => 'walk_in',
        'lead_score'           => 0,
        'temperature'          => 'warm',
        'status'               => 'new_enquiry',
        'consent_given'        => true,
        'consent_timestamp'    => now(),
        'consent_form_version' => 'v1.0',
    ]);

    return [$institution, $lead];
}

// ─── DQ-DL-001: Job idempotency — already VERIFIED document is a no-op ──────

test('DQ-DL-001: VerifyDigiLockerDocumentJob is a no-op when document is already VERIFIED', function (): void {
    Event::fake();

    [$institution, $lead] = makeDLIntegrationContext();

    $document = DigiLockerDocument::withoutGlobalScopes()->create([
        'institution_id'   => $institution->id,
        'lead_id'          => $lead->id,
        'document_type'    => 'marksheet_10',
        'status'           => DigiLockerStatus::VERIFIED,
        'is_verified'      => true,
        'verified_at'      => now(),
        'digilocker_uri'   => 'in.gov.digilocker.doc-existing',
        'storage_path'     => 'crm/digilocker/1/existing.enc',
        'consent_record_id'=> 1,
    ]);

    $originalUri = $document->digilocker_uri;

    // Run the job directly (synchronous) to test idempotency
    app(VerifyDigiLockerDocumentJob::class, ['documentId' => $document->id])
        ->handle(app(DigiLockerService::class));

    $document->refresh();

    // Status and URI must remain unchanged — no event should fire
    expect($document->status)->toBe(DigiLockerStatus::VERIFIED)
        ->and($document->digilocker_uri)->toBe($originalUri);

    Event::assertNotDispatched(DigiLockerVerifiedEvent::class);
});

// ─── DQ-DL-002: Job idempotency — already FAILED document is a no-op ────────

test('DQ-DL-002: VerifyDigiLockerDocumentJob is a no-op when document is already FAILED', function (): void {
    Event::fake();

    [$institution, $lead] = makeDLIntegrationContext();

    $document = DigiLockerDocument::withoutGlobalScopes()->create([
        'institution_id'   => $institution->id,
        'lead_id'          => $lead->id,
        'document_type'    => 'pan',
        'status'           => DigiLockerStatus::FAILED,
        'error_message'    => 'API Setu timeout',
        'consent_record_id'=> 2,
    ]);

    app(VerifyDigiLockerDocumentJob::class, ['documentId' => $document->id])
        ->handle(app(DigiLockerService::class));

    $document->refresh();

    expect($document->status)->toBe(DigiLockerStatus::FAILED);
    Event::assertNotDispatched(DigiLockerVerifiedEvent::class);
});

// ─── DQ-DL-003: DigiLockerVerifiedEvent dispatched on successful verification ─

test('DQ-DL-003: DigiLockerVerifiedEvent is dispatched when document is marked verified', function (): void {
    Event::fake();

    [$institution, $lead] = makeDLIntegrationContext();

    $document = DigiLockerDocument::withoutGlobalScopes()->create([
        'institution_id'   => $institution->id,
        'lead_id'          => $lead->id,
        'document_type'    => 'degree',
        'status'           => DigiLockerStatus::REQUESTED,
        'consent_record_id'=> 3,
    ]);

    app(VerifyDigiLockerDocumentJob::class, ['documentId' => $document->id])
        ->handle(app(DigiLockerService::class));

    $document->refresh();

    expect($document->status)->toBe(DigiLockerStatus::VERIFIED);
    Event::assertDispatched(DigiLockerVerifiedEvent::class, function ($event) use ($document) {
        return $event->document->id === $document->id;
    });
});

// ─── DQ-DL-004: Cross-institution guard — cannot request doc for another institution's lead ──

test('DQ-DL-004: initiateRequest scopes document to the lead institution', function (): void {
    [$institution, $lead] = makeDLIntegrationContext();

    $service  = app(DigiLockerService::class);
    $document = $service->initiateRequest($lead, 'marksheet_12', 4);

    expect($document->institution_id)->toBe($institution->id)
        ->and($document->lead_id)->toBe($lead->id);

    // The list for a different institution returns 0
    $otherInstitution = Institution::create(['name' => 'Other', 'code' => 'OTH2', 'is_active' => true]);
    $results          = $service->list($otherInstitution->id);

    expect($results->total())->toBe(0);
});

// ─── DQ-DL-005: failed() hook marks document FAILED after max retries ────────

test('DQ-DL-005: failed() hook marks DigiLockerDocument as FAILED', function (): void {
    [$institution, $lead] = makeDLIntegrationContext();

    $document = DigiLockerDocument::withoutGlobalScopes()->create([
        'institution_id'   => $institution->id,
        'lead_id'          => $lead->id,
        'document_type'    => 'id_proof',
        'status'           => DigiLockerStatus::REQUESTED,
        'consent_record_id'=> 5,
    ]);

    $job = new VerifyDigiLockerDocumentJob($document->id);
    $job->failed(new \RuntimeException('Simulated max-retry failure'));

    $document->refresh();

    expect($document->status)->toBe(DigiLockerStatus::FAILED)
        ->and($document->error_message)->toBe('Job failed after max retries');
});
