<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-006 — Auto-assignment configuration per institution
// BRD: CRM-EC-009 — Escalation threshold configuration
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counsellor_assignment_configs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id')->unique();
            $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();

            $table->unsignedBigInteger('campus_id')->nullable();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();

            // BRD: CRM-EC-006 — Assignment strategy: round_robin | load_balanced | manual
            $table->string('assignment_mode', 20)->default('round_robin');

            // BRD: CRM-EC-006 — Cap active leads assigned to a single counsellor
            $table->unsignedSmallInteger('max_leads_per_counsellor')->default(50);

            // Tracks the counsellor who last received a round-robin assignment
            $table->unsignedBigInteger('round_robin_pointer_user_id')->nullable();
            $table->foreign('round_robin_pointer_user_id', 'cac_rr_pointer_fk')->references('id')->on('users')->nullOnDelete();

            // BRD: CRM-EC-009 — Hours before an unactioned lead is escalated
            $table->unsignedSmallInteger('escalation_hours')->default(24);

            // The user (manager / admin) who receives escalation alerts
            $table->unsignedBigInteger('escalation_to_user_id')->nullable();
            $table->foreign('escalation_to_user_id', 'cac_escalation_user_fk')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counsellor_assignment_configs');
    }
};
