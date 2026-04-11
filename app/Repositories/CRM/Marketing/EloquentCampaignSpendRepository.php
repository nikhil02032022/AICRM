<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\Models\CRM\CampaignSpend;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentCampaignSpendRepository implements CampaignSpendRepositoryInterface
{
    public function create(array $data): CampaignSpend
    {
        return CampaignSpend::create($data);
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return CampaignSpend::query()
            ->when(
                ! empty($filters['source']),
                fn ($query) => $query->where('source', (string) $filters['source']),
            )
            ->when(
                ! empty($filters['campaign_name']),
                fn ($query) => $query->where('campaign_name', 'like', '%'.(string) $filters['campaign_name'].'%'),
            )
            ->when(
                ! empty($filters['period_start']),
                fn ($query) => $query->whereDate('period_end', '>=', (string) $filters['period_start']),
            )
            ->when(
                ! empty($filters['period_end']),
                fn ($query) => $query->whereDate('period_start', '<=', (string) $filters['period_end']),
            )
            ->orderByDesc('period_end')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
