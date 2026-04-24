<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AL-002 — Unique referral code per alumni per campaign
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_referral_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('alumni_referral_campaigns')->cascadeOnDelete();
            $table->unsignedBigInteger('alumni_id');
            $table->foreign('alumni_id')->references('id')->on('alumni_pipeline')->cascadeOnDelete();
            $table->string('code', 8);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->string('reward_status', 20)->default('pending'); // pending | earned | disbursed
            $table->timestamp('shared_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'code']);
            $table->index('campaign_id');
            $table->index('alumni_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_referral_codes');
    }
};
