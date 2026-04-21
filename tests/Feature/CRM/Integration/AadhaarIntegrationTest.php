<?php

declare(strict_types=1);

// BRD: DM-007 — Group Q integration hardening: markOtpSent, idempotency, nameMatch=false path,
//              event dispatch, DPDP API response guard

use App\Enums\CRM\AadhaarKycStatus;
use App\Events\CRM\AadhaarKycCompletedEvent;
use App\Http\Resources\CRM\AadhaarEkycLogResource;
use App\Jobs\CRM\ProcessAadhaarKycJob;
use App\Models\CRM\AadhaarEkycLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\AadhaarService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeAadhaarIntegrationContext(): array
{
    $institution = Institution::create(['name' => 'KYC Intg Uni', 'code' => 'KYCI', 'is_active' => true]);

    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id'       => $institution->id,
        'first_name'           => 'Mohan',
        'last_name'            => 'Lal',
        'mobile'               => '9000000002',
        'email'                => 'mohan@test.com',
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

// ─── DQ-AK-001: markOtpSent stores references and sets OTP_SENT status ───────

test('DQ-AK-001: markOtpSent stores otp_reference and transaction_id with status OTP_SENT', function (): void {
    [$institution, $lead] = makeAadhaarIntegrationContext();

    $service = app(AadhaarService::class);

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id'        => $lead->id,
        'status'         => AadhaarKycStatus::INITIATED,
        'consent_ip'     => '10.0.0.1',
        'consent_at'     => now(),
    ]);

    $service->markOtpSent($log, otpReference: 'OTP-REF-TEST', transactionId: 'TXN-TEST-001');

    $log->refresh();

    expect($log->status)->toBe(AadhaarKycStatus::OTP_SENT)
        ->and($log->otp_reference)->toBe('OTP-REF-TEST')
        ->and($log->transaction_id)->toBe('TXN-TEST-001');
});

// ─── DQ-AK-002: Job idempotency — OTP_SENT or VERIFIED session is a no-op ───

test('DQ-AK-002: ProcessAadhaarKycJob is a no-op when session is already OTP_SENT', function (): void {
    [$institution, $lead] = makeAadhaarIntegrationContext();

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id'        => $lead->id,
        'status'         => AadhaarKycStatus::OTP_SENT,
        'otp_reference'  => 'EXISTING-OTP-REF',
        'transaction_id' => 'EXISTING-TXN',
        'consent_ip'     => '10.0.0.2',
        'consent_at'     => now(),
    ]);

    app(ProcessAadhaarKycJob::class, ['logId' => $log->id])
        ->handle(app(AadhaarService::class));

    $log->refresh();

    // otp_reference must remain unchanged — no overwrite
    expect($log->status)->toBe(AadhaarKycStatus::OTP_SENT)
        ->and($log->otp_reference)->toBe('EXISTING-OTP-REF');
});

test('DQ-AK-002b: ProcessAadhaarKycJob is a no-op when session is already VERIFIED', function (): void {
    [$institution, $lead] = makeAadhaarIntegrationContext();

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id'   => $institution->id,
        'lead_id'          => $lead->id,
        'status'           => AadhaarKycStatus::VERIFIED,
        'kyc_complete'     => true,
        'kyc_completed_at' => now(),
        'consent_ip'       => '10.0.0.3',
        'consent_at'       => now(),
    ]);

    app(ProcessAadhaarKycJob::class, ['logId' => $log->id])
        ->handle(app(AadhaarService::class));

    $log->refresh();

    expect($log->status)->toBe(AadhaarKycStatus::VERIFIED);
});

// ─── DQ-AK-003: verifyOtp with nameMatch=false still completes KYC ──────────

test('DQ-AK-003: verifyOtp with nameMatch=false sets kyc_complete=true and name_match=false', function (): void {
    [$institution, $lead] = makeAadhaarIntegrationContext();

    $service = app(AadhaarService::class);

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id'        => $lead->id,
        'status'         => AadhaarKycStatus::OTP_SENT,
        'otp_reference'  => 'OTP-MISMATCH-001',
        'transaction_id' => 'TXN-MISMATCH-001',
        'consent_ip'     => '10.0.0.4',
        'consent_at'     => now(),
    ]);

    $service->verifyOtp($log, false);

    $log->refresh();

    expect($log->status)->toBe(AadhaarKycStatus::VERIFIED)
        ->and($log->kyc_complete)->toBeTrue()
        ->and($log->name_match)->toBeFalse();
});

// ─── DQ-AK-004: AadhaarKycCompletedEvent dispatched on successful OTP verify ─

test('DQ-AK-004: AadhaarKycCompletedEvent is dispatched when verifyOtp succeeds', function (): void {
    Event::fake();

    [$institution, $lead] = makeAadhaarIntegrationContext();

    $service = app(AadhaarService::class);

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id'        => $lead->id,
        'status'         => AadhaarKycStatus::OTP_SENT,
        'otp_reference'  => 'OTP-EVENT-001',
        'transaction_id' => 'TXN-EVENT-001',
        'consent_ip'     => '10.0.0.5',
        'consent_at'     => now(),
    ]);

    $service->verifyOtp($log, true);

    Event::assertDispatched(AadhaarKycCompletedEvent::class, function ($event) use ($log) {
        return $event->ekycLog->id === $log->id;
    });
});

// ─── DQ-AK-005: DPDP — AadhaarEkycLogResource omits sensitive fields ─────────

test('DQ-AK-005: AadhaarEkycLogResource response contains no otp_reference, transaction_id, or aadhaar data', function (): void {
    [$institution, $lead] = makeAadhaarIntegrationContext();

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id'        => $lead->id,
        'status'         => AadhaarKycStatus::VERIFIED,
        'otp_reference'  => 'SECRET-OTP-REF',
        'transaction_id' => 'SECRET-TXN',
        'kyc_complete'   => true,
        'name_match'     => true,
        'consent_ip'     => '10.0.0.6',
        'consent_at'     => now(),
    ]);

    $log->load('lead');
    $resource = (new AadhaarEkycLogResource($log))->toArray(new Request());

    expect($resource)->not->toHaveKey('otp_reference')
        ->and($resource)->not->toHaveKey('transaction_id')
        ->and($resource)->not->toHaveKey('aadhaar_number')
        ->and($resource)->not->toHaveKey('consent_ip')
        ->and($resource)->toHaveKey('kyc_complete')
        ->and($resource)->toHaveKey('status');
});
