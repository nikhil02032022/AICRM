<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AL-002 — Alumni referral campaign management
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_referral_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('reward_type', 30); // gift_voucher | fee_waiver | recognition
            $table->decimal('reward_value', 10, 2)->nullable();
            $table->string('status', 20)->default('draft'); // draft | active | paused | ended
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('institution_id');
            $table->index(['institution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_referral_campaigns');
    }
};
