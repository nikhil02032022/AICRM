<?php

declare(strict_types=1);

namespace App\Services\CRM\Erp;

use App\DTOs\CRM\ErpStudentDTO;
use App\Enums\CRM\IntegrationChannel;
use App\Models\CRM\IntegrationCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-020 — A2A ERP Student Master HTTP client
// OWASP A05: Credentials retrieved per-institution from encrypted IntegrationCredential store
final class ErpApiClient implements ErpApiClientInterface
{
    private const RETRY_TIMES = 3;
    private const RETRY_SLEEP_MS = 200;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    /**
     * Build an instance for the given institution from its stored IntegrationCredential,
     * falling back to global config for single-tenant / demo mode.
     */
    public static function forInstitution(int $institutionId): self
    {
        $credential = IntegrationCredential::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('channel', IntegrationChannel::ERP_A2A->value)
            ->where('is_active', true)
            ->first();

        if ($credential !== null) {
            return new self(
                baseUrl: (string) ($credential->getCredential('base_url') ?? config('services.a2a_erp.base_url', '')),
                apiKey: (string) ($credential->getCredential('api_key') ?? ''),
            );
        }

        // Fallback for environments without a stored credential (e.g., local/demo)
        return new self(
            baseUrl: (string) config('services.a2a_erp.base_url', ''),
            apiKey: '',
        );
    }

    /**
     * Register a new student in the ERP Student Master.
     *
     * BRD: CRM-AP-016 — Conversion write; returns ERP-assigned student ID on success.
     * DPDP: mobile/email are sent over HTTPS only; never logged.
     *
     * @param array<string, mixed> $payload
     */
    public function registerStudent(array $payload): ?string
    {
        if ($this->baseUrl === '') {
            Log::warning('ERP API: base_url not configured — skipping registerStudent.');
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout((int) config('services.a2a_erp.timeout', 10))
                ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
                ->post("{$this->baseUrl}/api/v1/students", $payload);

            if ($response->successful()) {
                return (string) ($response->json('data.student_id') ?? $response->json('data.id') ?? '');
            }

            Log::warning('ERP API: unexpected status on registerStudent.', [
                'status' => $response->status(),
                // DPDP: payload intentionally omitted from log
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::warning('ERP API: exception during registerStudent.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** BRD: CRM-AP-018 — Trigger ID card generation after ERP enrolment. */
    public function triggerIdCardGeneration(string $erpStudentId): bool
    {
        return $this->postOnboardingAction("/api/v1/students/{$erpStudentId}/id-card", 'triggerIdCardGeneration');
    }

    /** BRD: CRM-AP-018 — Trigger LMS enrolment after ERP enrolment. */
    public function triggerLmsEnrolment(string $erpStudentId, string $programmeCode): bool
    {
        return $this->postOnboardingAction(
            "/api/v1/students/{$erpStudentId}/lms-enrol",
            'triggerLmsEnrolment',
            ['programme_code' => $programmeCode],
        );
    }

    /** BRD: CRM-AP-018 — Trigger hostel allocation prompt after ERP enrolment. */
    public function triggerHostelAllocationPrompt(string $erpStudentId): bool
    {
        return $this->postOnboardingAction("/api/v1/students/{$erpStudentId}/hostel-prompt", 'triggerHostelAllocationPrompt');
    }

    /**
     * @param array<string, mixed> $body
     */
    private function postOnboardingAction(string $path, string $action, array $body = []): bool
    {
        if ($this->baseUrl === '') {
            Log::warning("ERP API: base_url not configured — skipping {$action}.");
            return false;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout((int) config('services.a2a_erp.timeout', 10))
                ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
                ->post("{$this->baseUrl}{$path}", $body);

            if ($response->successful()) {
                return true;
            }

            Log::warning("ERP API: unexpected status on {$action}.", ['status' => $response->status()]);
            return false;

        } catch (\Throwable $e) {
            Log::warning("ERP API: exception during {$action}.", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * BRD: CRM-FM-013 — Push CRM-collected fee ledger to ERP Fee module.
     *
     * @param array<string, mixed> $payload
     */
    public function pushFeeLedger(string $erpStudentId, array $payload): ?string
    {
        if ($this->baseUrl === '') {
            Log::warning('ERP API: base_url not configured — skipping pushFeeLedger.');
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout((int) config('services.a2a_erp.timeout', 10))
                ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
                ->post("{$this->baseUrl}/api/v1/students/{$erpStudentId}/fees", $payload);

            if ($response->successful()) {
                return (string) ($response->json('data.ledger_id') ?? $response->json('data.id') ?? '');
            }

            Log::warning('ERP API: unexpected status on pushFeeLedger.', ['status' => $response->status()]);
            return null;

        } catch (\Throwable $e) {
            Log::warning('ERP API: exception during pushFeeLedger.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Look up a student/alumni in the ERP Student Master by mobile number.
     *
     * - Returns an ErpStudentDTO on a successful match (HTTP 200).
     * - Returns null on no match (HTTP 404).
     * - Returns null on transient/permanent API failure (logs a warning — never throws).
     *
     * DPDP: Mobile number is sent over HTTPS only; never logged.
     */
    public function lookupStudentByMobile(string $mobile): ?ErpStudentDTO
    {
        if ($this->baseUrl === '') {
            Log::warning('ERP API: base_url not configured — skipping lookup.');
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout((int) config('services.a2a_erp.timeout', 10))
                ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
                ->get("{$this->baseUrl}/api/v1/students/lookup", [
                    'mobile' => $mobile,
                ]);

            if ($response->notFound()) {
                return null;
            }

            if ($response->successful()) {
                return ErpStudentDTO::fromArray($response->json('data', []));
            }

            Log::warning('ERP API: unexpected status code on student lookup.', [
                'status' => $response->status(),
                // DPDP: mobile number intentionally omitted from log
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::warning('ERP API: exception during student lookup.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
