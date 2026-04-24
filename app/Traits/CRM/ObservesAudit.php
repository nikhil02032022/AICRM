<?php

declare(strict_types=1);

namespace App\Traits\CRM;

use App\Observers\CRM\AuditObserver;

trait ObservesAudit
{
    protected static function bootObservesAudit(): void
    {
        static::observe(AuditObserver::class);
    }
}
