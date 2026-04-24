<?php

declare(strict_types=1);

namespace App\Policies\CRM\Communication;

use App\Models\CRM\CallLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

// BRD: CRM-AI-007 — RBAC gate for viewing and retrying AI call transcriptions
// BRD: NFR-SE-001 — Institution-scoped access control enforced on all transcription routes
final class CallTranscriptionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, CallLog $callLog): bool
    {
        return $user->institution_id === $callLog->institution_id;
    }

    public function retry(User $user, CallLog $callLog): bool
    {
        // Counsellor who owns the call, or any manager/admin within the same institution
        return $user->institution_id === $callLog->institution_id
            && (
                $user->id === $callLog->initiated_by
                || $user->hasRole(['manager', 'admin'])
            );
    }
}
