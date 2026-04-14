<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AR-018 — Custom report builder: report definitions and export history
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('created_by');   // FK users.id
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('entity', 50);               // ReportEntity enum: leads|applications|...
            $table->json('selected_fields');             // ordered list of field keys to display
            $table->json('filters')->nullable();         // [{field, operator, value}]
            $table->string('group_by', 100)->nullable();
            $table->string('sort_field', 100)->nullable();
            $table->string('sort_direction', 4)->default('asc'); // asc|desc
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'entity']);
            $table->index('created_by');
        });

        Schema::create('report_exports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('custom_report_id');
            $table->unsignedBigInteger('exported_by');  // FK users.id
            $table->string('format', 10);               // csv|excel|pdf
            $table->string('storage_path')->nullable(); // S3 key; null once purged
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('expires_at')->nullable();// S3 signed URL TTL anchor
            $table->timestamps();

            $table->index('institution_id');
            $table->index('custom_report_id');

            $table->foreign('custom_report_id')
                ->references('id')
                ->on('custom_reports')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('custom_reports');
    }
};
