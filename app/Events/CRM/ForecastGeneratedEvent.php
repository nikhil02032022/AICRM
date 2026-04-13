<?php

declare(strict_types=1);

namespace App\Events\CRM;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-AI-008 — Fired when monthly enrolment forecasts are generated for an institution
final class ForecastGeneratedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $institutionId,
        public readonly string $generatedForMonth,
        public readonly int $recordsGenerated,
    ) {}
}
