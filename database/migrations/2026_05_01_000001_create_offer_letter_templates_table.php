<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-012 — Customisable offer letter templates
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_letter_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            // Template metadata
            $table->string('name', 255);
            $table->string('type', 30)->default('offer'); // offer, confirmation, conditional
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Template content (HTML, supports merge tags like {{first_name}})
            $table->longText('html_template');

            // Header/footer images (S3 paths, encrypted)
            $table->text('header_image_path')->nullable();
            $table->text('footer_image_path')->nullable();

            // Digital signature field configuration (text for coordinates, width, height)
            $table->text('signature_config')->nullable(); // JSON: {x, y, width, height}

            // Merge tags available in this template (JSON array)
            $table->json('available_merge_tags')->nullable();

            // Versioning
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('last_used_at')->nullable();

            // Soft deletes & timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('type');
            $table->index('is_active');
            $table->index(['institution_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_letter_templates');
    }
};
