<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Repositories\CRM\Marketing\LandingPageRepositoryInterface;
use App\Services\CRM\Marketing\LandingPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

// BRD: CRM-LC-005 — Public landing page renderer for campaign pages
final class PublicLandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageRepositoryInterface $repository,
        private readonly LandingPageService $service,
    ) {}

    public function show(string $slug, Request $request): View
    {
        $landingPage = $this->repository->findPublishedBySlug($slug);

        abort_if($landingPage === null, 404);

        $visitorHash = $this->resolveVisitorHash($request);

        $this->service->recordPublicView($landingPage, [
            'visitor_hash' => $visitorHash,
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_term' => $request->query('utm_term'),
            'utm_content' => $request->query('utm_content'),
        ]);

        return view('public.landing-page.show', [
            'landingPage' => $landingPage,
        ]);
    }

    private function resolveVisitorHash(Request $request): ?string
    {
        $ip = trim((string) $request->ip());
        $userAgent = trim(substr((string) $request->userAgent(), 0, 255));

        if ($ip === '' && $userAgent === '') {
            return null;
        }

        return hash('sha256', $ip.'|'.$userAgent);
    }
}