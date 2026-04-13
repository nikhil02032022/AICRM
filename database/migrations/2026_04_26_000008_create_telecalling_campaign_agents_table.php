<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-006 — Agent assignment matrix for calling campaigns
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telecalling_campaign_agents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('telecalling_campaign_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['telecalling_campaign_id', 'user_id'], 'uq_tc_campaign_agent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telecalling_campaign_agents');
    }
};
