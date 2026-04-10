<?php

declare(strict_types=1);

namespace App\Services\CRM\Import\Normalizers;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;

/**
 * MetaLeadNormalizer — Maps Meta Lead Ads Graph API fetch response to CreateLeadDTO.
 *
 * Meta delivers webhook events with only a leadgen_id. ProcessMetaLeadJob fetches
 * the full form response from the Graph API, then calls this normalizer.
 *
 * Graph API response shape (GET /{leadgen_id}?fields=field_data):
 * {
 *   "id": "...",
 *   "created_time": "...",
 *   "ad_id": "...",
 *   "ad_name": "MBA 2026 Ad",
 *   "adset_name": "...",
 *   "campaign_name": "...",
 *   "platform": "fb" | "ig",
 *   "field_data": [
 *     { "name": "full_name", "values": ["Arjun Sharma"] },
 *     { "name": "phone_number", "values": ["+919876543210"] },
 *     { "name": "email", "values": ["arjun@example.com"] },
 *     { "name": "city", "values": ["Mumbai"] }
 *   ]
 * }
 *
 * BRD: CRM-LC-004 — Meta Lead Ads auto-import normaliser
 * BRD: CRM-CR-001 — Meta requires consent disclosure on lead gen forms; consent_given = true
 */
final class MetaLeadNormalizer implements NormalizerContract
{
    public function normalize(array $raw, int $institutionId, string $consentIp): CreateLeadDTO
    {
        // Flatten field_data array into keyed map
        $fields = [];

        foreach ($raw['field_data'] ?? [] as $field) {
            if (isset($field['name'], $field['values'][0])) {
                $fields[strtolower((string) $field['name'])] = (string) $field['values'][0];
            }
        }

        // Name
        [$firstName, $lastName] = $this->parseName(
            $fields['full_name'] ?? '',
            $fields['first_name'] ?? '',
            $fields['last_name'] ?? '',
        );

        // Mobile
        $mobile = $this->normaliseMobile($fields['phone_number'] ?? $fields['mobile'] ?? '');

        // Source — distinguish FB vs Instagram
        $platform = strtolower($raw['platform'] ?? 'fb');
        $source = $platform === 'ig' ? LeadSource::INSTAGRAM : LeadSource::FACEBOOK;

        // UTM params from campaign/ad naming
        $utmParams = array_filter([
            'utm_source' => 'meta',
            'utm_medium' => 'paid_social',
            'utm_campaign' => $raw['campaign_name'] ?? null,
            'utm_content' => $raw['ad_name'] ?? null,
        ]);

        return new CreateLeadDTO(
            firstName: $firstName,
            lastName: $lastName,
            mobile: $mobile,
            email: $fields['email'] ?? null,
            source: $source->value,
            // BRD: CRM-CR-001 — Meta Lead Ads require consent disclosure on the form
            consentGiven: true,
            consentIp: $consentIp,
            consentFormVersion: 'channel:meta:v1',
            campusId: null,
            city: $fields['city'] ?? null,
            state: $fields['state'] ?? null,
            notes: null,
            sourceUtmParams: !empty($utmParams) ? $utmParams : null,
            programmeIds: null,
        );
    }

    /** @return array{string, string} */
    private function parseName(string $fullName, string $firstName, string $lastName): array
    {
        if (!empty($firstName)) {
            return [
                trim($firstName),
                !empty($lastName) ? trim($lastName) : 'Unknown',
            ];
        }

        if (!empty($fullName)) {
            $parts = explode(' ', trim($fullName), 2);

            return [$parts[0], $parts[1] ?? 'Unknown'];
        }

        return ['Unknown', 'Unknown'];
    }

    /** Strips +91 or 091 prefix from Indian mobile numbers. */
    private function normaliseMobile(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return substr($digits, 2);
        }

        return $digits;
    }
}
