<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AG-002 — Unique referral link/code per agent for lead attribution
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_referral_codes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            // Format: {INST_SHORT}-AG-{4HEX}, e.g. INST01-AG-4F7C
            $table->string('code', 32)->unique();
            $table->string('url_slug', 64)->unique();
            $table->unsignedBigInteger('total_leads')->default(0);
            $table->unsignedBigInteger('total_conversions')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_referral_codes');
    }
};
