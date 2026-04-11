<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-016 — Multi-touch attribution ledger for lead source journey tracking.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_attributions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('lead_id');
            $table->string('touch_type', 20)->default('middle_touch');
            $table->string('source', 80);
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 120)->nullable();
            $table->string('utm_term', 120)->nullable();
            $table->string('utm_content', 120)->nullable();
            $table->timestamp('touchpoint_at');
            $table->boolean('is_first_touch')->default(false);
            $table->boolean('is_last_touch')->default(false);
            $table->decimal('first_touch_credit', 8, 4)->default(0);
            $table->decimal('last_touch_credit', 8, 4)->default(0);
            $table->decimal('linear_credit', 8, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('lead_id');
            $table->index('source');
            $table->index('touch_type');
            $table->index('touchpoint_at');
            $table->index('utm_campaign');
            $table->index(['institution_id', 'source', 'touchpoint_at'], 'lead_attr_inst_source_touched_idx');

            $table->foreign('institution_id')->references('id')->on('institutions')->restrictOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_attributions');
    }
};
