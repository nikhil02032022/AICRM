<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-019 — IVR for inbound enquiries, configurable per institution/campus
// BRD: CRM-LC-010 — Inbound calls auto-create lead records
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ivr_configs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->string('provider', 20); // TelephonyProvider enum
            $table->text('virtual_number'); // encrypted — DPDP (the DID/virtual number)
            $table->text('welcome_message')->nullable(); // TTS or audio URL played to callers
            $table->boolean('collect_name')->default(true);
            $table->boolean('collect_programme')->default(true);
            $table->foreignId('fallback_counsellor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('credentials_id')->nullable()->constrained('integration_credentials')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('is_active');
            $table->index(['institution_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ivr_configs');
    }
};
