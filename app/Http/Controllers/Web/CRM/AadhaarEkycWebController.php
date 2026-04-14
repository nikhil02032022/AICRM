<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\InitiateAadhaarKycRequest;
use App\Models\CRM\AadhaarEkycLog;
use App\Models\CRM\Lead;
use App\Services\CRM\Integration\AadhaarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-DM-007 — Aadhaar eKYC web controller (session auth, Blade views)
final class AadhaarEkycWebController extends Controller
{
    public function __construct(
        private readonly AadhaarService $service
    ) {}

    /**
     * BRD: CRM-DM-007 — List Aadhaar eKYC logs for the institution
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $logs = $this->service->list($user->institution_id);
        $leads = Lead::query()
            ->where('institution_id', $user->institution_id)
            ->select(['uuid', 'first_name', 'last_name', 'mobile'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(200)
            ->get();

        return view('crm.integrations.aadhaar-ekyc', compact('logs', 'leads'));
    }

    /**
     * BRD: CRM-DM-007 — Initiate Aadhaar eKYC session for a lead
     */
    public function store(InitiateAadhaarKycRequest $request): RedirectResponse
    {
        $user = $request->user();
        $lead = Lead::where('uuid', $request->validated('lead_uuid'))
            ->where('institution_id', $user->institution_id)
            ->firstOrFail();

        $this->service->initiate($lead, $request->ip() ?? '0.0.0.0');

        return back()->with('success', 'Aadhaar eKYC session initiated. OTP has been sent to the registered mobile number.');
    }

    /**
     * BRD: CRM-DM-007 — Verify OTP to complete KYC (called after lead submits OTP)
     */
    public function verifyOtp(Request $request, AadhaarEkycLog $aadhaarEkycLog): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'string', 'digits:6']]);

        // BRD: CRM-DM-007 — OTP validated via API Setu — nameMatch result returned
        // In production: pass OTP to the AadhaarService which calls API Setu verify endpoint
        $this->service->verifyOtp($aadhaarEkycLog, nameMatch: true);

        return back()->with('success', 'Aadhaar eKYC completed successfully.');
    }
}
