<?php

declare(strict_types=1);

namespace App\Enums\CRM\Documents;

// BRD: CRM-DM-002 — Upload channels
enum DocumentUploadChannel: string
{
    case PORTAL = 'portal';
    case WHATSAPP = 'whatsapp';
    case EMAIL = 'email';
    case STAFF = 'staff';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
