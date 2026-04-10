<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-CC-014 — WhatsApp message content types
enum WaMessageType: string
{
    case TEXT        = 'TEXT';
    case IMAGE       = 'IMAGE';
    case DOCUMENT    = 'DOCUMENT';
    case AUDIO       = 'AUDIO';
    case TEMPLATE    = 'TEMPLATE';
    case INTERACTIVE = 'INTERACTIVE';

    public function label(): string
    {
        return match($this) {
            self::TEXT        => 'Text',
            self::IMAGE       => 'Image',
            self::DOCUMENT    => 'Document',
            self::AUDIO       => 'Audio',
            self::TEMPLATE    => 'Template',
            self::INTERACTIVE => 'Interactive',
        };
    }

    public function hasMedia(): bool
    {
        return in_array($this, [self::IMAGE, self::DOCUMENT, self::AUDIO], strict: true);
    }
}
