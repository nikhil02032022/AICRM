<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Models\CRM\TelecallingCampaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TelecallingCampaignRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /** @param array<string, mixed> $payload */
    public function create(int $institutionId, int $createdBy, array $payload): TelecallingCampaign;

    /** @param array<string, mixed> $payload */
    public function update(TelecallingCampaign $campaign, array $payload): TelecallingCampaign;
}
