<?php

declare(strict_types=1);

// BRD: CRM-AI-007 — Feature tests for TranscribeCallJob

use App\Enums\CRM\AI\TranscriptionStatus;
use App\Jobs\CRM\AI\TranscribeCallJob;
use App\Models\CRM\CallLog;
use App\Models\CRM\Institution;
use App\Services\CRM\AI\CallTranscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();

    $this->callLog = CallLog::factory()->create([
        'institution_id'   => $this->institution->id,
        'transcript_text'  => 'Student is interested in MBA. Fees are a concern.',
        'transcription_status' => null,
        'disposition_notes'    => null,
    ]);
});

it('dispatches transcription service and marks call log completed', function (): void {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => json_encode([
                    'interests'        => ['MBA'],
                    'objections'       => ['Fees'],
                    'next_steps'       => ['Send fee structure'],
                    'lead_temperature' => 'Warm',
                    'summary_sentence' => 'Student interested in MBA, fees are a concern.',
                ])],
            ],
            'usage' => ['input_tokens' => 150, 'output_tokens' => 60],
        ]),
    ]);

    (new TranscribeCallJob($this->callLog->uuid))
        ->handle(app(CallTranscriptionService::class));

    $this->callLog->refresh();
    expect($this->callLog->transcription_status)->toBe(TranscriptionStatus::Completed)
        ->and($this->callLog->transcription_summary)->not->toBeNull()
        ->and($this->callLog->transcription_summary['lead_temperature'])->toBe('Warm');
});

it('skips processing when redis lock is already held', function (): void {
    $lockKey = "transcription-lock:{$this->callLog->institution_id}:{$this->callLog->id}";
    Cache::lock($lockKey, 120)->get();

    $service = Mockery::mock(CallTranscriptionService::class);
    $service->shouldNotReceive('transcribe');

    (new TranscribeCallJob($this->callLog->uuid))->handle($service);

    Cache::lock($lockKey, 120)->forceRelease();
});

it('auto-populates disposition notes when blank', function (): void {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => json_encode([
                    'interests'        => ['MBA'],
                    'objections'       => [],
                    'next_steps'       => ['Book campus tour'],
                    'lead_temperature' => 'Hot',
                    'summary_sentence' => 'Very interested student — book campus tour ASAP.',
                ])],
            ],
            'usage' => ['input_tokens' => 120, 'output_tokens' => 40],
        ]),
    ]);

    (new TranscribeCallJob($this->callLog->uuid))
        ->handle(app(CallTranscriptionService::class));

    $this->callLog->refresh();
    expect($this->callLog->disposition_notes)
        ->toBe('Very interested student — book campus tour ASAP.');
});

it('does not overwrite existing disposition notes', function (): void {
    $this->callLog->update(['disposition_notes' => 'Counsellor wrote these notes already.']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => json_encode([
                    'interests'        => [],
                    'objections'       => [],
                    'next_steps'       => [],
                    'lead_temperature' => 'Cold',
                    'summary_sentence' => 'AI-generated sentence.',
                ])],
            ],
            'usage' => ['input_tokens' => 100, 'output_tokens' => 30],
        ]),
    ]);

    (new TranscribeCallJob($this->callLog->uuid))
        ->handle(app(CallTranscriptionService::class));

    $this->callLog->refresh();
    expect($this->callLog->disposition_notes)->toBe('Counsellor wrote these notes already.');
});

it('marks status as failed when service throws', function (): void {
    $this->callLog->update(['transcription_status' => TranscriptionStatus::Processing]);

    $service = Mockery::mock(CallTranscriptionService::class);
    $service->shouldReceive('transcribe')->andThrow(new \RuntimeException('API error'));

    expect(fn () => (new TranscribeCallJob($this->callLog->uuid))->handle($service))
        ->toThrow(\RuntimeException::class);

    $this->callLog->refresh();
    // failed() hook would be invoked by queue worker; we test the service behaviour
    // The job's failed() method sets status to Failed
    $job = new TranscribeCallJob($this->callLog->uuid);
    $job->failed(new \RuntimeException('API error'));

    $this->callLog->refresh();
    expect($this->callLog->transcription_status)->toBe(TranscriptionStatus::Failed);
});

it('is idempotent when status is already completed', function (): void {
    $this->callLog->update(['transcription_status' => TranscriptionStatus::Completed]);

    $service = Mockery::mock(CallTranscriptionService::class);
    $service->shouldNotReceive('transcribe');

    (new TranscribeCallJob($this->callLog->uuid))->handle($service);
});

it('has unique job id per call log uuid preventing duplicate queue entries', function (): void {
    Queue::fake();

    TranscribeCallJob::dispatch($this->callLog->uuid);
    TranscribeCallJob::dispatch($this->callLog->uuid);

    $job = new TranscribeCallJob($this->callLog->uuid);

    expect($job->uniqueId())->toBe("call-transcription:{$this->callLog->uuid}");
});
