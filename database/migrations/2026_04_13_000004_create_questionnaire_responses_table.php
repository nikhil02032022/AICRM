<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LQ-009 — Questionnaire responses captured against leads
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_responses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('qualification_questionnaire_id');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->json('responses');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('completed_at');
            $table->unique(['qualification_questionnaire_id', 'lead_id'], 'questionnaire_lead_unique');

            $table->foreign('qualification_questionnaire_id', 'questionnaire_response_questionnaire_fk')
                ->references('id')
                ->on('qualification_questionnaires')
                ->onDelete('cascade');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');

            $table->foreign('submitted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_responses');
    }
};
