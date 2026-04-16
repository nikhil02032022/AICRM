<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\ApplicationFormDraft;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AP-003 — RBAC for application draft save-and-resume
class ApplicationFormDraftPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ApplicationFormDraft $draft): bool
    {
        return $user->can('crm.applications.view') && $user->institution_id === $draft->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.applications.create');
    }

    public function edit(User $user, ApplicationFormDraft $draft): bool
    {
        return $user->can('crm.applications.edit') && $user->institution_id === $draft->institution_id;
    }
}
