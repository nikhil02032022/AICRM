<?php

declare(strict_types=1);

use App\Enums\CRM\LeadSource;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function makeMarketingOpsUser(string $suffix = 'a'): array
{
    $institution = Institution::create([
        'name' => 'Attribution University '.$suffix,
        'code' => 'ATTR'.strtoupper($suffix),
        'is_active' => true,
    ]);

    $user = User::create([
        'name' => 'Marketing Ops '.$suffix,
        'email' => 'marketing-ops-'.$suffix.'@example.com',
        'password' => bcrypt('password'),
        'institution_id' => $institution->id,
    ]);

    $user->givePermissionTo(['crm.leads.create', 'crm.campaigns.manage']);

    return [$institution, $user];
}

function leadPayload(string $mobile, string $campaign = 'mba-2027'): array
{
    return [
        'first_name' => 'Aarav',
        'last_name' => 'Sharma',
        'mobile' => $mobile,
        'email' => $mobile.'@example.com',
        'source' => LeadSource::GOOGLE_ADS->value,
        'source_utm_params' => [
            'utm_source' => 'google_ads',
            'utm_medium' => 'cpc',
            'utm_campaign' => $campaign,
        ],
        'consent_given' => true,
        'consent_form_version' => 'web-form-v1',
    ];
}

it('records first attribution touchpoint automatically when a lead is created', function (): void {
    [, $user] = makeMarketingOpsUser();

    $createResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/leads', leadPayload('9876543210'));

    $createResponse->assertCreated();

    $lead = Lead::query()->latest('id')->firstOrFail();

    $timelineResponse = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/attributions/leads/'.$lead->uuid);

    $timelineResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.source', 'google_ads')
        ->assertJsonPath('data.0.is_first_touch', true)
        ->assertJsonPath('data.0.is_last_touch', true);

    expect((float) $timelineResponse->json('data.0.linear_credit'))->toBe(1.0);
});

it('recalculates first last and linear credits when another touchpoint is added', function (): void {
    [, $user] = makeMarketingOpsUser('b');

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/crm/leads', leadPayload('9876543211'))->assertCreated();

    $lead = Lead::query()->latest('id')->firstOrFail();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/attributions/leads/'.$lead->uuid.'/touchpoints', [
            'source' => 'website_organic',
            'utm_source' => 'blog',
            'utm_medium' => 'content',
            'utm_campaign' => 'mba-2027',
            'touchpoint_at' => now()->addMinute()->toIso8601String(),
        ])
        ->assertCreated();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/attributions/leads/'.$lead->uuid)
        ->assertOk();

    $touchpoints = collect($response->json('data'));

    expect($touchpoints)->toHaveCount(2);
    expect($touchpoints[0]['is_first_touch'])->toBeTrue();
    expect($touchpoints[0]['is_last_touch'])->toBeFalse();
    expect((float) $touchpoints[0]['linear_credit'])->toBe(0.5);

    expect($touchpoints[1]['is_first_touch'])->toBeFalse();
    expect($touchpoints[1]['is_last_touch'])->toBeTrue();
    expect((float) $touchpoints[1]['linear_credit'])->toBe(0.5);
});

it('calculates cost per lead from campaign spend and attributed leads', function (): void {
    [, $user] = makeMarketingOpsUser('c');

    $this->actingAs($user, 'sanctum')->postJson('/api/v1/crm/leads', leadPayload('9876543212'))->assertCreated();
    $this->actingAs($user, 'sanctum')->postJson('/api/v1/crm/leads', leadPayload('9876543213'))->assertCreated();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/crm/campaign-spends', [
            'source' => 'google_ads',
            'campaign_name' => 'mba-2027',
            'period_start' => now()->subDay()->toDateString(),
            'period_end' => now()->addDay()->toDateString(),
            'amount' => 1000,
            'currency' => 'INR',
            'attribution_model' => 'last_touch',
        ])
        ->assertCreated();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/crm/campaign-spends?source=google_ads&campaign_name=mba-2027')
        ->assertOk();

    $response->assertJsonPath('data.0.attributed_leads_count', 2);
    expect((float) $response->json('data.0.cost_per_lead'))->toBe(500.0);
});
