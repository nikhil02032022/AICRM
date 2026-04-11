<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\Models\CRM\CampaignSpend;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CampaignSpendRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): CampaignSpend;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;
}
