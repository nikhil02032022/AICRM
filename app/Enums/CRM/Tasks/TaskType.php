<?php

declare(strict_types=1);

namespace App\Enums\CRM\Tasks;

// BRD: CRM-TF-001 — Task types counsellors can create against a lead
enum TaskType: string
{
    case Call = 'call';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Meeting = 'meeting';
    case DocumentReview = 'document_review';

    public function label(): string
    {
        return match ($this) {
            self::Call           => 'Call',
            self::Email          => 'Email',
            self::WhatsApp       => 'WhatsApp',
            self::Meeting        => 'Meeting',
            self::DocumentReview => 'Document Review',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Call           => 'heroicon-o-phone',
            self::Email          => 'heroicon-o-envelope',
            self::WhatsApp       => 'heroicon-o-chat-bubble-left-ellipsis',
            self::Meeting        => 'heroicon-o-calendar-days',
            self::DocumentReview => 'heroicon-o-document-text',
        };
    }
}
