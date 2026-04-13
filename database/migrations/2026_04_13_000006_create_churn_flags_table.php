<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LQ-010 — Persist churn risk snapshots and rationale for counsellor actionability
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('churn_flags', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id');
            $table->string('risk_level', 20);
            $table->unsignedTinyInteger('risk_score');
            $table->text('rationale');
            $table->json('indicators')->nullable();
            $table->timestamp('flagged_at');
            $table->timestamp('mitigated_at')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index('lead_id');
            $table->index('risk_level');
            $table->index('flagged_at');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('churn_flags');
    }
};
