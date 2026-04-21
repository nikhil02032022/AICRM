<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Tasks;

use App\Models\CRM\Tasks\TaskAutoRule;
use Illuminate\Database\Eloquent\Collection;

final class EloquentTaskAutoRuleRepository implements TaskAutoRuleRepositoryInterface
{
    public function create(array $data): TaskAutoRule
    {
        return TaskAutoRule::create($data);
    }

    public function activeRulesFor(int $institutionId): Collection
    {
        return TaskAutoRule::where('institution_id', $institutionId)
            ->where('is_active', true)
            ->get();
    }

    public function findByUuid(string $uuid): ?TaskAutoRule
    {
        return TaskAutoRule::where('uuid', $uuid)->first();
    }
}
