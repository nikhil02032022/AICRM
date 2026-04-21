<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Repositories\CRM\Payments\PaymentReportRepositoryInterface;

// BRD: CRM-FM-012 — Compose finance dashboard widgets.
final class FeeDashboardService
{
    public function __construct(private readonly PaymentReportRepositoryInterface $repo) {}

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function compose(array $filters): array
    {
        $summary  = $this->repo->summary($filters);
        $byProg   = $this->repo->programmeBreakdown($filters);

        $forecast = array_sum(array_column(
            array_filter($byProg, fn ($r) => $r['status'] === 'pending' || $r['status'] === 'initiated'),
            'total',
        ));

        return [
            'summary'             => $summary,
            'programme_breakdown' => $byProg,
            'forecast'            => (float) $forecast,
        ];
    }
}
