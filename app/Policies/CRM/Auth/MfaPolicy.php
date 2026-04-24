<?php

declare(strict_types=1);

namespace App\Policies\CRM\Auth;

use App\Models\User;

// NFR-SE-003 — Governs who can disable MFA for another user.
final class MfaPolicy
{
    /**
     * An admin may disable MFA for any user within the same institution.
     */
    public function manage(User $actor, User $target): bool
    {
        return $actor->hasAnyRole(['institution-admin', 'super-admin'])
            && $actor->institution_id === $target->institution_id;
    }
}
