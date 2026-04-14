<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EI-008 — Alumni module bridge log — tracks each CRM→A2A Alumni handoff
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_bridge_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            // Source: lead that was converted to student, then graduated
            $table->unsignedBigInteger('lead_id');
            // A2A ERP identifiers (no PII)
            $table->string('erp_student_id', 80)->nullable();     // A2A Student Master ID
            $table->string('erp_alumni_id', 80)->nullable();      // A2A Alumni Module ID (set on success)
            $table->string('status', 30)->default('pending');     // AlumniBridgeStatus enum
            // Referral tracking (BRD: EI-008 — alumni referral tracking)
            $table->string('referral_code', 50)->nullable();
            $table->unsignedInteger('referrals_count')->default(0);
            // Payload snapshot for audit (no PII)
            $table->json('payload_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('bridged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'lead_id']);
            $table->index(['institution_id', 'status']);
            $table->index('erp_student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_bridge_logs');
    }
};
