<?php

declare(strict_types=1);

// BRD: CRM-FM-001, CRM-FM-002 — Fee initiation idempotency

use App\Enums\CRM\Payments\FeeType;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Models\CRM\Payments\FeeStructure;
use App\Models\CRM\Payments\PaymentTransaction;
use App\Services\CRM\Payments\FeeCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('crm_payments.gateways.razorpay', [
        'driver' => 'razorpay',
        'key_id' => 'k', 'key_secret' => 's',
        'webhook_secret' => 'w',
        'base_url' => 'https://api.razorpay.test/v1',
    ]);
});

function fmFixtures(): array
{
    $institution = Institution::factory()->create();
    $programme   = CrmProgramme::factory()->for($institution)->create();
    $lead        = Lead::factory()->for($institution)->create();

    $application = Application::factory()
        ->for($lead, 'lead')
        ->for($institution)
        ->create(['programme_id' => $programme->id]);

    FeeStructure::factory()->create([
        'institution_id' => $institution->id,
        'programme_id'   => $programme->id,
        'fee_type'       => FeeType::APPLICATION->value,
        'amount'         => 1500,
    ]);

    return compact('institution', 'programme', 'lead', 'application');
}

it('creates one transaction and is idempotent for repeated initiation', function () {
    Http::fake([
        '*/orders' => Http::response(['id' => 'order_test_123', 'status' => 'created'], 200),
    ]);

    ['application' => $application] = fmFixtures();

    $service = app(FeeCollectionService::class);

    $first  = $service->initiate($application, FeeType::APPLICATION);
    $second = $service->initiate($application, FeeType::APPLICATION);

    expect($first->id)->toBe($second->id)
        ->and(PaymentTransaction::withoutGlobalScopes()->count())->toBe(1)
        ->and($first->status)->toBe(PaymentStatus::PENDING)
        ->and($first->gateway_order_id)->toBe('order_test_123');
});

it('throws when no active fee structure exists', function () {
    ['application' => $application] = fmFixtures();
    FeeStructure::query()->update(['is_active' => false]);

    app(FeeCollectionService::class)->initiate($application, FeeType::SEAT_BOOKING);
})->throws(RuntimeException::class);
