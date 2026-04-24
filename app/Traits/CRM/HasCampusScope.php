<?php

declare(strict_types=1);

namespace App\Traits\CRM;

use App\Models\CRM\Scopes\CampusScope;

trait HasCampusScope
{
    protected static function bootHasCampusScope(): void
    {
        static::addGlobalScope(new CampusScope);
    }
}
