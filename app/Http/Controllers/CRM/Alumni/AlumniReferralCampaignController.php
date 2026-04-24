<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Alumni;

use App\Enums\CRM\Alumni\ReferralCampaignStatus;
use App\Enums\CRM\Alumni\ReferralRewardType;
use App\Http\Controllers\Controller;
use App\Models\CRM\Alumni\AlumniReferralCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-AL-002 — CRUD for alumni referral campaigns
final class AlumniReferralCampaignController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AlumniReferralCampaign::class);

        $campaigns = AlumniReferralCampaign::withCount('codes')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('crm.alumni.referral-campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('create', AlumniReferralCampaign::class);

        $rewardTypes = ReferralRewardType::cases();

        return view('crm.alumni.referral-campaigns.create', compact('rewardTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AlumniReferralCampaign::class);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'reward_type'  => ['required', 'string', 'in:' . implode(',', array_column(ReferralRewardType::cases(), 'value'))],
            'reward_value' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ]);

        AlumniReferralCampaign::create(array_merge($validated, [
            'institution_id' => $request->user()->institution_id,
            'status'         => ReferralCampaignStatus::Draft->value,
            'created_by'     => $request->user()->id,
        ]));

        return redirect()->route('crm.alumni.referral.campaigns.index')
            ->with('success', 'Referral campaign created successfully.');
    }

    public function edit(AlumniReferralCampaign $campaign): View
    {
        $this->authorize('update', $campaign);

        $rewardTypes = ReferralRewardType::cases();

        return view('crm.alumni.referral-campaigns.edit', compact('campaign', 'rewardTypes'));
    }

    public function update(Request $request, AlumniReferralCampaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'reward_type'  => ['required', 'string', 'in:' . implode(',', array_column(ReferralRewardType::cases(), 'value'))],
            'reward_value' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ]);

        $campaign->update($validated);

        return redirect()->route('crm.alumni.referral.campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(AlumniReferralCampaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('crm.alumni.referral.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    public function activate(AlumniReferralCampaign $campaign): RedirectResponse
    {
        $this->authorize('manage', $campaign);

        $campaign->update(['status' => ReferralCampaignStatus::Active->value]);

        return back()->with('success', 'Campaign activated.');
    }

    public function pause(AlumniReferralCampaign $campaign): RedirectResponse
    {
        $this->authorize('manage', $campaign);

        $campaign->update(['status' => ReferralCampaignStatus::Paused->value]);

        return back()->with('success', 'Campaign paused.');
    }
}
