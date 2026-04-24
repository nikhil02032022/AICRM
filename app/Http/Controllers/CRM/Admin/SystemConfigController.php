<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Admin;

use App\Http\Controllers\Controller;
use App\Services\CRM\Admin\SystemConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-SA-006 — System configuration (business hours, timezone, locale, branding)
final class SystemConfigController extends Controller
{
    public function __construct(private readonly SystemConfigService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('crm.admin.system-config.manage');

        $institutionId = $request->user()->institution_id;

        $config = $this->service->getAll($institutionId);

        return view('crm.admin.system-config.index', compact('config'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('crm.admin.system-config.manage');

        $institutionId = $request->user()->institution_id;

        $settingsMap = [
            'timezone'       => 'string',
            'locale'         => 'string',
            'business_hours' => 'json',
            'date_format'    => 'string',
            'currency'       => 'string',
            'logo_path'      => 'string',
            'primary_colour' => 'string',
        ];

        foreach ($settingsMap as $key => $type) {
            if ($request->has($key)) {
                $this->service->set($key, $request->input($key), $type, $institutionId);
            }
        }

        return redirect()->route('crm.admin.system-config.index')
            ->with('success', 'System configuration saved.');
    }
}
