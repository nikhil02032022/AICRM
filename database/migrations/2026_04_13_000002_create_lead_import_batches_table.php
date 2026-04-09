<?php

declare(strict_types=1);

use App\Enums\CRM\ImportBatchStatus;
use App\Enums\CRM\IntegrationChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-012 — Bulk CSV/Excel import batch tracking with progress + error report
// BRD: CRM-LC-008 — Also used for portal webhook batch tracking
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy — BRD NFR-MT-001
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            // Import source channel
            $table->string('channel')->default(IntegrationChannel::BULK_CSV->value);

            // File details (null for webhook-triggered batches)
            $table->string('file_name', 255)->nullable();
            $table->string('file_path', 1000)->nullable(); // S3 path to original uploaded file

            // Lifecycle status
            $table->string('status')->default(ImportBatchStatus::PENDING->value);

            // Progress tracking
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);

            // Error report — S3 path for downloadable CSV of failed rows + reason column
            $table->string('error_report_path', 1000)->nullable();

            // Who initiated the upload — null for webhook-triggered batches
            $table->unsignedBigInteger('initiated_by_user_id')->nullable();

            // Laravel job batch ID for Bus::batch() progress tracking
            $table->string('job_batch_id', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('institution_id');
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'channel']);
            $table->index('initiated_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_import_batches');
    }
};
