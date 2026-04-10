<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-LC-011 — RBAC gate checks for Lead resource
// BRD: NFR-SE-001 — OWASP A01: all CRM resource access must check institution membership
final class LeadPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('crm.leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('crm.leads.view')
            && $user->institution_id === $lead->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('crm.leads.edit')
            && $user->institution_id === $lead->institution_id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('crm.leads.delete')
            && $user->institution_id === $lead->institution_id;
    }

    public function viewPii(User $user, Lead $lead): bool
    {
        return $user->can('crm.leads.view_pii')
            && $user->institution_id === $lead->institution_id;
    }

    // BRD: CRM-EC-007 — Only managers/admins may reassign leads; counsellors cannot self-reassign
    public function assign(User $user, Lead $lead): bool
    {
        return $user->can('crm.leads.assign')
            && $user->institution_id === $lead->institution_id;
    }
}
