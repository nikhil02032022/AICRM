<?php

declare(strict_types=1);

namespace App\Services\CRM\Import\Normalizers;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;

/**
 * Careers360LeadNormalizer — Maps Careers360 lead API / webhook payload to CreateLeadDTO.
 *
 * Careers360 API lead format:
 * {
 *   "id": "...",
 *   "student_name": "Arjun Sharma",
 *   "mobile_no": "9876543210",
 *   "email_id": "arjun@example.com",
 *   "city_name": "Mumbai",
 *   "state_name": "Maharashtra",
 *   "programme": "MBA"
 * }
 *
 * BRD: CRM-LC-008 — Education portal import normaliser (Careers360)
 */
final class Careers360LeadNormalizer implements NormalizerContract
{
    public function normalize(array $raw, int $institutionId, string $consentIp): CreateLeadDTO
    {
        [$firstName, $lastName] = $this->parseName($raw['student_name'] ?? '');

        return new CreateLeadDTO(
            firstName: $firstName,
            lastName: $lastName,
            mobile: $this->normaliseMobile($raw['mobile_no'] ?? ''),
            email: $raw['email_id'] ?? null,
            source: LeadSource::EDUCATION_PORTAL->value,
            consentGiven: true,
            consentIp: $consentIp,
            consentFormVersion: 'channel:careers360:v1',
            campusId: null,
            city: $raw['city_name'] ?? null,
            state: $raw['state_name'] ?? null,
            notes: isset($raw['programme']) ? 'Course interest: '.$raw['programme'] : null,
            sourceUtmParams: ['utm_source' => 'careers360'],
            programmeIds: null,
        );
    }

    /** @return array{string, string} */
    private function parseName(string $full): array
    {
        $parts = explode(' ', trim($full), 2);

        return [!empty($parts[0]) ? $parts[0] : 'Unknown', $parts[1] ?? 'Unknown'];
    }

    private function normaliseMobile(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        return (strlen($digits) === 12 && str_starts_with($digits, '91')) ? substr($digits, 2) : $digits;
    }
}
