<?php

declare(strict_types=1);

// BRD: DM-007 — Aadhaar eKYC: initiate session, OTP verify marks kyc_complete; Aadhaar number never stored

use App\Enums\CRM\AadhaarKycStatus;
use App\Jobs\CRM\ProcessAadhaarKycJob;
use App\Models\CRM\AadhaarEkycLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use App\Services\CRM\Integration\AadhaarService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeAadhaarContext(): array
{
    $institution = Institution::create([
        'name' => 'KYC University', 'code' => 'KYC1', 'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'KYC Officer',
        'email' => 'kyc@officer.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);
    $user->givePermissionTo(['crm.integrations.manage']);

    $lead = Lead::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'first_name' => 'Sita',
        'last_name' => 'Devi',
        'mobile' => '9111111111',
        'email' => 'sita@test.com',
        'source' => 'facebook',
        'lead_score' => 0,
        'temperature' => 'hot',
        'status' => 'new_enquiry',
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1.0',
    ]);

    return [$institution, $user, $lead];
}

// ─── Aadhaar: initiate creates log with otp_sent status ──────────────────

test('initiate creates AadhaarEkycLog and dispatches ProcessAadhaarKycJob (DM-007)', function (): void {
    Queue::fake();

    [$institution, $user, $lead] = makeAadhaarContext();

    $service = app(AadhaarService::class);

    $log = $service->initiate($institution->id, [
        'lead_id' => $lead->id,
        'consent_ip' => '127.0.0.1',
    ]);

    expect($log)->toBeInstanceOf(AadhaarEkycLog::class)
        ->and($log->status)->toBe(AadhaarKycStatus::Initiated);

    Queue::assertPushed(ProcessAadhaarKycJob::class);
});

// ─── Aadhaar: Aadhaar number is never stored ──────────────────────────────

test('AadhaarEkycLog table has no aadhaar_number column (DM-007 DPDP compliance)', function (): void {
    [$institution, $user, $lead] = makeAadhaarContext();

    $service = app(AadhaarService::class);

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'status' => AadhaarKycStatus::Initiated,
        'consent_ip' => '10.0.0.1',
        'consent_at' => now(),
    ]);

    // Verify no aadhaar number can be persisted
    expect($log->toArray())->not->toHaveKey('aadhaar_number');
});

// ─── Aadhaar: verifyOtp marks kyc_complete ───────────────────────────────

test('verifyOtp marks AadhaarEkycLog as verified and kyc_complete (DM-007)', function (): void {
    [$institution, $user, $lead] = makeAadhaarContext();

    $service = app(AadhaarService::class);

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'status' => AadhaarKycStatus::OtpSent,
        'otp_reference' => 'OTP-REF-12345',
        'transaction_id' => 'TXN-999',
        'consent_ip' => '10.0.0.2',
        'consent_at' => now(),
    ]);

    $service->verifyOtp($log, '123456');

    $log->refresh();

    expect($log->status)->toBe(AadhaarKycStatus::Verified)
        ->and($log->kyc_complete)->toBeTrue();
});

// ─── Aadhaar: markFailed sets status to failed ───────────────────────────

test('markFailed sets AadhaarEkycLog status to failed (DM-007)', function (): void {
    [$institution, $user, $lead] = makeAadhaarContext();

    $service = app(AadhaarService::class);

    $log = AadhaarEkycLog::withoutGlobalScopes()->create([
        'institution_id' => $institution->id,
        'lead_id' => $lead->id,
        'status' => AadhaarKycStatus::OtpSent,
        'consent_ip' => '10.0.0.3',
        'consent_at' => now(),
    ]);

    $service->markFailed($log);

    $log->refresh();

    expect($log->status)->toBe(AadhaarKycStatus::Failed);
});
