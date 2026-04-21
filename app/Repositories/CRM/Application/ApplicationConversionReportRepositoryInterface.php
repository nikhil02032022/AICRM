<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use Illuminate\Support\Collection;

interface ApplicationConversionReportRepositoryInterface
{
    /**
     * Get conversion stats grouped by programme, source, and counsellor.
     *
     * @param array<string, mixed> $filters
     * @return Collection
     */
    public function getGroupedConversionStats(array $filters = []): Collection;

    /**
     * Get conversion rate stats (total applications vs enrolled) grouped by
     * programme, batch, source, and counsellor. BRD: CRM-AP-019.
     *
     * @param array<string, mixed> $filters
     * @return Collection
     */
    public function getConversionRates(array $filters = []): Collection;
}
