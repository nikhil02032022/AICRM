<?php

declare(strict_types=1);

// BRD: CRM-AR-021 — Unit tests for AnalyticsApiService delegation and PII stripping

use App\Models\CRM\Institution;
use App\Services\CRM\Analytics\AnalyticsApiService;
use App\Services\CRM\Analytics\CounsellorDashboardService;
use App\Services\CRM\Analytics\FunnelAnalyticsService;
use App\Services\CRM\Analytics\InstitutionDashboardService;
use App\Services\CRM\Analytics\ReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->from        = Carbon::parse('2025-07-01');
    $this->to          = Carbon::parse('2026-04-24');

    $this->funnelService      = Mockery::mock(FunnelAnalyticsService::class);
    $this->institutionService = Mockery::mock(InstitutionDashboardService::class);
    $this->counsellorService  = Mockery::mock(CounsellorDashboardService::class);
    $this->reportService      = Mockery::mock(ReportService::class);

    $this->service = new AnalyticsApiService(
        $this->funnelService,
        $this->institutionService,
        $this->counsellorService,
        $this->reportService,
    );
});

afterEach(fn () => Mockery::close());

describe('getLeadFunnelMetrics', function () {
    it('delegates to FunnelAnalyticsService and returns stages and by_source', function () {
        $expectedScope   = ['institution_id' => $this->institution->id, 'campus_id' => null, 'counsellor_ids' => null, 'role' => 'director'];
        $expectedFilters = ['from' => '2025-07-01', 'to' => '2026-04-24'];

        $this->funnelService
            ->shouldReceive('getFunnelStages')
            ->once()
            ->with($expectedScope, $expectedFilters)
            ->andReturn([['stage' => 'enquiry', 'count' => 100, 'label' => 'Enquiries', 'conversion_rate' => 100.0, 'drop_off_count' => 0, 'drop_off_rate' => 0.0]]);

        $this->funnelService
            ->shouldReceive('getFunnelBySource')
            ->once()
            ->with($expectedScope, $expectedFilters)
            ->andReturn(new Collection([['source' => 'google_ads', 'total_leads' => 50, 'total_applied' => 20, 'total_enrolled' => 8]]));

        $result = $this->service->getLeadFunnelMetrics($this->institution, $this->from, $this->to);

        expect($result)->toHaveKeys(['stages', 'by_source'])
            ->and($result['stages'][0]['stage'])->toBe('enquiry')
            ->and($result['by_source'][0]['source'])->toBe('google_ads');
    });

    it('strips PII keys from funnel data', function () {
        $this->funnelService
            ->shouldReceive('getFunnelStages')
            ->andReturn([['stage' => 'enquiry', 'count' => 10, 'name' => 'Should be removed', 'label' => 'Enquiries', 'conversion_rate' => 100.0, 'drop_off_count' => 0, 'drop_off_rate' => 0.0]]);

        $this->funnelService
            ->shouldReceive('getFunnelBySource')
            ->andReturn(new Collection([['source' => 'walk_in', 'total_leads' => 5, 'total_applied' => 2, 'total_enrolled' => 1, 'email' => 'pii@example.com']]));

        $result = $this->service->getLeadFunnelMetrics($this->institution, $this->from, $this->to);

        expect(array_key_exists('name', $result['stages'][0]))->toBeFalse()
            ->and(array_key_exists('email', $result['by_source'][0]))->toBeFalse();
    });
});

describe('getPipelineMetrics', function () {
    it('delegates to InstitutionDashboardService::getByProgramme', function () {
        $this->institutionService
            ->shouldReceive('getByProgramme')
            ->once()
            ->andReturn(new Collection([['programme_id' => 1, 'programme' => 'MBA', 'total_applications' => 100, 'total_offers' => 70, 'total_enrolments' => 50]]));

        $result = $this->service->getPipelineMetrics($this->institution, $this->from, $this->to);

        expect($result)->toBeArray()
            ->and($result[0]['programme'])->toBe('MBA');
    });
});

describe('getFeeCollectionMetrics', function () {
    it('delegates to ReportService::feeCollectionSummary and returns flat array', function () {
        $summary = (object) [
            'collected'          => '4820000.00',
            'pending_amount'     => '312000.00',
            'refunded'           => '25000.00',
            'total_transactions' => '310',
            'successful_count'   => '284',
        ];

        $this->reportService
            ->shouldReceive('feeCollectionSummary')
            ->once()
            ->andReturn($summary);

        $result = $this->service->getFeeCollectionMetrics($this->institution, $this->from, $this->to);

        expect($result['collected'])->toBe(4820000.0)
            ->and($result['total_transactions'])->toBe(310);
    });
});

describe('getCounsellorPerformanceMetrics', function () {
    it('delegates to CounsellorDashboardService::getPerformanceGrid', function () {
        $this->counsellorService
            ->shouldReceive('getPerformanceGrid')
            ->once()
            ->andReturn(new Collection([(object) ['counsellor_id' => 42, 'total_leads' => 87, 'total_converted' => 31, 'conversion_rate' => 35.6, 'total_tasks' => 214, 'tasks_completed' => 198, 'avg_response_hours' => 1.4]]));

        $result = $this->service->getCounsellorPerformanceMetrics($this->institution, $this->from, $this->to);

        expect($result[0]['counsellor_id'])->toBe(42);
    });

    it('strips name and mobile from counsellor data', function () {
        $this->counsellorService
            ->shouldReceive('getPerformanceGrid')
            ->andReturn(new Collection([(object) ['counsellor_id' => 5, 'total_leads' => 10, 'total_converted' => 3, 'conversion_rate' => 30.0, 'total_tasks' => 40, 'tasks_completed' => 38, 'avg_response_hours' => 2.0, 'name' => 'Jane Doe', 'mobile' => '9876543210']]));

        $result = $this->service->getCounsellorPerformanceMetrics($this->institution, $this->from, $this->to);

        expect(array_key_exists('name', $result[0]))->toBeFalse()
            ->and(array_key_exists('mobile', $result[0]))->toBeFalse()
            ->and($result[0]['counsellor_id'])->toBe(5);
    });
});
