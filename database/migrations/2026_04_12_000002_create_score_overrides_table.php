<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LQ-007 — Counsellor manual score override with documented reason (immutable audit record)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_overrides', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('lead_id')->index();
            $table->unsignedBigInteger('overridden_by')->index();
            $table->unsignedTinyInteger('previous_score');
            $table->unsignedTinyInteger('overridden_score');
            $table->text('reason');
            // Immutable audit record — no updated_at, no soft deletes
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('overridden_by')->references('id')->on('users')->onDelete('restrict');
        });

        // BRD: CRM-LQ-007 — Track whether lead score was last set by manual override
        Schema::table('leads', function (Blueprint $table): void {
            $table->boolean('score_manually_overridden')->default(false)->after('lead_score');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('score_manually_overridden');
        });
        Schema::dropIfExists('score_overrides');
    }
};
