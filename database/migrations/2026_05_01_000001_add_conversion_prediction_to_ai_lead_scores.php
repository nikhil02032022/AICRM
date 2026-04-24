<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-001 — Extend ai_lead_scores with Claude API conversion probability prediction columns
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_lead_scores', function (Blueprint $table): void {
            $table->decimal('conversion_probability', 5, 4)->nullable()->after('metadata');
            $table->decimal('confidence_score', 5, 4)->nullable()->after('conversion_probability');
            $table->json('prediction_factors')->nullable()->after('confidence_score');
            $table->timestamp('prediction_refreshed_at')->nullable()->after('prediction_factors');
            $table->enum('prediction_status', ['pending', 'processing', 'completed', 'failed'])->nullable()->after('prediction_refreshed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_lead_scores', function (Blueprint $table): void {
            $table->dropColumn([
                'conversion_probability',
                'confidence_score',
                'prediction_factors',
                'prediction_refreshed_at',
                'prediction_status',
            ]);
        });
    }
};
