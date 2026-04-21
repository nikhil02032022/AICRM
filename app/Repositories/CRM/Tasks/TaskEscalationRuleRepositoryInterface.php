<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Tasks;

use App\Models\CRM\Tasks\TaskEscalationRule;
use Illuminate\Database\Eloquent\Collection;

interface TaskEscalationRuleRepositoryInterface
{
    public function activeRulesFor(int $institutionId): Collection;
}
