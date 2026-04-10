<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change audit_logs.entity_id from unsignedBigInteger to varchar(36)
 * to support both integer PKs and UUID PKs (e.g. CounsellingSession, CounsellorAvailabilitySlot).
 *
 * BRD: CRM-SA-009 — Audit log must capture all CRM entity mutations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('entity_id', 36)->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('entity_id')->change();
        });
    }
};
