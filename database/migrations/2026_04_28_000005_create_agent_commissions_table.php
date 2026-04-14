<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AG-006 — Agent commission workflow — calculation, approval, and payout tracking
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_commissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            // Agent is a User with role 'agent' / 'channel_partner'
            $table->unsignedBigInteger('agent_user_id');
            // Source enrolment/lead
            $table->unsignedBigInteger('lead_id');
            // Commission details
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('commission_type', 30)->default('fixed');   // fixed|percentage
            $table->decimal('percentage_rate', 5, 2)->nullable();      // if type=percentage
            $table->decimal('base_amount', 12, 2)->nullable();         // fee amount on which % is calc
            // Approval workflow
            $table->string('status', 30)->default('pending');          // CommissionStatus enum
            $table->unsignedBigInteger('approved_by')->nullable();     // User ID of approver
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            // Payout
            $table->timestamp('paid_at')->nullable();
            $table->string('payout_reference', 100)->nullable();
            // Audit
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'agent_user_id']);
            $table->index(['institution_id', 'status']);
            $table->index('lead_id');
            $table->index('agent_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commissions');
    }
};
