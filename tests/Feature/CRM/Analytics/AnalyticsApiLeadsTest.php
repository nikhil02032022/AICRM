<?php

declare(strict_types=1);

// BRD: CRM-AR-021 — Lead funnel endpoint contract tests (response structure, date filtering, PII check)

use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'api_token.manage', 'guard_name' => 'web']);

    $this->institution = Institution::factory()->create();
    $this->admin = User::factory()->create(['institution_id' => $this->institution->id]);

    $tokenResult = $this->admin->createToken('bi-test', ['analytics:read']);
    DB::table('personal_access_tokens')
        ->where('id', $tokenResult->accessToken->id)
        ->update(['institution_id' => $this->institution->id]);

    $this->token = $tokenResult->plainTextToken;
});

it('returns 200 with correct JSON envelope structure', function () {
    $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
        ->getJson('/api/v1/crm/analytics/leads')
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['stages', 'by_source'],
            'meta' => ['from_date', 'to_date', 'institution_id', 'generated_at'],
            'links' => ['self'],
        ]);
});

it('reflects requested date range in meta', function () {
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
        ->getJson('/api/v1/crm/analytics/leads?from_date=2025-07-01&to_date=2026-03-31')
        ->assertOk();

    expect($response->json('meta.from_date'))->toBe('2025-07-01')
        ->and($response->json('meta.to_date'))->toBe('2026-03-31');
});

it('returns institution_id in meta matching authenticated token', function () {
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
        ->getJson('/api/v1/crm/analytics/leads')
        ->assertOk();

    expect($response->json('meta.institution_id'))->toBe($this->institution->id);
});

it('returns 422 when from_date has invalid format', function () {
    $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
        ->getJson('/api/v1/crm/analytics/leads?from_date=not-a-date')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['from_date']);
});

it('returns 422 when to_date is before from_date', function () {
    $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
        ->getJson('/api/v1/crm/analytics/leads?from_date=2026-01-01&to_date=2025-01-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['to_date']);
});

it('does not return PII fields in stages or by_source', function () {
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
        ->getJson('/api/v1/crm/analytics/leads')
        ->assertOk();

    $stages   = $response->json('data.stages');
    $bySource = $response->json('data.by_source');

    $piiKeys = ['name', 'email', 'phone', 'mobile', 'first_name', 'last_name'];

    foreach (array_merge($stages ?? [], $bySource ?? []) as $item) {
        foreach ($piiKeys as $key) {
            expect(array_key_exists($key, $item))->toBeFalse("PII key '{$key}' found in response");
        }
    }
});

it('includes a self link pointing to the correct URL', function () {
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
        ->getJson('/api/v1/crm/analytics/leads')
        ->assertOk();

    expect($response->json('links.self'))->toContain('/api/v1/crm/analytics/leads');
});
