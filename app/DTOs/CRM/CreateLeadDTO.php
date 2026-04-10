<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-LC-011 — Typed DTO for manual lead creation
// BRD: CRM-LC-014 — source is mandatory
final readonly class CreateLeadDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $mobile,
        public ?string $email,
        public string $source,
        public bool $consentGiven,
        public ?string $consentIp,
        public string $consentFormVersion,
        public ?int $campusId,
        public ?string $city,
        public ?string $state,
        public ?string $notes,
        public ?array $sourceUtmParams,
        public ?array $programmeIds,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated, string $ip): self
    {
        return new self(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            mobile: $validated['mobile'],
            email: $validated['email'] ?? null,
            source: $validated['source'],
            consentGiven: (bool) $validated['consent_given'],
            consentIp: $ip,
            consentFormVersion: $validated['consent_form_version'],
            campusId: $validated['campus_id'] ?? null,
            city: $validated['city'] ?? null,
            state: $validated['state'] ?? null,
            notes: $validated['notes'] ?? null,
            sourceUtmParams: $validated['source_utm_params'] ?? null,
            programmeIds: $validated['programme_ids'] ?? null,
        );
    }
}
