<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-019 — Merge tombstone columns on leads table
// Secondary leads get merged_into_uuid set after merge; primary lead retains its record.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            // UUID of the primary (surviving) lead this record was merged into
            $table->uuid('merged_into_uuid')
                ->nullable()
                ->after('erp_match_status');

            // Timestamp of when the merge was executed
            $table->timestamp('merged_at')
                ->nullable()
                ->after('merged_into_uuid');

            // User who initiated the merge (nullable on delete to preserve audit context)
            $table->unsignedBigInteger('merge_initiated_by')
                ->nullable()
                ->after('merged_at');

            $table->index('merged_into_uuid', 'leads_merged_into_uuid_index');

            $table->foreign('merge_initiated_by', 'leads_merge_initiated_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropForeign('leads_merge_initiated_by_foreign');
            $table->dropIndex('leads_merged_into_uuid_index');
            $table->dropColumn(['merged_into_uuid', 'merged_at', 'merge_initiated_by']);
        });
    }
};
