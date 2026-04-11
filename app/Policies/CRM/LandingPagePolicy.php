<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\LandingPage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-LC-005 — Institution-scoped access policy for landing pages
class LandingPagePolicy
{
    use HandlesAuthorization;

    public function view(User $user, LandingPage $landingPage): bool
    {
        return $user->can('crm.campaigns.manage') && $user->institution_id === $landingPage->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.campaigns.manage');
    }

    public function edit(User $user, LandingPage $landingPage): bool
    {
        return $user->can('crm.campaigns.manage') && $user->institution_id === $landingPage->institution_id;
    }

    public function delete(User $user, LandingPage $landingPage): bool
    {
        return $user->can('crm.campaigns.manage') && $user->institution_id === $landingPage->institution_id;
    }
}