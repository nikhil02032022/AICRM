<?php

declare(strict_types=1);

namespace App\DTOs\CRM\Tasks;

use Carbon\Carbon;

// BRD: CRM-TF-009 — Calendar view query parameters (daily/weekly/monthly)
final readonly class TaskCalendarQueryDTO
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public string $viewType,
    ) {}

    public static function fromRequest(string $start, string $end, string $viewType = 'week'): self
    {
        return new self(
            start:    Carbon::parse($start)->startOfDay(),
            end:      Carbon::parse($end)->endOfDay(),
            viewType: in_array($viewType, ['day', 'week', 'month'], true) ? $viewType : 'week',
        );
    }
}
