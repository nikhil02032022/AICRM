<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-008 — Per-stage audit trail of approvals / rejections
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_approvals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('scholarship_award_id');

            $table->string('stage', 20);        // ApprovalStage
            $table->string('decision', 20);     // approved | rejected | withdrawn
            $table->unsignedBigInteger('actor_id');
            $table->string('comment', 1000)->nullable();

            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('scholarship_award_id');
            $table->index(['scholarship_award_id', 'stage'], 'sap_award_stage_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_approvals');
    }
};
