<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-006 — Lead list and agent assignment per campaign
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telecalling_campaign_leads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('telecalling_campaign_id')->index();
            $table->unsignedBigInteger('lead_id')->index();
            $table->unsignedBigInteger('assigned_agent_id')->nullable()->index();
            $table->unsignedInteger('queue_order')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['telecalling_campaign_id', 'lead_id'], 'uq_tc_campaign_lead');
            $table->index(['telecalling_campaign_id', 'assigned_agent_id'], 'idx_tc_campaign_agent_lead');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telecalling_campaign_leads');
    }
};
