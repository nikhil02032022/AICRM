<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Tasks;

use App\Models\CRM\Tasks\TaskAutoRule;
use Illuminate\Database\Eloquent\Collection;

interface TaskAutoRuleRepositoryInterface
{
    public function create(array $data): TaskAutoRule;

    public function activeRulesFor(int $institutionId): Collection;

    public function findByUuid(string $uuid): ?TaskAutoRule;
}
