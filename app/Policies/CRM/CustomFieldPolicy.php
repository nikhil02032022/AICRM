<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\CustomField;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-EC-005 — RBAC gate checks for custom field management
// BRD: NFR-SE-001 — OWASP A01: institution-scoped access control
final class CustomFieldPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('crm.settings.custom-fields.view');
    }

    public function view(User $user, CustomField $field): bool
    {
        return $user->can('crm.settings.custom-fields.view')
            && $user->institution_id === $field->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.settings.custom-fields.manage');
    }

    public function update(User $user, CustomField $field): bool
    {
        return $user->can('crm.settings.custom-fields.manage')
            && $user->institution_id === $field->institution_id;
    }

    public function delete(User $user, CustomField $field): bool
    {
        return $user->can('crm.settings.custom-fields.manage')
            && $user->institution_id === $field->institution_id;
    }
}
