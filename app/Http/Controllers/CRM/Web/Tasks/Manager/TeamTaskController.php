<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Tasks\Manager;

use App\Models\CRM\Tasks\TaskAutoRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-TF-006 — Manager team task overview
final class TeamTaskController
{
    public function index(): View
    {
        Gate::authorize('viewAny', TaskAutoRule::class);

        return view('crm.manager.team-tasks');
    }
}
