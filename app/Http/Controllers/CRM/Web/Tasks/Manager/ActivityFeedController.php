<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Tasks\Manager;

use App\Models\CRM\Tasks\TaskAutoRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-TF-007 — Manager real-time activity feed
final class ActivityFeedController
{
    public function index(): View
    {
        Gate::authorize('viewActivityFeed', TaskAutoRule::class);

        return view('crm.manager.activity-feed');
    }
}
