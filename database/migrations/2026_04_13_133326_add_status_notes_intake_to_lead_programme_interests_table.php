<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-002 — Per-programme status tracking for each lead interest record
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_programme_interests', function (Blueprint $table): void {
            // Status of the lead's journey for this specific programme
            $table->string('status', 50)->default('interested')->after('is_primary');
            // Counsellor notes specific to this programme interest
            $table->text('notes')->nullable()->after('status');
            // Preferred intake/batch for this programme
            $table->string('preferred_intake', 100)->nullable()->after('notes');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('lead_programme_interests', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'notes', 'preferred_intake']);
        });
    }
};
