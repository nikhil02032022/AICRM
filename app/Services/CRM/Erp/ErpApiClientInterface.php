<?php

declare(strict_types=1);

namespace App\Services\CRM\Erp;

use App\DTOs\CRM\ErpStudentDTO;

// BRD: CRM-LC-020 — Contract for ERP Student Master outbound lookup
// BRD: CRM-AP-016 — Contract for ERP Student Master outbound registration
// BRD: CRM-AP-018 — Contract for ERP onboarding workflow triggers
interface ErpApiClientInterface
{
    /**
     * Look up a student or alumni in the A2A ERP Student Master by mobile number.
     *
     * Returns null when no match is found (HTTP 404) or when the API is
     * temporarily unavailable (handled gracefully — never throws).
     */
    public function lookupStudentByMobile(string $mobile): ?ErpStudentDTO;

    /**
     * Register a new student in the ERP Student Master (AP-016 conversion write).
     *
     * Payload keys: first_name, last_name, email, mobile, programme_code,
     * campus_code, admission_year, crm_application_uuid.
     *
     * Returns the ERP-assigned student ID string on success.
     * Returns null on API failure (logs warning — never throws).
     *
     * DPDP: mobile/email sent over HTTPS only; never logged.
     *
     * @param array<string, mixed> $payload
     */
    public function registerStudent(array $payload): ?string;

    /**
     * Trigger ID card generation for a newly enrolled ERP student (AP-018).
     * Returns true on success, false on any failure (never throws).
     */
    public function triggerIdCardGeneration(string $erpStudentId): bool;

    /**
     * Trigger LMS enrolment for a newly enrolled ERP student (AP-018).
     * Returns true on success, false on any failure (never throws).
     */
    public function triggerLmsEnrolment(string $erpStudentId, string $programmeCode): bool;

    /**
     * Trigger hostel allocation prompt for a newly enrolled ERP student (AP-018).
     * Returns true on success, false on any failure (never throws).
     */
    public function triggerHostelAllocationPrompt(string $erpStudentId): bool;

    /**
     * Push CRM-collected fee ledger to ERP Fee module on enrolment conversion.
     * Returns the ERP-side ledger ID on success, null on failure (never throws).
     *
     * BRD: CRM-FM-013
     *
     * @param array<string, mixed> $payload
     */
    public function pushFeeLedger(string $erpStudentId, array $payload): ?string;
}
