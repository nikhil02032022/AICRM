<?php

declare(strict_types=1);

namespace App\Services\CRM\Application;

use App\Repositories\CRM\Application\ApplicationConversionReportRepositoryInterface;
use Illuminate\Support\Collection;

class ConversionReportService
{
    public function __construct(
        protected ApplicationConversionReportRepositoryInterface $reportRepository
    ) {}

    /**
     * Get grouped conversion stats for reporting (BRD: CRM-AP-017)
     *
     * @param array<string, mixed> $filters
     * @return Collection
     */
    public function getGroupedStats(array $filters = []): Collection
    {
        return $this->reportRepository->getGroupedConversionStats($filters);
    }

    /**
     * Get conversion rate stats (applications → enrolled) by programme, batch, source, counsellor.
     * BRD: CRM-AP-019
     *
     * @param array<string, mixed> $filters
     * @return Collection
     */
    public function getConversionRates(array $filters = []): Collection
    {
        return $this->reportRepository->getConversionRates($filters);
    }
}
