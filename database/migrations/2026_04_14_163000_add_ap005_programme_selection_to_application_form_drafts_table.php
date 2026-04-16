<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-005 — Support simultaneous applications to multiple programmes
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_form_drafts', function (Blueprint $table): void {
            $table->json('selected_programme_uuids')->nullable()->after('form_data');
        });
    }

    public function down(): void
    {
        Schema::table('application_form_drafts', function (Blueprint $table): void {
            $table->dropColumn('selected_programme_uuids');
        });
    }
};
