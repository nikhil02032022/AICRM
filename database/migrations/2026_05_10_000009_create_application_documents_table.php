<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-DM-002, CRM-DM-003, CRM-DM-004, CRM-DM-008 — Uploaded documents per application
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->uuid('application_uuid');
            $table->uuid('lead_uuid')->nullable();
            $table->unsignedBigInteger('document_checklist_item_id');

            $table->string('status', 20)->default('not_submitted'); // DocumentStatus
            $table->string('storage_disk', 40)->nullable();
            $table->string('storage_path', 500)->nullable();        // encrypted payload path (uuid-named)
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('uploaded_via', 20)->nullable();          // DocumentUploadChannel
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->unsignedSmallInteger('version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('application_uuid');
            $table->index('lead_uuid');
            $table->index(['institution_id', 'status']);
            $table->index(['application_uuid', 'status']);
            $table->unique(['application_uuid', 'document_checklist_item_id'], 'ad_app_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
