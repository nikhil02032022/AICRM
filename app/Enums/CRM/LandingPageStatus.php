<?php

declare(strict_types=1);

namespace App\Enums\CRM;

// BRD: CRM-LC-005 — Landing page publication lifecycle
enum LandingPageStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::PUBLISHED;
    }
}