<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-001 — WebForm entity for embeddable web enquiry forms
// BRD: CRM-LC-009 — QR code support via embed_token + slug
// BRD: CRM-CR-002 — consent_form_version stored per form instance
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_forms', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy columns
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            // Form identity
            $table->string('name', 120);
            $table->string('slug', 80);

            // Field configuration — JSON array of field definition objects (LC-002)
            $table->json('fields');

            // State + behaviour
            $table->boolean('is_active')->default(true);
            $table->string('embed_token', 64)->unique();

            // Lead source pre-set (LC-014)
            $table->string('source', 50)->default('website_organic');

            // Post-submission redirect
            $table->string('redirect_url')->nullable();

            // DPDP consent version (CRM-CR-002)
            $table->string('consent_form_version', 30);

            // Branding
            $table->string('accent_color', 7)->nullable();   // hex e.g. #6366F1
            $table->string('logo_url')->nullable();
            $table->text('custom_css')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for query performance
            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('is_active');

            // Unique slug per institution (multi-tenant slug namespace)
            $table->unique(['institution_id', 'slug'], 'web_forms_institution_slug_unique');

            // Foreign keys
            $table->foreign('institution_id')
                ->references('id')
                ->on('institutions')
                ->restrictOnDelete();

            $table->foreign('campus_id')
                ->references('id')
                ->on('campuses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_forms');
    }
};
