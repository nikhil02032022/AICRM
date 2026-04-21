<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Payments;

interface PaymentReportRepositoryInterface
{
    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    public function summary(array $filters): array;

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function programmeBreakdown(array $filters): array;
}
