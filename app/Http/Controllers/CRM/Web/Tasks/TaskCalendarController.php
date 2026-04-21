<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Web\Tasks;

use App\Models\CRM\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-TF-009 — Task calendar view controller
final class TaskCalendarController
{
    public function index(Request $request): View
    {
        Gate::authorize('crm.tasks.calendar');

        return view('crm.tasks.calendar');
    }
}
