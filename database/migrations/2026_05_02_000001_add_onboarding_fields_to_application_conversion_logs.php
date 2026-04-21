<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-018 — Track onboarding workflow trigger status per conversion log
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_conversion_logs', function (Blueprint $table) {
            $table->timestamp('onboarding_triggered_at')->nullable()->after('completed_at');
            $table->json('onboarding_status')->nullable()->after('onboarding_triggered_at');
        });
    }

    public function down(): void
    {
        Schema::table('application_conversion_logs', function (Blueprint $table) {
            $table->dropColumn(['onboarding_triggered_at', 'onboarding_status']);
        });
    }
};
