<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use App\Enums\CRM\CommunicationChannel;

// BRD: CRM-CC-002 — DTO for sending an individual email from a lead record
final readonly class SendEmailDTO
{
    public function __construct(
        public readonly int $templateId,
        public readonly string $fromName,
        public readonly string $fromEmail,
        public readonly ?string $subject,
        public readonly ?string $customBodyHtml,
        public readonly CommunicationChannel $channel = CommunicationChannel::EMAIL,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromArray(array $validated): self
    {
        return new self(
            templateId: (int) ($validated['template_id'] ?? 0),
            fromName: $validated['from_name'],
            fromEmail: $validated['from_email'],
            subject: $validated['subject'] ?? null,
            customBodyHtml: $validated['custom_body_html'] ?? null,
        );
    }
}
