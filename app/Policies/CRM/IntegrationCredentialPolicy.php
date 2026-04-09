<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\IntegrationCredential;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-SA-010 — RBAC gate checks for integration credential management
// OWASP A01 — All access to integration credentials must verify institution membership
final class IntegrationCredentialPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('crm.integrations.view');
    }

    public function view(User $user, IntegrationCredential $credential): bool
    {
        return $user->can('crm.integrations.view')
            && $user->institution_id === $credential->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.integrations.manage');
    }

    public function update(User $user, IntegrationCredential $credential): bool
    {
        return $user->can('crm.integrations.manage')
            && $user->institution_id === $credential->institution_id;
    }

    public function delete(User $user, IntegrationCredential $credential): bool
    {
        return $user->can('crm.integrations.manage')
            && $user->institution_id === $credential->institution_id;
    }
}
