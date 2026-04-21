<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Tasks;

use App\Models\CRM\Tasks\TaskEscalationRule;
use Illuminate\Database\Eloquent\Collection;

final class EloquentTaskEscalationRuleRepository implements TaskEscalationRuleRepositoryInterface
{
    public function activeRulesFor(int $institutionId): Collection
    {
        return TaskEscalationRule::where('institution_id', $institutionId)
            ->where('is_active', true)
            ->with(['role'])
            ->get();
    }
}
