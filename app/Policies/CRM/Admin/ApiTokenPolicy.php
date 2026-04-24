<?php

declare(strict_types=1);

namespace App\Policies\CRM\Admin;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AR-021 — Restrict API token issuance and revocation to admin roles only
final class ApiTokenPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->can('api_token.manage');
    }
}
