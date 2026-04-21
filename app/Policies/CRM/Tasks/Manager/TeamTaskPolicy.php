<?php

declare(strict_types=1);

namespace App\Policies\CRM\Tasks\Manager;

use App\Models\User;

// BRD: CRM-TF-006, CRM-TF-007 — Manager-only policies for team task view and activity feed
final class TeamTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.tasks.team.view');
    }

    public function viewActivityFeed(User $user): bool
    {
        return $user->can('crm.tasks.activity-feed.view');
    }
}
