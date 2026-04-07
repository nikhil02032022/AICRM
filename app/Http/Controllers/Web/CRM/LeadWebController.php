<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\LeadSource;
use App\Http\Controllers\Controller;
use App\Models\CRM\Lead;
use Illuminate\View\View;

// BRD: CRM-LC-011 — Web controller for Blade views (non-API, web middleware stack)
final class LeadWebController extends Controller
{
    public function index(): View
    {
        return view('crm.leads.index');
    }

    public function create(): View
    {
        return view('crm.leads.create', [
            'sourceOptions' => LeadSource::optionsForSelect(),
        ]);
    }

    public function show(Lead $lead): View
    {
        $lead->load(['assignedCounsellor', 'programmeInterests']);

        return view('crm.leads.show', compact('lead'));
    }
}
