<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\CounsellingSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-EC-015 — RBAC gate checks for CounsellingSession resource
// BRD: NFR-SE-001 — Institution-scoped access control
final class CounsellingSessionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, CounsellingSession $session): bool
    {
        return $user->can('crm.sessions.view')
            && $user->institution_id === $session->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.sessions.create');
    }

    public function update(User $user, CounsellingSession $session): bool
    {
        return $user->can('crm.sessions.edit')
            && $user->institution_id === $session->institution_id
            && !$session->status->isTerminal();
    }

    public function cancel(User $user, CounsellingSession $session): bool
    {
        return $user->can('crm.sessions.cancel')
            && $user->institution_id === $session->institution_id
            && !$session->status->isTerminal();
    }
}
