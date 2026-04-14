<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-DM-006 — DigiLocker integration — verified document storage per applicant
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digilocker_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('lead_id');
            // DigiLocker request lifecycle
            $table->string('status', 30)->default('pending');    // DigiLockerStatus enum
            $table->string('document_type', 80)->nullable();     // e.g. MarkSheet, AadhaarCard
            // API Setu / DigiLocker identifiers — not PII, safe to store plain
            $table->string('digilocker_request_id', 120)->nullable();
            $table->string('digilocker_uri', 255)->nullable();   // document URI returned by API
            // Storage reference — actual document stored in encrypted S3
            $table->string('storage_path', 500)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            // Error tracking
            $table->text('error_message')->nullable();
            // DPDP: consent reference
            $table->unsignedBigInteger('consent_record_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'lead_id']);
            $table->index(['institution_id', 'status']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digilocker_documents');
    }
};
