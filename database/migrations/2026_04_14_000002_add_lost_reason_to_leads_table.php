<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-013 — Loss reason captured on status change to "Lost"
// BRD: CRM-EC-014 — Historical status journey preserved
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            // CRM-EC-013 — reason is mandatory when status transitions to LOST
            $table->string('lost_reason', 80)->nullable()->after('notes');
            // CRM-EC-014 — tracks when the status last changed for escalation timing
            $table->timestamp('status_changed_at')->nullable()->after('lost_reason');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn(['lost_reason', 'status_changed_at']);
        });
    }
};
