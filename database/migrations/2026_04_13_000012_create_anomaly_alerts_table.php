<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-009 — Persist anomaly alerts for lead volume drop-offs and monitoring
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomaly_alerts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->string('alert_type', 50);
            $table->string('metric_name', 80);
            $table->unsignedInteger('current_value');
            $table->unsignedInteger('baseline_value');
            $table->decimal('deviation_percent', 6, 2);
            $table->unsignedTinyInteger('threshold_percent')->default(25);
            $table->string('severity', 20);
            $table->text('rationale');
            $table->json('metadata')->nullable();
            $table->string('model_version', 80)->default('a2a-anomaly-rules-v1');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('metric_name');
            $table->index('severity');
            $table->index('detected_at');
            $table->index(['institution_id', 'detected_at'], 'aa_inst_detected_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_alerts');
    }
};
