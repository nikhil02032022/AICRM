<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-005 — Stores landing page view events for campaign analytics.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_page_views', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('landing_page_id');
            $table->timestamp('viewed_at');
            $table->string('visitor_hash', 64)->nullable();
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 120)->nullable();
            $table->string('utm_term', 120)->nullable();
            $table->string('utm_content', 120)->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'landing_page_id', 'viewed_at'], 'lp_views_inst_page_viewed_idx');
            $table->index('viewed_at');
            $table->index('utm_source');
            $table->index('utm_campaign');

            $table->foreign('institution_id')
                ->references('id')
                ->on('institutions')
                ->restrictOnDelete();

            $table->foreign('campus_id')
                ->references('id')
                ->on('campuses')
                ->nullOnDelete();

            $table->foreign('landing_page_id')
                ->references('id')
                ->on('landing_pages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_views');
    }
};