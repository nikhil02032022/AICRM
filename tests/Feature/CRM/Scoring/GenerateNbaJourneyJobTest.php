<?php

declare(strict_types=1);

use App\Jobs\CRM\GenerateNbaJourneyJob;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\NbaJourney;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('generates segment-wise nurture journey suggestions for institution', function (): void {
    $institution = Institution::create([
        'name' => 'Journey Job Institute',
        'code' => 'JJI01',
        'is_active' => true,
    ]);

    for ($i = 0; $i < 5; $i++) {
        Lead::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'first_name' => 'Hot',
            'last_name' => 'Lead'.$i,
            'mobile' => '986551'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'source' => 'walk_in',
            'status' => 'new_enquiry',
            'temperature' => 'hot',
            'lead_score' => 80,
            'consent_given' => true,
            'consent_timestamp' => now(),
            'consent_form_version' => 'v1',
        ]);
    }

    GenerateNbaJourneyJob::dispatchSync($institution->id, now()->toDateString(), null);

    $journeys = NbaJourney::withoutGlobalScopes()
        ->where('institution_id', $institution->id)
        ->whereDate('generated_for_date', now()->toDateString())
        ->get();

    expect($journeys->count())->toBeGreaterThan(0);
    expect($journeys->first()->segment_key)->not->toBe('');
    expect($journeys->first()->confidence_score)->toBeGreaterThanOrEqual(40);
});
