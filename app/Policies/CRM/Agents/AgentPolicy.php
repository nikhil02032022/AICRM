<?php

declare(strict_types=1);

namespace App\Policies\CRM\Agents;

use App\Models\CRM\Agents\Agent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AG-001 — Restrict agent CRUD to admissions_manager and above; enforce institution scope
final class AgentPolicy
{
    use HandlesAuthorization;

    private function isManagerOrAbove(User $user): bool
    {
        return $user->hasAnyRole(['admissions_manager', 'admissions_director', 'institution-admin', 'super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isManagerOrAbove($user);
    }

    public function view(User $user, Agent $agent): bool
    {
        return $this->isManagerOrAbove($user)
            && $user->institution_id === $agent->institution_id;
    }

    public function create(User $user): bool
    {
        return $this->isManagerOrAbove($user);
    }

    public function update(User $user, Agent $agent): bool
    {
        return $this->isManagerOrAbove($user)
            && $user->institution_id === $agent->institution_id;
    }

    public function delete(User $user, Agent $agent): bool
    {
        return $this->isManagerOrAbove($user)
            && $user->institution_id === $agent->institution_id;
    }
}
