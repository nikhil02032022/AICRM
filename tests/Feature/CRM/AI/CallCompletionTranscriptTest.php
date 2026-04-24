<?php

declare(strict_types=1);

// BRD: CRM-AI-007 — Feature tests for call completion with transcript and transcription retry

use App\Enums\CRM\AI\TranscriptionStatus;
use App\Jobs\CRM\AI\TranscribeCallJob;
use App\Models\CRM\CallLog;
use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();

    $this->user = User::factory()->create([
        'institution_id' => $this->institution->id,
    ]);
    $this->user->givePermissionTo('crm.communication.send');

    $this->callLog = CallLog::factory()->create([
        'institution_id' => $this->institution->id,
        'initiated_by'   => $this->user->id,
        'transcription_status' => null,
    ]);
});

it('queues TranscribeCallJob when transcript is submitted with disposition', function (): void {
    Queue::fake();

    $this->actingAs($this->user)
        ->post(route('crm.communication.voice.calls.disposition', $this->callLog->uuid), [
            'disposition'     => 'REACHED_INTERESTED',
            'transcript_text' => 'Student is keen on MBA programme.',
        ])
        ->assertRedirect();

    Queue::assertPushed(TranscribeCallJob::class, function ($job) {
        return $job->callLogUuid === $this->callLog->uuid;
    });
});

it('does not queue job when transcript field is empty', function (): void {
    Queue::fake();

    $this->actingAs($this->user)
        ->post(route('crm.communication.voice.calls.disposition', $this->callLog->uuid), [
            'disposition'     => 'REACHED_INTERESTED',
            'transcript_text' => '',
        ])
        ->assertRedirect();

    Queue::assertNotPushed(TranscribeCallJob::class);
});

it('stores transcript_text on call log after submission', function (): void {
    Queue::fake();

    $this->actingAs($this->user)
        ->post(route('crm.communication.voice.calls.disposition', $this->callLog->uuid), [
            'disposition'     => 'REACHED_INTERESTED',
            'transcript_text' => 'MBA interest expressed during call.',
        ]);

    $this->callLog->refresh();
    expect($this->callLog->transcript_text)->toBe('MBA interest expressed during call.')
        ->and($this->callLog->transcription_status)->toBe(TranscriptionStatus::Pending);
});

it('rejects transcript exceeding max character limit', function (): void {
    $this->actingAs($this->user)
        ->post(route('crm.communication.voice.calls.disposition', $this->callLog->uuid), [
            'disposition'     => 'REACHED_INTERESTED',
            'transcript_text' => str_repeat('x', 50001),
        ])
        ->assertSessionHasErrors('transcript_text');
});

it('allows retry for failed transcription by owning counsellor', function (): void {
    Queue::fake();

    $this->callLog->update(['transcription_status' => TranscriptionStatus::Failed]);

    $this->actingAs($this->user)
        ->post(route('crm.communication.voice.calls.transcription.retry', $this->callLog->uuid))
        ->assertRedirect();

    Queue::assertPushed(TranscribeCallJob::class);

    $this->callLog->refresh();
    expect($this->callLog->transcription_status)->toBe(TranscriptionStatus::Pending);
});

it('denies retry to counsellor from different institution', function (): void {
    Queue::fake();

    $otherInstitution = Institution::factory()->create();
    $otherUser        = User::factory()->create(['institution_id' => $otherInstitution->id]);
    $otherUser->givePermissionTo('crm.communication.send');

    $this->callLog->update(['transcription_status' => TranscriptionStatus::Failed]);

    $this->actingAs($otherUser)
        ->post(route('crm.communication.voice.calls.transcription.retry', $this->callLog->uuid))
        ->assertForbidden();

    Queue::assertNotPushed(TranscribeCallJob::class);
});

it('denies retry when transcription status is not failed', function (): void {
    Queue::fake();

    $this->callLog->update(['transcription_status' => TranscriptionStatus::Completed]);

    $this->actingAs($this->user)
        ->post(route('crm.communication.voice.calls.transcription.retry', $this->callLog->uuid))
        ->assertForbidden();
});
