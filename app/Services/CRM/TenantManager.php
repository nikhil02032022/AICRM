<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * TenantManager — Resolves the current institution_id from the authenticated user.
 *
 * Registered as a singleton in AppServiceProvider.
 * Inject via constructor or app(TenantManager::class).
 */
final class TenantManager
{
    public function institutionId(): int
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user === null || $user->institution_id === null) {
            abort(403, 'No institution context found for this user.');
        }

        return (int) $user->institution_id;
    }

    public function campusId(): ?int
    {
        /** @var User $user */
        $user = Auth::user();

        return $user?->campus_id !== null ? (int) $user->campus_id : null;
    }

    public function institution(): Institution
    {
        return Institution::findOrFail($this->institutionId());
    }
}
