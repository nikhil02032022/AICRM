<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-LC-013 — Typed DTO for walk-in kiosk lead capture submissions
final readonly class CreateKioskLeadDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $mobile,
        public ?string $email,
        public ?int $campusId,
        public string $queryMessage,
        public bool $consentGiven,
        public string $consentFormVersion,
        public ?string $kioskLabel,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            mobile: $validated['mobile'],
            email: $validated['email'] ?? null,
            campusId: $validated['campus_id'] ?? null,
            queryMessage: $validated['query_message'],
            consentGiven: (bool) $validated['consent_given'],
            consentFormVersion: $validated['consent_form_version'],
            kioskLabel: $validated['kiosk_label'] ?? null,
        );
    }
}