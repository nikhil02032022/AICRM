<?php

declare(strict_types=1);

namespace App\Services\CRM\Import\Normalizers;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;

/**
 * CollegeDuniaLeadNormalizer — Maps Collegedunia lead API / webhook payload to CreateLeadDTO.
 *
 * Collegedunia API lead format:
 * {
 *   "lead_id": "...",
 *   "name": "Arjun Sharma",
 *   "mobile": "9876543210",
 *   "email": "arjun@example.com",
 *   "location": "Mumbai",
 *   "course_type": "MBA",
 *   "specialization": "Finance"
 * }
 *
 * BRD: CRM-LC-008 — Education portal import normaliser (Collegedunia)
 */
final class CollegeDuniaLeadNormalizer implements NormalizerContract
{
    public function normalize(array $raw, int $institutionId, string $consentIp): CreateLeadDTO
    {
        [$firstName, $lastName] = $this->parseName($raw['name'] ?? '');

        $noteParts = array_filter([
            isset($raw['course_type']) ? 'Course: '.$raw['course_type'] : null,
            isset($raw['specialization']) ? 'Specialisation: '.$raw['specialization'] : null,
        ]);

        return new CreateLeadDTO(
            firstName: $firstName,
            lastName: $lastName,
            mobile: $this->normaliseMobile($raw['mobile'] ?? ''),
            email: $raw['email'] ?? null,
            source: LeadSource::EDUCATION_PORTAL->value,
            consentGiven: true,
            consentIp: $consentIp,
            consentFormVersion: 'channel:collegedunia:v1',
            campusId: null,
            city: $raw['location'] ?? null,
            state: null,
            notes: !empty($noteParts) ? implode('; ', $noteParts) : null,
            sourceUtmParams: ['utm_source' => 'collegedunia'],
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
