<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-LC-006 — Typed DTO for live chat lead ingestion
final readonly class CreateChatLeadDTO
{
    /**
     * @param  array<int, array<string, string>>|null  $transcript
     * @param  array<string, string>|null  $sourceUtmParams
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $sessionId,
        public string $firstName,
        public string $lastName,
        public string $mobile,
        public ?string $email,
        public ?int $campusId,
        public ?string $sourceUrl,
        public ?array $transcript,
        public bool $consentGiven,
        public string $consentFormVersion,
        public ?array $sourceUtmParams,
        public ?array $metadata,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(array $validated): self
    {
        return new self(
            sessionId: $validated['session_id'],
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            mobile: $validated['mobile'],
            email: $validated['email'] ?? null,
            campusId: $validated['campus_id'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            transcript: $validated['transcript'] ?? null,
            consentGiven: (bool) $validated['consent_given'],
            consentFormVersion: $validated['consent_form_version'],
            sourceUtmParams: $validated['source_utm_params'] ?? null,
            metadata: $validated['metadata'] ?? null,
        );
    }
}
