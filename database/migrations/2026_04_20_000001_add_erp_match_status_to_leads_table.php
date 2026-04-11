<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-020 — ERP Student/Alumni match status column on leads
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            // null = never checked; 'pending' = job dispatched; 'matched' = ERP student found;
            // 'no_match' = ERP queried, not found; 'error' = API failure on last attempt
            $table->string('erp_match_status', 20)
                ->nullable()
                ->after('erp_student_uuid');

            $table->index('erp_match_status', 'leads_erp_match_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_erp_match_status_index');
            $table->dropColumn('erp_match_status');
        });
    }
};
