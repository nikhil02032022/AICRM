<?php

declare(strict_types=1);

namespace App\Policies\CRM\Counselling;

use App\Models\CRM\WalkInToken;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-EC-019 — RBAC gate for walk-in queue operations; enforces campus-level scoping
final class WalkInQueuePolicy
{
    use HandlesAuthorization;

    /** Counsellor can manage (call/serve/skip) tokens at their own campus only. */
    public function manage(User $user, WalkInToken $token): bool
    {
        return $user->can('walk_in_queue.manage')
            && $user->institution_id === $token->institution_id
            && $user->campus_id === $token->campus_id;
    }

    /** Queue display screen is intentionally public — no personal data is shown. */
    public function viewDisplay(): bool
    {
        return true;
    }

    /** Stats view requires the walk_in_queue.stats permission. */
    public function viewStats(User $user): bool
    {
        return $user->can('walk_in_queue.stats');
    }
}
