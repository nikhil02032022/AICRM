<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Alumni;

use App\Http\Controllers\Controller;
use App\Jobs\CRM\Alumni\SendReferralCodeJob;
use App\Models\CRM\Alumni\AlumniPipeline;
use App\Models\CRM\Alumni\AlumniReferralCampaign;
use App\Models\CRM\Alumni\AlumniReferralCode;
use App\Services\CRM\Alumni\AlumniReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// BRD: CRM-AL-002 — Manage referral codes per campaign: generate and share
final class AlumniReferralCodeController extends Controller
{
    public function __construct(
        private readonly AlumniReferralService $service,
    ) {}

    public function index(AlumniReferralCampaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $codes = $campaign->codes()
            ->with('alumni.lead')
            ->orderByDesc('created_at')
            ->paginate(30);

        $stats = $this->service->getStats($campaign);

        return view('crm.alumni.referral-codes.index', compact('campaign', 'codes', 'stats'));
    }

    public function generate(AlumniReferralCampaign $campaign, AlumniPipeline $alumni): JsonResponse
    {
        $this->authorize('manage', $campaign);

        $code = $this->service->generateCode($alumni, $campaign);

        return response()->json([
            'success' => true,
            'code'    => $code->code,
            'id'      => $code->id,
        ], 201);
    }

    public function share(AlumniReferralCode $code): RedirectResponse
    {
        $this->authorize('manage', $code->campaign);

        SendReferralCodeJob::dispatch($code->id);

        return back()->with('success', 'Referral code share notification queued for delivery.');
    }
}
