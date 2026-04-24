<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Alumni;

use App\Http\Controllers\Controller;
use App\Models\CRM\Alumni\AlumniPipeline;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AL-001 — Alumni pipeline from enrolled students
final class AlumniPipelineController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('crm.alumni.pipeline.view');

        $records = AlumniPipeline::with(['lead:id,first_name,last_name,uuid', 'programme:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return view('crm.alumni.pipeline.index', compact('records'));
    }
}
