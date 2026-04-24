<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api;

use App\Http\Controllers\Controller;
use App\Models\CRM\Institution;
use App\Services\CRM\Analytics\AnalyticsApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

// BRD: CRM-AR-021 — REST API endpoints for Power BI / Tableau analytics integration
final class AnalyticsApiController extends Controller
{
    public function __construct(
        private readonly AnalyticsApiService $service,
    ) {}

    /** GET /api/v1/crm/analytics/leads */
    public function leads(Request $request): JsonResponse
    {
        [$institution, $from, $to] = $this->resolveContext($request);

        $data = $this->service->getLeadFunnelMetrics($institution, $from, $to);

        return $this->envelope($data, $from, $to, $institution->id, $request->fullUrl());
    }

    /** GET /api/v1/crm/analytics/pipeline */
    public function pipeline(Request $request): JsonResponse
    {
        [$institution, $from, $to] = $this->resolveContext($request);

        $data = $this->service->getPipelineMetrics($institution, $from, $to);

        return $this->envelope($data, $from, $to, $institution->id, $request->fullUrl());
    }

    /** GET /api/v1/crm/analytics/fees */
    public function feeCollection(Request $request): JsonResponse
    {
        [$institution, $from, $to] = $this->resolveContext($request);

        $data = $this->service->getFeeCollectionMetrics($institution, $from, $to);

        return $this->envelope($data, $from, $to, $institution->id, $request->fullUrl());
    }

    /** GET /api/v1/crm/analytics/counsellors */
    public function counsellorPerformance(Request $request): JsonResponse
    {
        [$institution, $from, $to] = $this->resolveContext($request);

        $data = $this->service->getCounsellorPerformanceMetrics($institution, $from, $to);

        return $this->envelope($data, $from, $to, $institution->id, $request->fullUrl());
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Validate the token ability, resolve the institution, and parse date range.
     *
     * @return array{0: Institution, 1: Carbon, 2: Carbon}
     */
    private function resolveContext(Request $request): array
    {
        // Ability gate — only analytics:read tokens may call these endpoints
        if (! $request->user()->tokenCan('analytics:read')) {
            abort(403, 'Token does not have the analytics:read ability.');
        }

        // Institution from token — explicit scoping (belt-and-suspenders vs InstitutionScope)
        $tokenInstitutionId = $request->user()->currentAccessToken()->institution_id;
        $userInstitutionId  = $request->user()->institution_id;

        if (! $tokenInstitutionId || $tokenInstitutionId !== $userInstitutionId) {
            abort(403, 'Token institution does not match the authenticated user.');
        }

        $institution = Institution::withoutGlobalScopes()->findOrFail($tokenInstitutionId);

        // Date range validation
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date'   => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        [$from, $to] = $this->resolveDates(
            $validated['from_date'] ?? null,
            $validated['to_date']   ?? null,
            $institution->id,
        );

        return [$institution, $from, $to];
    }

    /** Build Carbon date range; default to current academic year start → today. */
    private function resolveDates(?string $fromDate, ?string $toDate, int $institutionId): array
    {
        $to   = $toDate   ? Carbon::parse($toDate)   : Carbon::today();

        if ($fromDate) {
            $from = Carbon::parse($fromDate);
        } else {
            // Fall back to current active academic year start, then current month start
            $academicYear = \App\Models\CRM\Admin\AcademicYear::withoutGlobalScopes()
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->latest('start_date')
                ->first();

            $from = $academicYear
                ? Carbon::instance($academicYear->start_date)
                : Carbon::today()->startOfMonth();
        }

        return [$from, $to];
    }

    /** Standard JSON envelope for all analytics API responses. */
    private function envelope(
        mixed  $data,
        Carbon $from,
        Carbon $to,
        int    $institutionId,
        string $selfUrl,
    ): JsonResponse {
        return response()->json([
            'data' => $data,
            'meta' => [
                'from_date'      => $from->toDateString(),
                'to_date'        => $to->toDateString(),
                'institution_id' => $institutionId,
                'generated_at'   => now()->toIso8601String(),
            ],
            'links' => [
                'self' => $selfUrl,
            ],
        ]);
    }
}
