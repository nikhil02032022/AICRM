<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AL-003 — Lead tagged with referring alumni ID, code, and campaign on creation
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->unsignedBigInteger('referred_by_alumni_id')->nullable()->after('agent_id');
            $table->string('referral_code', 8)->nullable()->after('referred_by_alumni_id');
            $table->unsignedBigInteger('referral_campaign_id')->nullable()->after('referral_code');

            // Note: DB-level FK to alumni_pipeline is deferred; table ordering prevents it here

            $table->foreign('referral_campaign_id')
                ->references('id')
                ->on('alumni_referral_campaigns')
                ->nullOnDelete();

            $table->index('referred_by_alumni_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropForeign(['referral_campaign_id']);
            $table->dropIndex(['referred_by_alumni_id']);
            $table->dropColumn(['referred_by_alumni_id', 'referral_code', 'referral_campaign_id']);
        });
    }
};
