<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-005 — Landing pages built inside CRM for lead capture campaigns
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('web_form_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('name', 120);
            $table->string('slug', 100);
            $table->string('status', 20)->default('draft');
            $table->string('theme_variant', 40)->default('scholar');
            $table->string('headline', 180);
            $table->string('subheadline', 320)->nullable();
            $table->string('hero_image_url', 500)->nullable();
            $table->string('cta_label', 60)->default('Submit enquiry');
            $table->string('cta_secondary_label', 60)->nullable();
            $table->json('content')->nullable();
            $table->json('attribution_params')->nullable();
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('web_form_id');
            $table->index('status');
            $table->index('published_at');
            $table->unique(['institution_id', 'slug'], 'landing_pages_institution_slug_unique');

            $table->foreign('institution_id')
                ->references('id')
                ->on('institutions')
                ->restrictOnDelete();

            $table->foreign('campus_id')
                ->references('id')
                ->on('campuses')
                ->nullOnDelete();

            $table->foreign('web_form_id')
                ->references('id')
                ->on('web_forms')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};