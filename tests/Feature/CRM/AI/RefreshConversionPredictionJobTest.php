<?php

declare(strict_types=1);

// BRD: CRM-AI-001 — Feature tests for RefreshConversionPredictionJob

use App\Enums\CRM\AI\PredictionStatus;
use App\Jobs\CRM\AI\RefreshConversionPredictionJob;
use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\AI\ConversionPredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();
    $this->lead        = Lead::factory()->create(['institution_id' => $this->institution->id]);
});

it('dispatches prediction service and marks prediction completed', function (): void {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => json_encode([
                    'conversion_probability' => 0.68,
                    'confidence_score'       => 0.80,
                    'prediction_factors'     => [
                        ['factor' => 'Active sessions', 'weight' => 'positive', 'impact' => 'high'],
                        ['factor' => 'Low docs',        'weight' => 'negative', 'impact' => 'medium'],
                        ['factor' => 'Good source',     'weight' => 'positive', 'impact' => 'low'],
                    ],
                ])],
            ],
            'usage' => ['input_tokens' => 110, 'output_tokens' => 55],
        ]),
    ]);

    (new RefreshConversionPredictionJob($this->lead->uuid))
        ->handle(app(ConversionPredictionService::class));

    $score = AiLeadScore::withoutGlobalScopes()
        ->where('lead_id', $this->lead->id)
        ->whereNotNull('prediction_status')
        ->latest('calculated_at')
        ->first();

    expect($score)->not->toBeNull();
    expect($score->prediction_status)->toBe(PredictionStatus::Completed);
    expect($score->conversion_probability)->not->toBeNull();
});

it('skips processing when redis lock is already held', function (): void {
    // Pre-acquire the lock to simulate another running job
    $lockKey = "predict-lock:{$this->lead->institution_id}:{$this->lead->id}";
    Cache::lock($lockKey, 120)->get();

    $service = Mockery::mock(ConversionPredictionService::class);
    $service->shouldNotReceive('predict');

    (new RefreshConversionPredictionJob($this->lead->uuid))->handle($service);

    // Release lock
    Cache::lock($lockKey, 120)->forceRelease();
});

it('has unique job id per lead uuid preventing duplicate queue entries', function (): void {
    Queue::fake();

    RefreshConversionPredictionJob::dispatch($this->lead->uuid);
    RefreshConversionPredictionJob::dispatch($this->lead->uuid);

    $job = new RefreshConversionPredictionJob($this->lead->uuid);

    expect($job->uniqueId())->toBe("convert-prediction:{$this->lead->uuid}");
});
