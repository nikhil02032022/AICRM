<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LQ-001, CRM-LQ-005 — Per-institution configurable scoring weights and temperature thresholds
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_scoring_configs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();

            // BRD: CRM-LQ-001 — JSON blob of configurable signal weights
            // Keys: profile_completeness, programme_interest, source_quality,
            //       engagement, consent, geographic, response_time
            $table->json('weights');

            // BRD: CRM-LQ-005 — Configurable temperature thresholds per institution
            $table->unsignedTinyInteger('hot_threshold')->default(75);
            $table->unsignedTinyInteger('warm_threshold')->default(50);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Each institution has exactly one active scoring config
            $table->unique('institution_id');
            $table->index('institution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_scoring_configs');
    }
};
