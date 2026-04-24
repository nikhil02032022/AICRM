<?php

declare(strict_types=1);

namespace App\Policies\CRM\AI;

use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AI-001 — RBAC gate for viewing and providing feedback on conversion probability predictions
// BRD: NFR-SE-001 — Institution-scoped access control enforced on all AI prediction routes
final class AiPredictionPolicy
{
    use HandlesAuthorization;

    public function viewPrediction(User $user, Lead $lead): bool
    {
        return $user->can('ai.prediction.view')
            && $user->institution_id === $lead->institution_id;
    }

    public function feedback(User $user, Lead $lead): bool
    {
        return $user->can('ai.prediction.feedback')
            && $user->institution_id === $lead->institution_id;
    }
}
