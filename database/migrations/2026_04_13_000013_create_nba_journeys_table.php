<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-010 — Persist AI-suggested nurture journey blueprints by segment
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nba_journeys', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->string('segment_key', 80);
            $table->string('segment_label', 120);
            $table->unsignedTinyInteger('confidence_score');
            $table->text('rationale');
            $table->json('steps');
            $table->json('metadata')->nullable();
            $table->string('model_version', 80)->default('a2a-nba-journey-rules-v1');
            $table->date('generated_for_date');
            $table->timestamp('suggested_at');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('segment_key');
            $table->index('generated_for_date');
            $table->index('suggested_at');
            $table->index(['institution_id', 'generated_for_date'], 'nj_inst_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nba_journeys');
    }
};
