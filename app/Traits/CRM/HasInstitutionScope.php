<?php

declare(strict_types=1);

namespace App\Traits\CRM;

use App\Models\CRM\Scopes\InstitutionScope;

trait HasInstitutionScope
{
    protected static function bootHasInstitutionScope(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }
}
