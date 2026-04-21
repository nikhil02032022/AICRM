<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-001, CRM-FM-002, CRM-FM-005 — Payment transaction ledger with idempotency keys
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            $table->uuid('application_uuid');
            $table->uuid('lead_uuid')->nullable();
            $table->unsignedBigInteger('fee_structure_id')->nullable();

            $table->string('fee_type', 30);
            $table->string('gateway', 20);
            $table->string('gateway_order_id', 120)->nullable();
            $table->string('gateway_payment_id', 120)->nullable();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');

            $table->string('status', 30)->default('initiated');
            $table->string('idempotency_key', 80);

            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('failure_reason', 255)->nullable();

            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('idempotency_key');
            $table->unique(['gateway', 'gateway_order_id'], 'pt_gw_order_unique');
            $table->unique(['gateway', 'gateway_payment_id'], 'pt_gw_payment_unique');

            $table->index('institution_id');
            $table->index('application_uuid');
            $table->index('lead_uuid');
            $table->index('status');
            $table->index(['institution_id', 'status']);
            $table->index(['application_uuid', 'fee_type', 'status'], 'pt_app_fee_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
