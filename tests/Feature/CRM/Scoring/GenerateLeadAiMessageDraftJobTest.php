<?php

declare(strict_types=1);

use App\Events\CRM\LeadAiMessageDraftedEvent;
use App\Jobs\CRM\GenerateLeadAiMessageDraftJob;
use App\Models\CRM\AiMessageDraft;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('creates ai communication draft and dispatches event', function (): void {
    Event::fake([LeadAiMessageDraftedEvent::class]);

    $institution = Institution::create([
        'name' => 'Draft Test Institute',
        'code' => 'DTI',
        'is_active' => true,
    ]);

    $lead = Lead::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'institution_id' => $institution->id,
        'first_name' => 'Nila',
        'last_name' => 'Das',
        'mobile' => '9876500099',
        'source' => 'walk_in',
        'status' => 'new_enquiry',
        'temperature' => 'warm',
        'lead_score' => 58,
        'consent_given' => true,
        'consent_timestamp' => now(),
        'consent_form_version' => 'v1',
    ]);

    GenerateLeadAiMessageDraftJob::dispatchSync($lead->uuid, 'whatsapp');

    $draft = AiMessageDraft::withoutGlobalScopes()
        ->where('lead_id', $lead->id)
        ->latest('generated_at')
        ->first();

    expect($draft)->not->toBeNull();
    expect($draft->channel)->toBe('whatsapp');
    expect($draft->draft_text)->not->toBe('');

    Event::assertDispatched(LeadAiMessageDraftedEvent::class);
});
