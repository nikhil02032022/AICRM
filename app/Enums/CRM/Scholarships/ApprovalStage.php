<?php

declare(strict_types=1);

namespace App\Enums\CRM\Scholarships;

// BRD: CRM-FM-008 — counsellor -> manager -> finance
enum ApprovalStage: string
{
    case COUNSELLOR = 'counsellor';
    case MANAGER = 'manager';
    case FINANCE = 'finance';

    public function next(): ?self
    {
        return match ($this) {
            self::COUNSELLOR => self::MANAGER,
            self::MANAGER => self::FINANCE,
            self::FINANCE => null,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
