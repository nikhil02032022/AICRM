<?php

declare(strict_types=1);

// BRD: CRM-AR-021 — Happy-path contract tests for /pipeline, /fees, /counsellors endpoints

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

    $this->token  = $tokenResult->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer ' . $this->token];
});

describe('GET /analytics/pipeline', function () {
    it('returns 200 with data, meta, and links', function () {
        $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/pipeline')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['from_date', 'to_date', 'institution_id', 'generated_at'],
                'links' => ['self'],
            ]);
    });

    it('reflects date range in meta', function () {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/pipeline?from_date=2025-07-01&to_date=2026-03-31')
            ->assertOk();

        expect($response->json('meta.from_date'))->toBe('2025-07-01')
            ->and($response->json('meta.to_date'))->toBe('2026-03-31');
    });

    it('returns 422 on invalid date', function () {
        $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/pipeline?from_date=bad')
            ->assertUnprocessable();
    });
});

describe('GET /analytics/fees', function () {
    it('returns 200 with fee summary fields', function () {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/fees')
            ->assertOk();

        expect($response->json('data'))->toHaveKeys([
            'collected', 'pending_amount', 'refunded', 'total_transactions', 'successful_count',
        ]);
    });

    it('reflects date range in meta', function () {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/fees?from_date=2025-10-01&to_date=2026-04-01')
            ->assertOk();

        expect($response->json('meta.from_date'))->toBe('2025-10-01');
    });

    it('returns 422 on invalid to_date', function () {
        $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/fees?from_date=2026-01-01&to_date=2025-01-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to_date']);
    });

    it('does not expose PII in fee response', function () {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/fees')
            ->assertOk();

        $data = $response->json('data');
        foreach (['name', 'email', 'phone', 'mobile'] as $key) {
            expect(array_key_exists($key, $data ?? []))->toBeFalse();
        }
    });
});

describe('GET /analytics/counsellors', function () {
    it('returns 200 with data array and meta', function () {
        $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/counsellors')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['from_date', 'to_date', 'institution_id', 'generated_at'],
                'links' => ['self'],
            ]);
    });

    it('does not include counsellor name or mobile in response', function () {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/crm/analytics/counsellors')
            ->assertOk();

        foreach ($response->json('data') ?? [] as $item) {
            expect(array_key_exists('name', $item))->toBeFalse()
                ->and(array_key_exists('mobile', $item))->toBeFalse()
                ->and(array_key_exists('email', $item))->toBeFalse();
        }
    });
});
