<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-DM-009 — Bulk document download queue
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_bulk_download_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('requested_by');

            $table->string('scope', 30);         // application | programme_batch
            $table->string('target_ref', 120);   // application uuid or programme id
            $table->string('status', 20)->default('queued'); // BulkDownloadStatus
            $table->unsignedInteger('file_count')->nullable();
            $table->string('zip_path', 500)->nullable();
            $table->unsignedBigInteger('zip_size_bytes')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'status']);
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_bulk_download_jobs');
    }
};
