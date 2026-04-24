<?php

declare(strict_types=1);

namespace App\Policies\CRM\Alumni;

use App\Models\CRM\Alumni\AlumniReferralCampaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AL-002 — RBAC for alumni referral campaign management
final class AlumniReferralCampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('alumni.referral.view');
    }

    public function view(User $user, AlumniReferralCampaign $campaign): bool
    {
        return $user->can('alumni.referral.view')
            && $user->institution_id === $campaign->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('alumni.referral.manage');
    }

    public function update(User $user, AlumniReferralCampaign $campaign): bool
    {
        return $user->can('alumni.referral.manage')
            && $user->institution_id === $campaign->institution_id;
    }

    public function delete(User $user, AlumniReferralCampaign $campaign): bool
    {
        return $user->can('alumni.referral.manage')
            && $user->institution_id === $campaign->institution_id;
    }

    public function manage(User $user, AlumniReferralCampaign $campaign): bool
    {
        return $user->can('alumni.referral.manage')
            && $user->institution_id === $campaign->institution_id;
    }
}
