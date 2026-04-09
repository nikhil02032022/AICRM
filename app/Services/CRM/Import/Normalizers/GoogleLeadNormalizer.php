<?php

declare(strict_types=1);

namespace App\Services\CRM\Import\Normalizers;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;

/**
 * GoogleLeadNormalizer — Maps Google Lead Form Extensions webhook payload to CreateLeadDTO.
 *
 * Google Lead Form Extensions deliver a JSON payload with a "user_column_data" array
 * containing objects with "column_name" and "string_value" fields.
 *
 * Example payload:
 * {
 *   "lead_id": "...",
 *   "user_column_data": [
 *     { "column_name": "FULL_NAME", "string_value": "Arjun Sharma" },
 *     { "column_name": "PHONE_NUMBER", "string_value": "+919876543210" },
 *     { "column_name": "EMAIL", "string_value": "arjun@example.com" },
 *     { "column_name": "CITY", "string_value": "Mumbai" }
 *   ],
 *   "campaign_id": "...",
 *   "campaign_name": "MBA 2026 Campaign"
 * }
 *
 * BRD: CRM-LC-003 — Google Ads Lead Form Extensions webhook normaliser
 * BRD: CRM-CR-001 — Consent is captured at Google Ads form level; consent_given = true
 */
final class GoogleLeadNormalizer implements NormalizerContract
{
    public function normalize(array $raw, int $institutionId, string $consentIp): CreateLeadDTO
    {
        // Flatten user_column_data array into a keyed map for easy access
        $fields = [];
        foreach ($raw['user_column_data'] ?? [] as $col) {
            if (isset($col['column_name'], $col['string_value'])) {
                $fields[strtoupper((string) $col['column_name'])] = (string) $col['string_value'];
            }
        }

        // Name — Google provides FULL_NAME or separate FIRST_NAME / LAST_NAME
        [$firstName, $lastName] = $this->parseName(
            $fields['FULL_NAME']   ?? '',
            $fields['FIRST_NAME']  ?? '',
            $fields['LAST_NAME']   ?? '',
        );

        // Mobile — strip country code prefix if present
        $mobile = $this->normaliseMobile($fields['PHONE_NUMBER'] ?? $fields['MOBILE'] ?? '');

        // UTM — populate from campaign metadata
        $utmParams = array_filter([
            'utm_source'   => 'google',
            'utm_medium'   => 'cpc',
            'utm_campaign' => $raw['campaign_name'] ?? null,
        ]);

        return new CreateLeadDTO(
            firstName:          $firstName,
            lastName:           $lastName,
            mobile:             $mobile,
            email:              $fields['EMAIL'] ?? null,
            source:             LeadSource::GOOGLE_ADS->value,
            // BRD: CRM-CR-001 — Google requires advertiser consent disclosure before form submission
            consentGiven:       true,
            consentIp:          $consentIp,  // Platform IP (not student IP — documented)
            consentFormVersion: 'channel:google_ads:v1',
            campusId:           null,
            city:               $fields['CITY'] ?? null,
            state:              $fields['STATE'] ?? null,
            notes:              null,
            sourceUtmParams:    ! empty($utmParams) ? $utmParams : null,
            programmeIds:       null,
        );
    }

    /** @return array{string, string} */
    private function parseName(string $fullName, string $firstName, string $lastName): array
    {
        if (! empty($firstName)) {
            return [
                trim($firstName),
                ! empty($lastName) ? trim($lastName) : 'Unknown',
            ];
        }

        if (! empty($fullName)) {
            $parts = explode(' ', trim($fullName), 2);

            return [
                $parts[0],
                $parts[1] ?? 'Unknown',
            ];
        }

        return ['Unknown', 'Unknown'];
    }

    /** Strips +91 or 091 prefix from Indian mobile numbers. */
    private function normaliseMobile(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        // Strip leading 91 (country code) if present and result is 12 digits
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return substr($digits, 2);
        }

        return $digits;
    }
}
