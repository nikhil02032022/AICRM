<?php

declare(strict_types=1);

namespace App\Services\CRM\Admin;

use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use Illuminate\Support\Facades\Auth;

// BRD: CRM-SA-001, SA-002 — Resolve institution and campus from authenticated context
class TenancyService
{
    public function resolveInstitution(): Institution
    {
        return Institution::findOrFail(Auth::user()->institution_id);
    }

    public function resolveCampus(): ?Campus
    {
        $campusId = Auth::user()->campus_id ?? null;

        return $campusId ? Campus::find($campusId) : null;
    }

    public function institutionId(): int
    {
        return (int) Auth::user()->institution_id;
    }

    public function campusId(): ?int
    {
        return Auth::user()->campus_id ? (int) Auth::user()->campus_id : null;
    }
}
