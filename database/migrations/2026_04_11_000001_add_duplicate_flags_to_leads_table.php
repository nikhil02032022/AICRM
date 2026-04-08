<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-018 — Add duplicate detection flag columns to leads table so detected
// duplicates persist to the DB and can be surfaced in the UI + audit trail.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            // Whether this lead has been flagged as a possible duplicate by DetectLeadDuplicatesJob
            $table->boolean('is_duplicate_suspected')->default(false)->after('pii_anonymised_at');

            // UUID of the suspected primary/original lead (earliest created)
            $table->uuid('duplicate_of_uuid')->nullable()->after('is_duplicate_suspected');

            // Index for fast counsellor dashboard queries ("show me all suspected duplicates")
            $table->index('is_duplicate_suspected');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['is_duplicate_suspected']);
            $table->dropColumn(['is_duplicate_suspected', 'duplicate_of_uuid']);
        });
    }
};
