<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\DTOs\CRM\BookSessionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CRM\PublicBookSessionRequest;
use App\Models\CRM\Lead;
use App\Services\CRM\Counselling\CounsellingService;
use App\Services\CRM\Counselling\CounsellorAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-EC-016 — Public-facing appointment booking page (no auth required)
// BRD: CRM-LC-008 — Lead identity resolved from booking token, NOT session auth
final class PublicBookingController extends Controller
{
    public function __construct(
        private readonly CounsellingService $counsellingService,
        private readonly CounsellorAvailabilityService $availabilityService,
    ) {}

    /**
     * GET /book/{slug}
     * BRD: CRM-EC-016 — Show available time slots for public booking.
     * {slug} is a lead UUID — the lead must exist and have consent_given = true.
     */
    public function show(string $slug, Request $request): View
    {
        // BRD: DPDP — consent_given must be true before showing public booking form
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $slug)
            ->where('consent_given', true)
            ->firstOrFail();

        $counsellorId = (int) $request->query('counsellor_id', 0);
        $date = $request->query('date', today()->addDay()->toDateString());

        $availableTimes = $counsellorId > 0
            ? $this->availabilityService->getAvailableTimesForDate($counsellorId, Carbon::parse($date))
            : collect();

        $activeSlots = $this->availabilityService->getActiveForInstitution($lead->institution_id);

        return view('public.booking.show', compact('lead', 'counsellorId', 'date', 'availableTimes', 'activeSlots'));
    }

    /**
     * POST /book/{slug}
     * BRD: CRM-EC-016 — Submit appointment from public booking form.
     */
    public function submit(PublicBookSessionRequest $request, string $slug): RedirectResponse
    {
        // BRD: DPDP — Re-validate consent before booking
        $lead = Lead::withoutGlobalScopes()
            ->where('uuid', $slug)
            ->where('consent_given', true)
            ->firstOrFail();

        $dto = BookSessionDTO::fromValidated(
            array_merge($request->validated(), ['lead_id' => $lead->getKey()])
        );

        $session = $this->counsellingService->book($dto);
        $this->counsellingService->generateBookingToken($session);

        return redirect()
            ->route('public.booking.confirmation', $slug)
            ->with('session_uuid', $session->getKey());
    }

    /**
     * GET /book/{slug}/confirmation
     * BRD: CRM-EC-016 — Booking confirmation page.
     */
    public function confirmation(string $slug): View
    {
        return view('public.booking.confirmation', ['leadSlug' => $slug]);
    }
}
