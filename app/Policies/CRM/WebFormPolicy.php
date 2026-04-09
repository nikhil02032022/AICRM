<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\WebForm;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-LC-001 — RBAC gate policy for WebForm operations
// BRD: A01 OWASP — Ensures institution-scoped access control on all WebForm actions
class WebFormPolicy
{
    use HandlesAuthorization;

    public function view(User $user, WebForm $form): bool
    {
        return $user->can('crm.forms.view') && $user->institution_id === $form->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.forms.create');
    }

    public function edit(User $user, WebForm $form): bool
    {
        return $user->can('crm.forms.edit') && $user->institution_id === $form->institution_id;
    }

    public function delete(User $user, WebForm $form): bool
    {
        return $user->can('crm.forms.delete') && $user->institution_id === $form->institution_id;
    }
}
