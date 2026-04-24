<?php

declare(strict_types=1);

// BRD: CRM-AI-007 — Unit tests for CallTranscriptionService

use App\Enums\CRM\AI\TranscriptionStatus;
use App\Models\CRM\CallLog;
use App\Models\CRM\Institution;
use App\Services\CRM\AI\AiUsageLoggingService;
use App\Services\CRM\AI\CallTranscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();

    $this->callLog = CallLog::factory()->create([
        'institution_id'   => $this->institution->id,
        'transcript_text'  => 'Student: I am interested in MBA. Counsellor: Great, let me explain the fees.',
        'transcription_status' => null,
    ]);

    $this->service = new CallTranscriptionService(app(AiUsageLoggingService::class));
});

it('builds the system prompt with required instructions', function (): void {
    $reflection = new ReflectionClass(CallTranscriptionService::class);
    $method     = $reflection->getMethod('systemPrompt');
    $method->setAccessible(true);

    $prompt = $method->invoke($this->service);

    expect($prompt)->toContain('interests')
        ->and($prompt)->toContain('objections')
        ->and($prompt)->toContain('next_steps')
        ->and($prompt)->toContain('lead_temperature')
        ->and($prompt)->toContain('summary_sentence')
        ->and($prompt)->toContain('Hot, Warm, Cold');
});

it('truncates transcripts longer than MAX_CHARS', function (): void {
    $reflection = new ReflectionClass(CallTranscriptionService::class);
    $method     = $reflection->getMethod('truncateTranscript');
    $method->setAccessible(true);

    $longText  = str_repeat('a', 40000);
    $truncated = $method->invoke($this->service, $longText);

    expect($truncated)->toContain('[NOTE: Transcript was truncated');
    expect(mb_strlen($truncated))->toBeLessThan(40000);
});

it('does not truncate transcripts within MAX_CHARS', function (): void {
    $reflection = new ReflectionClass(CallTranscriptionService::class);
    $method     = $reflection->getMethod('truncateTranscript');
    $method->setAccessible(true);

    $text      = str_repeat('b', 100);
    $result    = $method->invoke($this->service, $text);

    expect($result)->toBe($text);
});

it('parses a valid Claude JSON response', function (): void {
    $raw = [
        'content' => [
            ['text' => json_encode([
                'interests'        => ['MBA', 'Finance'],
                'objections'       => ['Fees too high'],
                'next_steps'       => ['Send brochure', 'Schedule campus visit'],
                'lead_temperature' => 'Warm',
                'summary_sentence' => 'Student is interested in MBA but concerned about fees.',
            ])],
        ],
        'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
    ];

    $parsed = $this->service->parseResponse($raw);

    expect($parsed['interests'])->toBe(['MBA', 'Finance'])
        ->and($parsed['objections'])->toBe(['Fees too high'])
        ->and($parsed['next_steps'])->toBe(['Send brochure', 'Schedule campus visit'])
        ->and($parsed['lead_temperature'])->toBe('Warm')
        ->and($parsed['summary_sentence'])->toBe('Student is interested in MBA but concerned about fees.');
});

it('throws on malformed JSON from Claude', function (): void {
    $raw = [
        'content' => [['text' => 'This is not JSON at all']],
        'usage'   => ['input_tokens' => 10, 'output_tokens' => 5],
    ];

    expect(fn () => $this->service->parseResponse($raw))
        ->toThrow(\RuntimeException::class, 'non-JSON');
});

it('validates required keys in parsed response', function (): void {
    expect($this->service->validateStructure([
        'interests'        => ['MBA'],
        'objections'       => [],
        'next_steps'       => ['Follow up'],
        'lead_temperature' => 'Hot',
        'summary_sentence' => 'Good call.',
    ]))->toBeTrue();

    expect($this->service->validateStructure([
        'interests'   => ['MBA'],
        // missing objections, next_steps, lead_temperature, summary_sentence
    ]))->toBeFalse();
});

it('rejects invalid lead_temperature values', function (): void {
    $valid = $this->service->validateStructure([
        'interests'        => [],
        'objections'       => [],
        'next_steps'       => [],
        'lead_temperature' => 'Lukewarm', // invalid
        'summary_sentence' => 'Test.',
    ]);

    expect($valid)->toBeFalse();
});

it('calls Claude API and marks call log as completed', function (): void {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => json_encode([
                    'interests'        => ['MBA'],
                    'objections'       => ['Distance'],
                    'next_steps'       => ['Schedule visit'],
                    'lead_temperature' => 'Hot',
                    'summary_sentence' => 'Student is very keen on MBA programme.',
                ])],
            ],
            'usage' => ['input_tokens' => 200, 'output_tokens' => 80],
        ]),
    ]);

    $result = $this->service->transcribe($this->callLog);

    expect($result['lead_temperature'])->toBe('Hot')
        ->and($result['summary_sentence'])->toContain('MBA');

    $this->callLog->refresh();
    expect($this->callLog->transcription_status)->toBe(TranscriptionStatus::Completed)
        ->and($this->callLog->transcription_summary)->not->toBeNull()
        ->and($this->callLog->transcription_model)->toBe('claude-sonnet-4-6');
});
