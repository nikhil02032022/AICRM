<?php

declare(strict_types=1);

namespace App\Policies\CRM\Alumni;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AL-004 — Only admins may manage NPS snapshots
final class AlumniNpsPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->can('alumni.nps.manage');
    }

    public function viewAny(User $user): bool
    {
        return $user->can('alumni.nps.manage');
    }
}
